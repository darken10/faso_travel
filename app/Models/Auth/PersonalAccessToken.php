<?php

namespace App\Models\Auth;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Token Sanctum dont la durée de vie par défaut est portée par le token lui-même.
 *
 * Sanctum applique deux conditions cumulatives (Guard::isValidAccessToken) :
 * le plafond global config('sanctum.expiration') ET le expires_at de la ligne.
 * Tant que le plafond valait 24 h, tout token créé avec une échéance plus
 * lointaine était invalidé au bout de 24 h sans que rien ne le signale — c'est
 * ce qui rendait inopérants les tokens de 30 jours de AuthService.
 *
 * Le plafond global est donc désactivé, et c'est ce modèle qui garantit qu'un
 * token créé sans échéance explicite n'est pas éternel pour autant.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** Durée de vie appliquée à un token créé sans échéance explicite. */
    public const DEFAULT_LIFETIME_MINUTES = 60 * 24;

    /**
     * Sanctum 4 n'expose pas isExpired() : les appelants qui s'y fiaient
     * levaient une Error a l'execution. AuthService::refresh le faisait, ce qui
     * rendait /api/v2/auth/refresh inoperant des qu'un jeton valide etait trouve.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    protected static function booted(): void
    {
        static::creating(function (self $token) {
            if ($token->expires_at === null) {
                $token->expires_at = now()->addMinutes(self::DEFAULT_LIFETIME_MINUTES);
            }
        });
    }
}
