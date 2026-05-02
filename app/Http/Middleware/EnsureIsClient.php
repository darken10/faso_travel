<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user   = $request->user();
        $domain = config('app.domain');
        $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';

        if (!$user) {
            return redirect($scheme.'://app.'.$domain.'/login');
        }

        // Les admins ne passent pas par l'espace client
        if (in_array($user->role, [UserRole::Admin, UserRole::Root])) {
            return redirect($scheme.'://admin.'.$domain.'/');
        }

        // Les utilisateurs de compagnie vont sur leur espace
        if ($user->compagnie_id) {
            return redirect($scheme.'://compagnie.'.$domain.'/');
        }

        return $next($request);
    }
}
