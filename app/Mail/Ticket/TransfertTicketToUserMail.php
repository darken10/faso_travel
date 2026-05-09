<?php

namespace App\Mail\Ticket;

use App\Models\Ticket\Ticket;
use App\Services\Ticket\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransfertTicketToUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function build(): static
    {
        return $this->subject('Transfert de Ticket de Voyage')
            ->view('mail.ticket.transfert-ticket-to-user-mail', [
                'ticket'  => $this->ticket,
                'qrImage' => route('ticket.qrcode.image', $this->ticket->code_qr),
            ])
            ->attachData(
                app(PdfService::class)->output($this->ticket),
                'ticket.pdf',
                ['mime' => 'application/pdf'],
            );
    }
}
