<?php

namespace App\Helper\Payement;

use App\Enums\MoyenPayment;
use App\Enums\StatutPayement;
use Illuminate\Support\Str;


class Payement {


    public string $transaction_id;
    public string $token;

    protected function generate_transaction_id(string $moyen): string {
        $this->transaction_id  = $moyen.date('Y').date('m').date('d').'.'.date('H').date('i').'.C'.rand(10000,9999999999);
        return $this->transaction_id;
    }

    protected function createFakeToken():string {
        $this->token = Str::random(128);
        return $this->token;
    }

    public static function verificationPayementStatutByPayementApi(string $token, MoyenPayment $moyenPayement = MoyenPayment::LIGDICASH): StatutPayement
    {
        throw new \RuntimeException(
            'verificationPayementStatutByPayementApi() n\'est pas implémentée. ' .
            'Utilisez les méthodes getStatus() de chaque PaymentGateway.'
        );
    }

}
