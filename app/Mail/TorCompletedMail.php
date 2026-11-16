<?php

namespace App\Mail;

use App\Models\TorInvitation;
use App\Services\Tor\TorPublicUrlService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TorCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly TorInvitation $invitation, public readonly string $copyType = 'signer') {}

    public function envelope(): Envelope
    {
        $organisation = $this->invitation->flow->organisation;
        $subject = $this->copyType === 'organisation'
            ? 'Alert: Signed TOR - '.$this->invitation->flow->title
            : 'Your signed TOR copy: '.$this->invitation->flow->title;

        return new Envelope(
            from: new Address(config('mail.from.address'), $organisation->default_from_name ?: $organisation->name),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tor.completed', with: [
            'copyType' => $this->copyType,
            'downloadUrl' => app(TorPublicUrlService::class)->signedCopyUrl($this->invitation),
        ]);
    }

    public function attachments(): array
    {
        $filename = $this->copyType === 'organisation' ? 'organisation-tor-copy.pdf' : 'signer-original-tor.pdf';

        return [Attachment::fromStorageDisk('local', $this->invitation->signed_pdf_path)->as($filename)->withMime('application/pdf')];
    }
}
