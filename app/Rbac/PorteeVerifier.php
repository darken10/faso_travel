<?php

namespace App\Rbac;

use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Model;

/**
 * Vérifie qu'une permission s'applique bien à l'enregistrement visé.
 *
 * La permission dit *quoi*, la portée dit *sur quoi*. Un guichetier porte
 * `guichet.ticket.changeDate` en portée `gare` : il a le droit d'agir, mais seulement sur
 * les tickets de sa gare.
 */
class PorteeVerifier
{
    /** De la plus restrictive à la plus large. */
    public const HIERARCHIE = ['own' => 1, 'gare' => 2, 'compagnie' => 3, 'all' => 4];

    /**
     * Portée la plus large entre deux portées.
     *
     * Un compte qui cumule deux rôles portant la même permission garde la plus large :
     * retenir la plus étroite reviendrait à punir le cumul.
     */
    public static function plusLarge(string $a, string $b): string
    {
        return (self::HIERARCHIE[$a] ?? 0) >= (self::HIERARCHIE[$b] ?? 0) ? $a : $b;
    }

    public function autorise(User $user, string $portee, mixed $sujet = null): bool
    {
        if ($portee === 'all') {
            return true;
        }

        // Sans sujet, le contrôle porte sur un écran et non sur un enregistrement : le
        // filtrage des lignes est l'affaire des scopes de modèle, pas du gate.
        if ($sujet === null) {
            return true;
        }

        return match ($portee) {
            'compagnie' => $this->memeCompagnie($user, $sujet),
            'gare' => $this->memeGare($user, $sujet),
            'own' => $this->luiAppartient($user, $sujet),
            default => false,
        };
    }

    private function memeCompagnie(User $user, mixed $sujet): bool
    {
        if ($user->compagnie_id === null) {
            return false;
        }

        return $this->compagnieDuSujet($sujet) === (int) $user->compagnie_id;
    }

    /**
     * Portée « gare » — neutralisée jusqu'à la livraison de la table `gare_user`.
     *
     * Tant qu'aucune affectation de gare n'existe, traiter cette portée comme une portée
     * compagnie est le seul comportement sûr : la traiter comme un refus retirerait d'un
     * coup l'accès des guichetiers et des agents, qui n'ont aujourd'hui aucune gare.
     */
    private function memeGare(User $user, mixed $sujet): bool
    {
        return $this->memeCompagnie($user, $sujet);
    }

    private function luiAppartient(User $user, mixed $sujet): bool
    {
        if ($sujet instanceof User) {
            return $sujet->getKey() === $user->getKey();
        }

        if ($sujet instanceof Model && $sujet->getAttribute('user_id') !== null) {
            return (int) $sujet->getAttribute('user_id') === (int) $user->getKey();
        }

        // Propriété indéterminable : on refuse. Un doute sur l'appartenance d'un
        // enregistrement ne doit jamais se résoudre en autorisation.
        return false;
    }

    /**
     * Compagnie d'un sujet, ou null si elle ne peut pas être déterminée.
     *
     * Les tickets et les départs ne portent pas de `compagnie_id` : leur compagnie se lit
     * sur le voyage, comme le fait déjà `TicketPolicy::isCompagnieAgent()`. Sans ces deux
     * chemins, toute vérification de portée sur un ticket se résoudrait en refus.
     */
    private function compagnieDuSujet(mixed $sujet): ?int
    {
        if ($sujet instanceof Compagnie) {
            return (int) $sujet->getKey();
        }

        if ($sujet instanceof Model && $sujet->getAttribute('compagnie_id') !== null) {
            return (int) $sujet->getAttribute('compagnie_id');
        }

        if ($sujet instanceof Ticket) {
            $compagnieId = $sujet->voyageInstance?->voyage?->compagnie_id;

            return $compagnieId === null ? null : (int) $compagnieId;
        }

        if ($sujet instanceof VoyageInstance) {
            $compagnieId = $sujet->voyage?->compagnie_id;

            return $compagnieId === null ? null : (int) $compagnieId;
        }

        if (is_numeric($sujet)) {
            return (int) $sujet;
        }

        return null;
    }
}
