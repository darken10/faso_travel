<?php

namespace App\Listeners;

use App\Events\CreatedQrCodeEvent;
use App\Events\PayementEffectuerEvent;

/**
 * Le QR code est désormais généré à la volée (in-memory).
 * Ce listener transmet simplement l'événement suivant de la chaîne.
 */
class PayementCreatQrCodeListener
{
    public function handle(PayementEffectuerEvent $event): void
    {
        CreatedQrCodeEvent::dispatch($event->ticket);
    }
}
