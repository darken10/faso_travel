<?php

namespace App\Mail\ticket;

use App\Enums\TypeNotification;
use App\Models\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketNotificationMail extends Mailable
{


    public function __construct(
        public Ticket           $ticket,
        public TypeNotification $type,
        public string           $recipient,
        public string           $title   = "",
        public string           $message = "",
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function build()
    {
        $this->to($this->recipient)
            ->subject($this->title)
            ->from(config('mail.from.address'));

        switch ($this->type) {
            case TypeNotification::TICKET_MISE_PAUSE:
                return $this->view('emails.mise-pause-ticket-email', ['ticket' => $this->ticket]);
            case TypeNotification::TICKET_CLOSED:
                return $this->view('emails.close-ticket-email', ['ticket' => $this->ticket]);
            case TypeNotification::TICKET_ACTIVE:
                return $this->view('emails.active-ticket-email', ['ticket' => $this->ticket]);
            case TypeNotification::TICKET_SENDED:
                return $this->view('emails.sended-ticket-email', ['ticket' => $this->ticket]);
            case TypeNotification::TICKET_RECEIVED:
                return $this->view('emails.received-ticket-email', ['ticket' => $this->ticket])
                    ->attach(storage_path(getenv("STORAGE_DIR") . $this->ticket->pdf_uri), [
                        'mime' => 'application/pdf',
                        'as'   => 'ticket.pdf',
                    ]);
            case TypeNotification::TICKET_VALIDATED:
                return $this->view('emails.validated-ticket-email', ['ticket' => $this->ticket]);
            case TypeNotification::TICKET_REPORTED:
                return $this->view('emails.reported-ticket-email', ['ticket' => $this->ticket]);
            case TypeNotification::TICKET_UPDATED:
                return $this->view('emails.updated-ticket-email', ['ticket' => $this->ticket])
                    ->attach(storage_path(getenv("STORAGE_DIR") . $this->ticket->pdf_uri), [
                        'mime' => 'application/pdf',
                        'as'   => 'ticket.pdf',
                    ]);
            case TypeNotification::TICKET_PAYER:
                return $this->view('mail.ticket.ticket-mail', ['ticket' => $this->ticket])
                    ->attach(storage_path(getenv("STORAGE_DIR") . $this->ticket->pdf_uri), [
                        'mime' => 'application/pdf',
                        'as'   => 'ticket.pdf',
                    ]);
            case TypeNotification::TICKET_REDELIVERED:
                return $this->view('emails.redelivred-ticket-email', ['ticket' => $this->ticket])
                    ->attach(storage_path(getenv("STORAGE_DIR") . $this->ticket->pdf_uri), [
                        'mime' => 'application/pdf',
                        'as'   => 'ticket.pdf',
                    ]);
            case TypeNotification::TICKET_REGENERATED:{
                return $this->view('emails.regenerated-ticket-email', ['ticket' => $this->ticket])
                    ->attach(storage_path(getenv("STORAGE_DIR") . $this->ticket->pdf_uri), [
                        'mime' => 'application/pdf',
                        'as'   => 'ticket.pdf',
                    ]);
            }

            case TypeNotification::VOYAGE_ANNULE:
                return $this->view('emails.voyage-annule-email', [
                    'ticket'  => $this->ticket,
                    'message' => $this->message,
                ]);

            case TypeNotification::VOYAGE_RETARDE:
                return $this->view('emails.voyage-retarde-email', [
                    'ticket'  => $this->ticket,
                    'message' => $this->message,
                ]);

            case TypeNotification::PayerTicket:
            case TypeNotification::UpdateTicket:
            case TypeNotification::TransactionTicket:
            case TypeNotification::RecevoirTicket:
            case TypeNotification::CreationPost:
                throw new \Exception('notification de non implementer');
        }
    }

}
