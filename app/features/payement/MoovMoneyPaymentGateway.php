<?php

namespace App\Features\Payement;

use App\Enums\StatutPayement;
use App\Models\Ticket\Ticket;
use App\Models\User;

class MoovMoneyPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, Ticket $ticket, User $user, array $paymentDetails = []): bool|string
    {
        throw new \RuntimeException('MoovMoneyPaymentGateway n\'est pas encore intégré. Contactez l\'équipe de développement.');
    }

    public function getStatus(array $statusPayload): StatutPayement
    {
        throw new \RuntimeException('MoovMoneyPaymentGateway::getStatus() n\'est pas encore intégré.');
    }
}
