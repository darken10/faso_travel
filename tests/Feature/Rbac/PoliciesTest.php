<?php

namespace Tests\Feature\Rbac;

use App\Enums\StatutTicket;
use App\Enums\UserRole;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Les policies de modèle, réécrites contre les permissions.
 *
 * Elles ne sont appelées par aucun chemin de code aujourd'hui : le panneau contrôle ses
 * écrans et ses actions par le middleware et le trait d'autorisation. Mais elles sont
 * enregistrées, donc le premier `can()` écrit sur un de ces modèles obtiendra une vraie
 * réponse — et non `true` par défaut, comme c'était le cas pour le parc roulant.
 */
class PoliciesTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compagnie = Compagnie::factory()->create();
    }

    // ── CarePolicy ───────────────────────────────────────────────────────────

    public function test_la_policy_du_parc_ne_renvoie_plus_true_par_defaut(): void
    {
        $vehicule = Care::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $sansDroit = $this->membre([]);

        foreach (['view', 'create', 'update', 'delete', 'setStatut'] as $ability) {
            $this->assertFalse(
                Gate::forUser($sansDroit)->allows($ability, $vehicule),
                "La policy du parc autorise encore {$ability} sans permission."
            );
        }
    }

    public function test_la_policy_du_parc_est_bien_enregistree(): void
    {
        // Sans enregistrement explicite, la découverte automatique chercherait
        // App\Policies\Compagnie\CarePolicy et ne trouverait rien.
        $this->assertNotNull(Gate::getPolicyFor(Care::class));
    }

    public function test_le_chef_de_parc_modifie_ses_vehicules_et_pas_ceux_des_autres(): void
    {
        $chefDeParc = $this->membre(['reseau.vehicule.update']);

        $sien = Care::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $autre = Care::factory()->create(['compagnie_id' => Compagnie::factory()->create()->id]);

        $this->assertTrue(Gate::forUser($chefDeParc)->allows('update', $sien));
        $this->assertFalse(Gate::forUser($chefDeParc)->allows('update', $autre));
    }

    // ── TicketPolicy ─────────────────────────────────────────────────────────

    public function test_un_compte_verifie_par_telephone_peut_acheter_un_ticket(): void
    {
        $parTelephone = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => now(),
            'compagnie_id' => null,
        ]);

        // hasVerifiedEmail() aurait refusé : depuis l'ajout de phone_verified_at, un compte
        // créé par OTP téléphone est légitime et n'a pas d'e-mail vérifié.
        $this->assertTrue(Gate::forUser($parTelephone)->allows('create', Ticket::class));
    }

    public function test_un_compte_non_verifie_ne_peut_pas_acheter(): void
    {
        $nonVerifie = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'compagnie_id' => null,
        ]);

        $this->assertFalse(Gate::forUser($nonVerifie)->allows('create', Ticket::class));
    }

    public function test_le_proprietaire_voit_son_ticket(): void
    {
        $ticket = $this->ticket();
        $proprietaire = User::find($ticket->user_id);

        $this->assertTrue(Gate::forUser($proprietaire)->allows('view', $ticket));
    }

    public function test_un_tiers_sans_permission_ne_voit_pas_le_ticket(): void
    {
        $this->assertFalse(Gate::forUser($this->membre([]))->allows('view', $this->ticket()));
    }

    public function test_valider_un_embarquement_demande_la_permission_d_agent(): void
    {
        $ticket = $this->ticket();

        // Un rattachement à la compagnie ne suffit plus : un comptable ne valide pas au quai.
        $this->assertFalse(Gate::forUser($this->membre([]))->allows('validate', $ticket));
        $this->assertTrue(
            Gate::forUser($this->membre(['embarquement.ticket.validate']))->allows('validate', $ticket)
        );
    }

    public function test_le_proprietaire_met_son_ticket_en_pause_selon_son_etat(): void
    {
        $valide = $this->ticket(StatutTicket::Valider);
        $enAttente = $this->ticket(StatutTicket::EnAttente);

        $this->assertTrue(Gate::forUser(User::find($valide->user_id))->allows('pause', $valide));
        $this->assertFalse(Gate::forUser(User::find($enAttente->user_id))->allows('pause', $enAttente));
    }

    // ── VoyagePolicy ─────────────────────────────────────────────────────────

    public function test_le_catalogue_des_voyages_reste_consultable(): void
    {
        $voyage = Voyage::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $voyageur = User::factory()->create(['compagnie_id' => null]);

        // Le catalogue est ce qu'un voyageur parcourt : le fermer casserait la recherche.
        $this->assertTrue(Gate::forUser($voyageur)->allows('view', $voyage));
        $this->assertTrue(Gate::forUser($voyageur)->allows('viewAny', Voyage::class));
    }

    public function test_modifier_un_voyage_demande_la_permission_et_la_compagnie(): void
    {
        $exploitation = $this->membre(['voyage.voyage.update']);

        $sien = Voyage::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $autre = Voyage::factory()->create(['compagnie_id' => Compagnie::factory()->create()->id]);

        $this->assertTrue(Gate::forUser($exploitation)->allows('update', $sien));
        $this->assertFalse(Gate::forUser($exploitation)->allows('update', $autre));
    }

    // ── VoyageInstancePolicy ─────────────────────────────────────────────────

    public function test_annuler_un_depart_demande_la_permission_d_annulation(): void
    {
        $voyage = Voyage::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $instance = VoyageInstance::factory()->create(['voyage_id' => $voyage->id]);

        $this->assertFalse(Gate::forUser($this->membre([]))->allows('cancel', $instance));
        $this->assertTrue(
            Gate::forUser($this->membre(['voyage.instance.cancel']))->allows('cancel', $instance)
        );
    }

    // ── CompagniePolicy ──────────────────────────────────────────────────────

    public function test_la_policy_compagnie_ne_leve_plus_d_erreur(): void
    {
        $compagnie = $this->compagnie;

        // Elle référençait CompanyRole::Directeur et $user->company_role, qui n'existent
        // ni l'un ni l'autre : toute évaluation levait une erreur fatale.
        foreach ([$this->membre([]), $this->membre(['finance.bilan.view'])] as $user) {
            foreach (['view', 'create', 'update', 'delete', 'activate', 'suspend', 'manageUsers', 'manageFinance'] as $ability) {
                $this->assertIsBool(Gate::forUser($user)->allows($ability, $compagnie));
            }
        }
    }

    public function test_la_direction_voit_la_finance_de_sa_compagnie_seulement(): void
    {
        $dg = $this->membre(['finance.bilan.view']);

        $this->assertTrue(Gate::forUser($dg)->allows('manageFinance', $this->compagnie));
        $this->assertFalse(
            Gate::forUser($dg)->allows('manageFinance', Compagnie::factory()->create())
        );
    }

    // ── Gate des conflits ────────────────────────────────────────────────────

    public function test_le_gate_des_conflits_accepte_la_permission_et_les_roles_historiques(): void
    {
        $parPermission = $this->membre(['embarquement.conflit.view']);
        $parRoleHistorique = $this->membre([]);
        $parRoleHistorique->roles()->syncWithoutDetaching([
            Role::firstOrCreate(['name' => 'comptabilite', 'compagnie_id' => null], ['label' => 'Comptabilité'])->id,
        ]);
        $parRoleHistorique->invaliderCachePermissions();

        $this->assertTrue(Gate::forUser($parPermission)->allows('manage-boarding-conflicts'));
        $this->assertTrue(Gate::forUser($parRoleHistorique)->allows('manage-boarding-conflicts'));
        $this->assertFalse(Gate::forUser($this->membre([]))->allows('manage-boarding-conflicts'));
    }

    public function test_root_passe_toutes_les_policies(): void
    {
        $root = User::factory()->create(['role' => UserRole::Root, 'compagnie_id' => null]);
        $vehicule = Care::factory()->create(['compagnie_id' => $this->compagnie->id]);

        $this->assertTrue(Gate::forUser($root)->allows('delete', $vehicule));
        $this->assertTrue(Gate::forUser($root)->allows('manage-boarding-conflicts'));
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $permissions
     */
    private function membre(array $permissions): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => $this->compagnie->id,
        ]);

        if ($permissions === []) {
            return $user;
        }

        $role = Role::firstOrCreate(
            ['name' => 'pol_'.md5(implode('|', $permissions)), 'compagnie_id' => null],
            ['label' => 'Rôle de test', 'scope' => 'compagnie', 'is_system' => false]
        );

        foreach ($permissions as $nom) {
            $role->permissions()->syncWithoutDetaching([
                Permission::firstOrCreate(
                    ['name' => $nom],
                    [
                        'domaine' => explode('.', $nom)[0],
                        'label' => $nom,
                        'scope' => str_starts_with($nom, 'platform.') ? 'platform' : 'compagnie',
                    ]
                )->id => ['portee' => 'compagnie'],
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->invaliderCachePermissions();

        return $user;
    }

    private function ticket(StatutTicket $statut = StatutTicket::Payer): Ticket
    {
        $voyage = Voyage::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $instance = VoyageInstance::factory()->create(['voyage_id' => $voyage->id]);

        return Ticket::factory()->create([
            'voyage_instance_id' => $instance->id,
            'user_id' => User::factory()->create(['compagnie_id' => null])->id,
            'statut' => $statut,
        ]);
    }
}
