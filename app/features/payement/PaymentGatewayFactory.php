<?php

namespace App\Features\Payement;

use App\Enums\PaymentProvider;

class PaymentGatewayFactory
{
    public function getPaymentGateway(PaymentProvider $provider): PaymentGatewayInterface
    {
        return match ($provider) {
            PaymentProvider::ORANGE    => new OrangeMoneyPaymentGateway(),
            PaymentProvider::MOOV      => new MoovMoneyPaymentGateway(),
            PaymentProvider::CORIS     => new CorisMoneyPaymentGateway(),
            PaymentProvider::WAVE      => new WavePaymentGateway(),
            PaymentProvider::LIGDICASH => new LigdiCashPaymentGateway(),
        };
    }
}
