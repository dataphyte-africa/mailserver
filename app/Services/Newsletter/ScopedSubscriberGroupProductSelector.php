<?php

namespace App\Services\Newsletter;

use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ScopedSubscriberGroupProductSelector
{
    public function __construct(
        private readonly ScopedCampaignProductSelector $products,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function productsFor(?Authenticatable $operator): Collection
    {
        return $this->products->productsFor($operator)
            ->filter(fn (Product $product) => $product->organisation?->status === 'active')
            ->values();
    }

    public function resolve(?Authenticatable $operator, int $productId): ?Product
    {
        if ($productId <= 0) {
            return null;
        }

        return $this->productsFor($operator)
            ->first(fn (Product $product) => (int) $product->getKey() === $productId);
    }

    /**
     * @return Collection<int, SubscriberGroup>
     */
    public function groupsFor(?Authenticatable $operator): Collection
    {
        $productIds = $this->productsFor($operator)->modelKeys();

        if ($productIds === []) {
            return new Collection;
        }

        return SubscriberGroup::query()
            ->ownedByProducts($productIds)
            ->whereHas('product', function (Builder $query) use ($productIds): void {
                $query
                    ->whereKey($productIds)
                    ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                    ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle');
            })
            ->orderBy('subscriber_groups.name')
            ->get();
    }

    public function resolveGroup(
        ?Authenticatable $operator,
        SubscriberGroup $group,
    ): ?Product {
        if (
            ! $group->exists
            || $group->product_id === null
            || $group->organisation_id === null
        ) {
            return null;
        }

        $product = $this->resolve($operator, (int) $group->product_id);

        if (
            $product === null
            || (int) $group->organisation_id !== (int) $product->organisation_id
            || $group->collection_handle !== $product->primary_collection_handle
        ) {
            return null;
        }

        return $product;
    }

    public function resolveSubGroup(
        ?Authenticatable $operator,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
    ): ?Product {
        $product = $this->resolveGroup($operator, $group);

        if (
            $product === null
            || ! $subGroup->exists
            || (int) $subGroup->subscriber_group_id !== (int) $group->getKey()
        ) {
            return null;
        }

        return $product;
    }
}
