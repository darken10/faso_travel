<?php

namespace App\Policies;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Compagnie\Compagnie;
use App\Models\User;

class CompagniePolicy
{
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
        return $this->isAdmin($user);
    }

    public function update(User $user, Compagnie $compagnie): bool
    {
        return $this->isOwner($user, $compagnie) || $this->isAdmin($user);
    }

    public function delete(User $user, Compagnie $compagnie): bool
    {
        return $this->isAdmin($user);
    }

    public function manageUsers(User $user, Compagnie $compagnie): bool
    {
        return $this->isOwner($user, $compagnie) || $this->isAdmin($user);
    }

    public function manageFinance(User $user, Compagnie $compagnie): bool
    {
        return ($user->compagnie_id === $compagnie->id
                && ($user->role === UserRole::CompagnieBosse
                    || $user->hasAnyRole([
                        CompanyRole::Admin->value,
                        CompanyRole::Comptabilite->value,
                    ])))
            || $this->isAdmin($user);
    }

    public function activate(User $user, Compagnie $compagnie): bool
    {
        return $this->isAdmin($user);
    }

    private function isOwner(User $user, Compagnie $compagnie): bool
    {
        return $user->compagnie_id === $compagnie->id
            && ($user->role === UserRole::CompagnieBosse
                || $user->hasAnyRole([CompanyRole::Admin->value]));
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Root]);
    }
}
