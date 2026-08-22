<?php

namespace App\Notifications\Ticket;

use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Souhaite bon voyage à un passager déjà embarqué.
 *
 * N'est adressé qu'aux billets scannés par un agent : le voyage a réellement
 * commencé. Le délai après l'embarquement est réglé par chaque compagnie.
 */
class BonVoyageNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', \App\Notifications\Channels\ExpoChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bon voyage ! {$this->trajet()}")
            ->view('emails.bon-voyage', [
                'ticket'   => $this->ticket,
                'instance' => $this->ticket->voyageInstance,
                'passager' => $this->passager(),
                'arrivee'  => $this->arriveeEstimee(),
            ]);
    }

    public function toExpo(object $notifiable): array
    {
        return [
            'title' => 'Bon voyage !',
            'body'  => "Nous vous souhaitons un excellent trajet {$this->trajet()}.",
            'data'  => $this->donnees(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->donnees() + [
            'title'   => 'Bon voyage !',
            'message' => "Nous vous souhaitons un excellent trajet {$this->trajet()}.",
        ];
    }

    /** @return array<string, mixed> */
    private function donnees(): array
    {
        return [
            'type'        => 'bon_voyage',
            'ticket_id'   => (string) $this->ticket->id,
            'instance_id' => (string) $this->ticket->voyage_instance_id,
        ];
    }

    private function trajet(): string
    {
        $instance = $this->ticket->voyageInstance;

        return ($instance?->villeDepart()?->name ?? '—')
            .' → '.($instance?->villeArrive()?->name ?? '—');
    }

    /** Arrivée estimée, à partir de la durée déclarée du voyage. */
    private function arriveeEstimee(): ?Carbon
    {
        $instance = $this->ticket->voyageInstance;
        $duree = $instance?->voyage?->temps;

        if (! $instance?->date || ! $duree) {
            return null;
        }

        $heure = $instance->heure ? Carbon::parse($instance->heure)->format('H:i:s') : '00:00:00';
        $depart = Carbon::parse(Carbon::parse($instance->date)->toDateString().' '.$heure);

        $d = Carbon::parse($duree);

        return $depart->addMinutes($d->hour * 60 + $d->minute);
    }

    private function passager(): string
    {
        if ($this->ticket->autre_personne_id) {
            return $this->ticket->autre_personne->nom ?? 'Passager';
        }

        return $this->ticket->user?->name ?: 'Passager';
    }
}
