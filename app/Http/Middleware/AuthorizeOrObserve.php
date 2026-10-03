<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle une permission, en bloquant ou en observant selon `rbac.enforce`.
 *
 * Aucune des routes du panneau n'était filtrée jusqu'ici : l'usage réel de chaque écran
 * par chaque métier est donc inconnu. Fermer les accès sans l'avoir mesuré arrêterait des
 * guichets en production. En mode observation, un refus passe quand même et laisse une
 * trace ; la commande `rbac:refusals` les agrège, et c'est cette liste qui dit quand on
 * peut basculer.
 *
 * Ce middleware vient toujours après une garde d'authentification : un visiteur anonyme
 * est redirigé vers la connexion bien avant d'arriver ici.
 */
class AuthorizeOrObserve
{
    /** Repère de ligne, pour que `rbac:refusals` retrouve ses entrées. */
    public const MARQUEUR = 'rbac.refus';

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user?->hasPermission($permission)) {
            return $next($request);
        }

        if ((bool) config('rbac.enforce') === true) {
            abort(403, 'Vous n\'avez pas l\'autorisation « '.$permission.' ».');
        }

        $this->observer($request, $permission);

        return $next($request);
    }

    private function observer(Request $request, string $permission): void
    {
        $user = $request->user();

        Log::channel(config('rbac.log_channel'))->info(self::MARQUEUR, [
            'permission' => $permission,
            'user_id' => $user?->getKey(),
            'compagnie_id' => $user?->compagnie_id,
            'roles' => $user?->roles->pluck('name')->sort()->values()->all() ?? [],
            'route' => $request->route()?->getName() ?? $request->path(),
            'method' => $request->method(),
            'at' => now()->toIso8601String(),
        ]);
    }
}
