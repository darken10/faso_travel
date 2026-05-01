<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresCompagnie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->compagnie_id) {
            return response()->json([
                'error' => true,
                'message' => 'Accès non autorisé — utilisateur non associé à une compagnie.',
            ], 403);
        }

        return $next($request);
    }
}
