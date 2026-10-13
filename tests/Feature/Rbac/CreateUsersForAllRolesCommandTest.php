<?php

namespace Tests\Feature\Rbac;

use App\Enums\StatutUser;
use App\Models\Compagnie\Compagnie;
use App\Models\User;
use App\Rbac\RoleGabarits;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUsersForAllRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_cree_un_compte_actif_et_verifie_par_role_pour_chaque_compagnie(): void
    {
        $saramaya = Compagnie::factory()->create(['name' => 'Saramaya Transport']);
        $elitis = Compagnie::factory()->create(['name' => 'Elitis']);
        $rolesDeCompagnie = collect(RoleGabarits::tous())->where('scope', 'compagnie')->count();

        $this->artisan('user:users-all-roles', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame($rolesDeCompagnie * 2, User::count());

        $admin = User::where('email', 'admin@saramaya.com')->firstOrFail();
        $this->assertSame($saramaya->id, $admin->compagnie_id);
        $this->assertSame(StatutUser::Active, $admin->statut);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame(['compagnie_admin'], $admin->roles()->pluck('name')->all());
        $this->assertDatabaseHas('role_user', [
            'user_id' => $admin->id,
            'compagnie_id' => $saramaya->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'guichetier@elitis.com',
            'compagnie_id' => $elitis->id,
        ]);
    }

    public function test_dry_run_ne_cree_aucun_compte(): void
    {
        Compagnie::factory()->create(['name' => 'Saramaya']);

        $this->artisan('user:users-all-roles', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, User::count());
    }
}
