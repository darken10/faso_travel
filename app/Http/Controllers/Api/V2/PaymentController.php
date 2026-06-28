<?php

namespace App\Http\Controllers\Api\V2;

use Exception;
use App\Enums\TypeTicket;
use App\Enums\MoyenPayment;
use App\Enums\StatutTicket;
use App\Enums\StatutPayement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Enums\LienRelationAutrePersonneTicket;
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
            'passenger_first_name'         => 'required_if:is_for_self,false|nullable|string|max:255',
            'passenger_last_name'          => 'required_if:is_for_self,false|nullable|string|max:255',
            'passenger_sexe'               => 'required_if:is_for_self,false|nullable|string|in:Homme,Femme,Autre',
            'passenger_lien_relation'      => ['required_if:is_for_self,false', 'nullable', 'string', Rule::in(\App\Enums\LienRelationAutrePersonneTicket::values())],
            'passenger_email'              => 'nullable|email|max:255',
            'passenger_numero_identifiant' => 'nullable|string|max:10',
            'passenger_phone'              => 'nullable|string|max:30',
            'passenger_note'               => 'nullable|string|max:500',
            'promo_code'                   => 'nullable|string|max:50',
        ], [
            'passenger_first_name.required_if'    => 'Le prénom du passager est requis.',
            'passenger_last_name.required_if'     => 'Le nom du passager est requis.',
            'passenger_sexe.required_if'          => 'Le sexe du passager est requis.',
            'passenger_lien_relation.required_if' => 'Le lien avec le passager est requis.',
        ]);

        $voyageInstance = VoyageInstance::with(['care', 'voyage'])->findOrFail($validated['trip_id']);
        $tripType = $validated['trip_type'] === 'round-trip' ? TypeTicket::AllerRetour : TypeTicket::AllerSimple;
        $amount   = (int) $voyageInstance->getPrix($tripType);

        // ── Code promo (facultatif) : appliqué avant la charge ──────────────────
        $promo = null;
        $reduction = 0;
        if (!empty($validated['promo_code'])) {
            $promo = \App\Models\Finance\PromoCode::where('compagnie_id', $voyageInstance->voyage->compagnie_id)
                ->where('code', strtoupper(trim($validated['promo_code'])))
                ->first();
            if ($promo && $promo->isValide($amount)) {
                $reduction = $promo->reductionPour($amount);
                $amount = max(0, $amount - $reduction);
            } else {
                $promo = null; // code invalide → ignoré
            }
        }

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
                // Le numéro est stocké en chiffres uniquement (colonne entière) ;
                // l'indicatif (+226…) est conservé à part dans numero_identifiant.
                $rawPhone   = preg_replace('/\D/', '', $validated['passenger_phone'] ?? '');
                $localPhone = strlen($rawPhone) > 8 ? ltrim(substr($rawPhone, -8), '0') : $rawPhone;

                $ticketData['autre_personne']     = true;
                $ticketData['first_name']         = $validated['passenger_first_name'] ?? '';
                $ticketData['last_name']          = $validated['passenger_last_name'] ?? '';
                $ticketData['sexe']               = $validated['passenger_sexe'] ?? null;
                $ticketData['email']              = $validated['passenger_email'] ?? null;
                $ticketData['numero']             = $localPhone !== '' ? (int) $localPhone : null;
                $ticketData['numero_identifiant'] = $validated['passenger_numero_identifiant'] ?? '+226';
                $ticketData['lien_relation']      = $validated['passenger_lien_relation'] ?? null;
                $ticketData['note']               = $validated['passenger_note'] ?? null;
            }

            $ticket = $this->ticketService->createTicket($ticketData);

            // Applique la réduction promo sur le ticket.
            if ($promo) {
                $ticket->update(['promo_code_id' => $promo->id, 'reduction' => $reduction]);
                $promo->increment('used_count');
            }

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

            // Points de fidélité au client (acheteur) pour cet achat.
            try {
                if ($user = $request->user()) {
                    app(\App\Services\Loyalty\LoyaltyService::class)->award($user, (int) $amount, $ticket);
                }
            } catch (\Throwable $e) {
                Log::warning('[PaymentV2] attribution points fidélité échouée : ' . $e->getMessage());
            }

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
