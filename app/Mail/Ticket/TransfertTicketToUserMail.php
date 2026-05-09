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

class TransfertTicketToUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function build(): static
    {
        $dataPart = new DataPart(
            app(QrCodeService::class)->pngContent($this->ticket->code_qr),
            'qrcode.png',
            'image/png',
        );
        $dataPart->asInline();
        $cid = $dataPart->getContentId();

        $this->withSymfonyMessage(static function (Email $message) use ($dataPart): void {
            $message->addPart($dataPart);
        });

        return $this->subject('Transfert de Ticket de Voyage')
            ->view('mail.ticket.transfert-ticket-to-user-mail', [
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
