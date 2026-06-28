<?php

namespace App\Helper;

use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Models\Post\Post;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Builder;

class QueryHelpers
{
    private static function compagnieId(): ?int
    {
        return auth()->check() ? auth()->user()->compagnie_id : null;
    }

    public static function AllUsersOfMyCompagnie()
    {
        $compagnieId = self::compagnieId();

        if (!$compagnieId) {
            return \App\Models\User::whereRaw('1 = 0');
        }

        return auth()->user()->compagnie->users();
    }

    public static function AllPostsOfMyCompagnie(): Builder
    {
        $compagnieId = self::compagnieId();

        if (!$compagnieId) {
            return Post::whereRaw('1 = 0');
        }

        return Post::whereHas('user', fn($q) => $q->where('compagnie_id', $compagnieId));
    }

    public static function AllVoyagesOfMyCompagnie(): Builder
    {
        $compagnieId = self::compagnieId();

        if (!$compagnieId) {
            return Voyage::whereRaw('1 = 0');
        }

        return Voyage::where('compagnie_id', $compagnieId);
    }

    public static function AllVoyagesInstanceOfMyCompagnie(): Builder
    {
        $compagnieId = self::compagnieId();

        if (!$compagnieId) {
            return VoyageInstance::whereRaw('1 = 0');
        }

        return VoyageInstance::whereHas('voyage', fn($q) => $q->where('compagnie_id', $compagnieId));
    }

    public static function AllTicketOfMyCompagnie(?StatutTicket $statutTicket = null): Builder
    {
        $compagnieId = self::compagnieId();

        if (!$compagnieId) {
            return Ticket::whereRaw('1 = 0');
        }

        $query = Ticket::whereHas(
            'voyageInstance.voyage',
            fn($q) => $q->where('compagnie_id', $compagnieId)
        );

        if ($statutTicket !== null) {
            $query->where('statut', $statutTicket);
        }

        return $query;
    }

    /**
     * @param  StatutTicket|array<int, StatutTicket>|null  $statutTicket  Un statut ou une liste de statuts de ticket.
     */
    public static function AllPaymentsOfMyCompagnie(
        ?StatutPayement      $statutPayement = StatutPayement::Complete,
        StatutTicket|array|null $statutTicket = null
    ): Builder {
        $compagnieId = self::compagnieId();

        if (!$compagnieId) {
            return Payement::whereRaw('1 = 0');
        }

        $query = Payement::whereHas(
            'ticket.voyageInstance.voyage',
            fn($q) => $q->where('compagnie_id', $compagnieId)
        );

        if ($statutPayement !== null) {
            $query->where('statut', $statutPayement);
        }

        if ($statutTicket !== null) {
            $statuts = collect(is_array($statutTicket) ? $statutTicket : [$statutTicket])
                ->map(fn($s) => $s instanceof StatutTicket ? $s->value : $s)
                ->all();
            $query->whereHas('ticket', fn($q) => $q->whereIn('statut', $statuts));
        }

        return $query;
    }
}
