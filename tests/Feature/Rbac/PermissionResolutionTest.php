<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PermissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_permissions_des_roles_sont_resolues(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'guichetier', ['guichet.ticket.sell' => 'compagnie']);

        $this->assertTrue($user->hasPermission('guichet.ticket.sell'));
        $this->assertSame('compagnie', $user->porteePour('guichet.ticket.sell'));
    }

    public function test_sans_role_ni_derogation_aucune_permission(): void
    {
        $user = $this->agent();
        $this->permission('guichet.ticket.sell');

        $this->assertFalse($user->hasPermission('guichet.ticket.sell'));
        $this->assertNull($user->porteePour('guichet.ticket.sell'));
        $this->assertSame([], $user->permissionsEffectives());
    }

    public function test_la_portee_la_plus_large_gagne_entre_deux_roles(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'guichetier', ['guichet.ticket.view.gare' => 'gare']);
        $this->donnerRole($user, 'chef_gare', ['guichet.ticket.view.gare' => 'compagnie']);

        $this->assertSame('compagnie', $user->porteePour('guichet.ticket.view.gare'));
    }

    public function test_une_derogation_accordee_sajoute_aux_roles(): void
    {
        $user = $this->agent();
        $permission = $this->permission('finance.bilan.view');

        $this->assertFalse($user->hasPermission('finance.bilan.view'));

        $user->permissionsDerogees()->attach($permission->id, ['accordee' => true]);
        $user->invaliderCachePermissions();

        $this->assertTrue($user->hasPermission('finance.bilan.view'));
    }

    public function test_un_retrait_explicite_prime_sur_le_role(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'comptable', ['finance.depense.approve' => 'compagnie']);

        $this->assertTrue($user->hasPermission('finance.depense.approve'));

        $user->permissionsDerogees()->attach(
            Permission::where('name', 'finance.depense.approve')->value('id'),
            ['accordee' => false]
        );
        $user->invaliderCachePermissions();

        $this->assertFalse(
            $user->hasPermission('finance.depense.approve'),
            'Un retrait explicite doit effacer une permission portée par un rôle.'
        );
    }

    public function test_une_derogation_expiree_est_ignoree(): void
    {
        $user = $this->agent();
        $permission = $this->permission('finance.bilan.view');

        $user->permissionsDerogees()->attach($permission->id, [
            'accordee' => true,
            'expire_at' => now()->subDay(),
        ]);
        $user->invaliderCachePermissions();

        $this->assertFalse($user->hasPermission('finance.bilan.view'));
    }

    public function test_un_retrait_expire_rend_la_permission_du_role(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'comptable', ['finance.bilan.view' => 'compagnie']);

        $user->permissionsDerogees()->attach(
            Permission::where('name', 'finance.bilan.view')->value('id'),
            ['accordee' => false, 'expire_at' => now()->subHour()]
        );
        $user->invaliderCachePermissions();

        $this->assertTrue($user->hasPermission('finance.bilan.view'));
    }

    public function test_une_derogation_prend_la_portee_naturelle_du_domaine(): void
    {
        $user = $this->agent();

        $compagnie = $this->permission('finance.bilan.view', 'finance', 'compagnie');
        $platform = $this->permission('platform.stats.view', 'platform', 'platform');
        $client = $this->permission('client.ticket.buy', 'client', 'client');

        $user->permissionsDerogees()->attach([
            $compagnie->id => ['accordee' => true],
            $platform->id => ['accordee' => true],
            $client->id => ['accordee' => true],
        ]);
        $user->invaliderCachePermissions();

        // Une dérogation ne doit jamais ouvrir un droit sur les autres compagnies :
        // la portée suit le domaine de la permission, pas la générosité du geste.
        $this->assertSame('compagnie', $user->porteePour('finance.bilan.view'));
        $this->assertSame('all', $user->porteePour('platform.stats.view'));
        $this->assertSame('own', $user->porteePour('client.ticket.buy'));
    }

    public function test_root_obtient_tout_sans_permission_en_base(): void
    {
        $root = User::factory()->create(['role' => UserRole::Root, 'compagnie_id' => null]);

        $this->assertSame(0, Permission::count());
        $this->assertTrue($root->isRoot());
        $this->assertTrue($root->hasPermission('finance.depense.approve'));
        $this->assertTrue($root->hasPermission('permission.totalement.inventee'));
        $this->assertSame('all', $root->porteePour('finance.bilan.view'));
    }

    public function test_le_cache_est_invalide_quand_un_role_change(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'guichetier', ['guichet.ticket.sell' => 'compagnie']);

        $this->assertTrue($user->hasPermission('guichet.ticket.sell'));

        $user->syncRoles([]);

        $this->assertFalse(
            $user->fresh()->hasPermission('guichet.ticket.sell'),
            "Le retrait d'un rôle doit être visible immédiatement, pas à l'expiration du cache."
        );
    }

    public function test_le_cache_evite_de_rejouer_la_resolution(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'guichetier', ['guichet.ticket.sell' => 'compagnie']);

        $user->permissionsEffectives();

        // Modification directe du pivot, sans invalidation : la valeur en cache doit
        // rester servie, ce qui prouve que la résolution n'est pas rejouée à chaque appel.
        $user->roles()->detach();

        $this->assertTrue($user->hasPermission('guichet.ticket.sell'));
    }

    public function test_la_portee_est_verifiee_contre_le_sujet(): void
    {
        $user = $this->agent();
        $this->donnerRole($user, 'chef_parc', ['reseau.vehicule.update' => 'compagnie']);

        $sien = new Care(['compagnie_id' => $user->compagnie_id]);
        $autre = new Care(['compagnie_id' => $user->compagnie_id + 1]);

        $this->assertTrue($user->hasPermission('reseau.vehicule.update', $sien));
        $this->assertFalse($user->hasPermission('reseau.vehicule.update', $autre));
    }

    public function test_chaque_permission_du_catalogue_devient_une_ability(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->rechargerLesGates();

        $user = $this->agent();
        $this->donnerRole($user, 'guichetier', ['guichet.ticket.sell' => 'compagnie']);

        $this->assertTrue(Gate::forUser($user)->allows('guichet.ticket.sell'));
        $this->assertFalse(Gate::forUser($user)->allows('finance.depense.approve'));
    }

    public function test_un_compte_root_passe_tous_les_gates(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->rechargerLesGates();

        $root = User::factory()->create(['role' => UserRole::Root, 'compagnie_id' => null]);

        $this->assertTrue(Gate::forUser($root)->allows('finance.depense.approve'));
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function agent(): User
    {
        return User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => Compagnie::factory()->create()->id,
        ]);
    }

    private function permission(string $nom, ?string $domaine = null, ?string $scope = null): Permission
    {
        return Permission::firstOrCreate(
            ['name' => $nom],
            [
                'domaine' => $domaine ?? explode('.', $nom)[0],
                'label' => $nom,
                'scope' => $scope ?? 'compagnie',
            ]
        );
    }

    /**
     * @param  array<string, string>  $permissions
     */
    private function donnerRole(User $user, string $nomRole, array $permissions): Role
    {
        $role = Role::firstOrCreate(
            ['name' => $nomRole, 'compagnie_id' => null],
            ['label' => $nomRole, 'scope' => 'compagnie', 'is_system' => true]
        );

        foreach ($permissions as $nom => $portee) {
            $role->permissions()->syncWithoutDetaching([
                $this->permission($nom)->id => ['portee' => $portee],
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->invaliderCachePermissions();

        return $role;
    }

    private function rechargerLesGates(): void
    {
        Cache::forget(AppServiceProvider::CLE_CACHE_PERMISSIONS);
        (new AppServiceProvider($this->app))->boot();
    }
}
