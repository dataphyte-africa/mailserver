<?php

namespace App\Support\Platform\Ownership;

use App\Models\Organisation;
use App\Models\Product;
use InvalidArgumentException;
use LogicException;

class ProductOwnershipResolver
{
    public function productForPrimaryCollection(string $collectionHandle): Product
    {
        $collectionHandle = trim($collectionHandle);

        if ($collectionHandle === '') {
            throw new InvalidArgumentException('A primary collection handle is required to resolve product ownership.');
        }

        return Product::query()
            ->with('organisation')
            ->where('primary_collection_handle', $collectionHandle)
            ->sole();
    }

    public function organisationForProduct(Product $product): Organisation
    {
        if (! $product->exists) {
            throw new InvalidArgumentException('Ownership assignment requires a persisted product.');
        }

        $organisation = $product->organisation;

        if (! $organisation instanceof Organisation || ! $organisation->exists) {
            throw new LogicException('Ownership assignment requires the product to belong to a persisted organisation.');
        }

        return $organisation;
    }
}
