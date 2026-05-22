<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StatutTicket;
use App\Http\Controllers\Controller;
use App\Models\Ticket\Ticket;
use App\Services\Ticket\TicketCommandService;
use App\Services\Ticket\TicketQueryService;
use App\Services\Ticket\TicketValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function __construct(
        protected TicketValidationService $validationService,
        protected TicketCommandService $commandService,
        protected TicketQueryService $queryService,
    ) {}

    /**
     * Vérifier un ticket par QR code
     */
    public function verifyByQrCode(string $ticketCode): JsonResponse
    {
        $ticket = Ticket::where('code_qr', $ticketCode)
            ->with(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'autre_personne'])
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket introuvable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatTicket($ticket),
        ]);
    }

    /**
     * Get ticket by ID
     */
    public function getTicketById(string $ticketId): JsonResponse
    {
        \Log::info("Fetching ticket by ID: {$ticketId}");
        
        try {
            $ticket = Ticket::findOrFail($ticketId);
            \Log::info("Ticket found: {$ticket->numero_ticket}");
            
            $ticket->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'autre_personne']);

            return response()->json([
                'success' => true,
                'data' => $this->formatTicket($ticket),
            ]);
        } catch (\Exception $e) {
            \Log::error("Ticket not found or error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Ticket introuvable',
            ], 404);
        }
    }

    /**
     * Vérifier un ticket par numéro de téléphone + code SMS
     */
    public function verifyByPhoneAndCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = $this->validationService->searchByNumberAndCodeSMS(
            $request->input('phone'),
            $request->input('code')
        );

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun ticket trouvé avec ces informations',
            ], 404);
        }

        $ticket->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'autre_personne']);

        return response()->json([
            'success' => true,
            'data' => $this->formatTicket($ticket),
        ]);
    }

    /**
     * Valider un ticket
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::findOrFail($request->input('ticket_id'));

        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce ticket ne peut pas être validé (statut: ' . $ticket->statut->value . ')',
            ], 422);
        }

        $this->validationService->validate($ticket);
        $ticket->refresh()->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver']);

        return response()->json([
            'success' => true,
            'message' => 'Ticket validé avec succès',
            'data' => $this->formatTicket($ticket),
        ]);
    }

    /**
     * Changer le statut d'un ticket (pause, block) avec motif obligatoire
     */
    public function changeStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|string|in:Pause,Bloquer',
            'motif' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newStatut = StatutTicket::from($request->input('statut'));

        if ($newStatut === StatutTicket::Pause) {
            $this->validationService->pause($ticket);
        } elseif ($newStatut === StatutTicket::Bloquer) {
            $this->validationService->block($ticket);
        }

        $ticket->refresh()->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver']);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $this->formatTicket($ticket),
        ]);
    }

    /**
     * Batch sync — traite un tableau d'actions offline
     */
    public function batchSync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actions' => 'required|array|min:1',
            'actions.*.id' => 'required|string',
            'actions.*.type' => 'required|string|in:VALIDATE_TICKET,PAUSE_TICKET,BLOCK_TICKET',
            'actions.*.ticket_id' => 'required|integer|exists:tickets,id',
            'actions.*.payload' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];

        foreach ($request->input('actions') as $action) {
            try {
                $ticket = Ticket::findOrFail($action['ticket_id']);
                $success = false;

                DB::beginTransaction();

                switch ($action['type']) {
                    case 'VALIDATE_TICKET':
                        if (in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
                            $success = $this->validationService->validate($ticket);
                        }
                        break;

                    case 'PAUSE_TICKET':
                        $success = $this->validationService->pause($ticket);
                        break;

                    case 'BLOCK_TICKET':
                        $success = $this->validationService->block($ticket);
                        break;
                }

                DB::commit();

                $results[] = [
                    'id' => $action['id'],
                    'success' => $success,
                    'ticket_id' => $action['ticket_id'],
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Batch sync action failed", [
                    'action_id' => $action['id'],
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'id' => $action['id'],
                    'success' => false,
                    'ticket_id' => $action['ticket_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();
        $failedCount = collect($results)->where('success', false)->count();

        return response()->json([
            'success' => true,
            'message' => "$successCount action(s) synchronisée(s), $failedCount échec(s)",
            'data' => [
                'results' => $results,
                'synced' => $successCount,
                'failed' => $failedCount,
            ],
        ]);
    }

    /**
     * Récupérer les passagers (tickets) d'une instance de voyage
     */
    public function getPassengers(string $voyageInstance): JsonResponse
    {
        $tickets = Ticket::where('voyage_instance_id', $voyageInstance)
            ->whereIn('statut', [
                StatutTicket::Payer,
                StatutTicket::Valider,
                StatutTicket::Pause,
                StatutTicket::Bloquer,
            ])
            ->with(['user', 'autre_personne'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Passagers récupérés avec succès',
            'data'    => $tickets->map(fn($t) => $this->formatPassenger($t, $voyageInstance)),
        ]);
    }

    /**
     * Retourne le détail d'un passager (ticket) par son ID
     */
    public function getPassengerByTicket(string $ticketId): JsonResponse
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
            $ticket->load(['user', 'autre_personne']);

            return response()->json([
                'success' => true,
                'data'    => $this->formatPassenger($ticket),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Passager introuvable'], 404);
        }
    }

    private function formatPassenger(Ticket $ticket, ?string $voyageId = null): array
    {
        $isAutre = $ticket->autre_personne_id !== null;

        return [
            'id'          => $ticket->id,
            'ticket_id'   => $ticket->id,
            'name'        => $isAutre ? ($ticket->autre_personne?->nom ?? 'N/A') : ($ticket->user?->name ?? 'N/A'),
            'phone'       => $isAutre ? ($ticket->autre_personne?->numero ?? null) : ($ticket->user?->numero ?? null),
            'seat_number' => $ticket->numero_chaise,
            'qr_code'     => $ticket->code_qr,
            'code_sms'    => $ticket->code_sms,
            'status'      => $this->mapPassengerStatus($ticket->statut),
            'boarded_at'  => $ticket->valider_at,
            'voyage_id'   => $voyageId ?? $ticket->voyage_instance_id,
        ];
    }

    private function mapPassengerStatus(StatutTicket $statut): string
    {
        return match ($statut) {
            StatutTicket::Valider                        => 'boarded',
            StatutTicket::Payer, StatutTicket::Pause     => 'pending',
            default                                      => 'cancelled',
        };
    }

    /**
     * Format a ticket for API response
     */
    private function formatTicket(Ticket $ticket): array
    {
        $isAutre = $ticket->autre_personne_id !== null;
        $instance = $ticket->voyageInstance;
        $voyage = $instance?->voyage;
        $trajet = $voyage?->trajet;

        return [
            'id' => $ticket->id,
            'numero_ticket' => $ticket->numero_ticket,
            'numero_chaise' => $ticket->numero_chaise,
            'date' => $ticket->date,
            'type' => $ticket->type->value,
            'statut' => $ticket->statut->value,
            'code_qr' => $ticket->code_qr,
            'code_sms' => $ticket->code_sms,
            'valider_at' => $ticket->valider_at,
            'passenger_name' => $isAutre
                ? ($ticket->autre_personne?->nom ?? 'N/A')
                : ($ticket->user?->name ?? 'N/A'),
            'passenger_phone' => $isAutre
                ? ($ticket->autre_personne?->numero ?? '')
                : ($ticket->user?->numero ?? ''),
            'voyage_instance' => $instance ? [
                'id' => $instance->id,
                'date' => $instance->date,
                'heure' => $instance->heure,
                'nb_place' => $instance->nb_place,
                'voyage' => $voyage ? [
                    'trajet' => [
                        'depart' => ['name' => $trajet?->depart?->nom],
                        'arriver' => ['name' => $trajet?->arriver?->nom],
                    ],
                ] : null,
            ] : null,
        ];
    }
}
