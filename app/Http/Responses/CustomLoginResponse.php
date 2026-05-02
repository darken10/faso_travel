<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user   = auth()->user();
        $domain = config('app.domain');
        $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';

        if (in_array($user->role, [UserRole::Admin, UserRole::Root])) {
            return redirect($scheme.'://admin.'.$domain.'/');
        }

        if ($user->compagnie_id) {
            return redirect($scheme.'://compagnie.'.$domain.'/');
        }

        return redirect($scheme.'://app.'.$domain.'/');
    }
}
