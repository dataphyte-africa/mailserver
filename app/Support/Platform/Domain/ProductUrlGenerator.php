<?php

namespace App\Support\Platform\Domain;

use App\Contracts\Domain\DomainResolverInterface;
use App\Contracts\Domain\ProductUrlGeneratorInterface;
use Illuminate\Support\Str;

class ProductUrlGenerator implements ProductUrlGeneratorInterface
{
    public function __construct(
        private readonly DomainResolverInterface $domainResolver,
    ) {}

    public function landingPage(string $productKey): string
    {
        return $this->surfaceUrl($productKey, 'landing_page');
    }

    public function formPage(string $productKey, string $formHandle): string
    {
        return $this->surfaceUrl($productKey, 'form_page', [
            '{form}' => $formHandle,
        ]);
    }

    public function formSubmitEndpoint(string $productKey, string $formHandle): string
    {
        return $this->surfaceUrl($productKey, 'form_submit_endpoint', [
            '{form}' => $formHandle,
        ]);
    }

    public function preferencesPage(string $productKey, string $subscriberToken): string
    {
        return $this->surfaceUrl($productKey, 'preferences_page', [
            '{subscriber}' => $subscriberToken,
        ]);
    }

    public function unsubscribePage(string $productKey, string $subscriberToken): string
    {
        return $this->surfaceUrl($productKey, 'unsubscribe_page', [
            '{subscriber}' => $subscriberToken,
        ]);
    }

    public function browserViewPage(string $productKey, string $campaignKey): string
    {
        return $this->surfaceUrl($productKey, 'browser_view_page', [
            '{campaign}' => $campaignKey,
        ]);
    }

    public function campaignLink(string $productKey, string $pathOrUrl): string
    {
        if ($this->isAbsoluteUrl($pathOrUrl)) {
            return $pathOrUrl;
        }

        return $this->surfaceUrl($productKey, 'campaign_link', [], $pathOrUrl);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function surfaceUrl(string $productKey, string $surface, array $replacements = [], ?string $pathOverride = null): string
    {
        $surfaceConfig = config("platform.domain.surfaces.{$surface}", []);
        $domain = $this->domainResolver->resolveProductDomain($productKey, $surface)
            ?? (string) config('platform.domain.platform_domain', '');
        $path = $pathOverride ?? (is_array($surfaceConfig) ? ($surfaceConfig['path'] ?? '/') : '/');

        if (is_string($path) && $replacements !== []) {
            $path = strtr($path, $replacements);
        }

        return $this->buildUrl($domain, is_string($path) ? $path : '/');
    }

    protected function buildUrl(string $domain, string $path): string
    {
        $scheme = (string) config('platform.domain.platform_scheme', 'https');
        $trimmedDomain = trim($domain);
        $normalizedPath = '/'.ltrim($path, '/');

        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        if ($trimmedDomain === '') {
            return url($normalizedPath);
        }

        return sprintf('%s://%s%s', $scheme, $trimmedDomain, $normalizedPath);
    }

    protected function isAbsoluteUrl(string $pathOrUrl): bool
    {
        return Str::startsWith(Str::lower($pathOrUrl), ['http://', 'https://']);
    }
}
