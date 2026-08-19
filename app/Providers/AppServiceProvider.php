<?php

namespace App\Providers;

use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Policies\CompagniePolicy;
use App\Policies\CompagnieSettingPolicy;
use App\Policies\TicketPolicy;
use App\Policies\VoyageInstancePolicy;
use App\Policies\VoyagePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Features\Payement\PaymentGatewayFactory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactory::class, fn () => new PaymentGatewayFactory());
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        $this->registerPolicies();
        $this->registerGates();
        $this->configureRateLimiting();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Voyage::class, VoyagePolicy::class);
        Gate::policy(VoyageInstance::class, VoyageInstancePolicy::class);
        Gate::policy(Compagnie::class, CompagniePolicy::class);
    }

    /**
     * Le paramétrage porte sur le modèle Compagnie, déjà associé à CompagniePolicy :
     * ses autorisations sont donc exposées sous forme d'abilities nommées.
     */
    private function registerGates(): void
    {
        Gate::define('compagnie-settings.viewAny',       [CompagnieSettingPolicy::class, 'viewAny']);
        Gate::define('compagnie-settings.view',          [CompagnieSettingPolicy::class, 'view']);
        Gate::define('compagnie-settings.update',        [CompagnieSettingPolicy::class, 'update']);
        Gate::define('compagnie-settings.updateAdvanced', [CompagnieSettingPolicy::class, 'updateAdvanced']);
        Gate::define('compagnie-settings.reset',         [CompagnieSettingPolicy::class, 'reset']);
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
