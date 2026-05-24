<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Ticket\TicketQueryService;
use App\Services\Ticket\TicketCommandService;
use App\DTOs\Ticket\CreateTicketDTO;
use App\DTOs\Ticket\TransferTicketDTO;
use App\Models\Ticket\AutrePersonne;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;
use App\Enums\StatutTicket;
use App\Enums\TypeNotification;
use App\Enums\TypeTicket;
use App\Events\PayementEffectuerEvent;
use App\Events\SendClientTicketByMailEvent;
use App\Events\TranfererTicketToOtherUserEvent;
use App\Helper\TicketHelpers;
use App\Models\Ticket\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function __construct(
        private TicketQueryService $ticketQueryService,
        private TicketCommandService $ticketCommandService,
    ) {
    }

    /**
     * Get all tickets for the authenticated user
     */
    public function getUserTickets(): JsonResponse
    {
        $tickets = $this->ticketQueryService->getUserTickets();
        return response()->json(['success' => true, 'data' => $tickets]);
    }

    /**
     * Get ticket details by ID
     */
    public function getUserTicketDetails(string $ticketId): JsonResponse
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);
        return response()->json(['success' => true, 'data' => $ticket]);
    }

    /**
     * Create a new ticket
     */
    public function createTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'voyage_instance_id' => 'required|exists:voyage_instances,id',
            'type' => 'sometimes|string',
            'autre_personne' => 'sometimes|boolean',
            'nom_autre_personne' => 'required_if:autre_personne,true|string|max:255',
            'prenom_autre_personne' => 'required_if:autre_personne,true|string|max:255',
            'telephone_autre_personne' => 'required_if:autre_personne,true|string|max:20',
        ]);

        $dto = CreateTicketDTO::fromRequest($validated);
        $voyageInstance = VoyageInstance::findOrFail($dto->voyage_instance_id);
        $type = $dto->type ?? \App\Enums\TypeTicket::AllerSimple;

        $result = $this->ticketCommandService->createFromVoyageInstance($voyageInstance, $type);

        return response()->json($result, $result['created'] ? 201 : 200);
    }

    /**
     * Cancel a ticket
     */
    public function cancelTicket(string $ticketId): JsonResponse
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);
        $this->ticketCommandService->cancel($ticket);
        return response()->json(['message' => 'Ticket annulé avec succès.']);
    }

    /**
     * Transfer a ticket to another user (registered or not).
     * Requires the owner's password for security — mirrors the web flow.
     */
    public function transferTicket(Request $request, string $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'recipient_phone' => 'required|string|max:30',
            'recipient_name'  => 'required|string|max:255',
            'password'        => 'required|string',
        ]);

        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);

        // Ownership check
        if (!$ticket->is_my_ticket && $ticket->transferer_a_user_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Ce ticket n\'est plus en votre possession.',
            ], 422);
        }

        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un ticket payé ou en pause peut être transféré.',
            ], 422);
        }

        // Verify current user's password
        if (!Hash::check($validated['password'], Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.',
            ], 422);
        }

        // Determine recipient: registered user (by phone) or new AutrePersonne
        $recipient = User::where('numero', $validated['recipient_phone'])->first();

        DB::beginTransaction();
        try {
            if ($recipient) {
                $ticket->is_my_ticket         = false;
                $ticket->transferer_at        = now();
                $ticket->transferer_a_user_id = $recipient->id;
                $ticket->save();
            } else {
                $nameParts    = array_pad(explode(' ', trim($validated['recipient_name']), 2), 2, '');
                $autrePersonne = AutrePersonne::create([
                    'nom'     => $nameParts[0],
                    'prenom'  => $nameParts[1],
                    'contact' => $validated['recipient_phone'],
                ]);
                $ticket->autre_personne_id    = $autrePersonne->id;
                $ticket->is_my_ticket         = false;
                $ticket->transferer_at        = now();
                $ticket->save();
            }

            TicketHelpers::regenerateTicket($ticket);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[TicketV2] Transfer failed for ticket ' . $ticketId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du transfert.',
            ], 500);
        }

        try {
            TranfererTicketToOtherUserEvent::dispatch($ticket->fresh());
        } catch (\Throwable $e) {
            Log::warning('[TicketV2] Transfer event failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'             => true,
            'message'             => 'Ticket transféré avec succès.',
            'recipient_registered'=> $recipient !== null,
        ]);
    }

    /**
     * Put a ticket on pause (Payé → Pause).
     * Sends a notification email to the owner.
     */
    public function pauseTicket(string $ticketId): JsonResponse
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);

        try {
            $this->ticketCommandService->pause($ticket);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        try {
            SendClientTicketByMailEvent::dispatch($ticket->fresh(), TypeNotification::TICKET_MISE_PAUSE);
        } catch (\Throwable $e) {
            Log::warning('[TicketV2] Pause email failed for ticket ' . $ticketId . ': ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Ticket mis en pause avec succès.']);
    }

    /**
     * List future trips equivalent to a paused ticket (same compagnie + same trajet).
     */
    public function getEquivalentTrips(string $ticketId): JsonResponse
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);

        $trajetId    = $ticket->voyageInstance->voyage->trajet_id;
        $compagnieId = $ticket->voyageInstance->voyage->compagnie_id;

        $instances = VoyageInstance::with(['voyage', 'care'])
            ->whereHas('voyage', fn($q) => $q->where('trajet_id', $trajetId)->where('compagnie_id', $compagnieId))
            ->where('id', '!=', $ticket->voyage_instance_id)
            ->avenir()
            ->orderBy('date')
            ->get();

        $result = $instances->map(function (VoyageInstance $instance) {
            $occupied     = Ticket::where('voyage_instance_id', $instance->id)
                ->where('statut', '!=', StatutTicket::Annuler)
                ->count();
            $total        = $instance->nb_place ?: ($instance->care?->number_place ?? 50);
            $available    = max(0, $total - $occupied);

            return [
                'id'              => $instance->id,
                'date'            => $instance->date?->format('Y-m-d'),
                'heure'           => $instance->heure?->format('H:i'),
                'available_seats' => $available,
                'price'           => $instance->getPrix(TypeTicket::AllerSimple),
            ];
        })->filter(fn($i) => $i['available_seats'] > 0)->values();

        return response()->json($result);
    }

    /**
     * Reactivate a paused ticket on a new equivalent voyage instance.
     */
    public function activateTicket(Request $request, string $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'voyage_instance_id' => 'required|exists:voyage_instances,id',
            'numero_chaise'      => 'required|integer|min:1',
        ]);

        $ticket      = $this->ticketQueryService->getUserTicketById($ticketId);
        $newInstance = VoyageInstance::with('voyage')->findOrFail($validated['voyage_instance_id']);

        try {
            $this->ticketCommandService->activate($ticket, $newInstance, (int) $validated['numero_chaise']);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Ticket réactivé avec succès.']);
    }

    /**
     * Regenerate QR code, SMS code and PDF for a ticket.
     */
    public function regenerateTicket(string $ticketId): JsonResponse
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);

        if (!TicketHelpers::regenerateTicket($ticket)) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la régénération.'], 500);
        }

        try {
            PayementEffectuerEvent::dispatch($ticket->fresh());
        } catch (\Throwable $e) {
            Log::warning('[TicketV2] Regenerate PDF failed for ticket ' . $ticketId . ': ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Ticket régénéré avec succès.']);
    }

    /**
     * Get QR code for a ticket.
     * Retourne le code brut + un data URI PNG généré en mémoire (aucun fichier disque).
     */
    public function getTicketQrCode(string $ticketId): JsonResponse
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);

        return response()->json([
            'qr_code'      => $ticket->code_qr,
            'qr_image_uri' => app(\App\Services\Ticket\QrCodeService::class)->dataUri($ticket->code_qr),
            'pdf_url'      => route('api.v2.tickets.pdf', $ticketId),
        ]);
    }

    /**
     * Téléchargement du PDF ticket généré à la volée (aucun fichier disque).
     */
    public function downloadPdf(string $ticketId): \Illuminate\Http\Response
    {
        $ticket = $this->ticketQueryService->getUserTicketById($ticketId);

        return app(\App\Services\Ticket\PdfService::class)->download(
            $ticket,
            'ticket-' . $ticket->numero_ticket . '.pdf',
        );
    }
}
