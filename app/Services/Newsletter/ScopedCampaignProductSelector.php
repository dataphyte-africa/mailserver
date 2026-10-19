<?php

namespace App\Services\Newsletter;

use App\Models\Campaign;
use App\Models\Product;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

class ScopedCampaignProductSelector
{
    public function __construct(
        private readonly CpOperatorScope $scope,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function productsFor(?Authenticatable $operator): Collection
    {
        $productIds = $this->scope->productIds($operator);

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

        if ($productId <= 0 || $collectionHandle === '' || ! in_array($productId, $this->scope->productIds($operator), true)) {
            return null;
        }
        return $this->activeNewsletterProductsQuery()
            ->whereKey($productId)
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
