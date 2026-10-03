<?php

namespace App\Http\Middleware;

use App\Rbac\ControleAcces;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle une permission sur une route, en bloquant ou en observant selon
 * `rbac.enforce`.
 *
 * Aucune des routes du panneau n'était filtrée jusqu'ici : l'usage réel de chaque écran
 * par chaque métier est donc inconnu. Fermer les accès sans l'avoir mesuré arrêterait des
 * guichets en production. En mode observation, un refus passe quand même et laisse une
 * trace ; la commande `rbac:refusals` les agrège, et c'est cette liste qui dit quand on
 * peut basculer.
 *
 * Ce middleware vient toujours après une garde d'authentification : un visiteur anonyme
 * est redirigé vers la connexion bien avant d'arriver ici. Il échoue malgré tout fermé,
 * pour qu'une route mal configurée ne devienne pas une porte ouverte.
 *
 * Déclaré persistant auprès de Livewire : une mise à jour de composant ne repasse pas par
 * la route d'origine, et sans cela seul le premier chargement serait contrôlé.
 */
class AuthorizeOrObserve
{
    /** @deprecated Utiliser ControleAcces::MARQUEUR. */
    public const MARQUEUR = ControleAcces::MARQUEUR;

    public function __construct(private readonly ControleAcces $controle) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $autorise = $this->controle->autorise($request->user(), $permission);

        abort_unless($autorise, 403, 'Vous n\'avez pas l\'autorisation « '.$permission.' ».');

        return $next($request);
    }
}
