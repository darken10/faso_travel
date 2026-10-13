<?php

namespace App\Http\Middleware;

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

        if (! $request->user()->isPlatformUser()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
