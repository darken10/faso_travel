<?php

namespace App\Policies;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\User;

/**
 * Autorisations du paramétrage compagnie.
 *
 * Un administrateur de la plateforme configure n'importe quelle compagnie ;
 * une compagnie ne configure qu'elle-même, et jamais les paramètres avancés.
 *
 * Les permissions du catalogue sont ajoutées *en plus* de la logique de rôles existante,
 * jamais à sa place : cette policy est la seule du dossier réellement appelée en
 * production, et la remplacer d'un coup couperait le paramétrage aux comptes qui n'ont pas
 * encore reçu leurs permissions.
 */
class CompagnieSettingPolicy
{
    /** Accès à l'écran de paramétrage. */
    public function viewAny(User $user): bool
    {
        return $this->isPlatformAdmin($user) || $user->compagnie_id !== null;
    }

    /** Consultation des paramètres d'une compagnie donnée. */
    public function view(User $user, Compagnie $compagnie): bool
    {
        return $this->isPlatformAdmin($user)
            || $this->belongsTo($user, $compagnie)
            || $user->hasPermission('compagnie.parametres.view', $compagnie);
    }

    /** Modification des paramètres courants d'une compagnie. */
    public function update(User $user, Compagnie $compagnie): bool
    {
        return $this->isPlatformAdmin($user)
            || ($this->belongsTo($user, $compagnie) && $this->manageCompagnie($user, $compagnie))
            || $user->hasPermission('compagnie.parametres.update', $compagnie);
    }

    /** Modification des paramètres avancés (commission, maintenance, plafonds). */
    public function updateAdvanced(User $user): bool
    {
        return $this->isPlatformAdmin($user)
            || $user->hasPermission('compagnie.parametres.updateAdvanced');
    }

    /** Remise à zéro d'un groupe ou de l'ensemble du paramétrage. */
    public function reset(User $user, Compagnie $compagnie): bool
    {
        return $this->update($user, $compagnie)
            || $user->hasPermission('compagnie.parametres.reset', $compagnie);
    }

    private function isPlatformAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Root], true);
    }

    private function belongsTo(User $user, Compagnie $compagnie): bool
    {
        return $user->compagnie_id !== null && (int) $user->compagnie_id === (int) $compagnie->id;
    }

    /** Le membre a-t-il la main sur la configuration de sa compagnie ? */
    private function manageCompagnie(User $user, Compagnie $compagnie): bool
    {
        return $user->role === UserRole::CompagnieBosse
            || (int) $compagnie->user_id === (int) $user->id
            || $user->hasAnyRole([CompanyRole::Admin->value]);
    }
}
