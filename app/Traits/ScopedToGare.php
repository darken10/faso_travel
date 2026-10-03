<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Second axe de cloisonnement des écrans du panneau compagnie.
 *
 * Le pendant de `ScopedToCompagnie` pour les rôles de terrain : un chef de gare, un
 * guichetier, un agent d'embarquement ou un bagagiste ne travaille que sur l'activité des
 * gares auxquelles il est affecté. Les rôles de siège, eux, n'ont aucune affectation et
 * travaillent sur toute la compagnie — d'où `estBorneAUneGare()`, qui distingue les deux
 * cas sans avoir à tester le rôle.
 */
trait ScopedToGare
{
    /**
     * Gares de l'utilisateur connecté.
     *
     * @return list<int>
     */
    protected function garesAutorisees(): array
    {
        return Auth::user()?->gareIds() ?? [];
    }

    /** L'utilisateur connecté est-il affecté à au moins une gare ? */
    protected function estBorneAUneGare(): bool
    {
        return $this->garesAutorisees() !== [];
    }

    /**
     * Garde-fou d'accès par identifiant de gare.
     *
     * Les propriétés publiques d'un composant Livewire sont modifiables depuis le
     * navigateur : un identifiant de gare reçu de l'extérieur n'est jamais de confiance.
     */
    protected function autoriserGare(?int $gareId): void
    {
        if ($gareId === null || ! $this->estBorneAUneGare()) {
            return;
        }

        abort_unless(
            in_array($gareId, $this->garesAutorisees(), true),
            403,
            'Cette gare ne fait pas partie de vos affectations.'
        );
    }
}
