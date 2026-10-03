<?php

namespace App\Policies;

use App\Models\Finance\Depense;
use App\Models\User;
use App\Rbac\ReglesSeparation;

/**
 * Autorisations des dépenses.
 *
 * Son intérêt principal est `approve()` : la règle des quatre yeux ne peut pas se jouer au
 * niveau du catalogue de permissions, puisqu'un même compte peut légitimement porter le
 * droit de saisir et celui d'approuver. Elle se joue sur la pièce.
 */
class DepensePolicy
{
    public function __construct(private readonly ReglesSeparation $regles) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('finance.depense.view');
    }

    public function view(User $user, Depense $depense): bool
    {
        return $user->hasPermission('finance.depense.view', $depense);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('finance.depense.create');
    }

    public function update(User $user, Depense $depense): bool
    {
        return $user->hasPermission('finance.depense.update', $depense);
    }

    public function delete(User $user, Depense $depense): bool
    {
        return $user->hasPermission('finance.depense.delete', $depense);
    }

    /** Celui qui saisit n'approuve pas, même s'il porte les deux permissions. */
    public function approve(User $user, Depense $depense): bool
    {
        return $user->hasPermission('finance.depense.approve', $depense)
            && $this->regles->peutApprouver($user, $depense);
    }
}
