<?php

namespace App\Mail;

use App\Models\Organisation;
use App\Models\Subscriber;
use App\Services\Newsletter\NewsletterPublicUrlService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionManagementLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber,
        public readonly Organisation $organisation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Manage your '.$this->organisation->name.' subscription');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscriptions.management-link', with: [
            'organisationName' => $this->organisation->name,
            'preferencesUrl' => app(NewsletterPublicUrlService::class)->preferencesUrl(
                $this->subscriber,
                $this->organisation->primary_collection_handle,
                $this->organisation,
            ),
        ]);
    }
}
