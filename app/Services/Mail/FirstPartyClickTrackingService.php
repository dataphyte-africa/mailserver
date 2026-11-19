<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Crypt;

class FirstPartyClickTrackingService
{
    /** @param array{campaign_send_id?: int|string, tor_invitation_id?: int|string} $context */
    public function url(string $destination, array $context = []): string
    {
        $payload = Crypt::encryptString(json_encode([
            'url' => $destination,
            'campaign_send_id' => isset($context['campaign_send_id']) ? (int) $context['campaign_send_id'] : null,
            'tor_invitation_id' => isset($context['tor_invitation_id']) ? (int) $context['tor_invitation_id'] : null,
        ], JSON_THROW_ON_ERROR));

        $path = route('mail-tracking.click', ['payload' => $this->encode($payload)], false);
        $host = trim((string) config('platform.domain.platform_domain'))
            ?: (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return rtrim((string) config('platform.domain.platform_scheme', 'https'), ':/')
            .'://'.$host.$path;
    }

    /** @param array{campaign_send_id?: int|string, tor_invitation_id?: int|string} $context */
    public function rewriteHtml(string $html, array $context = []): string
    {
        if ($context === [] || ! str_contains(strtolower($html), 'href=')) {
            return $html;
        }

        return preg_replace_callback('/\bhref\s*=\s*(["\'])(https?:\/\/[^"\']+)\1/i', function (array $match) use ($context): string {
            $destination = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return 'href='.$match[1].e($this->url($destination, $context)).$match[1];
        }, $html) ?? $html;
    }

    /** @return array{url: string, campaign_send_id: ?int, tor_invitation_id: ?int} */
    public function decode(string $payload): array
    {
        $decoded = json_decode(Crypt::decryptString($this->decodePayload($payload)), true, flags: JSON_THROW_ON_ERROR);
        $url = is_string($decoded['url'] ?? null) ? $decoded['url'] : '';
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \UnexpectedValueException('Invalid click destination.');
        }

        return [
            'url' => $url,
            'campaign_send_id' => filled($decoded['campaign_send_id'] ?? null) ? (int) $decoded['campaign_send_id'] : null,
            'tor_invitation_id' => filled($decoded['tor_invitation_id'] ?? null) ? (int) $decoded['tor_invitation_id'] : null,
        ];
    }

    private function encode(string $payload): string
    {
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    private function decodePayload(string $payload): string
    {
        $remainder = strlen($payload) % 4;
        if ($remainder) {
            $payload .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if (! is_string($decoded)) {
            throw new \UnexpectedValueException('Invalid click payload.');
        }

        return $decoded;
    }
}
