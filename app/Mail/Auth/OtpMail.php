<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $otp) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre code de vérification LIPTRA');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.otp', with: ['otp' => $this->otp]);
    }
}
