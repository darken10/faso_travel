<?php

namespace App\Services\Ticket;

use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\Auth;

class TicketQueryService
{
    public const RELATIONS = [
        'user',
        'voyageInstance.voyage.trajet.depart',
        'voyageInstance.voyage.trajet.arriver',
        'voyageInstance.voyage.compagnie',
        'voyageInstance.voyage.gareDepart',
        'voyageInstance.voyage.gareArriver',
        'voyageInstance.care',
        'autre_personne',
        'payements',
    ];

    public function getUserTickets(): \Illuminate\Database\Eloquent\Collection
    {
        return Ticket::with(self::RELATIONS)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserTicketById(string $ticketId): Ticket
    {
        return Ticket::with(self::RELATIONS)
            ->where('id', $ticketId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }
}
