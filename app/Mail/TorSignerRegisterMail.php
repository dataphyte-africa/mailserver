<?php

namespace App\Mail;

use App\Models\TorFlow;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TorSignerRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TorFlow $flow,
        public readonly bool $final,
        public readonly int $signerCount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->final ? 'Final signer register: ' : 'Signer register: ').$this->flow->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tor.signer-register', with: [
            'downloadUrl' => cp_route('tor-flows.signer-register.download', $this->flow),
        ]);
    }
}
