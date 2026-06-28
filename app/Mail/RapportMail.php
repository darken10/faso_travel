<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RapportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $compagnie,
        public array $data,
        public string $periodLabel,
        public string $pdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rapport d'activité — {$this->periodLabel} — {$this->compagnie->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rapport');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, 'rapport.pdf')->withMime('application/pdf'),
        ];
    }
}
