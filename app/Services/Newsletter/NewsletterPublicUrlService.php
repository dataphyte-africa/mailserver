<?php

namespace App\Services\Newsletter;

use App\Contracts\Domain\DomainResolverInterface;
use App\Contracts\Domain\ProductUrlGeneratorInterface;
use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use Illuminate\Support\Facades\URL;

class NewsletterPublicUrlService
{
    public function __construct(
        private readonly ProductUrlGeneratorInterface $productUrls,
        private readonly DomainResolverInterface $domains,
    ) {}

    public function preferencesUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|Organisation|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.preferences.show', $subscriber, $collectionHandle, $productContext);
    }

    public function preferencesUpdateUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|Organisation|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.preferences.update', $subscriber, $collectionHandle, $productContext);
    }

    public function unsubscribeUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|Organisation|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.unsubscribe.show', $subscriber, $collectionHandle, $productContext);
    }

    public function unsubscribeProcessUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|Organisation|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.unsubscribe.process', $subscriber, $collectionHandle, $productContext);
    }

    public function hasValidSignature($request): bool
    {
        return $request->hasValidSignature() || $request->hasValidSignature(false);
    }

    private function signedProductUrl(string $routeName, Subscriber $subscriber, ?string $collectionHandle, Campaign|Organisation|string|int|null $productContext): string
    {
        $productId = $this->productId($productContext, $collectionHandle);
        $organisationId = $this->organisationId($productContext, $collectionHandle, $productId);
        $parameters = array_filter([
            'token' => $subscriber->ensureConfirmationToken(),
            'collection' => $collectionHandle,
            'organisation' => $organisationId,
            'product' => $productId,
        ], fn ($value) => filled($value));

        $relativeUrl = URL::signedRoute($routeName, $parameters, null, false);
        if ($productId !== null) {
            return $this->productUrls->campaignLink((string) $productId, $relativeUrl);
        }

        if ($organisationId !== null) {
            $domain = $this->domains->resolveOrganisationDomain((string) $organisationId, 'campaign_link');
            if ($domain) {
                return config('platform.domain.platform_scheme', 'https').'://'.$domain.$relativeUrl;
            }
        }

        return URL::signedRoute($routeName, $parameters);
    }

    private function organisationId(Campaign|Organisation|string|int|null $productContext, ?string $collectionHandle, ?int $productId): ?int
    {
        if ($productContext instanceof Organisation) {
            return (int) $productContext->getKey();
        }
        if ($productContext instanceof Campaign && $productContext->organisation_id) {
            return (int) $productContext->organisation_id;
        }

        if ($productId !== null) {
            return Product::query()->whereKey($productId)->value('organisation_id');
        }

        $handle = $collectionHandle ?: (is_string($productContext) ? $productContext : null);

        return $handle ? Organisation::query()->where('primary_collection_handle', $handle)->value('id') : null;
    }

    private function productId(Campaign|Organisation|string|int|null $productContext, ?string $collectionHandle): ?int
    {
        if ($productContext instanceof Organisation) {
            return null;
        }
        if ($productContext instanceof Campaign) {
            return $productContext->product_id ? (int) $productContext->product_id : null;
        }

        if (is_int($productContext)) {
            return Product::query()->whereKey($productContext)->value('id');
        }

        $key = is_string($productContext) && filled($productContext) ? trim($productContext) : $collectionHandle;
        if (! $key) {
            return null;
        }

        if (ctype_digit($key)) {
            return Product::query()->whereKey((int) $key)->value('id');
        }

        if ($productContext !== null) {
            $bySlug = Product::query()->where('slug', $key)
                ->when($collectionHandle, fn ($query) => $query->where('primary_collection_handle', $collectionHandle))
                ->value('id');
            if ($bySlug) {
                return (int) $bySlug;
            }
        }

        $matches = Product::query()->where('primary_collection_handle', $key)->limit(2)->pluck('id');

        return $matches->count() === 1 ? (int) $matches->first() : null;
    }
}
