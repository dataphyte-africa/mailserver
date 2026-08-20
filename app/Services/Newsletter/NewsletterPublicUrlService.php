<?php

namespace App\Services\Newsletter;

use App\Contracts\Domain\ProductUrlGeneratorInterface;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Support\Facades\URL;

class NewsletterPublicUrlService
{
    public function __construct(
        private readonly ProductUrlGeneratorInterface $productUrls,
    ) {}

    public function preferencesUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.preferences.show', $subscriber, $collectionHandle, $productContext);
    }

    public function preferencesUpdateUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.preferences.update', $subscriber, $collectionHandle, $productContext);
    }

    public function unsubscribeUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.unsubscribe.show', $subscriber, $collectionHandle, $productContext);
    }

    public function unsubscribeProcessUrl(Subscriber $subscriber, ?string $collectionHandle = null, Campaign|string|int|null $productContext = null): string
    {
        return $this->signedProductUrl('newsletter.unsubscribe.process', $subscriber, $collectionHandle, $productContext);
    }

    public function hasValidSignature($request): bool
    {
        return $request->hasValidSignature() || $request->hasValidSignature(false);
    }

    private function signedProductUrl(string $routeName, Subscriber $subscriber, ?string $collectionHandle, Campaign|string|int|null $productContext): string
    {
        $parameters = array_filter([
            'token' => $subscriber->ensureConfirmationToken(),
            'collection' => $collectionHandle,
        ], fn ($value) => filled($value));

        $relativeUrl = URL::signedRoute($routeName, $parameters, null, false);
        $productKey = $this->productKey($productContext, $collectionHandle);

        if ($productKey === null) {
            return URL::signedRoute($routeName, $parameters);
        }

        return $this->productUrls->campaignLink($productKey, $relativeUrl);
    }

    private function productKey(Campaign|string|int|null $productContext, ?string $collectionHandle): ?string
    {
        if ($productContext instanceof Campaign) {
            if ($productContext->product_id) {
                return (string) $productContext->product_id;
            }

            return filled($productContext->collection) ? (string) $productContext->collection : $collectionHandle;
        }

        if (is_string($productContext) && trim($productContext) !== '') {
            return trim($productContext);
        }

        if (is_int($productContext)) {
            return (string) $productContext;
        }

        return filled($collectionHandle) ? $collectionHandle : null;
    }
}
