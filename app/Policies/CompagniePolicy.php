<?php

namespace App\Policies;

use App\Models\Compagnie\Compagnie;
use App\Models\User;

/**
 * Autorisations d'une compagnie de transport.
 *
 * Deux niveaux se superposent : la plateforme, qui crée, active et suspend les compagnies,
 * et la compagnie elle-même, qui administre son propre profil.
 *
 * La version précédente référençait `CompanyRole::Directeur` et `$user->company_role`,
 * qui n'existent ni l'un ni l'autre : toute évaluation de `manageFinance()` levait une
 * erreur fatale.
 */
class CompagniePolicy
{
    /** La liste des compagnies est publique : c'est l'offre de transport. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Compagnie $compagnie): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('platform.compagnie.create');
    }

    public function update(User $user, Compagnie $compagnie): bool
    {
        return $user->hasPermission('platform.compagnie.update', $compagnie)
            || $user->hasPermission('compagnie.profil.update', $compagnie);
    }

    public function delete(User $user, Compagnie $compagnie): bool
    {
        return $user->hasPermission('platform.compagnie.delete', $compagnie);
    }

    public function activate(User $user, Compagnie $compagnie): bool
    {
        return $user->hasPermission('platform.compagnie.activate', $compagnie);
    }

    public function suspend(User $user, Compagnie $compagnie): bool
    {
        return $user->hasPermission('platform.compagnie.suspend', $compagnie);
    }

    public function manageUsers(User $user, Compagnie $compagnie): bool
    {
        return $user->hasPermission('compagnie.user.update', $compagnie)
            || $user->hasPermission('platform.user.update', $compagnie);
    }

    public function manageFinance(User $user, Compagnie $compagnie): bool
    {
        return $user->hasPermission('finance.bilan.view', $compagnie);
    }
}
