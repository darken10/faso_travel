<?php

namespace App\Features\Payement;

use App\Enums\StatutPayement;
use App\Models\Ticket\Ticket;
use App\Models\User;

class OrangeMoneyPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, Ticket $ticket, User $user, array $paymentDetails = []): bool|string
    {
        throw new \RuntimeException('OrangeMoneyPaymentGateway::processPayment() n\'est pas encore intégré. Utilisez OrangePayementController pour les paiements Orange Money directs.');
    }

    public function getStatus(array $statusPayload): StatutPayement
    {
        throw new \RuntimeException('OrangeMoneyPaymentGateway::getStatus() n\'est pas encore intégré.');
    }
}
