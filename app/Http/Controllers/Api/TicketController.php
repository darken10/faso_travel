<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketDetailResource;
use App\Http\Resources\TicketResource;
use App\Http\Resources\VoyageInstanceResource;
use App\Services\Ticket\TicketQueryService;
use App\Services\Ticket\TicketCommandService;
use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Models\Voyage\VoyageInstance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketQueryService   $ticketQueryService,
        private readonly TicketCommandService $ticketCommandService,
    ) {}

    // ─── Routes utilisateur ───────────────────────────────────────────────────

    public function getUserTickets(Request $request)
    {
        $status     = $request->input('status');
        $statusEnum = $status ? StatutTicket::tryFrom($status) : null;
        $tickets    = $this->ticketQueryService->getUserTickets($statusEnum);

        return response()->json(['status' => 'success', 'data' => $tickets]);
    }

    public function getUserTicketDetails(string $id)
    {
        $ticket = $this->ticketQueryService->getUserTicketById($id);
        return response()->json(['status' => 'success', 'data' => $ticket]);
    }

    public function createTicket(Request $request)
    {
        $request->validate([
            'voyage_instance_id' => 'required|exists:voyage_instances,id',
            'seat_number'        => 'required|integer|min:1',
            'is_autre_personne'  => 'required|boolean',
            'autre_personne_name'  => 'required_if:is_autre_personne,true|string|max:255',
            'autre_personne_phone' => 'required_if:is_autre_personne,true|string|max:20',
        ]);

        $voyageInstance = VoyageInstance::findOrFail($request->input('voyage_instance_id'));
        $result         = $this->ticketCommandService->createFromVoyageInstance(
            $voyageInstance,
            TypeTicket::AllerSimple
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Ticket créé avec succès.',
            'data'    => $result['ticket'],
        ], 201);
    }

    public function cancelTicket(string $id)
    {
        $ticket = $this->ticketQueryService->getUserTicketById($id);
        $result = $this->ticketCommandService->cancel($ticket);

        return response()->json(['status' => 'success', 'message' => 'Ticket annulé.', 'data' => $result]);
    }

    public function transferTicket(Request $request, string $id)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $ticket    = $this->ticketQueryService->getUserTicketById($id);
        $recipient = User::where('email', $request->email)->firstOrFail();
        $result    = $this->ticketCommandService->transfer($ticket, $recipient, $request->input('password', ''));

        return response()->json(['status' => 'success', 'message' => 'Ticket transféré.', 'data' => $result]);
    }

    public function getTicketQrCode(string $id)
    {
        $ticket = $this->ticketQueryService->getUserTicketById($id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'qr_code'    => $ticket->code_qr,
                'qr_image_url' => $ticket->code_qr_uri ? url($ticket->code_qr_uri) : null,
            ],
        ]);
    }

    // ─── Routes agent de compagnie (protégées par requires.compagnie) ────────

    public function todaysPaidPassengers(): AnonymousResourceCollection
    {
        return TicketResource::collection($this->ticketQueryService->getTodaysPaidPassengers());
    }

    public function todaysValidatedTickets(): AnonymousResourceCollection
    {
        return TicketResource::collection($this->ticketQueryService->getTodaysValidatedTickets());
    }

    public function todayVoyageInstances(): AnonymousResourceCollection
    {
        return VoyageInstanceResource::collection($this->ticketQueryService->getTodayVoyageInstances());
    }

    public function ticketsByVoyageInstance(string $voyageInstanceId): AnonymousResourceCollection
    {
        return TicketResource::collection($this->ticketQueryService->getTicketsByVoyageInstance($voyageInstanceId));
    }

    public function allValidatedTickets(): AnonymousResourceCollection
    {
        return TicketResource::collection($this->ticketQueryService->getAllValidatedTickets());
    }

    public function show(string $id): TicketDetailResource
    {
        return new TicketDetailResource($this->ticketQueryService->getUserTicketById($id));
    }

    public function findByQrCode(string $code): TicketDetailResource
    {
        return new TicketDetailResource($this->ticketQueryService->findByQrCode($code));
    }

    public function findByPhoneAndCode(Request $request): TicketDetailResource
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string',
        ]);

        $ticket = $this->ticketQueryService->findByPhoneAndCode($validated['phone'], $validated['code']);

        return new TicketDetailResource($ticket);
    }
}
