<?php

namespace App\Policies;

use App\Models\Compagnie\Care;
use App\Models\User;

/**
 * Autorisations du parc roulant.
 *
 * Cette policy était un squelette généré qui renvoyait `true` sur toutes ses abilities,
 * et elle n'était même pas enregistrée : la découverte automatique cherche
 * `App\Policies\Compagnie\CarePolicy` pour un modèle rangé sous `App\Models\Compagnie`.
 * Un `can()` écrit un jour sur un véhicule aurait donc tout autorisé en silence.
 */
class CarePolicy
{
    /** Le filtrage des lignes relève du scope de compagnie, pas de l'autorisation. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Care $care): bool
    {
        return $user->hasPermission('reseau.vehicule.view', $care);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reseau.vehicule.create');
    }

    public function update(User $user, Care $care): bool
    {
        return $user->hasPermission('reseau.vehicule.update', $care);
    }

    public function delete(User $user, Care $care): bool
    {
        return $user->hasPermission('reseau.vehicule.delete', $care);
    }

    public function restore(User $user, Care $care): bool
    {
        return $user->hasPermission('reseau.vehicule.update', $care);
    }

    public function forceDelete(User $user, Care $care): bool
    {
        return $user->hasPermission('reseau.vehicule.delete', $care);
    }

    public function setStatut(User $user, Care $care): bool
    {
        return $user->hasPermission('reseau.vehicule.setStatut', $care);
    }
}
