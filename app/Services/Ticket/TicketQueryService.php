<?php

namespace App\Services\Ticket;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Service dédié aux requêtes de lecture sur les tickets.
 * Sépare les queries (read) des commandes (write) pour respecter CQRS.
 */
class TicketQueryService
{
    // ─── Requêtes côté utilisateur ───────────────────────────────────────

    public function getUserTickets(?StatutTicket $status = null): Collection
    {
        $query = Ticket::with([
            'voyageInstance.voyage.trajet.depart',
            'voyageInstance.voyage.trajet.arriver',
            'voyageInstance.voyage.compagnie',
        ])->where('user_id', Auth::id());

        if ($status) {
            $query->where('statut', $status);
        }

        return $query->latest()->get();
    }

    public function getUserTicketById(string $id): Ticket
    {
        return Ticket::with([
            'voyageInstance.voyage.trajet.depart',
            'voyageInstance.voyage.trajet.arriver',
            'voyageInstance.voyage.compagnie',
            'user',
            'autre_personne',
            'payements',
        ])
        ->where(function ($query) {
            $query->where('user_id', Auth::id())
                  ->orWhere('transferer_a_user_id', Auth::id());
        })
        ->findOrFail($id);
    }

    // ─── Requêtes côté compagnie / admin ─────────────────────────────────

    public function getTodaysPaidPassengers(): Collection
    {
        $compagnieId = Auth::user()->compagnie_id;

        return Ticket::with(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->whereHas('voyageInstance', fn ($q) => $q->whereDate('date', Carbon::today()->addDay()))
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->where('statut', StatutTicket::Payer)
            ->get();
    }

    public function getTodaysValidatedTickets(): Collection
    {
        $compagnieId = Auth::user()->compagnie_id;

        return Ticket::with(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->whereDate('valider_at', Carbon::today())
            ->where('statut', StatutTicket::Valider)
            ->get();
    }

    public function getAllValidatedTickets(): Collection
    {
        $compagnieId = Auth::user()->compagnie_id;

        return Ticket::with(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->where('statut', StatutTicket::Valider)
            ->orderBy('valider_at', 'desc')
            ->get();
    }

    public function getTodayVoyageInstances(): Collection
    {
        $compagnieId = Auth::user()->compagnie_id;

        return VoyageInstance::with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'chauffer'])
            ->whereDate('date', Carbon::today())
            ->whereHas('voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->get();
    }

    public function getTicketsByVoyageInstance(string $voyageInstanceId): Collection
    {
        $compagnieId = Auth::user()->compagnie_id;

        return Ticket::with(['user', 'voyageInstance.voyage.trajet'])
            ->where('voyage_instance_id', $voyageInstanceId)
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->get();
    }

    public function findByQrCode(string $qrCode): Ticket
    {
        return Ticket::with(['user', 'autre_personne', 'voyageInstance.voyage.trajet', 'payements'])
            ->where('code_qr', $qrCode)
            ->firstOrFail();
    }

    public function findByPhoneAndCode(string $phone, string $code): ?Ticket
    {
        return Ticket::with(['user', 'autre_personne', 'voyageInstance.voyage.trajet'])
            ->where(function ($query) use ($phone) {
                $query->whereHas('user', fn ($q) => $q->where('numero', $phone))
                      ->orWhereHas('autre_personne', fn ($q) => $q->where('contact', $phone));
            })
            ->where('code_sms', $code)
            ->first();
    }
}
