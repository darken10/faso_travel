<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasWebCompagnie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->compagnie_id) {
            abort(403, 'Accès non autorisé — compte non associé à une compagnie.');
        }

        return $next($request);
    }
}
