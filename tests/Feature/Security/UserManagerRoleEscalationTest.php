<?php

namespace Tests\Feature\Security;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Livewire\Compagnie\Compagnie\UserManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerRoleEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_cannot_assign_platform_roles(): void
    {
        $compagnie = Compagnie::factory()->create();
        $admin = User::factory()->create([
            'compagnie_id' => $compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);
        $root = Role::firstOrCreate(['name' => 'root'], ['label' => 'root']);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$root->id])
            ->call('save')
            ->assertHasErrors(['selectedRoles.0']);

        $this->assertDatabaseCount('role_user', 0);
    }

    public function test_role_list_excludes_platform_roles(): void
    {
        $compagnie = Compagnie::factory()->create();
        $admin = User::factory()->create([
            'compagnie_id' => $compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);
        Role::firstOrCreate(['name' => 'admin'], ['label' => 'admin']);
        Role::firstOrCreate(['name' => 'root'], ['label' => 'root']);
        Role::firstOrCreate(
            ['name' => CompanyRole::Agent->value],
            ['label' => CompanyRole::Agent->label()],
        );

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openCreate')
            ->assertDontSee('admin')
            ->assertDontSee('root')
            ->assertSee(CompanyRole::Agent->label());
    }
}
