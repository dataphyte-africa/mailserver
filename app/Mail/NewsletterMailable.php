<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Services\Newsletter\CuratedRssStoriesService;
use App\Services\Newsletter\RssFeedService;
use App\Services\Newsletter\TemplateResolver;
use App\Services\Newsletter\UtmInjector;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;

class NewsletterMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Campaign   $campaign,
        public readonly Subscriber $subscriber,
        public readonly string     $campaignSendId,
    ) {}

    /* ------------------------------------------------------------------ */

    public function envelope(): Envelope
    {
        $sender = $this->campaign->sender();
        $subject = $this->normalizedSubject(
            $this->campaign->subject ?? $this->resolveEntry()?->get('subject') ?? $this->campaign->name
        );

        $replyTo = $sender['reply_to']
            ? [new Address($sender['reply_to'])]
            : [];

        return new Envelope(
            from:    new Address($sender['from_email'], $sender['from_name']),
            replyTo: $replyTo,
            subject: $subject,
            tags:    ['newsletter', $this->campaign->collection ?? 'general'],
        );
    }

    /* ------------------------------------------------------------------ */

    public function content(): Content
    {
        $entry    = $this->resolveEntry();
        $sender   = $this->campaign->sender();
        $settings = $this->newsletterSettings();

        // Resolve which Blade template to render
        $template = app(TemplateResolver::class)->resolve($entry, $this->campaign->collection);

        // Collection-aware logo and brand colour
        $collection      = $this->campaign->collection ?? '';
        $collectionKey   = str_replace('_newsletters', '', $collection); // insight | foundation
        $footerPartial   = 'emails.partials.' . str_replace('_', '-', $collectionKey) . '.footer';
        $collectionConfig = config("newsletter.collections.{$collection}", []);
        $collectionLogo  = $this->resolveAssetUrl($settings["{$collectionKey}_logo"] ?? null);
        $headerColor     = $settings["{$collectionKey}_brand_color"]
                            ?? config("newsletter.collections.{$collection}.brand_color", '#1a1a2e');

        // Hero image
        $heroUrl = $this->resolveAssetUrl($entry?->get('hero_image'));

        // Inject UTM into bard content, then replace subscriber merge tags
        $rawContent = $entry?->get('content') ?? '';
        $utmParams  = [
            'utm_source'   => 'newsletter',
            'utm_medium'   => 'email',
            'utm_campaign' => 'campaign-' . $this->campaign->id,
        ];
        $content = $this->prepareCampaignContent($rawContent, $utmParams);
        $rssFeedUrl = $entry?->get('rss_feed_url');
        $rssItemLimit = (int) ($entry?->get('rss_item_limit') ?: 6);
        $rssItems = app(RssFeedService::class)->items(
            is_string($rssFeedUrl) ? $rssFeedUrl : null,
            $rssItemLimit,
        );
        $curatedRss = app(CuratedRssStoriesService::class)->preparedItems($entry, $rssItems);
        $relatedRssFeedUrl = $entry?->get('related_rss_feed_url');
        $relatedRssItemLimit = (int) ($entry?->get('related_rss_item_limit') ?: 4);
        $recommendedRssFeedUrl = $entry?->get('recommended_rss_feed_url');
        $recommendedRssItemLimit = (int) ($entry?->get('recommended_rss_item_limit') ?: 4);

        $relatedFetchedItems = app(RssFeedService::class)->items(
            is_string($relatedRssFeedUrl) ? $relatedRssFeedUrl : null,
            $relatedRssItemLimit,
        );
        $recommendedFetchedItems = app(RssFeedService::class)->items(
            is_string($recommendedRssFeedUrl) ? $recommendedRssFeedUrl : null,
            $recommendedRssItemLimit,
        );
        $feedStories = app(CuratedRssStoriesService::class);
        $relatedRssItems = $feedStories->preparedList(
            $entry,
            'related_rss_items',
            $relatedFetchedItems,
            $relatedRssItemLimit,
        );
        $recommendedRssItems = $feedStories->preparedList(
            $entry,
            'recommended_rss_items',
            $recommendedFetchedItems,
            $recommendedRssItemLimit,
        );
        $marinaMaitamaSections = $this->extractDualPerspectiveSections($content);

        return new Content(
            view: $template,
            with: [
                'entryTitle'          => $entry?->get('title') ?? $this->campaign->name,
                'subject'            => $this->envelope()->subject,
                'preheader'          => $entry?->get('preheader') ?? '',
                'heroImageUrl'       => $heroUrl,
                'content'            => $content,
                'introContent'       => $marinaMaitamaSections['intro_html'],
                'marinaContent'      => $marinaMaitamaSections['marina_html'],
                'maitamaContent'     => $marinaMaitamaSections['maitama_html'],
                'highlightStat'      => $entry?->get('highlight_stat') ?? '',
                'highlightStatLabel' => $entry?->get('highlight_stat_label') ?? '',
                'author'             => $entry?->get('author') ?? $sender['from_name'],
                'fromName'           => $sender['from_name'],
                'sentDate'           => $this->campaign->sent_at?->format('F j, Y') ?? now()->format('F j, Y'),
                'collectionLogo'     => $collectionLogo,
                'headerColor'        => $headerColor,
                'footerConfig'       => $collectionConfig['footer'] ?? [],
                'footerPartial'      => $footerPartial,
                'newsletterSettings' => $settings,
                'unsubscribeUrl'     => $this->buildSignedUrl('newsletter.unsubscribe.show'),
                'preferencesUrl'     => $this->buildSignedUrl('newsletter.preferences.show'),
                // Subscriber personalisation variables (use in templates directly)
                'subscriberFirstName' => $this->subscriber->first_name ?? '',
                'subscriberLastName'  => $this->subscriber->last_name  ?? '',
                'subscriberFullName'  => $this->subscriber->full_name  ?? $this->subscriber->email,
                'subscriberEmail'     => $this->subscriber->email      ?? '',
                'pocketIntelligenceTitle' => $entry?->get('travel_intelligence_title') ?? '',
                'pocketIntelligenceSubtitle' => $entry?->get('travel_intelligence_subtitle') ?? '',
                'pocketIntelligenceItems' => collect($entry?->get('travel_intelligence_items') ?? [])
                    ->filter(fn ($item) => ($item['enabled'] ?? true) !== false)
                    ->values()
                    ->all(),
                'rssFeedUrl'          => $rssFeedUrl,
                'rssItems'            => $curatedRss['items'],
                'rssLeadItem'         => $curatedRss['lead'],
                'rssSecondaryItems'   => $curatedRss['secondary'],
                'relatedRssFeedUrl'   => $relatedRssFeedUrl,
                'relatedRssItems'     => $relatedRssItems,
                'recommendedRssFeedUrl' => $recommendedRssFeedUrl,
                'recommendedRssItems' => $recommendedRssItems,
            ],
        );
    }

    /* ------------------------------------------------------------------ */

    public function prepareCampaignContent(string $rawContent, array $utmParams): string
    {
        $content = UtmInjector::inject($rawContent, $utmParams);
        $content = $this->applyMergeTags($content);

        return $this->renderEmailContentHtml($content);
    }

    public function extractDualPerspectiveSections(string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            return [
                'intro_html' => '',
                'marina_html' => '',
                'maitama_html' => '',
            ];
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $html = '<!DOCTYPE html><html><body><div id="newsletter-content">' . $content . '</div></body></html>';

        if (! $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return [
                'intro_html' => $content,
                'marina_html' => '',
                'maitama_html' => '',
            ];
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $container = $dom->getElementById('newsletter-content');

        if (! $container) {
            return [
                'intro_html' => $content,
                'marina_html' => '',
                'maitama_html' => '',
            ];
        }

        $sections = [
            'intro_html' => '',
            'marina_html' => '',
            'maitama_html' => '',
        ];
        $current = 'intro_html';

        foreach (iterator_to_array($container->childNodes) as $child) {
            if ($child instanceof \DOMElement && strtolower($child->tagName) === 'h5') {
                $label = trim(preg_replace('/\s+/', ' ', $child->textContent ?? ''));

                if (preg_match('/^Marina\b/i', $label) === 1) {
                    $current = 'marina_html';
                    $sections[$current] .= $dom->saveHTML($child);
                    continue;
                }

                if (preg_match('/^Maitama\b/i', $label) === 1) {
                    $current = 'maitama_html';
                    $sections[$current] .= $dom->saveHTML($child);
                    continue;
                }
            }

            $sections[$current] .= $dom->saveHTML($child);
        }

        return $sections;
    }

    /* ------------------------------------------------------------------ */

    public function headers(): Headers
    {
        return new Headers(
            messageId:  null,
            references: [],
            text: [
                'List-Unsubscribe'      => '<' . $this->buildSignedUrl('newsletter.unsubscribe.show') . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Campaign-Id'         => (string) $this->campaign->id,
                'X-Campaign-Send-Id'    => $this->campaignSendId,
            ],
        );
    }

    /* ------------------------------------------------------------------ */

    private function resolveEntry(): ?object
    {
        if (! $this->campaign->entry_id) {
            return null;
        }

        return Entry::find($this->campaign->entry_id);
    }

    /**
     * Fetch newsletter_settings GlobalSet data, cached for 1 hour.
     * Falls back to empty array when the GlobalSet hasn't been scaffolded yet.
     */
    private function newsletterSettings(): array
    {
        return cache()->remember('newsletter_settings', 3600, function () {
            $set = GlobalSet::findByHandle('newsletter_settings');

            if (! $set) {
                return [];
            }

            return $set->inDefaultSite()?->data()?->toArray() ?? [];
        });
    }

    /**
     * Convert a Statamic asset value (path string or Asset object) to a
     * fully-qualified public URL.
     */
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

            if (str_starts_with($value, 'statamic://asset::')) {
                $resolved = $this->resolveStatamicAssetUrl($value);

                if ($resolved) {
                    return $resolved;
                }
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

    private function resolveStatamicAssetUrl(string $value): ?string
    {
        $identifier = substr($value, strlen('statamic://asset::'));
        [$containerHandle, $path] = array_pad(explode('::', $identifier, 2), 2, null);

        if (! $containerHandle || ! $path) {
            return null;
        }

        $container = AssetContainer::findByHandle($containerHandle);

        if (! $container) {
            return null;
        }

        $asset = $container->asset($path);

        return $asset ? $this->normalizeAssetUrl($asset->url()) : null;
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
                // Fall through to config-based detection when there is no active request.
            }
        }

        $assetRoot = config('app.asset_url') ?: config('app.url');

        return parse_url((string) $assetRoot, PHP_URL_SCHEME) === 'https';
    }

    /**
     * Replace {{merge_tag}} placeholders in the body content with real
     * subscriber data.  Editors type these directly in the Bard field.
     *
     * Supported tags:
     *   {{first_name}}  {{last_name}}  {{full_name}}  {{email}}
     */
    private function applyMergeTags(string $content): string
    {
        $firstName = $this->subscriber->first_name ?? '';
        $lastName = $this->subscriber->last_name ?? '';
        $fullName = $this->subscriber->full_name ?? $this->subscriber->email;
        $email = $this->subscriber->email ?? '';

        $map = [
            '{{first_name}}' => $firstName,
            '{{last_name}}'  => $lastName,
            '{{full_name}}'  => $fullName,
            '{{email}}'      => $email,
            '{{firstname}}'  => $firstName !== '' ? $firstName : 'Reader',
            '{{lastname}}'   => $lastName,
            '{{fullname}}'   => $fullName,
        ];

        return str_replace(array_keys($map), array_values($map), $content);
    }

    private function renderEmailContentHtml(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $html = '<!DOCTYPE html><html><body><div id="newsletter-content">' . $content . '</div></body></html>';

        if (! $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $content;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $container = $dom->getElementById('newsletter-content');

        if (! $container) {
            return $content;
        }

        $this->styleRichTextNodes($container);

        $rendered = '';
        foreach ($container->childNodes as $child) {
            $rendered .= $dom->saveHTML($child);
        }

        return $rendered;
    }

    private function styleRichTextNodes(\DOMNode $container): void
    {
        if (! $container instanceof \DOMElement) {
            return;
        }

        $this->promoteStandaloneHeadings($container);

        $styles = [
            'p' => "margin:0 0 18px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;",
            'h2' => "margin:0 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:1.22;color:#0d1b2a;",
            'h3' => "margin:0 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:1.28;color:#0d1b2a;",
            'ul' => "margin:0 0 18px;padding-left:22px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;",
            'ol' => "margin:0 0 18px;padding-left:22px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;",
            'li' => "margin:0 0 8px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;",
            'blockquote' => "margin:0 0 24px;padding:28px 26px 26px 34px;background:#e8eefb;border-left:6px solid #0f4c81;font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:1.7;color:#35528a;font-style:italic;",
            'a' => "color:#0d1b2a;text-decoration:underline;",
            'strong' => "font-weight:700;color:#0d1b2a;",
            'em' => "font-style:italic;",
            'img' => "display:block;width:100%;max-width:100%;height:auto;margin:0 0 18px;border:0;",
            'figure' => "margin:0 0 18px;",
            'figcaption' => "margin:8px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;color:#6b7280;",
            'hr' => "border:0;border-top:1px solid #d4d9e2;margin:22px 0;",
        ];

        foreach ($styles as $tag => $style) {
            foreach ($container->getElementsByTagName($tag) as $node) {
                if ($tag === 'img') {
                    $src = trim((string) $node->getAttribute('src'));
                    if ($src !== '') {
                        $resolved = $this->resolveAssetUrl($src);
                        if ($resolved) {
                            $node->setAttribute('src', $resolved);
                        }
                    }
                }

                if ($tag === 'a') {
                    $href = trim((string) $node->getAttribute('href'));
                    if (
                        $href !== ''
                        && ! str_starts_with($href, '#')
                        && ! str_starts_with($href, 'mailto:')
                        && ! str_starts_with($href, 'tel:')
                        && (
                            str_starts_with($href, 'http://')
                            || str_starts_with($href, 'https://')
                            || str_starts_with($href, '//')
                            || str_starts_with($href, '/')
                        )
                    ) {
                        $resolved = $this->resolveAssetUrl($href);
                        if ($resolved) {
                            $node->setAttribute('href', $resolved);
                        }
                    }
                }

                $this->mergeInlineStyle($node, $style);
            }
        }

        $this->styleGreetingParagraph($container);

        foreach ($container->getElementsByTagName('blockquote') as $node) {
            $this->styleBlockquoteNode($node);
        }
    }

    private function promoteStandaloneHeadings(\DOMElement $container): void
    {
        $paragraphs = [];

        foreach ($container->getElementsByTagName('p') as $paragraph) {
            $paragraphs[] = $paragraph;
        }

        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof \DOMElement) {
                continue;
            }

            if ($this->paragraphHasStructuralChildren($paragraph)) {
                continue;
            }

            $text = trim(preg_replace('/\s+/', ' ', $paragraph->textContent ?? ''));

            if ($text === '') {
                continue;
            }

            $targetTag = match (true) {
                preg_match('/^Heading\s*2$/i', $text) === 1 => 'h2',
                preg_match('/^Heading\s*3$/i', $text) === 1 => 'h3',
                $this->looksLikeStandaloneHeading($text) => 'h3',
                default => null,
            };

            if (! $targetTag) {
                continue;
            }

            $replacement = $paragraph->ownerDocument->createElement($targetTag);

            while ($paragraph->firstChild) {
                $replacement->appendChild($paragraph->firstChild);
            }

            $paragraph->parentNode?->replaceChild($replacement, $paragraph);
        }
    }

    private function paragraphHasStructuralChildren(\DOMElement $paragraph): bool
    {
        foreach ($paragraph->childNodes as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            if (! in_array(strtolower($child->tagName), ['strong', 'em', 'a', 'u', 'span', 'br'], true)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeStandaloneHeading(string $text): bool
    {
        if (preg_match('/^Dear\s+.+,\s*$/u', $text) === 1) {
            return false;
        }

        if (mb_strlen($text) > 70) {
            return false;
        }

        if (preg_match('/[.!?:]$/', $text) === 1) {
            return false;
        }

        $words = preg_split('/\s+/', $text) ?: [];

        if (count($words) < 2 || count($words) > 8) {
            return false;
        }

        return preg_match('/^[A-Z0-9“"\'(]/u', $text) === 1;
    }

    private function styleBlockquoteNode(\DOMElement $node): void
    {
        if (! $node->hasAttribute('data-email-quote-mark')) {
            $marker = $node->ownerDocument->createElement('div', '“');
            $marker->setAttribute(
                'style',
                "font-family:Georgia,'Times New Roman',serif;font-size:52px;line-height:0.8;color:#bdd0ed;margin:0 0 4px;"
            );
            $marker->setAttribute('data-email-quote-mark', '1');
            $node->insertBefore($marker, $node->firstChild);
        }

        foreach ($node->getElementsByTagName('p') as $paragraph) {
            $this->mergeInlineStyle(
                $paragraph,
                "margin:0 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:1.7;color:#35528a;font-style:italic;"
            );
        }
    }

    private function styleGreetingParagraph(\DOMElement $container): void
    {
        foreach ($container->childNodes as $child) {
            if (! $child instanceof \DOMElement || strtolower($child->tagName) !== 'p') {
                continue;
            }

            $text = trim(preg_replace('/\s+/', ' ', $child->textContent ?? ''));

            if (preg_match('/^Dear\s+.+,\s*$/u', $text) !== 1) {
                return;
            }

            $child->setAttribute(
                'style',
                "margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#1f2937;"
            );

            return;
        }
    }

    private function mergeInlineStyle(\DOMElement $node, string $style): void
    {
        $existing = trim((string) $node->getAttribute('style'));
        $merged = rtrim($style, ';') . ';';

        if ($existing !== '') {
            $merged .= ' ' . rtrim($existing, ';') . ';';
        }

        $node->setAttribute('style', trim($merged));
    }

    private function buildSignedUrl(string $routeName): string
    {
        return \URL::signedRoute($routeName, [
            'token' => $this->subscriber->ensureConfirmationToken(),
        ]);
    }

    private function normalizedSubject(?string $value): string
    {
        return html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
