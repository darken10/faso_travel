<?php

namespace App\Http\Controllers\Ticket\Payement;

use App\Enums\MoyenPayment;
use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Enums\TypeNotification;
use App\Events\PayementEffectuerEvent;
use App\Events\SendClientTicketByMailEvent;
use App\Helper\Payement\OrangePayementHelper;
use App\Helper\TicketHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\Payement\OrangePayementRequest;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrangePayementController extends Controller
{
    public function paymentPage(Ticket $ticket)
    {
        return view('ticket.ticket.payement.orange', ['ticket' => $ticket]);
    }

    public function payer(OrangePayementRequest $request, Ticket $ticket)
    {
        $data          = $request->validated();
        $prix          = $ticket->voyageInstance->getPrix($ticket->type);
        $orangePayement = new OrangePayementHelper($data['numero'], $data['otp'], $prix);

        if (!$orangePayement->payement()) {
            return back()->with('error', 'Code OTP invalide ou numéro Orange Money incorrect.');
        }

        $payementData = [
            'ticket_id'      => $ticket->id,
            'numero_payment' => $data['numero'],
            'montant'        => $orangePayement->montant,
            'trans_id'       => $orangePayement->transaction_id,
            'token'          => $orangePayement->token,
            'code_otp'       => $data['otp'],
            'statut'         => $orangePayement->payementStatut(),
            'moyen_payment'  => MoyenPayment::ORANGE_MONEY,
            'code_ticket'    => Str::random(12),
        ];

        try {
            DB::beginTransaction();

            $ticket->statut = StatutTicket::Payer;

            // Éviter de créer un doublon de paiement si déjà payé
            $existingPayement = Payement::whereBelongsTo($ticket)
                ->where('statut', StatutPayement::Complete)
                ->first();

            if (!$existingPayement) {
                Payement::create($payementData);
            }

            // Réassigner le siège si collision détectée
            $seatConflict = Ticket::where('voyage_instance_id', $ticket->voyage_instance_id)
                ->where('numero_chaise', $ticket->numero_chaise)
                ->where('statut', StatutTicket::Payer)
                ->where('id', '!=', $ticket->id)
                ->exists();

            if ($seatConflict) {
                $ticket->numero_chaise = TicketHelpers::getNumeroChaise($ticket->voyageInstance);
            }

            $ticket->save();
            DB::commit();

            // Envoyer QR + PDF + email en dehors de la transaction
            try {
                PayementEffectuerEvent::dispatch($ticket);
                SendClientTicketByMailEvent::dispatch($ticket, TypeNotification::TICKET_PAYER);
            } catch (\Throwable $e) {
                Log::error('[Orange] Post-payment notifications failed for ticket ' . $ticket->id . ': ' . $e->getMessage());
            }

            return redirect()->route('ticket.show-ticket', ['ticket' => $ticket])
                ->with('success', 'Paiement effectué avec succès. Votre ticket vous a été envoyé par email.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Orange] Payment failed for ticket ' . $ticket->id . ': ' . $e->getMessage());

            return back()->with('error', "Une erreur est survenue lors de l'enregistrement du paiement. Veuillez réessayer.");
        }
    }
}
