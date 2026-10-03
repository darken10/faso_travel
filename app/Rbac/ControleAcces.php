<?php

namespace App\Rbac;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Décision d'autorisation unique, partagée par les trois points de contrôle : le
 * middleware de route, les actions des composants Livewire et le login de l'application
 * agent.
 *
 * Trois copies de la même règle, c'est trois occasions de divergence le jour où la règle
 * change. Elle est donc écrite ici une fois.
 */
class ControleAcces
{
    /** Repère de ligne, pour que `rbac:refusals` retrouve ses entrées. */
    public const MARQUEUR = 'rbac.refus';

    /**
     * L'action est-elle permise ?
     *
     * En mode observation, un refus est journalisé mais laissé passer : fermer les accès
     * sans avoir mesuré l'usage réel des écrans arrêterait des guichets en production.
     *
     * @param  array<string, mixed>  $contexte  Informations d'origine, pour le rapport.
     */
    public function autorise(?User $user, string $permission, mixed $sujet = null, array $contexte = []): bool
    {
        if ($user?->hasPermission($permission, $sujet)) {
            return true;
        }

        if ((bool) config('rbac.enforce') === true) {
            return false;
        }

        $this->observer($user, $permission, $contexte);

        return true;
    }

    /**
     * Journalise un refus observé.
     *
     * Enveloppé : un journal indisponible — canal mal configuré, disque plein, façade
     * remplacée par un espion dans un test — ne doit pas faire échouer la requête. Le mode
     * observation existe pour ne rien casser ; la trace est un moyen, pas une fin.
     *
     * @param  array<string, mixed>  $contexte
     */
    private function observer(?User $user, string $permission, array $contexte): void
    {
        try {
            $this->ecrire($user, $permission, $contexte);
        } catch (Throwable) {
            // Rien à faire : le journal est précisément le canal de report.
        }
    }

    /** @param  array<string, mixed>  $contexte */
    private function ecrire(?User $user, string $permission, array $contexte): void
    {
        Log::channel(config('rbac.log_channel'))->info(self::MARQUEUR, $contexte + [
            'permission' => $permission,
            'user_id' => $user?->getKey(),
            'compagnie_id' => $user?->compagnie_id,
            'roles' => $user?->roles->pluck('name')->sort()->values()->all() ?? [],
            'route' => Request::route()?->getName() ?? Request::path(),
            'method' => Request::method(),
            'at' => now()->toIso8601String(),
        ]);
    }
}
