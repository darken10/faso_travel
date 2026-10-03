<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Fait porter aux comptes existants les rôles gabarits, d'après leurs rôles historiques.
 *
 * La commande n'enlève jamais un ancien rôle : le nettoyage de `user`, `admin` et des six
 * valeurs de `CompanyRole` est une décision distincte, à prendre après validation en
 * production. Tant qu'ils restent attachés, l'état antérieur est reconstituable.
 */
class RbacMigrateRoles extends Command
{
    protected $signature = 'rbac:migrate-roles
                            {--dry-run : Affiche le plan sans rien écrire}
                            {--compagnie= : Limite le traitement à une compagnie}
                            {--force : N\'attend pas de confirmation}';

    protected $description = 'Attribue les rôles gabarits aux comptes existants, d\'après leurs rôles historiques.';

    /** Déduction depuis la colonne `users.role`. */
    private const DEPUIS_ROLE_SYSTEME = [
        'Super User' => 'root',
        'Admin' => 'platform_admin',
        'Companie Bosse' => 'compagnie_dg',
    ];

    /** Déduction depuis les rôles historiques du pivot `role_user`. */
    private const DEPUIS_PIVOT = [
        'company_admin' => 'compagnie_admin',
        'agent' => 'agent_embarquement',
        'caisse' => 'guichetier',
        'comptabilite' => 'comptable',
        'rh' => 'rh',
        'bagagiste' => 'bagagiste',
    ];

    /**
     * Rôle attribué à un compte de compagnie dont aucun rôle ne permet de deviner le métier.
     *
     * Ces comptes accèdent aujourd'hui à la totalité du panneau sans porter le moindre
     * rôle : leur donner le rôle le plus courant est le choix le moins perturbant, mais il
     * demande un arbitrage humain, d'où le tableau nominatif en fin d'exécution.
     */
    private const ROLE_PAR_DEFAUT_COMPAGNIE = 'guichetier';

    private const TAILLE_LOT = 500;

    /** @var array<string, int> identifiants des rôles gabarits, par nom */
    private array $gabarits = [];

    /** @var array<string, string> portée des rôles gabarits, par nom */
    private array $scopes = [];

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $plan = [];

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $aArbitrer = [];

    private int $comptesTraites = 0;

    private int $attributions = 0;

    public function handle(): int
    {
        // Artisan met l'instance de commande en cache : sans cette remise à zéro, deux
        // appels dans le même processus cumuleraient leurs plans et la seconde exécution
        // rejouerait le rapport de la première.
        $this->plan = [];
        $this->aArbitrer = [];
        $this->comptesTraites = 0;
        $this->attributions = 0;

        $simulation = (bool) $this->option('dry-run');

        if (! $this->chargerGabarits()) {
            return self::FAILURE;
        }

        if (! $simulation && ! $this->confirmerEcriture()) {
            $this->warn('Abandon : aucune écriture.');

            return self::SUCCESS;
        }

        try {
            $this->parcourirLesComptes($simulation);
        } catch (Throwable $e) {
            $this->error('Échec : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->restituer($simulation);

        return self::SUCCESS;
    }

    /**
     * Les rôles gabarits doivent exister avant toute attribution.
     */
    private function chargerGabarits(): bool
    {
        $roles = Role::query()->whereNull('compagnie_id')->get(['id', 'name', 'scope']);

        $this->gabarits = $roles->pluck('id', 'name')->all();
        $this->scopes = $roles->pluck('scope', 'name')->all();

        $attendus = array_unique(array_merge(
            array_values(self::DEPUIS_ROLE_SYSTEME),
            array_values(self::DEPUIS_PIVOT),
            [self::ROLE_PAR_DEFAUT_COMPAGNIE, 'client'],
        ));

        $manquants = array_values(array_diff($attendus, array_keys($this->gabarits)));

        if ($manquants !== []) {
            $this->error('Rôles gabarits absents : '.implode(', ', $manquants));
            $this->line('Exécuter d\'abord : php artisan db:seed --class=PermissionSeeder puis --class=RoleSeeder');

            return false;
        }

        return true;
    }

    private function confirmerEcriture(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $cible = $this->option('compagnie')
            ? 'la compagnie '.$this->option('compagnie')
            : 'tous les comptes';

        return $this->confirm("Attribuer les rôles gabarits à {$cible} ?", false);
    }

    private function parcourirLesComptes(bool $simulation): void
    {
        $requete = User::query()->with('roles:id,name,scope');

        if ($compagnieId = $this->option('compagnie')) {
            $requete->where('compagnie_id', $compagnieId);
        }

        $requete->chunkById(self::TAILLE_LOT, function (Collection $comptes) use ($simulation): void {
            if ($simulation) {
                $comptes->each(fn (User $user) => $this->traiter($user, true));

                return;
            }

            // Une transaction par lot : un échec sur un compte ne laisse pas la moitié du
            // lot attribuée, et un lot déjà écrit n'est pas rejoué puisque l'attribution
            // est idempotente.
            DB::transaction(fn () => $comptes->each(fn (User $user) => $this->traiter($user, false)));
        });
    }

    private function traiter(User $user, bool $simulation): void
    {
        $this->comptesTraites++;

        $rolesActuels = $user->roles->pluck('name')->all();
        $aAjouter = $this->rolesCibles($user, $rolesActuels);

        $nouveaux = array_values(array_diff($aAjouter, $rolesActuels));

        if ($nouveaux === []) {
            return;
        }

        $this->plan[] = [
            (string) $user->getKey(),
            $user->email ?? ($user->numero_identifiant.$user->numero),
            implode(', ', $nouveaux),
        ];

        if ($this->doitEtreArbitre($user, $rolesActuels)) {
            $this->aArbitrer[] = [
                (string) $user->getKey(),
                $user->email ?? ($user->numero_identifiant.$user->numero),
                (string) $user->compagnie_id,
            ];
        }

        $this->attributions += count($nouveaux);

        if ($simulation) {
            return;
        }

        $this->attacher($user, $nouveaux);
    }

    /**
     * @param  list<string>  $rolesActuels
     * @return list<string>
     */
    private function rolesCibles(User $user, array $rolesActuels): array
    {
        $cibles = [];

        $valeurRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (isset(self::DEPUIS_ROLE_SYSTEME[$valeurRole])) {
            $cibles[] = self::DEPUIS_ROLE_SYSTEME[$valeurRole];
        }

        if ($valeurRole === UserRole::User->value && $user->compagnie_id === null) {
            $cibles[] = 'client';
        }

        foreach ($rolesActuels as $nom) {
            if (isset(self::DEPUIS_PIVOT[$nom])) {
                $cibles[] = self::DEPUIS_PIVOT[$nom];
            }
        }

        if ($cibles === [] && $user->compagnie_id !== null) {
            $cibles[] = self::ROLE_PAR_DEFAUT_COMPAGNIE;
        }

        return array_values(array_unique($cibles));
    }

    /**
     * Un compte de compagnie dont le métier n'a pu être déduit d'aucun rôle.
     *
     * @param  list<string>  $rolesActuels
     */
    private function doitEtreArbitre(User $user, array $rolesActuels): bool
    {
        if ($user->compagnie_id === null) {
            return false;
        }

        $valeurRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (isset(self::DEPUIS_ROLE_SYSTEME[$valeurRole])) {
            return false;
        }

        return array_intersect($rolesActuels, array_keys(self::DEPUIS_PIVOT)) === [];
    }

    /**
     * @param  list<string>  $noms
     */
    private function attacher(User $user, array $noms): void
    {
        $aAttacher = [];

        foreach ($noms as $nom) {
            $aAttacher[$this->gabarits[$nom]] = [
                'compagnie_id' => $this->scopes[$nom] === 'compagnie' ? $user->compagnie_id : null,
            ];
        }

        $user->roles()->syncWithoutDetaching($aAttacher);
        $user->invaliderCachePermissions();
    }

    private function restituer(bool $simulation): void
    {
        $this->newLine();

        if ($this->plan === []) {
            $this->info("Rien à faire : les {$this->comptesTraites} comptes examinés portent déjà leurs rôles.");

            return;
        }

        $this->line($simulation ? '<comment>SIMULATION — aucune écriture</comment>' : '<info>Attributions appliquées</info>');
        $this->table(['Compte', 'Identifiant', 'Rôles ajoutés'], $this->plan);

        $this->line(sprintf(
            '%d compte(s) examiné(s), %d attribution(s) sur %d compte(s).',
            $this->comptesTraites,
            $this->attributions,
            count($this->plan),
        ));

        if ($this->aArbitrer === []) {
            return;
        }

        $this->newLine();
        $this->warn('Comptes de compagnie dont le métier n\'a pu être déduit d\'aucun rôle.');
        $this->line(sprintf(
            'Le rôle « %s » leur a été attribué par défaut : à confirmer un par un.',
            self::ROLE_PAR_DEFAUT_COMPAGNIE
        ));
        $this->table(['Compte', 'Identifiant', 'Compagnie'], $this->aArbitrer);
        $this->line('<comment>Conserver ce tableau : une seconde exécution ne le reproduira pas,</comment>');
        $this->line('<comment>ces comptes portant désormais un rôle.</comment>');
    }
}
