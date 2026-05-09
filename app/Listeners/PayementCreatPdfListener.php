<?php

namespace App\Listeners;

use App\Events\CreatedQrCodeEvent;

/**
 * Le PDF est désormais généré à la volée (in-memory) au moment de l'envoi email
 * ou du téléchargement. Ce listener ne fait plus aucune écriture disque.
 */
class PayementCreatPdfListener
{
    public function handle(CreatedQrCodeEvent $event): void
    {
        // Rien à persister : PdfService génère le PDF en mémoire à la demande.
    }
}
