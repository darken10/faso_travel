<?php

namespace App\Helper\Payement;

use App\Features\Payement\PaymentGatewayFactory;

class PaymentHelper
{
    public function __construct(protected PaymentGatewayFactory $paymentGatewayFactory) {}

    public function processPayment(string $provider, float $amount, array $paymentDetails)
    {
        $paymentGateway = $this->paymentGatewayFactory->getPaymentGateway($provider);
        $paymentGateway->processPayment($amount, $paymentDetails);

        // Logique supplémentaire après le traitement du paiement
    }
}
