<?php

namespace App\Notifications;

use App\Models\Messages\Message;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient un client qu'une compagnie a répondu à son message : notification push sur
 * son téléphone, et e-mail quand il en a un.
 *
 * Mise en file : l'envoi appelle des services externes (Expo, serveur SMTP) et ne doit
 * jamais faire attendre la personne qui vient de répondre.
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
        $canaux = [ExpoChannel::class];

        if (self::peutRecevoirUnEmail($notifiable)) {
            $canaux[] = 'mail';
        }

        return $canaux;
    }

    /**
     * Les comptes créés avec un simple numéro de téléphone n'ont pas d'e-mail. Une
     * adresse non vérifiée est aussi écartée : elle peut être une faute de frappe, et
     * le message d'une compagnie à un client ne doit pas atterrir dans la boîte d'un
     * inconnu.
     */
    public static function peutRecevoirUnEmail(object $notifiable): bool
    {
        return ! empty($notifiable->email) && $notifiable->email_verified_at !== null;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle réponse de ' . $this->expediteur . ' — LIPTRA')
            ->view('emails.nouveau-message', [
                'user'      => $notifiable,
                'compagnie' => $this->expediteur,
                'texte'     => $this->message->message,
            ]);
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
