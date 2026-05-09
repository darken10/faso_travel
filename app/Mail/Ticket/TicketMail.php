<?php

namespace App\Mail\Ticket;

use App\Models\Ticket\Ticket;
use App\Services\Ticket\PdfService;
use App\Services\Ticket\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function build(): static
    {
        // Crée la part inline et récupère son CID avant de construire la vue.
        $dataPart = new DataPart(
            app(QrCodeService::class)->pngContent($this->ticket->code_qr),
            'qrcode.png',
            'image/png',
        );
        $dataPart->asInline();
        $cid = $dataPart->getContentId();

        // Attache la part inline au message Symfony final.
        $this->withSymfonyMessage(static function (Email $message) use ($dataPart): void {
            $message->addPart($dataPart);
        });

        return $this->subject('Achat de Ticket')
            ->view('mail.ticket.ticket-mail', [
                'ticket'  => $this->ticket,
                'qrImage' => 'cid:' . $cid,
            ])
            ->attachData(
                app(PdfService::class)->output($this->ticket),
                'ticket.pdf',
                ['mime' => 'application/pdf'],
            );
    }
}
