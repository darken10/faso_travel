<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\StatutTicket;
use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Enums\TypeTicket;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketValidation;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Idempotence du rejeu des opérations hors connexion.
 *
 * Un téléphone qui perd le réseau pendant un POST ne sait pas si le serveur a
 * reçu : il réessaie. Sans clé d'idempotence, ce réessai réexécutait l'action.
 * Le cas le plus grave est le ticket AllerRetour, dont la validation fait
 * Payer -> Pause + type RetourSimple : un second passage consommait le retour.
 */
class BatchSyncIdempotencyTest extends TestCase
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

    private function ticketOfCompagnie(Compagnie $compagnie, array $attributes = []): Ticket
    {
        $voyage   = Voyage::factory()->create(['compagnie_id' => $compagnie->id]);
        $instance = VoyageInstance::factory()->create(['voyage_id' => $voyage->id]);

        return Ticket::factory()->create($attributes + [
            'voyage_instance_id' => $instance->id,
            'statut'             => StatutTicket::Payer,
            'type'               => TypeTicket::AllerSimple,
        ]);
    }

    private function ticket(array $attributes = []): Ticket
    {
        return $this->ticketOfCompagnie($this->compagnie, $attributes);
    }

    /** @param array<string,mixed> $overrides */
    private function push(Ticket $ticket, string $operationId, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/admin/tickets/batch-sync', [
            'actions' => [$overrides + [
                'id'        => $operationId,
                'type'      => 'VALIDATE_TICKET',
                'ticket_id' => $ticket->id,
            ]],
        ]);
    }

    // ── Idempotence ────────────────────────────────────────────────────────

    public function test_rejouer_la_meme_operation_ne_la_reexecute_pas(): void
    {
        $ticket = $this->ticket();
        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-1')
            ->assertOk()
            ->assertJsonPath('data.results.0.status', SyncResult::Applied->value);

        // Même operation_id : le serveur doit reconnaître le rejeu.
        $this->push($ticket, 'op-1')
            ->assertOk()
            ->assertJsonPath('data.results.0.status', SyncResult::AlreadyApplied->value)
            ->assertJsonPath('data.results.0.success', true);

        $this->assertSame(StatutTicket::Valider, $ticket->fresh()->statut);
        $this->assertSame(1, TicketValidation::where('operation_id', 'op-1')->count());
    }

    public function test_rejouer_une_validation_ne_consomme_pas_le_retour_dun_aller_retour(): void
    {
        $ticket = $this->ticket(['type' => TypeTicket::AllerRetour]);
        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-aller')->assertOk();

        // Après l'aller : Pause + RetourSimple, le retour reste disponible.
        $apresAller = $ticket->fresh();
        $this->assertSame(StatutTicket::Pause, $apresAller->statut);
        $this->assertSame(TypeTicket::RetourSimple, $apresAller->type);

        // Trois réessais du même scan, comme le ferait un téléphone en zone blanche.
        $this->push($ticket, 'op-aller')->assertOk();
        $this->push($ticket, 'op-aller')->assertOk();
        $this->push($ticket, 'op-aller')->assertOk();

        $apresRejeux = $ticket->fresh();
        $this->assertSame(
            StatutTicket::Pause,
            $apresRejeux->statut,
            'Le rejeu a consommé le trajet retour : la clé d\'idempotence ne protège plus.',
        );
        $this->assertSame(TypeTicket::RetourSimple, $apresRejeux->type);
    }

    public function test_deux_operations_distinctes_consomment_bien_les_deux_trajets(): void
    {
        $ticket = $this->ticket(['type' => TypeTicket::AllerRetour]);
        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-aller')->assertOk();
        $this->push($ticket, 'op-retour')
            ->assertOk()
            ->assertJsonPath('data.results.0.status', SyncResult::Applied->value);

        $this->assertSame(StatutTicket::Valider, $ticket->fresh()->statut);
    }

    // ── Conflits ───────────────────────────────────────────────────────────

    public function test_un_ticket_deja_valide_par_un_autre_agent_produit_un_conflit(): void
    {
        $ticket = $this->ticket(['statut' => StatutTicket::Valider]);
        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-conflit')
            ->assertOk()
            ->assertJsonPath('data.results.0.status', SyncResult::Rejected->value)
            ->assertJsonPath('data.results.0.success', false)
            ->assertJsonPath('data.results.0.error_code', SyncErrorCode::AlreadyValidated->value);

        $journal = TicketValidation::where('operation_id', 'op-conflit')->sole();
        $this->assertTrue($journal->error_code->isConflict());
    }

    public function test_un_statut_incompatible_nest_pas_un_conflit(): void
    {
        $ticket = $this->ticket(['statut' => StatutTicket::Annuler]);
        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-annule')
            ->assertOk()
            ->assertJsonPath('data.results.0.error_code', SyncErrorCode::InvalidStatus->value);

        $this->assertFalse(
            TicketValidation::where('operation_id', 'op-annule')->sole()->error_code->isConflict(),
        );
    }

    public function test_un_ticket_dune_autre_compagnie_est_refuse_et_journalise(): void
    {
        $ticketTiers = $this->ticketOfCompagnie(Compagnie::factory()->create());
        Sanctum::actingAs($this->agent);

        $this->push($ticketTiers, 'op-tiers')
            ->assertOk()
            ->assertJsonPath('data.results.0.error_code', SyncErrorCode::NotFound->value);

        $this->assertSame(StatutTicket::Payer, $ticketTiers->fresh()->statut);

        // Le refus est auditable même si le ticket n'a pas pu être rattaché.
        $journal = TicketValidation::where('operation_id', 'op-tiers')->sole();
        $this->assertNull($journal->ticket_id);
        $this->assertSame($ticketTiers->id, $journal->requested_ticket_id);
    }

    public function test_un_ticket_inexistant_est_journalise_sans_casser_le_lot(): void
    {
        $valide = $this->ticket();
        Sanctum::actingAs($this->agent);

        $response = $this->postJson('/api/admin/tickets/batch-sync', [
            'actions' => [
                ['id' => 'op-fantome', 'type' => 'VALIDATE_TICKET', 'ticket_id' => 999999],
                ['id' => 'op-reelle',  'type' => 'VALIDATE_TICKET', 'ticket_id' => $valide->id],
            ],
        ])->assertOk();

        $parId = collect($response->json('data.results'))->keyBy('id');
        $this->assertSame(SyncErrorCode::NotFound->value, $parId['op-fantome']['error_code']);
        $this->assertSame(SyncResult::Applied->value, $parId['op-reelle']['status']);

        // Une action invalide ne doit pas empêcher les autres d'aboutir.
        $this->assertSame(StatutTicket::Valider, $valide->fresh()->statut);
    }

    public function test_un_ticket_dun_autre_voyage_est_refuse(): void
    {
        $ticket      = $this->ticket();
        $autreVoyage = $this->ticket();

        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-mauvais-voyage', [
            'voyage_instance_id' => $autreVoyage->voyage_instance_id,
        ])
            ->assertOk()
            ->assertJsonPath('data.results.0.error_code', SyncErrorCode::WrongVoyage->value);

        $this->assertSame(StatutTicket::Payer, $ticket->fresh()->statut);
    }

    // ── Métadonnées du terrain ─────────────────────────────────────────────

    public function test_lhorodatage_du_terrain_est_conserve(): void
    {
        $ticket = $this->ticket();
        $scanne = now()->subDays(3)->startOfMinute();

        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-datee', [
            'client_created_at' => $scanne->toIso8601String(),
            'device_id'         => 'phone-agent-42',
            'method'            => 'qr',
            'verified_offline'  => false,
        ])->assertOk();

        $journal = TicketValidation::where('operation_id', 'op-datee')->sole();

        // Sans cela, un scan fait il y a 3 jours serait daté de la synchro.
        $this->assertTrue($scanne->equalTo($journal->client_created_at));
        $this->assertSame('phone-agent-42', $journal->device_id);
        $this->assertSame($this->agent->id, $journal->agent_id);
        $this->assertFalse($journal->verified_offline);
    }

    public function test_les_controles_non_verifies_sont_isolables(): void
    {
        $verifie   = $this->ticket();
        $aLaveugle = $this->ticket();

        Sanctum::actingAs($this->agent);

        $this->push($verifie, 'op-verifiee', ['verified_offline' => true])->assertOk();
        $this->push($aLaveugle, 'op-aveugle', ['verified_offline' => false])->assertOk();

        $this->assertSame(1, TicketValidation::unverified()->count());
        $this->assertSame('op-aveugle', TicketValidation::unverified()->sole()->operation_id);
    }

    // ── Garde-fous du contrat ──────────────────────────────────────────────

    public function test_un_lot_trop_volumineux_est_refuse(): void
    {
        $ticket = $this->ticket();
        Sanctum::actingAs($this->agent);

        $actions = collect(range(1, 201))->map(fn ($i) => [
            'id' => "op-$i", 'type' => 'VALIDATE_TICKET', 'ticket_id' => $ticket->id,
        ])->all();

        $this->postJson('/api/admin/tickets/batch-sync', ['actions' => $actions])
            ->assertStatus(422)
            ->assertJsonValidationErrors('actions');
    }

    public function test_un_type_daction_inconnu_est_refuse(): void
    {
        $ticket = $this->ticket();
        Sanctum::actingAs($this->agent);

        $this->push($ticket, 'op-x', ['type' => 'SUPPRIMER_TICKET'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('actions.0.type');
    }

    public function test_le_batch_sync_exige_une_authentification(): void
    {
        $this->postJson('/api/admin/tickets/batch-sync', [
            'actions' => [['id' => 'op-1', 'type' => 'VALIDATE_TICKET', 'ticket_id' => 1]],
        ])->assertUnauthorized();
    }
}
