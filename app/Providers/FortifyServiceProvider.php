<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Enums\UserRole;
use App\Http\Responses\CustomLoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, CustomLoginResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(function (Request $request) {
            $host = $request->getHost();
            $domain = config('app.domain', 'liptra.net');

            if (str_starts_with($host, 'admin.')) {
                return view('auth.login-admin');
            }
            if (str_starts_with($host, 'compagnie.')) {
                return view('auth.login-compagnie');
            }
            return view('auth.login-client');
        });

        Fortify::authenticateUsing(function (Request $request) {
            $host = $request->getHost();

            if (str_starts_with($host, 'admin.') || str_starts_with($host, 'compagnie.')) {
                // Admin et compagnie : email + mot de passe
                $user = User::where('email', $request->input('email', ''))->first();
            } else {
                // Client : email OU numéro de téléphone + mot de passe
                $identifier = trim($request->input('email', ''));
                if (str_contains($identifier, '@')) {
                    $user = User::where('email', $identifier)->first();
                } else {
                    $phone = preg_replace('/\D/', '', $identifier);
                    $user  = $phone ? User::where('numero', (int) $phone)->first() : null;
                }
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return null;
            }

            if (str_starts_with($host, 'admin.')) {
                return in_array($user->role, [UserRole::Admin, UserRole::Root]) ? $user : null;
            }

            if (str_starts_with($host, 'compagnie.')) {
                return $user->compagnie_id ? $user : null;
            }

            if (in_array($user->role, [UserRole::Admin, UserRole::Root])) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
