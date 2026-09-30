<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StatutTicket;
use App\Enums\SyncActionType;
use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Http\Controllers\Controller;
use App\Models\Ticket\Ticket;
use App\Services\Ticket\TicketCommandService;
use App\Services\Ticket\TicketQueryService;
use App\Services\Ticket\TicketSyncService;
use App\Services\Ticket\TicketValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $compagnieId = auth()->user()?->compagnie_id;
        abort_if($compagnieId === null, 403, 'Compte non associé à une compagnie.');

        // Cloisonnement : sans ce scope, n'importe quel agent authentifié pouvait
        // lire le billet d'une compagnie concurrente, code_qr et telephone du
        // passager compris, en devinant ou en scannant simplement son QR.
        $ticket = Ticket::ofCompagnie((int) $compagnieId)
            ->where('code_qr', $ticketCode)
            ->with(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'voyageInstance.voyage.classe', 'autre_personne'])
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
    /**
     * Résout un billet en garantissant qu'il porte sur un voyage opéré par la
     * compagnie de l'agent connecté.
     *
     * Sans cette vérification, un agent pouvait consulter — et surtout valider
     * ou changer le statut — d'un billet vendu par une compagnie concurrente.
     */
    private function findTicketOfCompagnie(string $ticketId): Ticket
    {
        $compagnieId = auth()->user()?->compagnie_id;

        abort_if($compagnieId === null, 403, 'Compte non associé à une compagnie.');

        return Ticket::ofCompagnie((int) $compagnieId)->findOrFail($ticketId);
    }

    public function getTicketById(string $ticketId): JsonResponse
    {
        \Log::info("Fetching ticket by ID: {$ticketId}");
        
        try {
            $ticket = $this->findTicketOfCompagnie($ticketId);
            \Log::info("Ticket found: {$ticket->numero_ticket}");
            
            $ticket->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'voyageInstance.voyage.classe', 'autre_personne']);

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

        $ticket = $this->findTicketOfCompagnie($request->input('ticket_id'));

        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce ticket ne peut pas être validé (statut: ' . $ticket->statut->value . ')',
            ], 422);
        }

        $this->validationService->validate($ticket);
        $ticket->refresh()->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'voyageInstance.voyage.classe']);

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

        $ticket->refresh()->load(['user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'voyageInstance.voyage.classe']);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $this->formatTicket($ticket),
        ]);
    }

    /**
     * Batch sync — rejoue les opérations réalisées hors connexion.
     *
     * Le traitement est idempotent : chaque action porte un identifiant généré par
     * le téléphone (`id`), et une action déjà reçue renvoie son verdict d'origine
     * sans être réexécutée. Un réessai après timeout est donc sans danger.
     *
     * Chaque résultat porte un `status` — applied, already_applied ou rejected —
     * que le client doit lire à la place de l'ancien booléen : « déjà validé » et
     * « échec réel » y étaient indiscernables.
     */
    public function batchSync(Request $request, TicketSyncService $syncService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actions'                        => 'required|array|min:1|max:200',
            'actions.*.id'                   => 'required|string|max:64',
            'actions.*.type'                 => ['required', 'string', Rule::in(SyncActionType::values())],
            // Pas de règle exists : un ticket introuvable doit être journalisé
            // comme refus auditable, pas rejeter le lot entier en 422.
            // L'identifiant ou le QR suffit : un ticket absent du cache du
            // téléphone n'a pas d'identifiant connu, seulement le code scanné.
            'actions.*.ticket_id'            => 'required_without:actions.*.qr_code|nullable|integer|min:1',
            'actions.*.qr_code'              => 'required_without:actions.*.ticket_id|nullable|string|max:128',
            'actions.*.voyage_instance_id'   => 'nullable|uuid',
            'actions.*.device_id'            => 'nullable|string|max:100',
            'actions.*.method'               => 'nullable|string|in:qr,sms',
            'actions.*.verified_offline'     => 'nullable|boolean',
            'actions.*.client_created_at'    => 'nullable|date',
            'actions.*.payload'              => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $agent = $request->user();

        $results = collect($validator->validated()['actions'])->map(function (array $action) use ($syncService, $agent) {
            // L'identifiant client circule sous le nom `id` dans le contrat HTTP
            // et `operation_id` côté journal.
            $action['operation_id'] = $action['id'];

            try {
                $outcome = $syncService->apply($action, $agent);
            } catch (\Throwable $e) {
                Log::error('Batch sync : opération non traitée', [
                    'operation_id' => $action['id'],
                    'error'        => $e->getMessage(),
                ]);

                return [
                    'id'         => $action['id'],
                    'ticket_id'  => $action['ticket_id'] ?? null,
                    'status'     => SyncResult::Rejected->value,
                    'success'    => false,
                    'error_code' => SyncErrorCode::ServerError->value,
                ];
            }

            return [
                'id'         => $action['id'],
                'ticket_id'  => $outcome->journal->ticket_id ?? $action['ticket_id'] ?? null,
                'status'     => $outcome->status()->value,
                // Conservé pour les versions de l'app antérieures au champ status.
                'success'    => $outcome->isSuccess(),
                'error_code' => $outcome->errorCode()?->value,
            ];
        })->all();

        $syncedCount   = collect($results)->where('success', true)->count();
        $rejectedCount = count($results) - $syncedCount;

        return response()->json([
            'success' => true,
            'message' => "$syncedCount opération(s) synchronisée(s), $rejectedCount refusée(s)",
            'data'    => [
                'results'  => $results,
                'synced'   => $syncedCount,
                'failed'   => $rejectedCount,
                'rejected' => $rejectedCount,
            ],
        ]);
    }

    /**
     * Récupérer les passagers (tickets) d'une instance de voyage
     */
    public function getPassengers(string $voyageInstance): JsonResponse
    {
        $compagnieId = auth()->user()?->compagnie_id;
        abort_if($compagnieId === null, 403, 'Compte non associé à une compagnie.');

        // Cloisonnement : l'identifiant d'instance est un uuid, mais rien
        // n'empechait un agent d'en presenter un appartenant a une autre
        // compagnie et d'obtenir la liste de ses passagers avec leurs code_qr.
        $tickets = Ticket::ofCompagnie((int) $compagnieId)
            ->where('voyage_instance_id', $voyageInstance)
            ->whereIn('statut', [
                StatutTicket::Payer,
                StatutTicket::Valider,
                StatutTicket::Pause,
                StatutTicket::Bloquer,
            ])
            ->with(['user', 'autre_personne', 'voyageInstance.voyage.classe'])
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
            $ticket = $this->findTicketOfCompagnie($ticketId);
            $ticket->load(['user', 'autre_personne', 'voyageInstance.voyage.classe']);

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
        $t = $this->formatTicket($ticket);

        return [
            'id'            => $t['id'],
            'ticket_id'     => $t['id'],
            'numero_ticket' => $t['numero_ticket'],
            'name'          => $t['passenger_name'],
            'phone'         => $t['passenger_phone'],
            'seat_number'   => $t['numero_chaise'],
            'qr_code'       => $t['code_qr'],
            'code_sms'      => $ticket->code_sms,
            'status'        => $this->mapPassengerStatus($ticket->statut),
            'boarded_at'    => $t['valider_at'],
            'voyage_id'     => $voyageId ?? $ticket->voyage_instance_id,
            'type'          => $t['type'],
            'classe'        => $t['classe'],
            'date'          => $ticket->date?->format('Y-m-d'),
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
            'valider_at' => $ticket->valider_at,
            'classe' => $voyage?->classe?->name,
            // La colonne d'autre_personnes est `name`, pas `nom` : l'ancien
            // accès renvoyait toujours null, donc 'N/A' pour tout billet acheté
            // au nom d'un tiers.
            'passenger_name' => $isAutre
                ? ($ticket->autre_personne?->name ?? 'N/A')
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
                        // Idem : villes.name, et non villes.nom.
                        'depart' => ['name' => $trajet?->depart?->name],
                        'arriver' => ['name' => $trajet?->arriver?->name],
                    ],
                ] : null,
            ] : null,
        ];
    }
}
