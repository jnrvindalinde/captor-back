<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Career 360 Consult session is booked');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.meeting-scheduled',
            with: ['meeting' => $this->meeting],
        );
    }
}
