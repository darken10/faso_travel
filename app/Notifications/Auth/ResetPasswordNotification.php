<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expiresInMinutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject(Lang::get('Réinitialisation du mot de passe — LIPTRA'))
            ->view('emails.auth.reset-password', [
                'user'             => $notifiable,
                'resetUrl'         => $resetUrl,
                'expiresInMinutes' => $expiresInMinutes,
            ]);
    }
}
