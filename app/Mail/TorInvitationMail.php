<?php

namespace App\Mail;

use App\Models\TorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TorInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly TorInvitation $invitation) {}

    public function envelope(): Envelope
    {
        $o = $this->invitation->flow->organisation;

        return new Envelope(from: new Address(config('mail.from.address'), $o->default_from_name ?: $o->name), subject: 'Please sign: '.$this->invitation->flow->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tor.invitation');
    }

    public function headers(): Headers
    {
        return new Headers(text: ['X-TOR-Invitation-ID' => (string) $this->invitation->id]);
    }
}
