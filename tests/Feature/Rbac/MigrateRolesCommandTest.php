<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrateRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_dry_run_ne_modifie_rien(): void
    {
        $user = $this->compte(UserRole::Admin);

        $this->artisan('rbac:migrate-roles', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, $user->roles()->count());
    }

    public function test_sans_force_la_commande_demande_confirmation(): void
    {
        $user = $this->compte(UserRole::Admin);

        $this->artisan('rbac:migrate-roles')
            ->expectsConfirmation('Attribuer les rôles gabarits à tous les comptes ?', 'no')
            ->assertSuccessful();

        $this->assertSame(0, $user->roles()->count());
    }

    /**
     * @dataProvider correspondancesDepuisLeRoleSysteme
     */
    public function test_les_roles_systeme_sont_convertis(UserRole $role, ?bool $avecCompagnie, string $attendu): void
    {
        $user = $this->compte($role, $avecCompagnie);

        $this->lancer();

        $this->assertTrue(
            $user->fresh()->roles->pluck('name')->contains($attendu),
            "Le compte {$role->value} devrait porter le rôle {$attendu}."
        );
    }

    public static function correspondancesDepuisLeRoleSysteme(): array
    {
        return [
            'Super User → root' => [UserRole::Root, false, 'root'],
            'Admin → platform_admin' => [UserRole::Admin, false, 'platform_admin'],
            'User sans compagnie → client' => [UserRole::User, false, 'client'],
            'Companie Bosse → compagnie_dg' => [UserRole::CompagnieBosse, true, 'compagnie_dg'],
        ];
    }

    /**
     * @dataProvider correspondancesDepuisLePivot
     */
    public function test_les_roles_historiques_du_pivot_sont_convertis(string $ancien, string $attendu): void
    {
        $user = $this->compte(UserRole::User, true);
        $user->roles()->attach(Role::where('name', $ancien)->whereNull('compagnie_id')->value('id'));

        $this->lancer();

        $this->assertTrue(
            $user->fresh()->roles->pluck('name')->contains($attendu),
            "Le rôle historique {$ancien} devrait donner {$attendu}."
        );
    }

    public static function correspondancesDepuisLePivot(): array
    {
        return [
            'company_admin → compagnie_admin' => ['company_admin', 'compagnie_admin'],
            'agent → agent_embarquement' => ['agent', 'agent_embarquement'],
            'caisse → guichetier' => ['caisse', 'guichetier'],
            'comptabilite → comptable' => ['comptabilite', 'comptable'],
            'rh → rh' => ['rh', 'rh'],
            'bagagiste → bagagiste' => ['bagagiste', 'bagagiste'],
        ];
    }

    public function test_un_compte_de_compagnie_sans_role_est_signale(): void
    {
        $user = $this->compte(UserRole::User, true);

        $this->artisan('rbac:migrate-roles', ['--force' => true])
            ->expectsOutputToContain("Comptes de compagnie dont le métier n'a pu être déduit d'aucun rôle.")
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->roles->pluck('name')->contains('guichetier'));
    }

    public function test_un_compte_de_compagnie_avec_role_historique_n_est_pas_signale(): void
    {
        $user = $this->compte(UserRole::User, true);
        $user->roles()->attach(Role::where('name', 'comptabilite')->whereNull('compagnie_id')->value('id'));

        $this->artisan('rbac:migrate-roles', ['--force' => true])
            ->doesntExpectOutputToContain("Comptes de compagnie dont le métier n'a pu être déduit")
            ->assertSuccessful();
    }

    public function test_la_commande_est_idempotente(): void
    {
        $this->compte(UserRole::Admin);
        $this->compte(UserRole::User, true);
        $this->compte(UserRole::CompagnieBosse, true);

        $this->lancer();
        $apresPremier = DB::table('role_user')->count();

        $this->lancer();

        $this->assertSame($apresPremier, DB::table('role_user')->count());
    }

    public function test_les_anciens_roles_sont_conserves(): void
    {
        $user = $this->compte(UserRole::User, true);
        $ancienId = Role::where('name', 'caisse')->whereNull('compagnie_id')->value('id');
        $user->roles()->attach($ancienId);

        $this->lancer();

        $this->assertTrue(
            $user->fresh()->roles->pluck('id')->contains($ancienId),
            "L'ancien rôle doit rester attaché : il est la seule trace de l'état antérieur."
        );
    }

    public function test_le_pivot_porte_la_compagnie_pour_un_role_de_compagnie(): void
    {
        $user = $this->compte(UserRole::CompagnieBosse, true);

        $this->lancer();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => Role::where('name', 'compagnie_dg')->whereNull('compagnie_id')->value('id'),
            'compagnie_id' => $user->compagnie_id,
        ]);
    }

    public function test_le_pivot_ne_porte_pas_de_compagnie_pour_un_role_plateforme(): void
    {
        $user = $this->compte(UserRole::Admin);

        $this->lancer();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => Role::where('name', 'platform_admin')->whereNull('compagnie_id')->value('id'),
            'compagnie_id' => null,
        ]);
    }

    public function test_l_option_compagnie_restreint_le_traitement(): void
    {
        $cible = $this->compte(UserRole::User, true);
        $horsCible = $this->compte(UserRole::User, true);

        $this->artisan('rbac:migrate-roles', [
            '--force' => true,
            '--compagnie' => $cible->compagnie_id,
        ])->assertSuccessful();

        $this->assertSame(1, $cible->fresh()->roles()->count());
        $this->assertSame(0, $horsCible->fresh()->roles()->count());
    }

    public function test_la_commande_echoue_si_les_gabarits_sont_absents(): void
    {
        Role::whereIn('name', ['guichetier', 'compagnie_dg'])->delete();

        $this->artisan('rbac:migrate-roles', ['--force' => true])
            ->expectsOutputToContain('Rôles gabarits absents')
            ->assertFailed();
    }

    public function test_un_compte_deja_migre_n_apparait_pas_dans_le_plan(): void
    {
        $this->compte(UserRole::Admin);

        $this->lancer();

        $this->artisan('rbac:migrate-roles', ['--force' => true])
            ->expectsOutputToContain('portent déjà leurs rôles')
            ->assertSuccessful();
    }

    private function lancer(): void
    {
        $this->artisan('rbac:migrate-roles', ['--force' => true])->assertSuccessful();
    }

    private function compte(UserRole $role, bool $avecCompagnie = false): User
    {
        return User::factory()->create([
            'role' => $role,
            'compagnie_id' => $avecCompagnie ? Compagnie::factory()->create()->id : null,
        ]);
    }
}
