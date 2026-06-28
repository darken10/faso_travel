<?php

namespace App\Services\Loyalty;

use App\Enums\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /** 1 point gagné pour 100 XOF dépensés. */
    private const POINTS_PAR_XOF = 100;

    /** Attribue des points de fidélité au client pour un achat. */
    public function award(User $user, int $montant, ?Ticket $ticket = null): int
    {
        $points = intdiv($montant, self::POINTS_PAR_XOF);
        if ($points <= 0) {
            return 0;
        }

        DB::transaction(function () use ($user, $points, $ticket) {
            $user->increment('loyalty_points', $points);
            $user->increment('loyalty_lifetime_points', $points);

            LoyaltyTransaction::create([
                'user_id'   => $user->id,
                'points'    => $points,
                'reason'    => $ticket ? "Achat ticket {$ticket->numero_ticket}" : 'Achat ticket',
                'ticket_id' => $ticket?->id,
            ]);
        });

        return $points;
    }

    /** Synthèse fidélité d'un utilisateur. */
    public function summary(User $user): array
    {
        $lifetime = (int) $user->loyalty_lifetime_points;
        $tier = LoyaltyTier::pour($lifetime);
        $next = $tier->suivant();

        return [
            'points'            => (int) $user->loyalty_points,
            'lifetime_points'   => $lifetime,
            'tier'              => $tier->value,
            'tier_color'        => $tier->couleur(),
            'next_tier'         => $next?->value,
            'next_tier_seuil'   => $next?->seuil(),
            'points_to_next'    => $next ? max(0, $next->seuil() - $lifetime) : 0,
        ];
    }
}
