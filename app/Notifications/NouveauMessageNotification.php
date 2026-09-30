<?php

namespace App\Notifications;

use App\Models\Messages\Message;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Prévient un client, sur son téléphone, qu'une compagnie a répondu à son message.
 *
 * Mise en file : l'envoi appelle un service externe (Expo) et ne doit jamais faire
 * attendre la personne qui vient de répondre.
 */
class NouveauMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public string $expediteur,
    ) {}

    public function via($notifiable): array
    {
        return [ExpoChannel::class];
    }

    public function toExpo($notifiable): array
    {
        return [
            'title' => $this->expediteur,
            'body'  => mb_substr($this->message->message, 0, 100),
            'data'  => [
                'type'            => 'message',
                'conversation_id' => $this->message->conversation_id,
            ],
        ];
    }
}
