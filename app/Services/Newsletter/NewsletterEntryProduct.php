<?php

namespace App\Services\Newsletter;

use App\Models\Product;
use Illuminate\Support\Collection;

class NewsletterEntryProduct
{
    private ?Collection $products = null;

    public function find(string $collection, ?string $blueprint): ?Product
    {
        if (! $blueprint) {
            return null;
        }

        $matches = $this->products()->get($collection.'|'.$blueprint, collect());

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function products(): Collection
    {
        return $this->products ??= Product::query()->whereNotNull('primary_collection_handle')
            ->whereNotNull('blueprint_handle')->get()
            ->groupBy(fn (Product $product) => $product->primary_collection_handle.'|'.$product->blueprint_handle);
    }
}
