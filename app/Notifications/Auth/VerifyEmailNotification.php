<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('Vérifiez votre adresse e-mail — LIPTRA'))
            ->view('emails.auth.verify-email', [
                'user'            => $notifiable,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
