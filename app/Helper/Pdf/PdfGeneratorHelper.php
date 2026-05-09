<?php

namespace App\Helper\Pdf;

use App\Models\Ticket\Ticket;
use App\Services\Ticket\PdfService;

/**
 * @deprecated Utiliser App\Services\Ticket\PdfService directement.
 * Cette classe est conservée pour la compatibilité ascendante uniquement.
 */
class PdfGeneratorHelper
{
    /**
     * Retourne le contenu binaire du PDF en mémoire (plus d'écriture disque).
     * Le paramètre $qrCodePath est ignoré — le QR est généré à la volée depuis ticket->code_qr.
     *
     * @deprecated Injecter PdfService et appeler ->output() / ->download() / ->stream()
     */
    public static function generate(mixed $qrCodePath, Ticket $ticket): string
    {
        return app(PdfService::class)->output($ticket);
    }
}
