<?php

namespace App\Services\Ticket;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

/**
 * Génération de QR codes entièrement en mémoire.
 * Aucun fichier n'est écrit sur le disque.
 */
final class QrCodeService
{
    public function __construct(
        private readonly int $size   = 200,
        private readonly int $margin = 10,
    ) {}

    /**
     * Retourne un data URI prêt à être injecté dans du HTML/CSS ou un PDF DomPDF.
     * Format : "data:image/png;base64,..."
     */
    public function dataUri(string $data): string
    {
        return $this->build($data)->getDataUri();
    }

    /**
     * Retourne les octets bruts du PNG (pour un stream HTTP ou un email).
     */
    public function pngContent(string $data): string
    {
        return $this->build($data)->getString();
    }

    private function build(string $data): ResultInterface
    {
        // Silencer les E_DEPRECATED de la lib Endroid (signatures PHP 8.1+ non mises à jour).
        $prev = error_reporting(error_reporting() & ~E_DEPRECATED);
        try {
            return Builder::create()
                ->writer(new PngWriter())
                ->data($data)
                ->size($this->size)
                ->margin($this->margin)
                ->build();
        } finally {
            error_reporting($prev);
        }
    }
}
