<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FocalPointApplicationVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Dataphyte Foundation'),
            subject: 'Verify your State Coordinator application email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.foundation.focal-point-application-verification',
            with: ['logoUrl' => asset('assets/foundation/Dataphyte%20Foundation%20Logo.png')],
        );
    }
}
