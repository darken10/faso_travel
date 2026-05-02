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
        $apiKey     = config('services.orange_money.api_key');
        $apiUrl     = config('services.orange_money.api_url');
        $simulation = config('services.orange_money.simulation', !$apiKey || !$apiUrl);

        $transId = $this->generate_transaction_id('OM');
        $token   = $this->createFakeToken();

        if ($simulation) {
            // Simulation locale : accepte tout numéro/OTP sans appel réseau
            $this->transaction_id = $transId;
            $this->token          = $token;

            return [
                'transaction_id' => $this->transaction_id,
                'numero'         => $this->numero,
                'otp'            => $this->otp,
                'token'          => $this->token,
                'montant'        => $this->montant,
            ];
        }

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
