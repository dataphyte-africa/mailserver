<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SubscriptionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber,
        public readonly string $status,
        public readonly array $mailConfig,
    ) {}

    public function envelope(): Envelope
    {
        $sender = $this->mailConfig['sender'] ?? [];
        $replyTo = filled($sender['reply_to'] ?? null)
            ? [new Address($sender['reply_to'])]
            : [];

        return new Envelope(
            from: new Address($sender['from_email'] ?? config('mail.from.address'), $sender['from_name'] ?? config('mail.from.name')),
            replyTo: $replyTo,
            subject: $this->interpolate((string) ($this->mailConfig['subject'] ?? 'Subscription confirmation')),
            tags: ['newsletter-subscription', $this->mailConfig['collection_handle'] ?? 'general', $this->status],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: array_filter([
                'X-Form-Submission-Id' => $this->mailConfig['submission_id'] ?? null,
                'X-Form-Handle' => $this->mailConfig['form_handle'] ?? null,
                'X-Submission-Mode' => $this->mailConfig['submission_mode'] ?? null,
            ], fn ($value) => filled($value)),
        );
    }

    public function content(): Content
    {
        $sender = $this->mailConfig['sender'] ?? [];
        $collectionHandle = $this->mailConfig['collection_handle'] ?? null;
        $collectionKey = is_string($collectionHandle)
            ? str_replace('_newsletters', '', $collectionHandle)
            : '';
        $collectionConfig = is_string($collectionHandle)
            ? config("newsletter.collections.{$collectionHandle}", [])
            : [];
        $footerPartial = $collectionKey !== ''
            ? 'emails.partials.' . str_replace('_', '-', $collectionKey) . '.footer'
            : 'emails.partials.shared.footer-base';

        return new Content(
            view: 'emails.subscriptions.confirmation',
            with: [
                'subject' => $this->envelope()->subject,
                'preheader' => $this->preheader(),
                'fromName' => $sender['from_name'] ?? config('mail.from.name'),
                'headerColor' => $this->mailConfig['brand_color'] ?? '#1a1a2e',
                'collectionLogo' => $this->resolveAssetUrl($this->mailConfig['logo_url'] ?? null),
                'headline' => $this->headline(),
                'bodyCopy' => $this->interpolate((string) ($this->mailConfig['body'] ?? '')),
                'privacyUrl' => $this->mailConfig['privacy_url'] ?? null,
                'unsubscribeUrl' => \URL::signedRoute('newsletter.unsubscribe.show', array_filter([
                    'token' => $this->subscriber->ensureConfirmationToken(),
                    'collection' => $collectionHandle,
                ], fn ($value) => filled($value))),
                'preferencesUrl' => \URL::signedRoute('newsletter.preferences.show', array_filter([
                    'token' => $this->subscriber->ensureConfirmationToken(),
                    'collection' => $collectionHandle,
                ], fn ($value) => filled($value))),
                'subscriberFirstName' => $this->subscriber->first_name ?? '',
                'subscriberLastName' => $this->subscriber->last_name ?? '',
                'subscriberFullName' => $this->subscriber->full_name ?? $this->subscriber->email,
                'subscriberEmail' => $this->subscriber->email,
                'collectionLabel' => $this->mailConfig['collection_label'] ?? ($sender['from_name'] ?? config('app.name')),
                'submissionSummary' => $this->mailConfig['submission_summary'] ?? [],
                'summaryHeading' => $this->mailConfig['summary_heading'] ?? 'Key information submitted',
                'footerConfig' => $collectionConfig['footer'] ?? [],
                'footerPartial' => $footerPartial,
            ],
        );
    }

    private function headline(): string
    {
        $collectionLabel = $this->mailConfig['collection_label'] ?? ($this->mailConfig['sender']['from_name'] ?? config('app.name'));

        return match ($this->status) {
            'resubscribed' => "Welcome back to {$collectionLabel}",
            'subscription_updated' => "Your {$collectionLabel} preferences were updated",
            default => "Welcome to {$collectionLabel}",
        };
    }

    private function preheader(): string
    {
        return match ($this->status) {
            'resubscribed' => 'Your subscription has been restored.',
            'subscription_updated' => 'Your profile and preferences were updated.',
            default => 'Your subscription is confirmed.',
        };
    }

    private function interpolate(string $value): string
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{full_name}}', '{{email}}'],
            [
                $this->subscriber->first_name ?? '',
                $this->subscriber->last_name ?? '',
                $this->subscriber->full_name ?? $this->subscriber->email,
                $this->subscriber->email ?? '',
            ],
            $value
        );
    }

    private function resolveAssetUrl(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_array($value)) {
            return $this->resolveAssetUrl(reset($value) ?: null);
        }

        if (is_object($value) && method_exists($value, 'value') && ! method_exists($value, 'url')) {
            return $this->resolveAssetUrl($value->value());
        }

        if (is_object($value) && method_exists($value, 'url')) {
            return $this->normalizeAssetUrl($value->url());
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '//')) {
                return $this->normalizeAssetUrl($value);
            }

            if (str_starts_with($value, '/')) {
                return $this->normalizeAssetUrl(url($value, [], $this->shouldUseHttpsForAssets()));
            }

            return $this->normalizeAssetUrl(asset('storage/' . ltrim($value, '/'), $this->shouldUseHttpsForAssets()));
        }

        return null;
    }

    private function normalizeAssetUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return ($this->shouldUseHttpsForAssets() ? 'https:' : 'http:') . $url;
        }

        if ($this->shouldUseHttpsForAssets() && str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return url($url, [], $this->shouldUseHttpsForAssets());
        }

        return $url;
    }

    private function shouldUseHttpsForAssets(): bool
    {
        if (app()->bound('request')) {
            try {
                return request()->isSecure();
            } catch (\Throwable) {
                // Fall back to config-based detection when there is no active request.
            }
        }

        $assetRoot = config('app.asset_url') ?: config('app.url');

        return parse_url((string) $assetRoot, PHP_URL_SCHEME) === 'https';
    }
}
