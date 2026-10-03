<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voyage\Voyage;

/**
 * Autorisations de l'offre de voyage.
 *
 * La consultation reste ouverte : le catalogue des voyages est public, c'est ce qu'un
 * voyageur parcourt avant même de créer un compte. Seules les écritures sont gardées.
 */
class VoyagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Voyage $voyage): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('voyage.voyage.create');
    }

    public function update(User $user, Voyage $voyage): bool
    {
        return $user->hasPermission('voyage.voyage.update', $voyage);
    }

    public function delete(User $user, Voyage $voyage): bool
    {
        return $user->hasPermission('voyage.voyage.delete', $voyage);
    }

    public function publish(User $user, Voyage $voyage): bool
    {
        return $user->hasPermission('voyage.voyage.publish', $voyage);
    }

    public function manageInstances(User $user, Voyage $voyage): bool
    {
        return $user->hasPermission('voyage.instance.update', $voyage);
    }

    public function updateTarif(User $user, Voyage $voyage): bool
    {
        return $user->hasPermission('voyage.tarif.update', $voyage);
    }
}
