<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class FocalPointApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $fullName,
        public readonly string $reference,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Dataphyte Foundation'),
            subject: 'State Focal Point application received',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.foundation.focal-point-application-received',
            with: ['logoUrl' => asset('assets/foundation/Dataphyte%20Foundation%20Logo.png')],
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'X-Lifecycle-Email' => 'focal_point_application_receipt',
            'X-Focal-Point-Application-Id' => $this->reference,
        ]);
    }
}
