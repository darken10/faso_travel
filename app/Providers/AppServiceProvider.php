<?php

namespace App\Providers;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Features\Payement\PaymentGatewayFactory;
use App\Http\Middleware\AuthorizeOrObserve;
use App\Models\Auth\PersonalAccessToken;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Policies\CarePolicy;
use App\Policies\CompagniePolicy;
use App\Policies\CompagnieSettingPolicy;
use App\Policies\TicketPolicy;
use App\Policies\VoyageInstancePolicy;
use App\Policies\VoyagePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /** Liste des abilities dérivées du catalogue de permissions. */
    public const CLE_CACHE_PERMISSIONS = 'rbac:permissions:noms';

    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactory::class, fn () => new PaymentGatewayFactory);
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // Voir le modele : garantit une echeance par defaut maintenant que le
        // plafond global de config('sanctum.expiration') est desactive.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->registerPolicies();
        $this->registerGates();
        $this->registerPermissionGates();
        $this->registerLivewirePersistentMiddleware();
        $this->configureRateLimiting();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Voyage::class, VoyagePolicy::class);
        Gate::policy(VoyageInstance::class, VoyageInstancePolicy::class);
        Gate::policy(Compagnie::class, CompagniePolicy::class);
        // La découverte automatique chercherait App\Policies\Compagnie\CarePolicy pour un
        // modèle rangé sous App\Models\Compagnie : sans cet enregistrement explicite, la
        // policy du parc n'était jamais appliquée.
        Gate::policy(Care::class, CarePolicy::class);
    }

    /**
     * Le paramétrage porte sur le modèle Compagnie, déjà associé à CompagniePolicy :
     * ses autorisations sont donc exposées sous forme d'abilities nommées.
     */
    private function registerGates(): void
    {
        Gate::define('compagnie-settings.viewAny', [CompagnieSettingPolicy::class, 'viewAny']);
        Gate::define('compagnie-settings.view', [CompagnieSettingPolicy::class, 'view']);
        Gate::define('compagnie-settings.update', [CompagnieSettingPolicy::class, 'update']);
        Gate::define('compagnie-settings.updateAdvanced', [CompagnieSettingPolicy::class, 'updateAdvanced']);
        Gate::define('compagnie-settings.reset', [CompagnieSettingPolicy::class, 'reset']);

        // Instruction des validations refusées : réservée à la direction, à
        // l'administration et à la comptabilité. Un agent terrain ou un guichetier
        // ne doit pas pouvoir classer sans suite le refus qui le concerne.
        Gate::define('manage-boarding-conflicts', function (User $user): bool {
            if ($user->compagnie_id === null) {
                return false;
            }

            // Les permissions du catalogue s'ajoutent aux rôles historiques : un compte
            // déjà migré doit passer, un compte pas encore migré doit continuer à passer.
            return $user->role === UserRole::CompagnieBosse
                || $user->hasAnyRole([CompanyRole::Admin->value, CompanyRole::Comptabilite->value])
                || $user->hasPermission('embarquement.conflit.view');
        });
    }

    /**
     * Expose chaque permission du catalogue comme une ability nommée.
     *
     * `Gate::before` traite le cas racine en amont : un compte `root` reste opérationnel
     * même sur une base dont les permissions ne sont pas encore semées.
     *
     * La liste est mise en cache et l'accès est encadré par un `try` : au `boot` d'un
     * `artisan migrate` sur une base vide, la table `permissions` n'existe pas encore et
     * l'application doit démarrer quand même.
     */
    private function registerPermissionGates(): void
    {
        Gate::before(fn (User $user, string $ability): ?bool => $user->isRoot() ? true : null);

        try {
            $noms = Cache::remember(
                self::CLE_CACHE_PERMISSIONS,
                3600,
                fn (): array => Permission::pluck('name')->all()
            );
        } catch (\Throwable) {
            return;
        }

        foreach ($noms as $nom) {
            Gate::define(
                $nom,
                fn (User $user, mixed $sujet = null): bool => $user->hasPermission($nom, $sujet)
            );
        }
    }

    /**
     * Rend le contrôle d'autorisation persistant d'une requête Livewire à l'autre.
     *
     * Une mise à jour Livewire ne passe pas par la route d'origine : elle tape
     * `/livewire/update`. Sans cette déclaration, le middleware `can.rbac` posé sur une
     * route ne protégerait que le premier chargement de la page, et chaque action du
     * composant serait ensuite hors contrôle — or c'est là que se font les écritures.
     */
    private function registerLivewirePersistentMiddleware(): void
    {
        Livewire::addPersistentMiddleware([
            AuthorizeOrObserve::class,
        ]);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api-payment', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
