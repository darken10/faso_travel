<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Livewire\Compagnie\Compagnie\UserManager;
use App\Livewire\Compagnie\Habilitation\RoleManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Finance\CategorieDepense;
use App\Models\Finance\Depense;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Rbac\ReglesSeparation;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class SeparationDesPouvoirsTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    private User $patron;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->compagnie = Compagnie::factory()->create();
        $this->patron = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);
        $this->patron->roles()->syncWithoutDetaching([$this->role('compagnie_dg')->id]);
        $this->patron->invaliderCachePermissions();
    }

    // ── Cumuls interdits ─────────────────────────────────────────────────────

    /**
     * @dataProvider cumulsInterdits
     */
    public function test_un_cumul_interdit_est_refuse(string $premier, string $second): void
    {
        $this->creerMembre([$premier, $second])->assertHasErrors('selectedRoles');

        $this->assertDatabaseMissing('users', ['email' => 'awa@example.test']);
    }

    public static function cumulsInterdits(): array
    {
        $cas = [];

        foreach (ReglesSeparation::CUMULS_INTERDITS as [$a, $b, $raison]) {
            $cas[$a.' + '.$b] = [$a, $b];
        }

        return $cas;
    }

    public function test_un_role_plateforme_ne_se_cumule_pas_avec_un_role_de_compagnie(): void
    {
        $erreurs = app(ReglesSeparation::class)->verifierEnsemble([
            $this->role('platform_admin'),
            $this->role('guichetier'),
        ]);

        $this->assertNotEmpty($erreurs);
        $this->assertStringContainsString('jamais porter un rôle plateforme', $erreurs[0]);
    }

    public function test_un_cumul_legitime_est_accepte(): void
    {
        // Chef de gare et bagagiste travaillent tous deux au quai : rien ne s'y oppose.
        $this->creerMembre(['chef_gare', 'bagagiste'])->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'awa@example.test']);
    }

    public function test_le_message_de_refus_donne_la_raison(): void
    {
        $erreurs = app(ReglesSeparation::class)->verifierEnsemble([
            $this->role('guichetier'),
            $this->role('chef_caisse'),
        ]);

        // « Interdit » n'apprend rien à qui essaie, et la règle finit par être contournée.
        $this->assertStringContainsString('ses propres écarts de caisse', $erreurs[0]);
    }

    // ── Règle de rang ────────────────────────────────────────────────────────

    public function test_un_chef_de_gare_ne_peut_pas_creer_un_chef_de_gare(): void
    {
        $chefDeGare = $this->membreAvecRole('chef_gare', ['compagnie.user.create']);

        Livewire::actingAs($chefDeGare)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('chef_gare')->id])
            ->call('save')
            ->assertHasErrors('selectedRoles');
    }

    public function test_un_chef_de_gare_peut_creer_un_guichetier(): void
    {
        $chefDeGare = $this->membreAvecRole('chef_gare', ['compagnie.user.create']);

        Livewire::actingAs($chefDeGare)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_un_patron_sans_role_attache_peut_encore_administrer(): void
    {
        // Entre le deploiement et rbac:migrate-roles, un patron porte users.role =
        // Companie Bosse sans aucune ligne dans role_user. S'en tenir aux roles attaches
        // lui donnerait le rang 0 et l'empecherait d'administrer sa propre equipe.
        $patronNonMigre = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);

        $this->assertSame(0, $patronNonMigre->roles()->count());

        Livewire::actingAs($patronNonMigre)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_un_simple_membre_ne_peut_attribuer_aucun_role(): void
    {
        $membre = $this->membreAvecRole('guichetier', ['compagnie.user.create']);

        // Rang 20 contre 20 : un guichetier n'attribue pas son propre rôle.
        Livewire::actingAs($membre)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->call('save')
            ->assertHasErrors('selectedRoles');
    }

    public function test_un_compte_root_echappe_a_la_regle_de_rang(): void
    {
        $root = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::Root,
        ]);

        Livewire::actingAs($root)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('compagnie_admin')->id])
            ->call('save')
            ->assertHasNoErrors();
    }

    // ── Auto-administration ──────────────────────────────────────────────────

    public function test_un_utilisateur_ne_peut_pas_modifier_ses_propres_roles(): void
    {
        Livewire::actingAs($this->patron)
            ->test(UserManager::class)
            ->call('openEdit', $this->patron->id)
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->call('save')
            ->assertHasErrors('selectedRoles');

        $this->assertTrue($this->patron->fresh()->roles->pluck('name')->contains('compagnie_dg'));
    }

    // ── Quatre yeux ──────────────────────────────────────────────────────────

    public function test_l_auteur_d_une_depense_ne_peut_pas_l_approuver(): void
    {
        $comptable = $this->membreAvecRole('comptable', ['finance.depense.approve']);
        $sienne = $this->depense($comptable);

        // Séparer create de approve dans le catalogue ne suffit pas : un compte peut
        // légitimement porter les deux, et la règle se joue alors sur la pièce.
        $this->assertFalse(Gate::forUser($comptable)->allows('approve', $sienne));
    }

    public function test_un_autre_comptable_peut_approuver_la_depense(): void
    {
        $saisisseur = $this->membreAvecRole('guichetier', []);
        $approbateur = $this->membreAvecRole('comptable', ['finance.depense.approve']);

        $this->assertTrue(Gate::forUser($approbateur)->allows('approve', $this->depense($saisisseur)));
    }

    public function test_une_depense_sans_auteur_connu_n_est_pas_approuvable(): void
    {
        $approbateur = $this->membreAvecRole('comptable', ['finance.depense.approve']);
        $orpheline = $this->depense($approbateur);
        $orpheline->user_id = null;

        // Un doute sur l'origine d'une écriture financière ne s'arbitre pas en faveur de
        // l'approbation.
        $this->assertFalse(Gate::forUser($approbateur)->allows('approve', $orpheline));
    }

    // ── Écran d'administration des rôles ─────────────────────────────────────

    public function test_un_role_systeme_n_est_pas_modifiable(): void
    {
        $gabarit = $this->role('guichetier');

        // En HTTP, le gestionnaire d'exceptions convertit cela en 404 ; le harness
        // Livewire laisse l'exception remonter telle quelle.
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->patron)
            ->test(RoleManager::class)
            ->call('openEdit', $gabarit->id);
    }

    public function test_un_role_d_une_autre_compagnie_n_est_pas_modifiable(): void
    {
        $etranger = Role::create([
            'name' => 'role_concurrent',
            'label' => 'Rôle du concurrent',
            'compagnie_id' => Compagnie::factory()->create()->id,
            'scope' => 'compagnie',
            'is_system' => false,
            'rang' => 10,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->patron)
            ->test(RoleManager::class)
            ->call('openEdit', $etranger->id);
    }

    public function test_un_gabarit_peut_etre_derive_en_role_de_compagnie(): void
    {
        Livewire::actingAs($this->patron)
            ->test(RoleManager::class)
            ->call('derive', $this->role('guichetier')->id)
            ->assertSet('showModal', true)
            ->call('save')
            ->assertHasNoErrors();

        $derive = Role::where('compagnie_id', $this->compagnie->id)->sole();

        $this->assertFalse($derive->is_system);
        $this->assertSame('Guichetier (adapté)', $derive->label);
        $this->assertGreaterThan(0, $derive->permissions()->count());
    }

    public function test_un_role_ne_peut_pas_etre_cree_au_dessus_du_sien(): void
    {
        $chefDeGare = $this->membreAvecRole('chef_gare', ['compagnie.role.manage']);

        Livewire::actingAs($chefDeGare)
            ->test(RoleManager::class)
            ->set('label', 'Super chef')
            ->set('rang', 90)
            ->call('save')
            ->assertHasErrors('rang');
    }

    public function test_un_role_encore_attribue_n_est_pas_supprimable(): void
    {
        $role = Role::create([
            'name' => 'role_utilise',
            'label' => 'Rôle utilisé',
            'compagnie_id' => $this->compagnie->id,
            'scope' => 'compagnie',
            'is_system' => false,
            'rang' => 10,
        ]);
        $membre = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $membre->roles()->attach($role->id);

        Livewire::actingAs($this->patron)
            ->test(RoleManager::class)
            ->call('delete', $role->id);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $nomsDeRoles
     */
    private function creerMembre(array $nomsDeRoles): \Livewire\Features\SupportTesting\Testable
    {
        $ids = array_map(fn (string $nom): int => $this->role($nom)->id, $nomsDeRoles);

        return Livewire::actingAs($this->patron)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', $ids)
            ->call('save');
    }

    /**
     * @param  list<string>  $permissionsSupplementaires
     */
    private function membreAvecRole(string $nomRole, array $permissionsSupplementaires): User
    {
        $user = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::User,
        ]);

        $role = $this->role($nomRole);

        foreach ($permissionsSupplementaires as $nom) {
            $role->permissions()->syncWithoutDetaching([
                Permission::where('name', $nom)->value('id') => ['portee' => 'compagnie'],
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->invaliderCachePermissions();

        return $user;
    }

    private function role(string $nom): Role
    {
        return Role::where('name', $nom)->whereNull('compagnie_id')->sole();
    }

    private function depense(User $auteur): Depense
    {
        return Depense::create([
            'compagnie_id' => $this->compagnie->id,
            'categorie_depense_id' => CategorieDepense::firstOrCreate([
                'nom' => 'Carburant',
                'compagnie_id' => $this->compagnie->id,
            ])->id,
            'libelle' => 'Gasoil',
            'montant' => 50000,
            'date_depense' => now()->toDateString(),
            'user_id' => $auteur->id,
        ]);
    }
}
