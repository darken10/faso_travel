<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;

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
        return $user->compagnie_id !== null || $this->isAdmin($user);
    }

    public function update(User $user, VoyageInstance $instance): bool
    {
        return $this->ownsInstance($user, $instance) || $this->isAdmin($user);
    }

    public function delete(User $user, VoyageInstance $instance): bool
    {
        return $this->ownsInstance($user, $instance) || $this->isAdmin($user);
    }

    public function cancel(User $user, VoyageInstance $instance): bool
    {
        return $this->ownsInstance($user, $instance) || $this->isAdmin($user);
    }

    private function ownsInstance(User $user, VoyageInstance $instance): bool
    {
        return $user->compagnie_id !== null
            && $user->compagnie_id === $instance->voyage?->compagnie_id;
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Root]);
    }
}
