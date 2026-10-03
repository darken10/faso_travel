<?php

namespace Tests\Feature\Audit;

use App\Enums\CompagnieSettingKey;
use App\Enums\ConflictResolution;
use App\Enums\StatutTicket;
use App\Enums\SyncActionType;
use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Enums\UserRole;
use App\Livewire\Compagnie\Compagnie\UserManager;
use App\Livewire\Compagnie\Finance\DepenseManager;
use App\Livewire\Compagnie\Finance\RecetteManager;
use App\Livewire\Compagnie\Ticket\ConflitManager;
use App\Livewire\Compagnie\Ticket\TicketManager;
use App\Models\AuditLog;
use App\Models\Compagnie\Compagnie;
use App\Models\Finance\CategorieDepense;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use App\Models\Role;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketValidation;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Services\Compagnie\CompagnieSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Vérifie que les actions sensibles existantes écrivent bien leur trace.
 *
 * Un service de journalisation testé en isolation ne prouve rien : ce qui compte est que
 * chaque écran sensible l'appelle réellement.
 */
class PointsDAppelTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    private User $patron;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compagnie = Compagnie::factory()->create();
        $this->patron = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);
    }

    public function test_la_suppression_d_une_depense_est_journalisee(): void
    {
        $depense = Depense::create([
            'compagnie_id' => $this->compagnie->id,
            'categorie_depense_id' => $this->categorie()->id,
            'libelle' => 'Carburant Ouaga-Bobo',
            'montant' => 45000,
            'date_depense' => now()->toDateString(),
            'user_id' => $this->patron->id,
        ]);

        Livewire::actingAs($this->patron)
            ->test(DepenseManager::class)
            ->call('delete', $depense->id);

        $trace = AuditLog::action('finance.depense.delete')->sole();

        $this->assertSame($this->patron->id, $trace->user_id);
        $this->assertSame($this->compagnie->id, $trace->compagnie_id);
        $this->assertSame('Carburant Ouaga-Bobo', $trace->avant['libelle']);
        $this->assertSame(45000, (int) $trace->avant['montant']);
    }

    public function test_la_suppression_d_une_recette_est_journalisee(): void
    {
        $recette = Recette::create([
            'compagnie_id' => $this->compagnie->id,
            'libelle' => 'Location de bus',
            'montant' => 120000,
            'date_recette' => now()->toDateString(),
            'user_id' => $this->patron->id,
        ]);

        Livewire::actingAs($this->patron)
            ->test(RecetteManager::class)
            ->call('delete', $recette->id);

        $trace = AuditLog::action('finance.recette.delete')->sole();

        $this->assertSame('Location de bus', $trace->avant['libelle']);
    }

    public function test_l_attribution_de_roles_est_journalisee(): void
    {
        $guichetier = Role::firstOrCreate(
            ['name' => 'guichetier', 'compagnie_id' => null],
            ['label' => 'Guichetier', 'scope' => 'compagnie', 'is_system' => true]
        );

        Livewire::actingAs($this->patron)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$guichetier->id])
            ->call('save')
            ->assertHasNoErrors();

        $trace = AuditLog::action('compagnie.role.assign')->sole();

        $this->assertSame([], $trace->avant['roles']);
        $this->assertSame(['guichetier'], $trace->apres['roles']);
    }

    public function test_un_enregistrement_sans_changement_de_role_n_est_pas_journalise(): void
    {
        $guichetier = Role::firstOrCreate(
            ['name' => 'guichetier', 'compagnie_id' => null],
            ['label' => 'Guichetier', 'scope' => 'compagnie', 'is_system' => true]
        );

        $membre = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $membre->roles()->attach($guichetier->id);

        Livewire::actingAs($this->patron)
            ->test(UserManager::class)
            ->call('openEdit', $membre->id)
            ->set('first_name', 'Nouveau prénom')
            ->call('save')
            ->assertHasNoErrors();

        // Une trace par enregistrement de formulaire noierait les attributions réelles.
        $this->assertSame(0, AuditLog::action('compagnie.role.assign')->count());
    }

    public function test_un_parametre_sensible_est_journalise(): void
    {
        $this->actingAs($this->patron);

        app(CompagnieSettingService::class)->setMany(
            $this->compagnie,
            [CompagnieSettingKey::COMMISSION_PLATEFORME->value => 7],
            allowAdminOnly: true,
        );

        $trace = AuditLog::action('compagnie.parametres.updateAdvanced')->sole();

        $this->assertSame(7, (int) $trace->apres[CompagnieSettingKey::COMMISSION_PLATEFORME->value]);
        $this->assertSame($this->compagnie->id, $trace->auditable_id);
    }

    public function test_un_parametre_ordinaire_n_est_pas_journalise(): void
    {
        $this->actingAs($this->patron);

        app(CompagnieSettingService::class)->setMany(
            $this->compagnie,
            [CompagnieSettingKey::CONTACT_TELEPHONE->value => '+22670000000'],
            allowAdminOnly: true,
        );

        // Journaliser le numéro de téléphone de contact rendrait la piste illisible.
        $this->assertSame(0, AuditLog::action('compagnie.parametres.updateAdvanced')->count());
    }

    public function test_le_remboursement_d_un_ticket_est_journalise(): void
    {
        $ticket = $this->ticket(StatutTicket::Pause);

        Livewire::actingAs($this->patron)
            ->test(TicketManager::class)
            ->call('rembourser', $ticket->id);

        $trace = AuditLog::action('finance.remboursement.approve')->sole();

        $this->assertSame(StatutTicket::Pause->value, $trace->avant['statut']);
        $this->assertSame(StatutTicket::Annuler->value, $trace->apres['statut']);
        $this->assertSame($ticket->id, (int) $trace->auditable_id);
    }

    public function test_la_reactivation_d_un_ticket_est_journalisee(): void
    {
        $ticket = $this->ticket(StatutTicket::Bloquer);

        Livewire::actingAs($this->patron)
            ->test(TicketManager::class)
            ->call('activer', $ticket->id);

        $this->assertSame(1, AuditLog::action('guichet.ticket.unblock')->count());
    }

    public function test_l_arbitrage_d_un_conflit_est_journalise(): void
    {
        $agent = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $conflit = TicketValidation::create([
            'operation_id' => 'op-'.uniqid(),
            'ticket_id' => $this->ticket(StatutTicket::Valider)->id,
            'agent_id' => $agent->id,
            'action' => SyncActionType::ValidateTicket,
            'client_created_at' => now()->subHours(3),
            'result' => SyncResult::Rejected,
            'error_code' => SyncErrorCode::AlreadyValidated,
        ]);

        Livewire::actingAs($this->patron)
            ->test(ConflitManager::class)
            ->call('openResolve', $conflit->id)
            ->set('resolution', ConflictResolution::Fraud->value)
            ->set('note', 'QR dupliqué, constat signé par le chef de gare.')
            ->call('resolve')
            ->assertHasNoErrors();

        $trace = AuditLog::action('embarquement.conflit.resolve')->sole();

        $this->assertSame(ConflictResolution::Fraud->value, $trace->apres['resolution']);
        $this->assertSame('QR dupliqué, constat signé par le chef de gare.', $trace->motif);
        $this->assertSame($this->patron->id, $trace->user_id);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function ticket(StatutTicket $statut): Ticket
    {
        $voyage = Voyage::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $instance = VoyageInstance::factory()->create(['voyage_id' => $voyage->id]);

        return Ticket::factory()->create([
            'voyage_instance_id' => $instance->id,
            'user_id' => User::factory()->create(['compagnie_id' => null])->id,
            'statut' => $statut,
        ]);
    }

    private function categorie(): CategorieDepense
    {
        return CategorieDepense::firstOrCreate(
            ['nom' => 'Carburant', 'compagnie_id' => $this->compagnie->id],
        );
    }
}
