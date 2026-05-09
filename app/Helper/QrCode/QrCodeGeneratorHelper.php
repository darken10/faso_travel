<?php

namespace App\Helper\QrCode;

use App\Services\Ticket\QrCodeService;

/**
 * @deprecated Utiliser App\Services\Ticket\QrCodeService directement.
 * Cette classe est conservée pour la compatibilité ascendante uniquement.
 */
class QrCodeGeneratorHelper
{
    /**
     * Retourne un data URI PNG en mémoire (plus d'écriture disque).
     *
     * @deprecated Injecter QrCodeService et appeler ->dataUri()
     */
    public static function generate(string $code): string
    {
        return app(QrCodeService::class)->dataUri($code);
    }
}
