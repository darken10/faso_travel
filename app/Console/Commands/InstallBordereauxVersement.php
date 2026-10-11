<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Installe de façon idempotente les droits du circuit de remise de caisse.
 *
 * La migration reste volontairement une étape séparée : une commande qui modifie les
 * données RBAC ne doit pas déclencher une migration non inspectée en production.
 */
class InstallBordereauxVersement extends Command
{
    protected $signature = 'finance:install-bordereaux
                            {--dry-run : Vérifie les prérequis et affiche les changements sans écrire}
                            {--force : Exécute sans demander de confirmation}';

    protected $description = 'Installe le rôle DSF et les permissions du circuit de bordereaux de versement.';

    /** @var list<string> */
    private const PERMISSIONS_BORDEREAU = [
        'caisse.bordereau.emit',
        'caisse.bordereau.view',
        'caisse.bordereau.export',
        'caisse.bordereau.receive',
        'caisse.bordereau.transfer',
        'caisse.bordereau.account',
    ];

    /** @var list<string> */
    private const TABLES_REQUISES = [
        'bordereaux_versement',
        'permissions',
        'roles',
        'permission_role',
    ];

    public function handle(): int
    {
        if (! $this->verifierPrerequis()) {
            return self::FAILURE;
        }

        $permissionsManquantes = Permission::query()
            ->whereIn('name', self::PERMISSIONS_BORDEREAU)
            ->pluck('name')
            ->all();
        $permissionsManquantes = array_values(array_diff(self::PERMISSIONS_BORDEREAU, $permissionsManquantes));

        $roleDsfExiste = Role::query()
            ->where('name', 'dsf_finance')
            ->whereNull('compagnie_id')
            ->exists();

        $this->afficherPlan($permissionsManquantes, $roleDsfExiste);

        if ($this->option('dry-run')) {
            $this->info('Simulation terminée : aucune donnée n\'a été modifiée.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Synchroniser les permissions et rôles gabarits RBAC ?', false)) {
            $this->warn('Abandon : aucune écriture.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function (): void {
                app(PermissionSeeder::class)->run();
                app(RoleSeeder::class)->run();
            });
        } catch (Throwable $e) {
            $this->error('Échec de l\'installation : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Installation terminée : rôle « Direction financière (DSF) » et permissions synchronisés.');
        $this->line('Aucun utilisateur n\'a reçu ce rôle automatiquement. Affectez-le depuis la gestion des rôles.');

        return self::SUCCESS;
    }

    private function verifierPrerequis(): bool
    {
        $tablesManquantes = array_values(array_filter(
            self::TABLES_REQUISES,
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($tablesManquantes === []) {
            return true;
        }

        $this->error('Tables absentes : '.implode(', ', $tablesManquantes).'.');
        $this->line('Exécutez d\'abord : php artisan migrate --force');

        return false;
    }

    /**
     * @param  list<string>  $permissionsManquantes
     */
    private function afficherPlan(array $permissionsManquantes, bool $roleDsfExiste): void
    {
        if ($permissionsManquantes === []) {
            $this->line('Les 6 permissions de bordereau existent déjà.');
        } else {
            $this->line(count($permissionsManquantes).' permission(s) de bordereau à créer : '.implode(', ', $permissionsManquantes));
        }

        $this->line($roleDsfExiste
            ? 'Le rôle gabarit DSF sera resynchronisé.'
            : 'Le rôle gabarit « Direction financière (DSF) » sera créé.');
    }
}
