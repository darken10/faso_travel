<?php

namespace App\Http\Controllers\Ticket\Payement;

use App\Enums\PaymentProvider;
use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Events\PayementEffectuerEvent;
use App\Events\SendClientTicketByMailEvent;
use App\Features\Payement\PaymentGatewayFactory;
use App\Http\Controllers\Controller;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController2 extends Controller
{
    public function __construct(private readonly PaymentGatewayFactory $paymentGatewayFactory) {}

    public function processPayment(Ticket $ticket, string $provider)
    {
        $paymentProvider = PaymentProvider::tryFrom($provider);

        if (!$paymentProvider) {
            abort(400, 'Provider de paiement invalide.');
        }

        $gateway = $this->paymentGatewayFactory->getPaymentGateway($paymentProvider);
        $amount  = (int) $ticket->voyageInstance?->getPrix($ticket->type) ?? 0;
        $result  = $gateway->processPayment($amount, $ticket, auth()->user());

        if (is_string($result) && str_starts_with($result, 'https://')) {
            return redirect($result);
        }

        if ($result) {
            return response()->json(['message' => 'Paiement initié avec succès.'], 200);
        }

        return response()->json(['message' => 'Échec du paiement. Veuillez réessayer.'], 400);
    }

    public function successFunction(Request $request, Ticket $ticket, string $provider)
    {
        $paymentProvider = PaymentProvider::tryFrom($provider);

        if (!$paymentProvider) {
            return to_route('voyage.index')->with('error', 'Provider de paiement invalide.');
        }

        $gateway  = $this->paymentGatewayFactory->getPaymentGateway($paymentProvider);
        $payement = $ticket->payements()->latest()->first();

        if (!$payement) {
            return to_route('voyage.index')->with('error', 'Aucun paiement trouvé pour ce ticket.');
        }

        try {
            DB::beginTransaction();

            $statut = $gateway->getStatus(['token' => $payement->token]);
            $payement->update(['statut' => $statut]);

            if ($statut === StatutPayement::Complete) {
                $ticket->statut = StatutTicket::Payer;
                $ticket->save();
                DB::commit();

                try {
                    PayementEffectuerEvent::dispatch($ticket);
                    SendClientTicketByMailEvent::dispatch($ticket);
                } catch (\Throwable $e) {
                    Log::error('[Payment] Notifications post-paiement échouées pour ticket ' . $ticket->id . ': ' . $e->getMessage());
                }

                return to_route('ticket.show-ticket', ['ticket' => $ticket])
                    ->with('success', 'Votre ticket a été envoyé par email.');
            }

            DB::commit();

            return to_route('voyage.index')->with('error', 'Le paiement n\'a pas pu être confirmé. Statut : ' . $statut->value);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Payment] Erreur success callback pour ticket ' . $ticket->id . ': ' . $e->getMessage());

            return to_route('voyage.index')->with('error', 'Une erreur est survenue lors de la confirmation du paiement.');
        }
    }

    public function cancelFunction(Request $request, Ticket $ticket)
    {
        return to_route('voyage.index')->with('error', 'Votre paiement a été annulé.');
    }

    public function callbackFunction(Request $request, Ticket $ticket, string $provider)
    {
        $paymentProvider = PaymentProvider::tryFrom($provider);

        if (!$paymentProvider) {
            Log::warning('[Payment Callback] Provider invalide reçu : ' . $provider);
            return response()->json(['received' => false], 400);
        }

        try {
            $gateway = $this->paymentGatewayFactory->getPaymentGateway($paymentProvider);
            $statut  = $gateway->getStatus($request->all());

            DB::transaction(function () use ($ticket, $statut) {
                $payement = $ticket->payements()->latest()->first();
                if ($payement) {
                    $payement->update(['statut' => $statut]);
                }

                if ($statut === StatutPayement::Complete && $ticket->statut !== StatutTicket::Payer) {
                    $ticket->statut = StatutTicket::Payer;
                    $ticket->save();
                    PayementEffectuerEvent::dispatch($ticket);
                }
            });

            return response()->json(['received' => true], 200);
        } catch (\Throwable $e) {
            Log::error('[Payment Callback] Erreur pour ticket ' . $ticket->id . ': ' . $e->getMessage());
            return response()->json(['received' => false], 500);
        }
    }
}
