<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
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
 * Couverture des routes /api/admin/* consommées par l'application agent.
 *
 * Ces routes n'avaient aucun test, ce qui a laissé passer deux pannes 500 :
 *   1. TicketController type-hintait App\Services\Ticket\TicketValidationService,
 *      classe supprimée par 3ed7f1f et jamais recréée. Le conteneur ne pouvait
 *      plus instancier le contrôleur → 500 sur ses 8 routes.
 *   2. SyncController::pull eager-loadait la relation 'autrePersonne', qui
 *      n'existe pas (elle s'appelle autre_personne) → 500 dès qu'au moins un
 *      ticket entrait dans la fenêtre delta, donc invisible sur base vide.
 *
 * Les tests marqués « régression » ci-dessous échouent si l'une de ces deux
 * pannes réapparaît.
 */
class AgentSyncEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();

        // On vérifie des contrats HTTP, pas les effets de bord (mails, broadcast)
        // déclenchés par les events de validation.
        Event::fake();

        $this->compagnie = Compagnie::factory()->create();
        $this->agent     = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
    }

    /**
     * Crée un ticket rattaché à une compagnie donnée.
     *
     * La chaîne Voyage → VoyageInstance → Ticket est construite explicitement :
     * le cloisonnement testé passe par ofCompagnie(), qui remonte
     * voyageInstance.voyage.compagnie_id. S'appuyer sur le hook creating de
     * Voyage rendrait le test dépendant de l'utilisateur authentifié au moment
     * de la factory, ce qui est fragile.
     *
     * À appeler avant Sanctum::actingAs pour que le hook ne réécrive pas
     * compagnie_id.
     */
    private function ticketOfCompagnie(Compagnie $compagnie, array $attributes = []): Ticket
    {
        $voyage = Voyage::factory()->create(['compagnie_id' => $compagnie->id]);
        $instance = VoyageInstance::factory()->create(['voyage_id' => $voyage->id]);

        return Ticket::factory()->create($attributes + [
            'voyage_instance_id' => $instance->id,
            'statut'             => StatutTicket::Payer,
            'type'               => TypeTicket::AllerSimple,
        ]);
    }

    /** Ticket vendu sur un voyage de la compagnie de l'agent sous test. */
    private function ticketOfAgentCompagnie(array $attributes = []): Ticket
    {
        return $this->ticketOfCompagnie($this->compagnie, $attributes);
    }

    // ── Régression : le conteneur sait instancier TicketController ──────────

    public function test_les_routes_de_ticketcontroller_sont_instanciables(): void
    {
        Sanctum::actingAs($this->agent);

        // Un 404 métier prouve que le contrôleur a été résolu et exécuté.
        // Avant le correctif, le conteneur échouait avant même d'y entrer (500).
        $response = $this->getJson('/api/admin/tickets/verify/code-inexistant');

        $this->assertNotSame(
            500,
            $response->status(),
            'TicketController ne peut pas être instancié — TicketValidationService est probablement à nouveau manquante.',
        );
        $response->assertNotFound();
    }

    // ── Régression : pull ne casse plus dès qu'un ticket entre dans la fenêtre

    public function test_sync_pull_fonctionne_avec_des_tickets_dans_la_fenetre(): void
    {
        $ticket = $this->ticketOfAgentCompagnie(['statut' => StatutTicket::Valider]);

        Sanctum::actingAs($this->agent);

        $response = $this->getJson('/api/admin/sync/pull?since=' . urlencode(now()->subHour()->toIso8601String()));

        $this->assertNotSame(
            500,
            $response->status(),
            "sync/pull lève une erreur serveur — un eager-load de relation inexistante a probablement été réintroduit.",
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.validations.0.ticket_id', $ticket->id)
            ->assertJsonPath('data.validations.0.statut', StatutTicket::Valider->value);
    }

    public function test_sync_pull_accepte_le_format_iso_envoye_par_lapp_mobile(): void
    {
        // Date.toISOString() côté JS produit un suffixe 'Z', sans '+' à encoder.
        $this->ticketOfAgentCompagnie(['statut' => StatutTicket::Valider]);

        Sanctum::actingAs($this->agent);

        $this->getJson('/api/admin/sync/pull?since=' . now()->subHour()->utc()->format('Y-m-d\\TH:i:s\\Z'))
            ->assertOk()
            ->assertJsonCount(1, 'data.validations');
    }

    public function test_sync_pull_rejette_une_date_since_invalide(): void
    {
        Sanctum::actingAs($this->agent);

        $this->getJson('/api/admin/sync/pull?since=pas-une-date')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_sync_pull_ne_fuite_pas_les_tickets_dune_autre_compagnie(): void
    {
        // Ticket vendu par une compagnie tierce
        $this->ticketOfCompagnie(
            Compagnie::factory()->create(),
            ['statut' => StatutTicket::Valider],
        );

        Sanctum::actingAs($this->agent);

        $this->getJson('/api/admin/sync/pull?since=' . urlencode(now()->subHour()->toIso8601String()))
            ->assertOk()
            ->assertJsonCount(0, 'data.validations');
    }

    public function test_sync_pull_exige_une_authentification(): void
    {
        $this->getJson('/api/admin/sync/pull')->assertUnauthorized();
    }

    // ── Validation d'un ticket ─────────────────────────────────────────────

    public function test_un_agent_valide_un_ticket_paye(): void
    {
        $ticket = $this->ticketOfAgentCompagnie();

        Sanctum::actingAs($this->agent);

        $this->postJson('/api/admin/tickets/validate', ['ticket_id' => $ticket->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(StatutTicket::Valider, $ticket->fresh()->statut);
    }

    public function test_un_ticket_deja_valide_est_refuse(): void
    {
        $ticket = $this->ticketOfAgentCompagnie(['statut' => StatutTicket::Valider]);

        Sanctum::actingAs($this->agent);

        $this->postJson('/api/admin/tickets/validate', ['ticket_id' => $ticket->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_un_agent_ne_peut_pas_valider_le_ticket_dune_autre_compagnie(): void
    {
        $ticketTiers = $this->ticketOfCompagnie(Compagnie::factory()->create());

        Sanctum::actingAs($this->agent);

        $this->postJson('/api/admin/tickets/validate', ['ticket_id' => $ticketTiers->id])
            ->assertNotFound();

        $this->assertSame(StatutTicket::Payer, $ticketTiers->fresh()->statut);
    }

    // ── Batch sync ─────────────────────────────────────────────────────────

    public function test_batch_sync_rejoue_une_validation_hors_ligne(): void
    {
        $ticket = $this->ticketOfAgentCompagnie();

        Sanctum::actingAs($this->agent);

        $this->postJson('/api/admin/tickets/batch-sync', [
            'actions' => [[
                'id'        => 'op-locale-1',
                'type'      => 'VALIDATE_TICKET',
                'ticket_id' => $ticket->id,
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.results.0.id', 'op-locale-1')
            ->assertJsonPath('data.results.0.success', true)
            ->assertJsonPath('data.synced', 1);

        $this->assertSame(StatutTicket::Valider, $ticket->fresh()->statut);
    }

    public function test_batch_sync_correle_chaque_resultat_a_son_id_client(): void
    {
        $premier = $this->ticketOfAgentCompagnie();
        $second  = $this->ticketOfAgentCompagnie();

        Sanctum::actingAs($this->agent);

        $response = $this->postJson('/api/admin/tickets/batch-sync', [
            'actions' => [
                ['id' => 'op-a', 'type' => 'VALIDATE_TICKET', 'ticket_id' => $premier->id],
                ['id' => 'op-b', 'type' => 'VALIDATE_TICKET', 'ticket_id' => $second->id],
            ],
        ])->assertOk();

        $ids = array_column($response->json('data.results'), 'id');
        $this->assertEqualsCanonicalizing(['op-a', 'op-b'], $ids);
    }

    // ── Pré-chargement offline ─────────────────────────────────────────────

    public function test_la_liste_des_passagers_dun_voyage_est_accessible(): void
    {
        $ticket = $this->ticketOfAgentCompagnie();

        Sanctum::actingAs($this->agent);

        $response = $this->getJson("/api/admin/voyages/{$ticket->voyage_instance_id}/passengers");

        $this->assertNotSame(500, $response->status());
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_un_ticket_est_verifiable_par_son_code_qr(): void
    {
        $ticket = $this->ticketOfAgentCompagnie(['code_qr' => 'QR-TEST-AGENT-001']);

        Sanctum::actingAs($this->agent);

        $this->getJson('/api/admin/tickets/verify/QR-TEST-AGENT-001')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ticket->id);
    }
}
