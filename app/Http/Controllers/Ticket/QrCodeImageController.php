<?php

namespace App\Http\Controllers\Ticket;

use App\Services\Ticket\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sert le QR code en PNG à la volée depuis le code brut (jeton 32 chars).
 * Route publique (sans auth) — utilisée dans les emails pour afficher le QR.
 * Le code_qr est le secret : seuls ceux qui reçoivent l'email connaissent le jeton.
 */
final class QrCodeImageController
{
    public function __invoke(Request $request, string $code, QrCodeService $qrCode): Response
    {
        $png = $qrCode->pngContent($code);

        return response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400, immutable',
        ]);
    }
}
