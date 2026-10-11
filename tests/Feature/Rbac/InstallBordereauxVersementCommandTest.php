<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallBordereauxVersementCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS_BORDEREAU = [
        'caisse.bordereau.emit',
        'caisse.bordereau.view',
        'caisse.bordereau.export',
        'caisse.bordereau.receive',
        'caisse.bordereau.transfer',
        'caisse.bordereau.account',
    ];

    public function test_la_commande_installe_le_role_dsf_et_ses_permissions(): void
    {
        $this->artisan('finance:install-bordereaux', ['--force' => true])
            ->expectsOutputToContain('Installation terminée')
            ->assertSuccessful();

        $role = Role::query()
            ->where('name', 'dsf_finance')
            ->whereNull('compagnie_id')
            ->firstOrFail();

        $this->assertSame('Direction financière (DSF)', $role->label);
        $this->assertEqualsCanonicalizing(
            [
                'caisse.bordereau.export',
                'caisse.bordereau.receive',
                'caisse.bordereau.transfer',
                'caisse.bordereau.view',
            ],
            $role->permissions()->whereIn('name', self::PERMISSIONS_BORDEREAU)->pluck('name')->all()
        );

        $this->assertSame(6, Permission::query()->whereIn('name', self::PERMISSIONS_BORDEREAU)->count());
    }

    public function test_le_mode_simulation_n_ecrit_rien(): void
    {
        $permissionsAvant = Permission::count();
        $rolesAvant = Role::count();

        $this->artisan('finance:install-bordereaux', ['--dry-run' => true])
            ->expectsOutputToContain('Simulation terminée')
            ->assertSuccessful();

        $this->assertSame($permissionsAvant, Permission::count());
        $this->assertSame($rolesAvant, Role::count());
    }
}
