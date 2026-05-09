<?php

namespace App\Mail\ticket;

use App\Enums\TypeNotification;
use App\Models\Ticket\Ticket;
use App\Services\Ticket\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket           $ticket,
        public TypeNotification $type,
        public string           $recipient,
        public string           $title   = '',
        public string           $message = '',
    ) {}

    /**
     * @throws \Exception
     */
    public function build(): static
    {
        $this->to($this->recipient)
            ->subject($this->title)
            ->from(config('mail.from.address'));

        // URL publique vers le QR généré à la volée — compatible tous clients mail.
        $qrImage  = route('ticket.qrcode.image', $this->ticket->code_qr);
        $viewData = ['ticket' => $this->ticket, 'qrImage' => $qrImage];

        return match ($this->type) {
            TypeNotification::TICKET_MISE_PAUSE  => $this->view('emails.mise-pause-ticket-email', $viewData),

            TypeNotification::TICKET_CLOSED      => $this->view('emails.close-ticket-email', $viewData),

            TypeNotification::TICKET_ACTIVE      => $this->view('emails.active-ticket-email', $viewData),

            TypeNotification::TICKET_SENDED      => $this->view('emails.sended-ticket-email', $viewData),

            TypeNotification::TICKET_VALIDATED   => $this->view('emails.validated-ticket-email', $viewData),

            TypeNotification::TICKET_REPORTED    => $this->view('emails.reported-ticket-email', $viewData),

            TypeNotification::VOYAGE_ANNULE      => $this->view('emails.voyage-annule-email', [
                'ticket'  => $this->ticket,
                'message' => $this->message,
            ]),

            TypeNotification::VOYAGE_RETARDE     => $this->view('emails.voyage-retarde-email', [
                'ticket'  => $this->ticket,
                'message' => $this->message,
            ]),

            // Cas avec QR dans le corps + PDF en pièce jointe.
            TypeNotification::TICKET_RECEIVED    => $this->view('emails.received-ticket-email', $viewData)
                ->attachData($this->pdfContent(), 'ticket.pdf', ['mime' => 'application/pdf']),

            TypeNotification::TICKET_UPDATED     => $this->view('emails.updated-ticket-email', $viewData)
                ->attachData($this->pdfContent(), 'ticket.pdf', ['mime' => 'application/pdf']),

            TypeNotification::TICKET_PAYER       => $this->view('mail.ticket.ticket-mail', $viewData)
                ->attachData($this->pdfContent(), 'ticket.pdf', ['mime' => 'application/pdf']),

            TypeNotification::TICKET_REDELIVERED => $this->view('emails.redelivred-ticket-email', $viewData)
                ->attachData($this->pdfContent(), 'ticket.pdf', ['mime' => 'application/pdf']),

            TypeNotification::TICKET_REGENERATED => $this->view('emails.regenerated-ticket-email', $viewData)
                ->attachData($this->pdfContent(), 'ticket.pdf', ['mime' => 'application/pdf']),

            TypeNotification::PayerTicket,
            TypeNotification::UpdateTicket,
            TypeNotification::TransactionTicket,
            TypeNotification::RecevoirTicket,
            TypeNotification::CreationPost       => throw new \Exception('notification non implémentée : ' . $this->type->name),
        };
    }

    private function pdfContent(): string
    {
        return app(PdfService::class)->output($this->ticket);
    }
}
