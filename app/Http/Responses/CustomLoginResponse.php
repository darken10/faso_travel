<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Une seule porte de sortie après connexion : la route `dashboard` dirige le
        // compte vers son espace plateforme, compagnie ou client selon ses rôles.
        return redirect()->route('dashboard');
    }
}
