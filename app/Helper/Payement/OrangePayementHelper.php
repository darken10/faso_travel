<?php

namespace App\Helper\Payement;

use App\Enums\MoyenPayment;
use App\Enums\StatutPayement;
use App\Models\Ticket\Ticket;

class OrangePayementHelper extends Payement{


    public function __construct(public string $numero, public string $otp,public int $montant=0)
    {}

    public function payement(): array|false
    {
        $apiKey = config('services.orange_money.api_key');
        $apiUrl = config('services.orange_money.api_url');

        if (!$apiKey || !$apiUrl) {
            throw new \RuntimeException(
                'Orange Money API non configurée. Définissez ORANGE_MONEY_API_KEY et ORANGE_MONEY_API_URL dans .env'
            );
        }

        $transId = $this->generate_transaction_id('OM');
        $token   = $this->createFakeToken();

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post($apiUrl . '/payment', [
            'msisdn'   => $this->numero,
            'pin'      => $this->otp,
            'amount'   => $this->montant,
            'order_id' => $transId,
        ]);

        if (!$response->successful()) {
            return false;
        }

        $body = $response->json();

        if (($body['status'] ?? '') !== 'SUCCESS') {
            return false;
        }

        $this->transaction_id = $body['transaction_id'] ?? $transId;
        $this->token          = $body['token'] ?? $token;

        return [
            'transaction_id' => $this->transaction_id,
            'numero'         => $this->numero,
            'otp'            => $this->otp,
            'token'          => $this->token,
            'montant'        => $this->montant,
        ];
    }

    public function payementStatut():StatutPayement{
        return StatutPayement::Complete;
    }


}
