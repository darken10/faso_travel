<?php

namespace App\Notifications\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepartureReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public VoyageInstance $instance) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', \App\Notifications\Channels\ExpoChannel::class];
    }

    private function trajet(): string
    {
        return ($this->instance->villeDepart()?->name ?? '—') . ' → ' . ($this->instance->villeArrive()?->name ?? '—');
    }

    private function heure(): string
    {
        return Carbon::parse($this->instance->heure)->format('H\hi');
    }

    public function toExpo(object $notifiable): array
    {
        $gare = $this->instance->gareDepart()?->name;

        return [
            'title' => 'Départ imminent',
            'body'  => "Votre voyage {$this->trajet()} part à {$this->heure()}" . ($gare ? " — gare {$gare}." : '.'),
            'data'  => ['type' => 'departure_reminder', 'ticket_id' => $this->ticket->id, 'instance_id' => $this->instance->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $gare = $this->instance->gareDepart()?->name;

        return (new MailMessage)
            ->subject("Rappel : départ à {$this->heure()} — {$this->trajet()}")
            ->greeting('Bonjour,')
            ->line("Votre voyage {$this->trajet()} (ticket {$this->ticket->numero_ticket}) part aujourd'hui à {$this->heure()}.")
            ->when($gare, fn ($m) => $m->line("Gare de départ : {$gare}."))
            ->line('Présentez-vous au moins 30 minutes avant le départ. Bon voyage !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'departure_reminder',
            'ticket_id'   => $this->ticket->id,
            'instance_id' => $this->instance->id,
            'title'       => 'Départ imminent',
            'message'     => "Votre voyage {$this->trajet()} part à {$this->heure()}.",
        ];
    }
}
