<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FocalPointApplicationResumeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $resumeUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Dataphyte Foundation'),
            subject: 'Resume your State Focal Point application',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.foundation.focal-point-application-resume');
    }
}
