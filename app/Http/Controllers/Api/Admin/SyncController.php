<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class SyncController extends Controller
{
    /**
     * Retourne les voyages et validations modifiés depuis une date donnée.
     *
     * GET /api/admin/sync/pull?since=2025-01-15T08:00:00Z
     */
    public function pull(Request $request): JsonResponse
    {
        $since       = $request->query('since');
        $compagnieId = Auth::user()->compagnie_id;

        $sinceDate = $since
            ? \Carbon\Carbon::parse($since)
            : now()->subDay();

        // Voyages modifiés
        $voyages = VoyageInstance::whereHas('voyage', fn($q) => $q->where('compagnie_id', $compagnieId))
            ->where('updated_at', '>=', $sinceDate)
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'voyage.compagnie', 'care', 'chauffer'])
            ->get()
            ->map(fn($instance) => $this->formatVoyage($instance));

        // Tickets validés ou modifiés depuis la date
        $validations = Ticket::whereHas('voyageInstance.voyage', fn($q) => $q->where('compagnie_id', $compagnieId))
            ->where('updated_at', '>=', $sinceDate)
            ->whereIn('statut', [StatutTicket::Valider, StatutTicket::Pause, StatutTicket::Bloquer])
            ->with(['user', 'voyageInstance', 'autrePersonne'])
            ->get()
            ->map(fn($ticket) => [
                'ticket_id'    => $ticket->id,
                'voyage_id'    => $ticket->voyage_instance_id,
                'statut'       => $ticket->statut->value,
                'valider_at'   => $ticket->valider_at,
                'updated_at'   => $ticket->updated_at,
            ]);

        return response()->json([
            'success'      => true,
            'last_sync_at' => now()->toISOString(),
            'data'         => [
                'voyages'     => $voyages,
                'validations' => $validations,
            ],
        ]);
    }

    private function formatVoyage(VoyageInstance $instance): array
    {
        $voyage = $instance->voyage;
        $trajet = $voyage?->trajet;
        $boarded = $instance->tickets()
            ->where('statut', StatutTicket::Valider)
            ->count();
        $total = $instance->tickets()
            ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider, StatutTicket::Pause])
            ->count();

        return [
            'id'              => $instance->id,
            'numero_voyage'   => $voyage?->reference ?? $instance->id,
            'departure_time'  => $instance->date->format('Y-m-d') . 'T' . ($instance->heure ? $instance->heure->format('H:i:s') : '00:00:00'),
            'status'          => $this->mapStatut($instance->statut),
            'total_seats'     => $instance->nb_place,
            'boarded_count'   => $boarded,
            'ticket_count'    => $total,
            'departure'       => ['id' => $trajet?->depart?->id ?? 0, 'name' => $trajet?->depart?->name ?? ''],
            'arrival'         => ['id' => $trajet?->arriver?->id ?? 0, 'name' => $trajet?->arriver?->name ?? ''],
            'compagnie'       => ['id' => $voyage?->compagnie?->id ?? 0, 'name' => $voyage?->compagnie?->name ?? ''],
            'vehicle'         => $instance->care ? [
                'id'              => $instance->care->id,
                'immatriculation' => $instance->care->immatrculation,
                'capacity'        => $instance->care->number_place,
                'numero'          => $instance->care->numero,
            ] : null,
            'updated_at'      => $instance->updated_at,
        ];
    }

    private function mapStatut(?StatutVoyageInstance $statut): string
    {
        return match ($statut) {
            StatutVoyageInstance::DISPONIBLE => 'boarding',
            StatutVoyageInstance::INACTIF    => 'departed',
            StatutVoyageInstance::ANNULE     => 'cancelled',
            StatutVoyageInstance::RETARDE    => 'scheduled',
            default                          => 'scheduled',
        };
    }
}
