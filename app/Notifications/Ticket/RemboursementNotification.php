<?php

namespace App\Notifications\Ticket;

use App\Models\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RemboursementNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public int $montant) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', \App\Notifications\Channels\ExpoChannel::class];
    }

    public function toExpo(object $notifiable): array
    {
        return [
            'title' => 'Ticket remboursé',
            'body'  => "Votre ticket {$this->ticket->numero_ticket} a été remboursé (" . number_format($this->montant, 0, ',', ' ') . ' XOF).',
            'data'  => ['type' => 'remboursement', 'ticket_id' => $this->ticket->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Remboursement de votre ticket {$this->ticket->numero_ticket}")
            ->greeting('Bonjour,')
            ->line("Suite à l'annulation de votre voyage, votre ticket {$this->ticket->numero_ticket} a été remboursé.")
            ->line('Montant remboursé : ' . number_format($this->montant, 0, ',', ' ') . ' XOF.')
            ->line('Merci de votre confiance.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'remboursement',
            'ticket_id'     => $this->ticket->id,
            'numero_ticket' => $this->ticket->numero_ticket,
            'montant'       => $this->montant,
            'title'         => 'Ticket remboursé',
            'message'       => "Votre ticket {$this->ticket->numero_ticket} a été remboursé ({$this->montant} XOF).",
        ];
    }
}
