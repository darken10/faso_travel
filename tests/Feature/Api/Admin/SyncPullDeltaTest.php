<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\StatutTicket;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Protocole de pull incrémental alimentant le cache offline de l'agent.
 *
 * Deux propriétés comptent avant tout :
 *   - l'agent reçoit de quoi reconnaître un ticket hors connexion, sans qu'aucun
 *     secret réutilisable ne quitte le serveur ;
 *   - la pagination ne saute aucune ligne, même si des tickets sont validés
 *     entre deux pages.
 */
class SyncPullDeltaTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->compagnie = Compagnie::factory()->create();
        $this->agent     = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
    }

    private function voyageInstance(?string $date = null, ?Compagnie $compagnie = null): VoyageInstance
    {
        $voyage = Voyage::factory()->create([
            'compagnie_id' => ($compagnie ?? $this->compagnie)->id,
        ]);

        return VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => $date ?? now()->toDateString(),
        ]);
    }

    private function ticket(VoyageInstance $instance, array $attributes = []): Ticket
    {
        return Ticket::factory()->create($attributes + [
            'voyage_instance_id' => $instance->id,
            'statut'             => StatutTicket::Payer,
        ]);
    }

    private function pull(array $query = []): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->agent);

        return $this->getJson('/api/admin/sync/pull?' . http_build_query($query));
    }

    // ── Fenêtre J+0 / J+1 ──────────────────────────────────────────────────

    public function test_la_fenetre_couvre_aujourdhui_et_demain(): void
    {
        $aujourdhui = $this->ticket($this->voyageInstance(now()->toDateString()));
        $demain     = $this->ticket($this->voyageInstance(now()->addDay()->toDateString()));

        $recus = collect($this->pull()->assertOk()->json('data.tickets'))->pluck('id');

        $this->assertEqualsCanonicalizing([$aujourdhui->id, $demain->id], $recus->all());
    }

    public function test_la_fenetre_exclut_les_voyages_hors_periode(): void
    {
        $this->ticket($this->voyageInstance(now()->addDays(5)->toDateString()));
        $this->ticket($this->voyageInstance(now()->subDay()->toDateString()));

        $this->pull()->assertOk()->assertJsonCount(0, 'data.tickets');
    }

    public function test_les_donnees_dune_autre_compagnie_ne_sont_jamais_renvoyees(): void
    {
        $this->ticket($this->voyageInstance(compagnie: Compagnie::factory()->create()));

        $this->pull()
            ->assertOk()
            ->assertJsonCount(0, 'data.tickets')
            ->assertJsonCount(0, 'data.voyages');
    }

    // ── Secrets ────────────────────────────────────────────────────────────

    public function test_le_code_qr_nest_transmis_que_sous_forme_dempreinte(): void
    {
        $this->ticket($this->voyageInstance(), ['code_qr' => 'SECRET-QR-BRUT', 'code_sms' => '123456']);

        $response = $this->pull()->assertOk();
        $ticket   = $response->json('data.tickets.0');

        // L'agent doit pouvoir reconnaître un code scanné...
        $this->assertSame(hash('sha256', 'SECRET-QR-BRUT'), $ticket['qr_hash']);
        $this->assertSame(hash('sha256', '123456'), $ticket['sms_hash']);

        // ...sans jamais recevoir de quoi fabriquer un billet.
        $this->assertArrayNotHasKey('code_qr', $ticket);
        $this->assertArrayNotHasKey('code_sms', $ticket);
        $response->assertDontSee('SECRET-QR-BRUT');
    }

    public function test_le_nom_du_passager_est_disponible_hors_connexion(): void
    {
        // users.numero est un entier local ; l'indicatif vit dans numero_identifiant.
        $passager = User::factory()->create([
            'name'               => 'Aminata Ouédraogo',
            'numero'             => 70000000,
            'numero_identifiant' => '+226',
        ]);
        $this->ticket($this->voyageInstance(), ['user_id' => $passager->id, 'is_my_ticket' => true]);

        $this->pull()
            ->assertOk()
            ->assertJsonPath('data.tickets.0.passenger_name', 'Aminata Ouédraogo')
            ->assertJsonPath('data.tickets.0.passenger_phone', '+226 70000000');
    }

    // ── Delta ──────────────────────────────────────────────────────────────

    public function test_sans_since_le_pull_renvoie_un_instantane_complet(): void
    {
        $instance = $this->voyageInstance();
        $this->ticket($instance);
        $this->ticket($instance);

        // Même les tickets anciens doivent revenir : c'est l'amorçage du cache.
        Ticket::query()->update(['updated_at' => now()->subMonth()]);

        $this->pull()->assertOk()->assertJsonCount(2, 'data.tickets');
    }

    public function test_avec_since_seuls_les_tickets_modifies_reviennent(): void
    {
        $instance = $this->voyageInstance();
        $ancien   = $this->ticket($instance);
        $recent   = $this->ticket($instance);

        Ticket::whereKey($ancien->id)->update(['updated_at' => now()->subDays(2)]);

        $recus = collect(
            $this->pull(['since' => now()->subHour()->toIso8601String()])->assertOk()->json('data.tickets'),
        )->pluck('id');

        $this->assertSame([$recent->id], $recus->all());
    }

    public function test_les_voyages_supprimes_sont_signales(): void
    {
        $instance = $this->voyageInstance();
        $instance->delete();

        $this->pull(['since' => now()->subHour()->toIso8601String()])
            ->assertOk()
            ->assertJsonPath('data.deleted.voyages.0', $instance->id);
    }

    // ── Pagination keyset ──────────────────────────────────────────────────

    public function test_la_pagination_parcourt_tous_les_tickets_sans_doublon(): void
    {
        $instance = $this->voyageInstance();
        for ($i = 0; $i < 7; $i++) {
            $this->ticket($instance);
        }

        $vus    = [];
        $cursor = null;
        $pages  = 0;

        do {
            $response = $this->pull(array_filter(['limit' => 2, 'cursor' => $cursor]))->assertOk();
            $vus      = array_merge($vus, collect($response->json('data.tickets'))->pluck('id')->all());
            $cursor   = $response->json('cursor.next');
            $pages++;

            $this->assertLessThan(10, $pages, 'La pagination ne se termine pas.');
        } while ($response->json('cursor.has_more'));

        $this->assertCount(7, $vus);
        $this->assertSame(7, count(array_unique($vus)), 'Un ticket a été renvoyé sur deux pages.');
    }

    public function test_last_sync_at_reste_null_tant_quil_reste_des_pages(): void
    {
        $instance = $this->voyageInstance();
        $this->ticket($instance);
        $this->ticket($instance);
        $this->ticket($instance);

        // Avancer son point de reprise ici ferait manquer les pages suivantes.
        $this->pull(['limit' => 1])
            ->assertOk()
            ->assertJsonPath('cursor.has_more', true)
            ->assertJsonPath('last_sync_at', null);

        $this->pull(['limit' => 50])
            ->assertOk()
            ->assertJsonPath('cursor.has_more', false)
            ->assertJsonPath('last_sync_at', fn ($v) => is_string($v) && $v !== '');
    }

    public function test_chaque_page_expose_un_repere_de_reprise(): void
    {
        $instance = $this->voyageInstance();
        $this->ticket($instance);
        $this->ticket($instance);

        // synced_at est capturé avant la lecture. Calculé après, un ticket modifié
        // pendant la requête serait antérieur au repère et ne serait jamais reçu.
        $page = $this->pull(['limit' => 1])->assertOk();

        $this->assertNotEmpty($page->json('synced_at'));
        $this->assertTrue($page->json('cursor.has_more'));
        $this->assertNull($page->json('last_sync_at'));
    }

    public function test_le_numero_de_voyage_nest_jamais_un_uuid(): void
    {
        $instance = $this->voyageInstance();

        $numero = $this->pull()->assertOk()->json('data.voyages.0.numero_voyage');

        $this->assertNotSame($instance->id, $numero);
    }

    public function test_les_voyages_ne_sont_renvoyes_quune_fois_sur_une_pagination(): void
    {
        $instance = $this->voyageInstance();
        $this->ticket($instance);
        $this->ticket($instance);
        $this->ticket($instance);

        $premiere = $this->pull(['limit' => 2])->assertOk();
        $this->assertCount(1, $premiere->json('data.voyages'));

        $suivante = $this->pull(['limit' => 2, 'cursor' => $premiere->json('cursor.next')])->assertOk();
        $this->assertCount(0, $suivante->json('data.voyages'));
        $this->assertCount(1, $suivante->json('data.tickets'));
    }

    public function test_un_instantane_ne_liste_pas_de_voyages_supprimes(): void
    {
        $this->voyageInstance()->delete();

        $this->pull()->assertOk()->assertJsonCount(0, 'data.deleted.voyages');
    }

    public function test_un_curseur_illisible_est_ignore_sans_erreur(): void
    {
        $this->ticket($this->voyageInstance());

        // Un curseur corrompu doit repartir du début, pas renvoyer une 500.
        $this->pull(['cursor' => 'n-importe-quoi'])
            ->assertOk()
            ->assertJsonCount(1, 'data.tickets');
    }

    public function test_une_limite_hors_bornes_est_refusee(): void
    {
        $this->pull(['limit' => 5000])->assertStatus(422);
        $this->pull(['limit' => 0])->assertStatus(422);
    }

    // ── Voyages ────────────────────────────────────────────────────────────

    public function test_les_compteurs_dembarquement_sont_exacts(): void
    {
        $instance = $this->voyageInstance();
        $this->ticket($instance, ['statut' => StatutTicket::Valider]);
        $this->ticket($instance, ['statut' => StatutTicket::Payer]);
        $this->ticket($instance, ['statut' => StatutTicket::Annuler]);

        $this->pull()
            ->assertOk()
            ->assertJsonPath('data.voyages.0.boarded_count', 1)
            // Les tickets annulés ne comptent pas parmi les attendus.
            ->assertJsonPath('data.voyages.0.ticket_count', 2);
    }

    public function test_un_agent_sans_compagnie_est_refuse(): void
    {
        $orphelin = User::factory()->create(['compagnie_id' => null]);
        Sanctum::actingAs($orphelin);

        $this->getJson('/api/admin/sync/pull')->assertForbidden();
    }
}
