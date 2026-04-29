<?php

namespace App\Helper\Pdf;

use App\Models\Ticket\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfGeneratorHelper{

    /**
     * @param $qrCodePath
     * @param Ticket $ticket
     * @return string|null exemple d'uri  : tickets/qrcode/le_name.pdf
     *
     */
    public static function generate($qrCodePath,Ticket $ticket){

        $name = Str::random(10).'-'.uniqid().date('Y').date('m').date('d').date('h').date('i').date('s');
        $uri = "tickets/pdf/$name.pdf";
        $path = storage_path("app/public/tickets/pdf/$name.pdf");

        // Convert QR code to base64 data URI so DomPDF renders it reliably
        // regardless of filesystem path resolution.
        $qrDataUri = null;
        if ($qrCodePath && file_exists($qrCodePath)) {
            $mime     = mime_content_type($qrCodePath) ?: 'image/png';
            $qrDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($qrCodePath));
        }

        $pdf = Pdf::loadView('ticket.ticket.pdf.ticket',[
            'qrCodePath' => $qrDataUri ?? $qrCodePath,
            'ticket'     => $ticket,
        ])
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'dpi'                       => 150,
            'defaultFont'               => 'DejaVu Sans',
            'isRemoteEnabled'           => false,
            'isHtml5ParserEnabled'      => true,
            'isFontSubsettingEnabled'   => true,
        ]);
        $pdf->save($path);
        if(file_exists($path)){
            return $uri;
        }
        return null;
    }

}
