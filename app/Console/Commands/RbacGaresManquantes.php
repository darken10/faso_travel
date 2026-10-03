<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Liste les comptes de terrain qui n'ont aucune gare d'affectation.
 *
 * Le cloisonnement par gare n'est strict que pour les comptes qui portent au moins une
 * affectation : un compte de terrain sans gare travaille donc encore sur toute la
 * compagnie. C'est le trou à boucher avant de passer `RBAC_ENFORCE` à `true`, et cette
 * commande est le contrôle qui le rend visible.
 */
class RbacGaresManquantes extends Command
{
    protected $signature = 'rbac:gares-manquantes
                            {--compagnie= : Limite le contrôle à une compagnie}';

    protected $description = 'Liste les comptes de terrain sans aucune gare d\'affectation.';

    /** Rôles dont le périmètre de travail est une gare. */
    private const ROLES_DE_TERRAIN = [
        'chef_gare',
        'guichetier',
        'agent_embarquement',
        'bagagiste',
    ];

    public function handle(): int
    {
        $requete = User::query()
            ->whereNotNull('compagnie_id')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ROLES_DE_TERRAIN))
            ->whereDoesntHave('gares')
            ->with(['compagnie:id,name', 'roles:id,name']);

        if ($compagnieId = $this->option('compagnie')) {
            $requete->where('compagnie_id', $compagnieId);
        }

        $comptes = $requete->get();

        if ($comptes->isEmpty()) {
            $this->info('Tous les comptes de terrain portent au moins une gare.');

            return self::SUCCESS;
        }

        $this->warn($comptes->count().' compte(s) de terrain sans gare d\'affectation.');
        $this->line('Ces comptes travaillent encore sur toute la compagnie : les affecter');
        $this->line('avant de passer RBAC_ENFORCE à true.');
        $this->newLine();

        $this->table(
            ['Compte', 'Identifiant', 'Compagnie', 'Rôles de terrain'],
            $comptes->map(fn (User $user): array => [
                (string) $user->getKey(),
                $user->email ?? ($user->numero_identifiant.$user->numero),
                $user->compagnie?->name ?? '—',
                $this->rolesDeTerrain($user),
            ])->all()
        );

        return self::FAILURE;
    }

    private function rolesDeTerrain(User $user): string
    {
        return $user->roles
            ->pluck('name')
            ->intersect(self::ROLES_DE_TERRAIN)
            ->implode(', ');
    }
}
