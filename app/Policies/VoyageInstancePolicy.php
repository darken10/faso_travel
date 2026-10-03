<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voyage\VoyageInstance;

/**
 * Autorisations des départs programmés.
 *
 * Comme pour l'offre, la consultation reste ouverte : un voyageur doit pouvoir voir les
 * départs disponibles. La portée d'une permission remonte la compagnie d'un départ par
 * son voyage, le modèle ne portant pas de `compagnie_id`.
 */
class VoyageInstancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VoyageInstance $instance): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('voyage.instance.generate');
    }

    public function update(User $user, VoyageInstance $instance): bool
    {
        return $user->hasPermission('voyage.instance.update', $instance);
    }

    /** Un départ ne se supprime pas : il s'annule, et ses tickets suivent. */
    public function delete(User $user, VoyageInstance $instance): bool
    {
        return $user->hasPermission('voyage.instance.cancel', $instance);
    }

    public function cancel(User $user, VoyageInstance $instance): bool
    {
        return $user->hasPermission('voyage.instance.cancel', $instance);
    }

    public function close(User $user, VoyageInstance $instance): bool
    {
        return $user->hasPermission('voyage.instance.close', $instance);
    }

    public function assign(User $user, VoyageInstance $instance): bool
    {
        return $user->hasPermission('voyage.instance.assignVehicule', $instance);
    }
}
