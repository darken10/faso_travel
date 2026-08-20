<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\VoyageTicketService;

class VoyageTicketController extends Controller
{
    protected $voyageTicketService;

    public function __construct(VoyageTicketService $voyageTicketService)
    {
        $this->voyageTicketService = $voyageTicketService;
    }

    /**
     * Résout une instance de voyage en garantissant qu'elle est bien opérée par
     * la compagnie de l'agent connecté.
     *
     * Sans cette vérification, un identifiant deviné suffisait à consulter le
     * voyage et la liste des passagers d'une compagnie concurrente.
     */
    private function findInstanceOfCompagnie(string $voyageInstanceId): VoyageInstance
    {
        $compagnieId = auth()->user()?->compagnie_id;

        abort_if($compagnieId === null, 403, 'Compte non associé à une compagnie.');

        return VoyageInstance::whereHas('voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->findOrFail($voyageInstanceId);
    }

    /**
     * Récupère les instances de voyage par date pour la compagnie de l'utilisateur connecté
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVoyageInstancesByDate(Request $request)
    {
        try {
            $date = $request->query('date');
            \Log::info("Fetching voyages for date: {$date}");
            
            $voyageInstances = $this->voyageTicketService->getVoyageInstancesByDate($date);
            \Log::info("Found " . count($voyageInstances) . " voyage instances");

            $voyageInstances->load([
                'voyage.trajet.depart',
                'voyage.trajet.arriver',
                'voyage.compagnie',
                'care',
                'chauffer',
                'tickets',
            ]);

            $data = $voyageInstances->map(fn($instance) => $this->formatInstance($instance));

            return response()->json([
                'success' => true,
                'message' => 'Voyages récupérés avec succès',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des voyages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retourne le détail d'une instance de voyage
     */
    public function getVoyageInstanceDetail(string $voyageInstance): JsonResponse
    {
        try {
            $instance = $this->findInstanceOfCompagnie($voyageInstance);
            $instance->load(['voyage.trajet.depart', 'voyage.trajet.arriver', 'voyage.compagnie', 'care', 'chauffer', 'tickets']);

            return response()->json([
                'success' => true,
                'data'    => $this->formatInstance($instance),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Voyage introuvable'], 404);
        }
    }

    /**
     * Retourne les statistiques d'embarquement d'une instance de voyage
     */
    public function getVoyageStats(string $voyageInstance): JsonResponse
    {
        try {
            $instance = $this->findInstanceOfCompagnie($voyageInstance);
            $tickets  = $instance->tickets()
                ->whereIn('statut', [
                    StatutTicket::Payer,
                    StatutTicket::Valider,
                    StatutTicket::Pause,
                    StatutTicket::Bloquer,
                    StatutTicket::Annuler,
                ])
                ->get();

            $total     = $tickets->count();
            $boarded   = $tickets->where('statut', StatutTicket::Valider)->count();
            $pending   = $tickets->whereIn('statut', [StatutTicket::Payer, StatutTicket::Pause])->count();
            $cancelled = $tickets->whereIn('statut', [StatutTicket::Annuler, StatutTicket::Bloquer])->count();
            $absent    = max(0, $total - $boarded - $pending - $cancelled);

            return response()->json([
                'success' => true,
                'data'    => [
                    'total'      => $total,
                    'boarded'    => $boarded,
                    'pending'    => $pending,
                    'absent'     => $absent,
                    'cancelled'  => $cancelled,
                    'percentage' => $total > 0 ? round($boarded / $total * 100, 1) : 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Voyage introuvable'], 404);
        }
    }

    private function formatInstance(VoyageInstance $instance): array
    {
        $voyage = $instance->voyage;
        $trajet = $voyage?->trajet;
        $boarded = $instance->tickets ? $instance->tickets->filter(fn($t) => $t->statut === StatutTicket::Valider)->count() : 0;
        $total   = $instance->tickets ? $instance->tickets->filter(fn($t) => in_array($t->statut, [StatutTicket::Payer, StatutTicket::Valider, StatutTicket::Pause]))->count() : 0;

        return [
            'id'             => $instance->id,
            'numero_voyage'  => $voyage?->reference ?? $instance->id,
            'departure_time' => $instance->date->format('Y-m-d') . 'T' . ($instance->heure ? $instance->heure->format('H:i:s') : '00:00:00'),
            'status'         => $this->mapStatut($instance->statut),
            'total_seats'    => $instance->nb_place,
            'boarded_count'  => $boarded,
            'ticket_count'   => $total,
            'departure'      => [
                'id'   => $trajet?->depart?->id ?? 0,
                'name' => $trajet?->depart?->name ?? '',
            ],
            'arrival'        => [
                'id'   => $trajet?->arriver?->id ?? 0,
                'name' => $trajet?->arriver?->name ?? '',
            ],
            'compagnie'      => [
                'id'   => $voyage?->compagnie?->id ?? 0,
                'name' => $voyage?->compagnie?->name ?? '',
                'logo' => $voyage?->compagnie?->logo ?? null,
            ],
            'vehicle'        => $instance->care ? [
                'id'              => $instance->care->id,
                'immatriculation' => $instance->care->immatrculation,
                'capacity'        => $instance->care->number_place,
                'numero'          => $instance->care->numero,
            ] : null,
            'driver'         => $instance->chauffer ? [
                'id'    => $instance->chauffer->id,
                'name'  => trim(($instance->chauffer->first_name ?? '') . ' ' . ($instance->chauffer->last_name ?? '')),
                'phone' => $instance->chauffer->telephone ?? null,
            ] : null,
            'created_at'     => $instance->created_at,
            'updated_at'     => $instance->updated_at,
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

    /**
     * Récupère les tickets pour une instance de voyage donnée
     *
     * @param string $voyageInstanceId
     * @return JsonResponse
     */
    public function getTicketsByVoyageInstance($voyageInstance)
    {
        try {
            $tickets = $this->voyageTicketService->getTicketsByVoyageInstance($voyageInstance);

            return response()->json($tickets);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
