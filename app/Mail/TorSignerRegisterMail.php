<?php

namespace App\Mail;

use App\Models\TorFlow;
use App\Services\Tor\TorSignerRegisterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TorSignerRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TorFlow $flow,
        public readonly string $csv,
        public readonly bool $final,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->final ? 'Final signer register: ' : 'Signer register: ').$this->flow->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tor.signer-register');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->csv, app(TorSignerRegisterService::class)->filename($this->flow, $this->final))->withMime('text/csv')];
    }
}
