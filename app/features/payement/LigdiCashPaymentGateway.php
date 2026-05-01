<?php

namespace App\Features\Payement;

use App\Enums\MoyenPayment;
use App\Enums\StatutPayement;
use App\Features\Payement\PaymentGatewayInterface;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Ligdicash\Ligdicash;

class LigdiCashPaymentGateway implements PaymentGatewayInterface
{
    protected Ligdicash $ligdiCash;

    public function __construct()
    {
        $this->ligdiCash = new Ligdicash([
            "api_key" => config('payement.ligdicash.api_key'),
            "auth_token" => config('payement.ligdicash.auth_token'),
            "platform" => "live"
        ]);


    }

    public function processPayment(float $amount, Ticket $ticket, User $user, array $paymentDetails = []): bool | string
    {
        // Décrire la facture et le client
        $invoice = $this->ligdiCash->Invoice([
            "currency" => "XOF",
            "description" => "Payement d'un ticket",
            "customer_firstname" => $user->first_name,
            "customer_lastname" => $user->last_name,
            "customer_email" => $user->email,
            "store_name" => "LIPTRA",
            "store_website_url" => config('app.url')
        ]);
        $invoice->addItem([
            "name" => "Ticket de Voyage a {$ticket->compagnie()->name}",
            "description" => "une description du ticket",
            "quantity" => 1,
            "unit_price" => $ticket->prix()
        ]);
        $response = $invoice->payWithRedirection([
            "return_url" => route("controller-payment.success",['provider' => "ligdicash",'ticket' => $ticket]),
            "cancel_url" => route("controller-payment.cancel",['provider' => "ligdicash",'ticket' => $ticket]),
            "callback_url" =>route("controller-payment.callback",['provider' => "ligdicash",'ticket' => $ticket]),
            "custom_data" => [
                "order_id" => $ticket->numero_ticket,
                "customer_id" => $user->email
            ]
        ]);
        Payement::create([
            'ticket_id'=>$ticket->id,
            'montant'=>$ticket->prix(),
            "token"=> $response->token,
            'statut'=> StatutPayement::EnAttente,
            'moyen_payment'=> MoyenPayment::LIGDICASH,
        ]);
        return $response->response_text;

    }


    public function getStatus(array $statusPayload): StatutPayement
    {
        if (array_key_exists('token', $statusPayload)) {
            $transaction = $this->ligdiCash->getTransaction([
                "token" => $statusPayload["token"],
                "type" => "payin" # "payin" ou "client_payout" ou "merchant_payout"
            ]);

            $status = $transaction->status;
            if ($status === "completed") {
                return StatutPayement::Complete;
            } elseif ($status === "pending") {
                return StatutPayement::EnAttente;
            }
        }
      return StatutPayement::Annuler;


    }
}


