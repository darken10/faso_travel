<?php

namespace App\Http\Controllers\Api\V2;

use Exception;
use App\Enums\TypeTicket;
use App\Enums\MoyenPayment;
use App\Enums\StatutTicket;
use App\Enums\StatutPayement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\Payement;
use App\Models\Voyage\VoyageInstance;
use App\Helper\Payement\OrangePayementHelper;
use App\Http\Controllers\Controller;
use App\Services\V2\TicketService;
use App\Events\PayementEffectuerEvent;
use App\Events\SendClientTicketByMailEvent;
use App\Enums\TypeNotification;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    /**
     * Process an Orange Money payment and create a ticket in a single atomic operation.
     *
     * POST /api/v2/payement/orange-money
     *
     * Body:
     *  - phone_number   : string (required) — numéro Orange Money
     *  - otp            : string (required, 6 chars) — code OTP reçu par SMS
     *  - trip_id        : string (required) — id de l'instance de voyage
     *  - trip_type      : 'one-way'|'round-trip' (required)
     *  - is_for_self    : bool (required) — true = ticket pour soi-même
     *  - passenger_name : string (required_if is_for_self=false)
     *  - passenger_phone: string (required_if is_for_self=false)
     *  - passenger_email: string (required_if is_for_self=false)
     *  - relation       : string (optional)
     */
    public function orangeMoney(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number'    => 'required|string|min:8|max:15',
            'otp'             => 'required|string|size:6',
            'trip_id'         => 'required|string|exists:voyage_instances,id',
            'trip_type'       => 'required|in:one-way,round-trip',
            'is_for_self'     => 'required|boolean',
            'seat_number'     => 'nullable|integer|min:1',
            'passenger_name'  => 'required_if:is_for_self,false|nullable|string|max:255',
            'passenger_phone' => 'required_if:is_for_self,false|nullable|string|max:30',
            'passenger_email' => 'nullable|email|max:255',
            'relation'        => 'nullable|string|max:100',
        ]);

        $voyageInstance = VoyageInstance::with(['care', 'voyage'])->findOrFail($validated['trip_id']);
        $tripType = $validated['trip_type'] === 'round-trip' ? TypeTicket::AllerRetour : TypeTicket::AllerSimple;
        $amount   = (int) $voyageInstance->getPrix($tripType);

        // ── Étape 1 : Valider le paiement Orange Money ──────────────────────────
        $orangeHelper = new OrangePayementHelper(
            $validated['phone_number'],
            $validated['otp'],
            $amount
        );

        $transactionData = $orangeHelper->payement();

        if (!$transactionData) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide ou numéro Orange Money incorrect. Veuillez réessayer.',
            ], 422);
        }

        // ── Étape 2 : Créer le ticket + enregistrer le paiement (atomique) ──────
        DB::beginTransaction();
        try {
            // Vérifier la disponibilité (ré-vérification en contexte transactionnel)
            $placesOccupees = Ticket::where('voyage_instance_id', $voyageInstance->id)
                ->where('statut', '!=', StatutTicket::Annuler)
                ->count();

            $totalSeats = $voyageInstance->nb_place ?: ($voyageInstance->care?->number_place ?? 50);

            if ($placesOccupees >= $totalSeats) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Désolé, ce voyage n\'a plus de places disponibles.',
                ], 409);
            }

            // Vérifier que le siège demandé est libre
            if (!empty($validated['seat_number'])) {
                $siegeDejaPris = Ticket::where('voyage_instance_id', $voyageInstance->id)
                    ->where('statut', '!=', StatutTicket::Annuler)
                    ->where('numero_chaise', $validated['seat_number'])
                    ->exists();

                if ($siegeDejaPris) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'La place n°' . $validated['seat_number'] . ' est déjà réservée. Veuillez choisir une autre place.',
                    ], 409);
                }
            }

            // Créer le ticket
            $ticketData = [
                'voyage_instance_id' => $voyageInstance->id,
                'type'               => $tripType->value,
                'is_for_self'        => $validated['is_for_self'],
                'seat_number'        => $validated['seat_number'] ?? null,
            ];

            if (!$validated['is_for_self']) {
                $nameParts = array_pad(explode(' ', trim($validated['passenger_name'] ?? ''), 2), 2, '');
                $ticketData['autre_personne']  = true;
                $ticketData['first_name']      = $nameParts[0];
                $ticketData['last_name']       = $nameParts[1];
                $ticketData['email']           = $validated['passenger_email'] ?? null;
                $ticketData['numero']          = $validated['passenger_phone'] ?? null;
                $ticketData['lien_relation']   = $validated['relation'] ?? null;
            }

            $ticket = $this->ticketService->createTicket($ticketData);

            // Enregistrer le paiement
            Payement::create([
                'ticket_id'      => $ticket->id,
                'numero_payment' => $validated['phone_number'],
                'montant'        => $amount,
                'trans_id'       => $transactionData['transaction_id'],
                'token'          => $transactionData['token'],
                'code_otp'       => $validated['otp'],
                'statut'         => $orangeHelper->payementStatut(),
                'moyen_payment'  => MoyenPayment::ORANGE_MONEY,
            ]);

            DB::commit();

            // Reload full relations needed for PDF generation and email
            $ticket->load([
                'voyageInstance.voyage.trajet.depart',
                'voyageInstance.voyage.trajet.arriver',
                'voyageInstance.voyage.compagnie',
                'voyageInstance.care',
                'user',
                'autre_personne',
            ]);

            // Generate QR code image → PDF (synchronous listeners), then send by email
            try {
                PayementEffectuerEvent::dispatch($ticket);
                SendClientTicketByMailEvent::dispatch($ticket, TypeNotification::TICKET_PAYER);
            } catch (\Throwable $e) {
                Log::error('[PaymentV2] PDF/email failed for ticket ' . $ticket->id . ': ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement effectué avec succès ! Votre ticket a été envoyé par email.',
                'data'    => [
                    'ticket_id'          => $ticket->id,
                    'code_qr'            => $ticket->code_qr,
                    'numero_chaise'      => $ticket->numero_chaise,
                    'statut'             => $ticket->statut->value ?? $ticket->statut,
                    'montant'            => $amount,
                    'moyen_payment'      => MoyenPayment::ORANGE_MONEY->value,
                    'transaction_id'     => $transactionData['transaction_id'],
                    'depart'             => $voyageInstance->voyage?->trajet?->depart?->name ?? '',
                    'arrivee'            => $voyageInstance->voyage?->trajet?->arriver?->name ?? '',
                    'date_depart'        => $voyageInstance->date,
                    'heure_depart'       => $voyageInstance->heure,
                    'passager'           => $validated['is_for_self']
                        ? Auth::user()?->name
                        : ($validated['passenger_name'] ?? ''),
                ],
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement. Veuillez contacter le support.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
