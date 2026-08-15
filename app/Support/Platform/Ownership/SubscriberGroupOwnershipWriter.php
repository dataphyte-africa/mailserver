<?php

namespace App\Support\Platform\Ownership;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use InvalidArgumentException;
use LogicException;

class SubscriberGroupOwnershipWriter
{
    public function __construct(
        private readonly ProductOwnershipResolver $ownership,
    ) {}

    public function productForPrimaryCollection(string $collectionHandle): Product
    {
        return $this->ownership->productForPrimaryCollection($collectionHandle);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function createForProduct(Product $product, array $values): SubscriberGroup
    {
        return $this->saveForProduct(new SubscriberGroup, $product, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateForProduct(
        Product $product,
        SubscriberGroup $group,
        array $values,
    ): SubscriberGroup {
        return $this->saveForProduct($group, $product, $values);
    }

    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $values
     */
    public function updateOrCreateForProduct(
        Product $product,
        array $identity,
        array $values,
    ): SubscriberGroup {
        if ($identity === []) {
            throw new InvalidArgumentException('Subscriber group identity attributes are required.');
        }

        $group = SubscriberGroup::query()->firstOrNew($identity);

        return $this->saveForProduct($group, $product, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveForProduct(
        SubscriberGroup $group,
        Product $product,
        array $values,
    ): SubscriberGroup {
        $organisation = $this->ownership->organisationForProduct($product);

        $this->assertCompatibleOwnership($group, $product, $organisation);

        $group->fill($values);
        $group->product()->associate($product);
        $group->organisation()->associate($organisation);
        $group->save();

        return $group;
    }

    private function assertCompatibleOwnership(
        SubscriberGroup $group,
        Product $product,
        Organisation $organisation,
    ): void {
        if ($group->product_id !== null && (int) $group->product_id !== (int) $product->getKey()) {
            throw new LogicException('The subscriber group is already owned by another product.');
        }

        if ($group->organisation_id !== null && (int) $group->organisation_id !== (int) $organisation->getKey()) {
            throw new LogicException('The subscriber group is already owned by another organisation.');
        }
    }
}
