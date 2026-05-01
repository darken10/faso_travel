<?php

namespace App\Features\Payement;

use App\Enums\StatutPayement;
use App\Models\Ticket\Ticket;
use App\Models\User;

class WavePaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, Ticket $ticket, User $user, array $paymentDetails = []): bool|string
    {
        throw new \RuntimeException('WavePaymentGateway n\'est pas encore intégré. Contactez l\'équipe de développement.');
    }

    public function getStatus(array $statusPayload): StatutPayement
    {
        throw new \RuntimeException('WavePaymentGateway::getStatus() n\'est pas encore intégré.');
    }
}
