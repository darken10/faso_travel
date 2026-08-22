<?php

namespace App\Notifications\Ticket;

use App\Enums\RappelDepart;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Rappel de départ, décliné selon le palier atteint.
 *
 * Le champ `palier` de `data` est ce qui permet de savoir qu'un passager a déjà
 * reçu ce rappel : c'est la trace lue par {@see \App\Services\Ticket\RappelDepartService}.
 */
class DepartureReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public VoyageInstance $instance,
        public RappelDepart $palier = RappelDepart::AvantDepart,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', \App\Notifications\Channels\ExpoChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->sujet())
            ->view('emails.rappel-depart', [
                'ticket'   => $this->ticket,
                'instance' => $this->instance,
                'palier'   => $this->palier,
                'depart'   => $this->departAt(),
                'passager' => $this->passager(),
            ]);
    }

    public function toExpo(object $notifiable): array
    {
        return [
            'title' => $this->palier->label(),
            'body'  => $this->resume(),
            'data'  => $this->donnees(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->donnees() + [
            'title'   => $this->palier->label(),
            'message' => $this->resume(),
        ];
    }

    /** @return array<string, mixed> */
    private function donnees(): array
    {
        return [
            'type'        => 'departure_reminder',
            'palier'      => $this->palier->value,
            'ticket_id'   => (string) $this->ticket->id,
            'instance_id' => (string) $this->instance->id,
        ];
    }

    private function sujet(): string
    {
        $heure = $this->heure();

        return match ($this->palier) {
            RappelDepart::Veille       => "Demain : {$this->trajet()} a {$heure}",
            RappelDepart::AvantDepart  => "Depart imminent : {$this->trajet()} a {$heure}",
            RappelDepart::Embarquement => "Embarquement en cours : {$this->trajet()}",
        };
    }

    private function resume(): string
    {
        $gare = $this->instance->gareDepart()?->name;
        $suffixe = $gare ? " — gare {$gare}." : '.';

        return match ($this->palier) {
            RappelDepart::Veille       => "Votre voyage {$this->trajet()} part demain à {$this->heure()}{$suffixe}",
            RappelDepart::AvantDepart  => "Votre voyage {$this->trajet()} part à {$this->heure()}{$suffixe}",
            RappelDepart::Embarquement => "L'embarquement pour {$this->trajet()} commence{$suffixe}",
        };
    }

    private function trajet(): string
    {
        return ($this->instance->villeDepart()?->name ?? '—')
            .' → '.($this->instance->villeArrive()?->name ?? '—');
    }

    private function heure(): string
    {
        return $this->instance->heure
            ? Carbon::parse($this->instance->heure)->format('H\hi')
            : '--h--';
    }

    private function departAt(): ?Carbon
    {
        if (! $this->instance->date) {
            return null;
        }

        $heure = $this->instance->heure ? Carbon::parse($this->instance->heure)->format('H:i:s') : '00:00:00';

        return Carbon::parse(Carbon::parse($this->instance->date)->toDateString().' '.$heure);
    }

    private function passager(): string
    {
        if ($this->ticket->autre_personne_id) {
            return $this->ticket->autre_personne->nom ?? 'Passager';
        }

        return $this->ticket->user?->name ?: 'Passager';
    }
}
