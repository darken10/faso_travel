<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_menu_ne_montre_que_les_ecrans_autorises(): void
    {
        config(['rbac.enforce' => true]);

        $comptable = $this->membre(['finance.depense.view', 'finance.recette.view']);

        $this->actingAs($comptable)
            ->get(route('panel.compagnie.depenses'))
            ->assertOk()
            ->assertSee('Dépenses')
            ->assertSee('Recettes')
            ->assertDontSee('Vente de ticket')
            ->assertDontSee('Chauffeurs');
    }

    public function test_une_section_sans_entree_visible_disparait(): void
    {
        config(['rbac.enforce' => true]);

        $comptable = $this->membre(['finance.depense.view']);

        $this->actingAs($comptable)
            ->get(route('panel.compagnie.depenses'))
            ->assertOk()
            // Aucune entrée de ces sections n'est autorisée : leur titre ne doit pas
            // rester affiché comme un intertitre orphelin.
            ->assertDontSee('Ressources')
            ->assertDontSee('Guichet')
            ->assertSee('Comptabilité');
    }

    public function test_en_mode_observation_le_menu_reste_complet(): void
    {
        config(['rbac.enforce' => false]);

        $membre = $this->membre([]);

        // Masquer le menu pendant l'observation priverait les métiers des écrans dont on
        // cherche justement à mesurer l'usage.
        $this->actingAs($membre)
            ->get(route('panel.compagnie.depenses'))
            ->assertOk()
            ->assertSee('Vente de ticket')
            ->assertSee('Chauffeurs')
            ->assertSee('Ressources');
    }

    public function test_l_entree_conflits_reste_masquee_meme_en_observation(): void
    {
        config(['rbac.enforce' => false]);

        // L'écran des conflits est protégé en dur par son composant : afficher son lien
        // renverrait vers un 403.
        $this->actingAs($this->membre([]))
            ->get(route('panel.compagnie.depenses'))
            ->assertOk()
            ->assertDontSee('/conflits', false);
    }

    public function test_le_menu_admin_est_filtre_aussi(): void
    {
        config(['rbac.enforce' => true]);

        $admin = User::factory()->create(['role' => UserRole::Admin, 'compagnie_id' => null]);
        $this->donnerPermission($admin, 'platform.region.manage', 'all');
        $this->donnerPermission($admin, 'platform.stats.view', 'all');

        $this->actingAs($admin)
            ->get(route('panel.admin.dashboard'))
            ->assertOk()
            // On cible les liens du menu, et on évite les écrans que le tableau de bord
            // met aussi en raccourci dans son corps : ses cartes ne sont pas filtrées sur
            // les permissions, et renvoient donc encore vers des écrans interdits.
            ->assertSee('/regions', false)
            ->assertDontSee('/villes', false);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function membre(array $permissions): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => Compagnie::factory()->create()->id,
        ]);

        foreach ($permissions as $nom) {
            $this->donnerPermission($user, $nom, 'compagnie');
        }

        return $user;
    }

    private function donnerPermission(User $user, string $nom, string $portee): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'nav_'.md5($nom), 'compagnie_id' => null],
            ['label' => 'Rôle de test', 'scope' => 'compagnie', 'is_system' => false]
        );

        $role->permissions()->syncWithoutDetaching([
            Permission::firstOrCreate(
                ['name' => $nom],
                [
                    'domaine' => explode('.', $nom)[0],
                    'label' => $nom,
                    'scope' => str_starts_with($nom, 'platform.') ? 'platform' : 'compagnie',
                ]
            )->id => ['portee' => $portee],
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->invaliderCachePermissions();
    }
}
