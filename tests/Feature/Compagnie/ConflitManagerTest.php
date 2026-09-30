<?php

namespace Tests\Feature\Compagnie;

use App\Enums\ConflictResolution;
use App\Enums\CompanyRole;
use App\Enums\StatutTicket;
use App\Enums\SyncActionType;
use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Enums\UserRole;
use App\Livewire\Compagnie\Ticket\ConflitManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Role;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketValidation;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Instruction par l'administration et la finance des validations refusées.
 *
 * Quand l'agent rejoue ses embarquements hors ligne, le serveur en refuse certains
 * (ticket déjà utilisé ailleurs, annulé entre-temps). Le passager est déjà monté
 * dans le bus : ces refus doivent apparaître côté administration et finance, et
 * UNIQUEMENT pour la compagnie concernée.
 */
class ConflitManagerTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compagnie = Compagnie::factory()->create();
    }

    private function membre(CompanyRole $role, ?Compagnie $compagnie = null): User
    {
        $user = User::factory()->create([
            'compagnie_id' => ($compagnie ?? $this->compagnie)->id,
            'role'         => UserRole::User,
        ]);
        Role::firstOrCreate(['name' => $role->value], ['label' => $role->label()]);
        $user->assignRole($role->value);

        return $user;
    }

    private function agent(?Compagnie $compagnie = null): User
    {
        return $this->membre(CompanyRole::Agent, $compagnie);
    }

    /** Un refus enregistré pour l'agent donné. */
    private function refus(User $agent, array $attributes = []): TicketValidation
    {
        $ticket = $attributes['ticket'] ?? null;
        unset($attributes['ticket']);

        return TicketValidation::create($attributes + [
            'operation_id'        => 'op-' . uniqid(),
            'ticket_id'           => $ticket?->id,
            'requested_ticket_id' => $ticket?->id,
            'agent_id'            => $agent->id,
            'action'              => SyncActionType::ValidateTicket,
            'client_created_at'   => now()->subHours(3),
            'result'              => SyncResult::Rejected,
            'error_code'          => SyncErrorCode::AlreadyValidated,
        ]);
    }

    private function ticketDe(Compagnie $compagnie): Ticket
    {
        $voyage   = Voyage::factory()->create(['compagnie_id' => $compagnie->id]);
        $instance = VoyageInstance::factory()->create(['voyage_id' => $voyage->id]);

        return Ticket::factory()->create([
            'voyage_instance_id' => $instance->id,
            'statut'             => StatutTicket::Valider,
        ]);
    }

    // ── Accès ──────────────────────────────────────────────────────────────

    public function test_ladministrateur_et_la_comptabilite_ont_acces(): void
    {
        foreach ([CompanyRole::Admin, CompanyRole::Comptabilite] as $role) {
            Livewire::actingAs($this->membre($role))->test(ConflitManager::class)->assertOk();
        }
    }

    public function test_la_direction_de_la_compagnie_a_acces(): void
    {
        $boss = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role'         => UserRole::CompagnieBosse,
        ]);

        Livewire::actingAs($boss)->test(ConflitManager::class)->assertOk();
    }

    public function test_un_agent_terrain_nas_pas_acces(): void
    {
        Livewire::actingAs($this->agent())->test(ConflitManager::class)->assertForbidden();
    }

    public function test_un_guichetier_nas_pas_acces(): void
    {
        Livewire::actingAs($this->membre(CompanyRole::Caisse))->test(ConflitManager::class)->assertForbidden();
    }

    // ── Page complète (layout, navigation, badge) ──────────────────────────

    public function test_la_page_complete_affiche_le_menu_avec_le_compteur(): void
    {
        $agent = $this->agent();
        $this->refus($agent, ['operation_id' => 'a']);
        $this->refus($agent, ['operation_id' => 'b']);
        $this->refus($this->agent(Compagnie::factory()->create()), ['operation_id' => 'etranger']);

        $this->actingAs($this->membre(CompanyRole::Admin))
            ->get(route('panel.compagnie.conflits'))
            ->assertOk()
            ->assertSee("Conflits d'embarquement", false)
            // 2 conflits ouverts chez nous ; celui de la compagnie étrangère ne compte pas.
            ->assertSee('bg-amber-500 text-white text-xs font-bold">2<', false);
    }

    public function test_la_page_est_interdite_a_un_agent_et_absente_de_son_menu(): void
    {
        $this->actingAs($this->agent())
            ->get(route('panel.compagnie.conflits'))
            ->assertForbidden();

        // Sur une autre page du panneau, l'entrée « Conflits » ne doit pas apparaître.
        $this->actingAs($this->agent())
            ->get(route('panel.compagnie.tickets'))
            ->assertOk()
            ->assertDontSee('panel.compagnie.conflits')
            ->assertDontSee('/conflits', false);
    }

    public function test_sans_conflit_le_menu_napparait_sans_badge(): void
    {
        $this->actingAs($this->membre(CompanyRole::Admin))
            ->get(route('panel.compagnie.tickets'))
            ->assertOk()
            ->assertSee('/conflits', false)
            ->assertDontSee('bg-amber-500 text-white text-xs font-bold', false);
    }

    // ── Affichage et cloisonnement ─────────────────────────────────────────

    public function test_seuls_les_refus_sont_listes(): void
    {
        $agent = $this->agent();
        $refus = $this->refus($agent, ['operation_id' => 'op-refuse']);
        TicketValidation::create([
            'operation_id' => 'op-ok', 'agent_id' => $agent->id, 'action' => SyncActionType::ValidateTicket,
            'client_created_at' => now(), 'result' => SyncResult::Applied,
        ]);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->assertViewHas('conflits', fn ($page) => $page->pluck('id')->all() === [$refus->id]);
    }

    public function test_les_conflits_dune_autre_compagnie_sont_invisibles(): void
    {
        $autre = Compagnie::factory()->create();
        $this->refus($this->agent($autre), ['operation_id' => 'op-concurrent']);
        $mien = $this->refus($this->agent(), ['operation_id' => 'op-mien']);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->assertViewHas('conflits', fn ($page) => $page->pluck('id')->all() === [$mien->id])
            ->assertViewHas('ouverts', 1);
    }

    public function test_un_refus_sans_ticket_reste_visible(): void
    {
        // « Ticket introuvable » n'a pas de ticket, donc aucun voyage par lequel
        // remonter à la compagnie : le rattachement passe par l'agent.
        $refus = $this->refus($this->agent(), [
            'operation_id' => 'op-faux-qr',
            'error_code'   => SyncErrorCode::NotFound,
            'requested_qr_hash' => hash('sha256', 'QR-FABRIQUE'),
        ]);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->assertViewHas('conflits', fn ($page) => $page->pluck('id')->all() === [$refus->id])
            ->assertSee('Ticket inconnu');
    }

    public function test_un_ticket_annule_entre_temps_est_remonte(): void
    {
        $refus = $this->refus($this->agent(), [
            'operation_id' => 'op-annule',
            'error_code'   => SyncErrorCode::InvalidStatus,
        ]);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->assertViewHas('conflits', fn ($page) => $page->pluck('id')->all() === [$refus->id]);
    }

    // ── Filtres ────────────────────────────────────────────────────────────

    public function test_la_liste_par_defaut_ne_montre_que_les_conflits_a_traiter(): void
    {
        $agent = $this->agent();
        $ouvert = $this->refus($agent, ['operation_id' => 'op-ouvert']);
        $this->refus($agent, [
            'operation_id' => 'op-traite',
            'resolved_at'  => now(), 'resolution' => ConflictResolution::Dismissed,
        ]);

        $composant = Livewire::actingAs($this->membre(CompanyRole::Admin))->test(ConflitManager::class);

        $composant->assertViewHas('conflits', fn ($p) => $p->pluck('id')->all() === [$ouvert->id]);
        $composant->set('etat', 'tous')->assertViewHas('conflits', fn ($p) => $p->count() === 2);
        $composant->set('etat', 'traites')->assertViewHas('conflits', fn ($p) => $p->count() === 1);
    }

    public function test_filtre_par_motif(): void
    {
        $agent = $this->agent();
        $this->refus($agent, ['operation_id' => 'a', 'error_code' => SyncErrorCode::AlreadyValidated]);
        $attendu = $this->refus($agent, ['operation_id' => 'b', 'error_code' => SyncErrorCode::WrongVoyage]);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->set('motif', SyncErrorCode::WrongVoyage->value)
            ->assertViewHas('conflits', fn ($p) => $p->pluck('id')->all() === [$attendu->id]);
    }

    public function test_filtre_par_date_du_scan(): void
    {
        $agent = $this->agent();
        $this->refus($agent, ['operation_id' => 'vieux', 'client_created_at' => now()->subDays(10)]);
        $recent = $this->refus($agent, ['operation_id' => 'recent', 'client_created_at' => now()->subHour()]);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->set('dateFrom', now()->subDays(2)->toDateString())
            ->assertViewHas('conflits', fn ($p) => $p->pluck('id')->all() === [$recent->id]);
    }

    // ── Traitement ─────────────────────────────────────────────────────────

    public function test_le_traitement_enregistre_lissue_lauteur_et_la_date(): void
    {
        $refus = $this->refus($this->agent());
        $finance = $this->membre(CompanyRole::Comptabilite);

        Livewire::actingAs($finance)
            ->test(ConflitManager::class)
            ->call('openResolve', $refus->id)
            ->set('resolution', ConflictResolution::Regularized->value)
            ->set('note', '  Passager a payé la différence au guichet.  ')
            ->call('resolve')
            ->assertHasNoErrors();

        $refus->refresh();
        $this->assertSame(ConflictResolution::Regularized, $refus->resolution);
        $this->assertSame('Passager a payé la différence au guichet.', $refus->resolution_note);
        $this->assertSame($finance->id, $refus->resolved_by_id);
        $this->assertNotNull($refus->resolved_at);
        $this->assertSame(0, TicketValidation::openConflicts()->count());
    }

    public function test_une_issue_est_obligatoire_et_valide(): void
    {
        $refus = $this->refus($this->agent());
        $composant = Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->call('openResolve', $refus->id);

        $composant->call('resolve')->assertHasErrors(['resolution' => 'required']);
        $composant->set('resolution', 'supprimer-tout')->call('resolve')->assertHasErrors(['resolution' => 'in']);

        $this->assertNull($refus->fresh()->resolved_at);
    }

    public function test_on_ne_peut_pas_traiter_le_conflit_dune_autre_compagnie(): void
    {
        $etranger = $this->refus($this->agent(Compagnie::factory()->create()), ['operation_id' => 'op-etranger']);

        // Identifiant forgé depuis le navigateur.
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->call('openResolve', $etranger->id);
    }

    public function test_un_identifiant_forge_ne_permet_pas_de_traiter_un_conflit_etranger(): void
    {
        $mien     = $this->refus($this->agent(), ['operation_id' => 'op-mien']);
        $etranger = $this->refus($this->agent(Compagnie::factory()->create()), ['operation_id' => 'op-etranger']);

        $composant = Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->call('openResolve', $mien->id)
            ->set('resolution', ConflictResolution::Fraud->value);

        // Le navigateur remplace l'identifiant par celui d'une autre compagnie.
        $composant->set('resolvingId', $etranger->id);

        try {
            $composant->call('resolve');
            $this->fail('Le conflit étranger n\'aurait pas dû être traitable.');
        } catch (ModelNotFoundException) {
            // Comportement attendu.
        }

        $this->assertNull($etranger->fresh()->resolved_at);
    }

    public function test_un_conflit_traite_peut_etre_corrige(): void
    {
        $refus = $this->refus($this->agent(), [
            'resolved_at' => now()->subDay(), 'resolution' => ConflictResolution::Dismissed,
        ]);

        Livewire::actingAs($this->membre(CompanyRole::Admin))
            ->test(ConflitManager::class)
            ->call('openResolve', $refus->id)
            ->assertSet('resolution', ConflictResolution::Dismissed->value)
            ->set('resolution', ConflictResolution::Fraud->value)
            ->call('resolve');

        $this->assertSame(ConflictResolution::Fraud, $refus->fresh()->resolution);
    }
}
