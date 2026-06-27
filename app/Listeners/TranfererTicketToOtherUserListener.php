<?php

namespace App\Listeners;

use App\Enums\TypeNotification;
use App\Events\PayementEffectuerEvent;
use App\Events\TranfererTicketToOtherUserEvent;
use App\Helper\TicketHelpers;
use App\Mail\Ticket\TicketMail;
use App\Mail\ticket\TicketNotificationMail;
use App\Mail\Ticket\TransfertTicketToOrherUserMyMail;
use App\Mail\Ticket\TransfertTicketToUserMail;
use App\Notifications\Ticket\TicketNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class TranfererTicketToOtherUserListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TranfererTicketToOtherUserEvent $event): void
    {
        PayementEffectuerEvent::dispatch($event->ticket);
        $email = TicketHelpers::getEmailToSendMail($event->ticket);

        // Notifie l'expéditeur que son ticket a bien été transféré
        $event->sender?->notify(new TicketNotification($event->ticket, TypeNotification::TICKET_SENDED, "Ticket {$event->ticket->numero_ticket} transféré"));

        // Envoie le ticket au destinataire (avec le nom de l'expéditeur dans le mail)
        Mail::to($email)->send(new TicketNotificationMail(
            $event->ticket,
            TypeNotification::TICKET_RECEIVED,
            $email,
            "Réception du ticket {$event->ticket->numero_ticket}",
            '',
            $event->sender?->name,
        ));
        /*
        Mail::to($event->ticket->user->email)->send(new TransfertTicketToOrherUserMyMail($event->ticket));*/
    }
}
