<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Voyage\Voyage;

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
        return $user->compagnie_id !== null || $this->isAdmin($user);
    }

    public function update(User $user, Voyage $voyage): bool
    {
        return $this->ownsVoyage($user, $voyage) || $this->isAdmin($user);
    }

    public function delete(User $user, Voyage $voyage): bool
    {
        return $this->ownsVoyage($user, $voyage) || $this->isAdmin($user);
    }

    public function manageInstances(User $user, Voyage $voyage): bool
    {
        return $this->ownsVoyage($user, $voyage) || $this->isAdmin($user);
    }

    private function ownsVoyage(User $user, Voyage $voyage): bool
    {
        return $user->compagnie_id !== null
            && $user->compagnie_id === $voyage->compagnie_id;
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Root]);
    }
}
