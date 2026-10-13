<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['rbac.enforce' => true]);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_le_tableau_de_bord_rh_ne_montre_que_ses_outils(): void
    {
        $response = $this->actingAs($this->membreCompagnie('rh'))
            ->get(route('panel.compagnie.dashboard'));

        $response->assertOk()
            ->assertSee('Bulletins de paie')
            ->assertSee('Absences et congés')
            ->assertDontSee('Vente de tickets')
            ->assertDontSee('Recettes tickets');
    }

    public function test_un_agent_embarquement_a_un_tableau_de_bord_adapte(): void
    {
        $this->actingAs($this->membreCompagnie('agent_embarquement'))
            ->get(route('panel.compagnie.dashboard'))
            ->assertOk()
            ->assertSee('Tickets')
            ->assertDontSee('Bulletins de paie');
    }

    public function test_le_support_plateforme_accede_au_tableau_de_bord_admin(): void
    {
        $support = User::factory()->create(['role' => UserRole::User, 'compagnie_id' => null]);
        $support->syncRoles([Role::where('name', 'platform_support')->whereNull('compagnie_id')->value('id')]);

        $this->actingAs($support)
            ->get(route('panel.admin.dashboard'))
            ->assertOk()
            ->assertSee('Compagnies')
            ->assertDontSee('Gérer les pays');
    }

    private function membreCompagnie(string $roleName): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => Compagnie::factory()->create()->id,
        ]);
        $user->syncRoles([
            Role::where('name', $roleName)->whereNull('compagnie_id')->value('id') => [
                'compagnie_id' => $user->compagnie_id,
            ],
        ]);

        return $user;
    }
}
