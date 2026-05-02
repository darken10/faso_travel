<?php

namespace App\Notifications\Ticket;

use App\Enums\TypeNotification;
use App\Mail\ticket\TicketNotificationMail;
use App\Models\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Ticket           $ticket,
        private readonly TypeNotification $type,
        private readonly string           $title,
        private readonly string           $message ="",
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database','mail'];
    }

    /**
     * Get the mail representation of the notification.
     * @throws \Exception
     */
    public function toMail(object $notifiable): TicketNotificationMail
    {
        return new TicketNotificationMail($this->ticket, $this->type, $notifiable->email, $this->title, $this->message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user'=>$notifiable->only(['id']),
            'ticket_id'=> $this->ticket->only(['id']),
            'type'=> $this->type,
            'title'=> $this->title,
            'message'=> $this->message,
        ];
    }
}
