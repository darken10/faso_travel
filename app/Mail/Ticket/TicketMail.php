<?php

namespace App\Mail\Ticket;

use App\Models\Ticket\Ticket;
use App\Services\Ticket\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function build(): static
    {
        return $this->subject('Achat de Ticket')
            ->view('mail.ticket.ticket-mail', [
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
