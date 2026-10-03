<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle d'accès des routes des deux panneaux.
 *
 * Le test porte sur l'autorisation, pas sur le rendu : un écran peut échouer faute de
 * données sans que cela dise quoi que ce soit sur les droits. On vérifie donc qu'un compte
 * sans la permission est refusé, et qu'un compte qui l'a n'est pas refusé.
 */
class RouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Routes du panneau compagnie sans paramètre, avec la permission qui les garde.
     *
     * `caisse.detail`, `voyages.*` et les autres routes paramétrées sont exclues : leur
     * résolution de modèle échouerait avant le contrôle d'autorisation, et le test
     * mesurerait alors la présence de données, pas les droits.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function routesCompagnie(): array
    {
        return [
            'dashboard' => ['panel.compagnie.dashboard', 'compagnie.dashboard.view'],
            'trajets' => ['panel.compagnie.trajets', 'voyage.trajet.view'],
            'voyages' => ['panel.compagnie.voyages', 'voyage.voyage.view'],
            'voyages.create' => ['panel.compagnie.voyages.create', 'voyage.voyage.create'],
            'classes' => ['panel.compagnie.classes', 'voyage.classe.manage'],
            'instances' => ['panel.compagnie.instances', 'voyage.instance.view'],
            'vente-ticket' => ['panel.compagnie.vente-ticket', 'guichet.ticket.sell'],
            'tickets' => ['panel.compagnie.tickets', 'guichet.ticket.view.gare'],
            'messages' => ['panel.compagnie.messages', 'crm.conversation.view'],
            'caisse' => ['panel.compagnie.caisse', 'caisse.session.view.own'],
            'caisses-historique' => ['panel.compagnie.caisses-historique', 'caisse.historique.view'],
            'gares' => ['panel.compagnie.gares', 'reseau.gare.view'],
            'cares' => ['panel.compagnie.cares', 'reseau.vehicule.view'],
            'chauffeurs' => ['panel.compagnie.chauffeurs', 'reseau.chauffeur.view'],
            'users' => ['panel.compagnie.users', 'compagnie.user.view'],
            'posts' => ['panel.compagnie.posts', 'contenu.article.view'],
            'posts.create' => ['panel.compagnie.posts.create', 'contenu.article.create'],
            'documents' => ['panel.compagnie.documents', 'reseau.document.view'],
            'rapports' => ['panel.compagnie.rapports', 'finance.rapport.view'],
            'bilan' => ['panel.compagnie.bilan', 'finance.bilan.view'],
            'depenses' => ['panel.compagnie.depenses', 'finance.depense.view'],
            'recettes' => ['panel.compagnie.recettes', 'finance.recette.view'],
            'categories' => ['panel.compagnie.categories', 'finance.categorie.manage'],
            'promos' => ['panel.compagnie.promos', 'finance.promo.view'],
            'parametres' => ['panel.compagnie.parametres', 'compagnie.parametres.view'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function routesAdmin(): array
    {
        return [
            'dashboard' => ['panel.admin.dashboard', 'platform.stats.view'],
            'pays' => ['panel.admin.pays', 'platform.pays.manage'],
            'regions' => ['panel.admin.regions', 'platform.region.manage'],
            'villes' => ['panel.admin.villes', 'platform.ville.manage'],
            'compagnies' => ['panel.admin.compagnies', 'platform.compagnie.view'],
            'settings' => ['panel.admin.settings', 'platform.settings.manage'],
        ];
    }

    /**
     * @dataProvider routesCompagnie
     */
    public function test_une_route_compagnie_est_refusee_sans_sa_permission(string $route, string $permission): void
    {
        config(['rbac.enforce' => true]);

        $this->actingAs($this->membreCompagnie())
            ->get(route($route))
            ->assertForbidden();
    }

    /**
     * @dataProvider routesCompagnie
     */
    public function test_une_route_compagnie_passe_avec_sa_permission(string $route, string $permission): void
    {
        config(['rbac.enforce' => true]);

        $this->actingAs($this->membreCompagnie($permission))
            ->get(route($route))
            ->assertStatus(200);
    }

    /**
     * @dataProvider routesAdmin
     */
    public function test_une_route_admin_est_refusee_sans_sa_permission(string $route, string $permission): void
    {
        config(['rbac.enforce' => true]);

        $this->actingAs($this->administrateurPlateforme())
            ->get(route($route))
            ->assertForbidden();
    }

    /**
     * @dataProvider routesAdmin
     */
    public function test_une_route_admin_passe_avec_sa_permission(string $route, string $permission): void
    {
        config(['rbac.enforce' => true]);

        $this->actingAs($this->administrateurPlateforme($permission))
            ->get(route($route))
            ->assertStatus(200);
    }

    public function test_en_mode_observation_toutes_les_routes_passent(): void
    {
        config(['rbac.enforce' => false]);
        $membre = $this->membreCompagnie();

        foreach (self::routesCompagnie() as [$route, $permission]) {
            $this->actingAs($membre)
                ->get(route($route))
                ->assertStatus(200, "La route {$route} devrait passer en mode observation.");
        }
    }

    public function test_un_compte_root_passe_partout(): void
    {
        config(['rbac.enforce' => true]);
        $root = User::factory()->create(['role' => UserRole::Root, 'compagnie_id' => null]);

        foreach (self::routesAdmin() as [$route, $permission]) {
            $this->actingAs($root)->get(route($route))->assertStatus(200);
        }
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function membreCompagnie(?string $permission = null): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => Compagnie::factory()->create()->id,
        ]);

        return $this->avecPermission($user, $permission, 'compagnie');
    }

    private function administrateurPlateforme(?string $permission = null): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'compagnie_id' => null,
        ]);

        return $this->avecPermission($user, $permission, 'all');
    }

    private function avecPermission(User $user, ?string $permission, string $portee): User
    {
        if ($permission === null) {
            return $user;
        }

        $role = Role::firstOrCreate(
            ['name' => 'role_de_test_'.md5($permission), 'compagnie_id' => null],
            ['label' => 'Rôle de test', 'scope' => 'compagnie', 'is_system' => false]
        );

        $role->permissions()->syncWithoutDetaching([
            Permission::firstOrCreate(
                ['name' => $permission],
                [
                    'domaine' => explode('.', $permission)[0],
                    'label' => $permission,
                    'scope' => str_starts_with($permission, 'platform.') ? 'platform' : 'compagnie',
                ]
            )->id => ['portee' => $portee],
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->invaliderCachePermissions();

        return $user;
    }
}
