<?php

namespace App\Mail;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BugReportSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly BugReport $report)
    {
    }

    public function envelope(): Envelope
    {
        $categoryLabel = [
            'bug'        => '🐛 Bug',
            'payment'    => '💳 Paiement',
            'ticket'     => '🎫 Ticket',
            'suggestion' => '💡 Suggestion',
            'other'      => '📋 Autre',
        ][$this->report->category] ?? $this->report->category;

        return new Envelope(
            subject: "[LIPTRA Support] {$categoryLabel} — {$this->report->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bug-report',
        );
    }
}
