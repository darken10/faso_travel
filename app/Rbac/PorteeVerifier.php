<?php

namespace App\Rbac;

use App\Models\Compagnie\Compagnie;
use App\Models\Compagnie\Gare;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
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
     * Portée « gare » : le sujet doit relever d'une gare d'affectation du compte.
     *
     * Un compte sans aucune affectation ne voit rien. C'est volontaire : seuls les quatre
     * rôles de terrain reçoivent cette portée, et un compte de terrain sans gare est une
     * affectation oubliée, pas un compte qui travaille partout. La commande
     * `rbac:gares-manquantes` existe précisément pour détecter ces oublis avant que les
     * contrôles ne deviennent bloquants.
     */
    private function memeGare(User $user, mixed $sujet): bool
    {
        if (! $this->memeCompagnie($user, $sujet)) {
            return false;
        }

        $affectations = $user->gareIds();

        if ($affectations === []) {
            return false;
        }

        $garesDuSujet = $this->garesDuSujet($sujet);

        if ($garesDuSujet === []) {
            // Gare indéterminable : on refuse. Un doute sur la localisation d'un
            // enregistrement ne doit jamais se résoudre en autorisation.
            return false;
        }

        return array_intersect($affectations, $garesDuSujet) !== [];
    }

    /**
     * Gares auxquelles un sujet se rattache.
     *
     * Un voyage en porte deux — départ et arrivée — et les deux comptent : sur un
     * aller-retour, le retour part de la gare d'arrivée, et c'est l'agent de cette gare
     * qui le contrôle.
     *
     * @return list<int>
     */
    private function garesDuSujet(mixed $sujet): array
    {
        if ($sujet instanceof Gare) {
            return [(int) $sujet->getKey()];
        }

        if ($sujet instanceof User) {
            return $sujet->gareIds();
        }

        $voyage = match (true) {
            $sujet instanceof Voyage => $sujet,
            $sujet instanceof VoyageInstance => $sujet->voyage,
            $sujet instanceof Ticket => $sujet->voyageInstance?->voyage,
            default => null,
        };

        if ($voyage !== null) {
            return array_values(array_map(
                'intval',
                array_filter([$voyage->depart_id, $voyage->arrive_id])
            ));
        }

        // Une référence directe à la gare primera toujours sur une déduction.
        if ($sujet instanceof Model && $sujet->getAttribute('gare_id') !== null) {
            return [(int) $sujet->getAttribute('gare_id')];
        }

        // Une session de caisse appartient à un agent, pas à une gare : elle hérite des
        // affectations de son titulaire.
        if ($sujet instanceof Model && $sujet->getAttribute('user_id') !== null && $sujet->isRelation('user')) {
            return $sujet->user?->gareIds() ?? [];
        }

        return [];
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
