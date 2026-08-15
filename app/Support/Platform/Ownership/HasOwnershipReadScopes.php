<?php

namespace App\Support\Platform\Ownership;

use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

trait HasOwnershipReadScopes
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedByProduct(Builder $query, Product|int $product): Builder
    {
        return $query->where(
            $query->getModel()->qualifyColumn('product_id'),
            $this->ownershipKey($product),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @param  iterable<int, Product|int>  $products
     * @return Builder<static>
     */
    public function scopeOwnedByProducts(Builder $query, iterable $products): Builder
    {
        $productIds = [];

        foreach ($products as $product) {
            $productIds[] = $this->ownershipKey($product);
        }

        return $query->whereIn(
            $query->getModel()->qualifyColumn('product_id'),
            array_values(array_unique($productIds)),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedByOrganisation(Builder $query, Organisation|int $organisation): Builder
    {
        return $query->where(
            $query->getModel()->qualifyColumn('organisation_id'),
            $this->ownershipKey($organisation),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithinOwnership(
        Builder $query,
        Organisation|int $organisation,
        Product|int $product,
    ): Builder {
        return $this->scopeOwnedByProduct(
            $this->scopeOwnedByOrganisation($query, $organisation),
            $product,
        );
    }

    private function ownershipKey(Organisation|Product|int $owner): int
    {
        if (is_int($owner)) {
            return $owner;
        }

        $key = $owner->getKey();

        if (! $owner->exists || (! is_int($key) && ! ctype_digit((string) $key))) {
            throw new InvalidArgumentException('Ownership scopes require a persisted organisation or product.');
        }

        return (int) $key;
    }
}
