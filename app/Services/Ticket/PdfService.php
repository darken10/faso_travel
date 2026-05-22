<?php

namespace App\Services\Ticket;

use App\Models\Ticket\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;

/**
 * Génération de PDF tickets entièrement en mémoire.
 * Aucun fichier n'est écrit sur le disque.
 */
final class PdfService
{
    public function __construct(private readonly QrCodeService $qrCode) {}

    /**
     * Retourne le contenu binaire brut du PDF.
     * Utiliser pour les pièces jointes email ou tout autre usage custom.
     */
    public function output(Ticket $ticket): string
    {
        return $this->build($ticket)->output();
    }

    /**
     * Retourne une réponse HTTP de téléchargement direct (Content-Disposition: attachment).
     */
    public function download(Ticket $ticket, string $filename = 'ticket.pdf'): \Illuminate\Http\Response
    {
        return $this->build($ticket)->download($filename);
    }

    /**
     * Retourne une réponse HTTP d'affichage inline dans le navigateur (Content-Disposition: inline).
     */
    public function stream(Ticket $ticket, string $filename = 'ticket.pdf'): \Illuminate\Http\Response
    {
        return $this->build($ticket)->stream($filename);
    }

    private function build(Ticket $ticket): DomPdfDocument
    {
        $qrDataUri = $this->qrCode->dataUri($ticket->code_qr);

        return Pdf::loadView('ticket.ticket.pdf.ticket', [
            'qrCodePath' => $qrDataUri,
            'ticket'     => $ticket,
        ])
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'dpi'                     => 150,
            'defaultFont'             => 'DejaVu Sans',
            'isRemoteEnabled'         => false,
            'isHtml5ParserEnabled'    => true,
            'isFontSubsettingEnabled' => true,
        ]);
    }
}
