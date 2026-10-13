<?php

namespace App\Console\Commands;

use App\Enums\SexeUser;
use App\Enums\StatutUser;
use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\Role;
use App\Models\User;
use App\Rbac\RoleGabarits;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class CreerUserForAllRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:users-all-roles {--dry-run : Vérifie les prérequis et affiche les changements sans écrire}
                            {--force : Exécute sans demander de confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée un compte de démonstration actif et vérifié pour chaque rôle de chaque compagnie.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->verifierPrerequis()) {
            return self::FAILURE;
        }

        /** @var Collection<int, Role> $roles */
        $roles = $this->rolesDeCompagnie();
        $rolesManquants = array_values(array_diff(
            $this->nomsRolesDeCompagnie(),
            $roles->pluck('name')->all(),
        ));

        if ($rolesManquants !== []) {
            $this->error('Rôles gabarits absents : '.implode(', ', $rolesManquants).'.');
            $this->line('Exécutez d\'abord : php artisan db:seed --class=PermissionSeeder puis --class=RoleSeeder');

            return self::FAILURE;
        }

        /** @var Collection<int, Compagnie> $compagnies */
        $compagnies = Compagnie::query()->orderBy('id')->get(['id', 'name']);

        if ($compagnies->isEmpty()) {
            $this->warn('Aucune compagnie trouvée : aucun compte à créer.');

            return self::SUCCESS;
        }

        $comptes = $this->preparerComptes($compagnies, $roles);

        if (! $this->verifierConflitsEmail($comptes)) {
            return self::FAILURE;
        }

        $this->afficherPlan($comptes);

        if ($this->option('dry-run')) {
            $this->info('Simulation terminée : aucune donnée n\'a été modifiée.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Créer ou synchroniser ces comptes de démonstration ?', false)) {
            $this->warn('Abandon : aucune écriture.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($comptes): void {
                foreach ($comptes as $compte) {
                    /** @var Compagnie $compagnie */
                    $compagnie = $compte['compagnie'];
                    /** @var Role $role */
                    $role = $compte['role'];

                    $user = User::query()->firstOrNew(['email' => $compte['email']]);
                    $user->fill([
                        'first_name' => 'Compte',
                        'last_name' => $role->label ?? Str::headline($role->name),
                        'sexe' => SexeUser::Autre,
                        'password' => Hash::make('password'),
                        'role' => UserRole::User,
                        'compagnie_id' => $compagnie->id,
                    ]);
                    // Ces deux attributs ne sont volontairement pas exposés au mass
                    // assignment HTTP ; la commande système les fixe explicitement.
                    $user->statut = StatutUser::Active;
                    $user->email_verified_at = now();
                    $user->save();

                    // Le pivot garde la compagnie : le même rôle gabarit peut ainsi être
                    // utilisé dans plusieurs compagnies sans élargir sa portée.
                    $user->syncRoles([
                        $role->id => ['compagnie_id' => $compagnie->id],
                    ]);
                }
            });
        } catch (Throwable $e) {
            $this->error('Échec de la création des comptes : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info($comptes->count().' compte(s) créé(s) ou synchronisé(s).');
        $this->line('Ils sont actifs et vérifiés. Mot de passe commun : password');

        return self::SUCCESS;
    }

    private function verifierPrerequis(): bool
    {
        $tablesManquantes = array_values(array_filter(
            ['compagnies', 'users', 'roles', 'role_user'],
            fn (string $table): bool => ! Schema::hasTable($table),
        ));

        if ($tablesManquantes === []) {
            return true;
        }

        $this->error('Tables absentes : '.implode(', ', $tablesManquantes).'.');
        $this->line('Exécutez d\'abord : php artisan migrate --force');

        return false;
    }

    /** @return list<string> */
    private function nomsRolesDeCompagnie(): array
    {
        return collect(RoleGabarits::tous())
            ->where('scope', 'compagnie')
            ->pluck('name')
            ->all();
    }

    /** @return Collection<int, Role> */
    private function rolesDeCompagnie(): Collection
    {
        return Role::query()
            ->whereNull('compagnie_id')
            ->whereIn('name', $this->nomsRolesDeCompagnie())
            ->orderBy('rang', 'desc')
            ->get();
    }

    /**
     * @param Collection<int, Compagnie> $compagnies
     * @param Collection<int, Role> $roles
     * @return Collection<int, array{compagnie: Compagnie, role: Role, email: string}>
     */
    private function preparerComptes(Collection $compagnies, Collection $roles): Collection
    {
        return $compagnies->flatMap(function (Compagnie $compagnie) use ($roles): Collection {
            $domaine = $this->domaineDeCompagnie($compagnie);

            return $roles->map(fn (Role $role): array => [
                'compagnie' => $compagnie,
                'role' => $role,
                'email' => $this->prefixeRole($role->name).'@'.$domaine.'.com',
            ]);
        })->values();
    }

    private function domaineDeCompagnie(Compagnie $compagnie): string
    {
        $nom = Str::ascii(Str::lower($compagnie->name));
        // « Transport » fait partie de la raison sociale, pas de l'identifiant
        // court attendu dans les adresses de démonstration.
        $nom = preg_replace('/\btransport\b/', '', $nom);
        $domaine = preg_replace('/[^a-z0-9]/', '', $nom);

        return $domaine !== '' ? $domaine : 'compagnie'.$compagnie->id;
    }

    private function prefixeRole(string $role): string
    {
        return str_replace('_', '-', Str::after($role, 'compagnie_'));
    }

    /**
     * Évite qu'un compte existant d'une autre compagnie soit modifié par erreur.
     *
     * @param Collection<int, array{compagnie: Compagnie, role: Role, email: string}> $comptes
     */
    private function verifierConflitsEmail(Collection $comptes): bool
    {
        $doublons = $comptes->groupBy('email')->filter(fn (Collection $lignes): bool => $lignes->count() > 1);
        if ($doublons->isNotEmpty()) {
            $this->error('Des compagnies produisent les mêmes adresses : '.implode(', ', $doublons->keys()->all()).'.');

            return false;
        }

        $utilisateursParEmail = User::query()
            ->whereIn('email', $comptes->pluck('email'))
            ->pluck('compagnie_id', 'email');

        foreach ($comptes as $compte) {
            $compagnieExistante = $utilisateursParEmail[$compte['email']] ?? null;

            if ($compagnieExistante !== null && (int) $compagnieExistante !== $compte['compagnie']->id) {
                $this->error("L'adresse {$compte['email']} appartient déjà à une autre compagnie.");

                return false;
            }
        }

        return true;
    }

    /** @param Collection<int, array{compagnie: Compagnie, role: Role, email: string}> $comptes */
    private function afficherPlan(Collection $comptes): void
    {
        $this->line(sprintf(
            '%d compte(s) à traiter pour %d compagnie(s) et %d rôle(s) par compagnie.',
            $comptes->count(),
            $comptes->pluck('compagnie.id')->unique()->count(),
            $comptes->pluck('role.id')->unique()->count(),
        ));

        $this->table(
            ['Compagnie', 'Rôle', 'Email'],
            $comptes->map(fn (array $compte): array => [
                $compte['compagnie']->name,
                $compte['role']->label ?? $compte['role']->name,
                $compte['email'],
            ])->all(),
        );
    }
}
