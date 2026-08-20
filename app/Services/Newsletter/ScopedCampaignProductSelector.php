<?php

namespace App\Services\Newsletter;

use App\Contracts\Authorization\ScopeResolverInterface;
use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Statamic\Contracts\Auth\User as StatamicUser;

class ScopedCampaignProductSelector
{
    public function __construct(
        private readonly StatamicUserIdentityBridgeInterface $identityBridge,
        private readonly ScopeResolverInterface $scopes,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function productsFor(?Authenticatable $operator): Collection
    {
        if ($operator instanceof StatamicUser && $operator->isSuper()) {
            return $this->activeNewsletterProductsQuery()->get();
        }

        $user = $this->identityBridge->resolve($operator);

        if ($user === null) {
            return new Collection;
        }

        $productIds = $this->scopes->productIds($user);

        if ($productIds === []) {
            return new Collection;
        }

        return $this->activeNewsletterProductsQuery()
            ->whereKey($productIds)
            ->get();
    }

    public function resolve(
        ?Authenticatable $operator,
        int $productId,
        string $collectionHandle,
    ): ?Product {
        $collectionHandle = trim($collectionHandle);
        $user = $this->identityBridge->resolve($operator);

        if ($user === null || $productId <= 0 || $collectionHandle === '') {
            return null;
        }

        if (! $this->scopes->hasProductScope($user, $productId)) {
            return null;
        }

        return Product::query()
            ->with('organisation')
            ->whereKey($productId)
            ->where('status', 'active')
            ->where('primary_collection_handle', $collectionHandle)
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function activeNewsletterProductsQuery()
    {
        return Product::query()
            ->with('organisation')
            ->where('status', 'active')
            ->whereNotNull('primary_collection_handle')
            ->where('primary_collection_handle', '!=', '')
            ->whereHas('organisation', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name');
    }

    public function resolveCampaign(
        ?Authenticatable $operator,
        Campaign $campaign,
        ?string $collectionHandle = null,
    ): ?Product {
        if (
            ! $campaign->exists
            || $campaign->product_id === null
            || $campaign->organisation_id === null
        ) {
            return null;
        }

        $product = $this->resolve(
            $operator,
            (int) $campaign->product_id,
            $collectionHandle ?? (string) $campaign->collection,
        );

        if (
            $product === null
            || $product->organisation === null
            || $product->organisation->status !== 'active'
            || (int) $campaign->organisation_id !== (int) $product->organisation->getKey()
        ) {
            return null;
        }

        return $product;
    }
}
