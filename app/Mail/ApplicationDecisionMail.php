<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Lead $lead
     * @param 'approved'|'declined' $decision
     * @param string|null $note  optional human note from the advisor
     */
    public function __construct(
        public Lead $lead,
        public string $decision,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->decision === 'approved'
            ? 'Your Career 360 Consult application is approved'
            : 'Update on your Career 360 Consult application';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.application-decision',
            with: [
                'lead' => $this->lead,
                'decision' => $this->decision,
                'note' => $this->note,
            ],
        );
    }
}
