<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';
            return redirect($scheme.'://app.'.config('app.domain').'/login');
        }

        $role = $request->user()->role;

        if ($role !== UserRole::Admin && $role !== UserRole::Root) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
