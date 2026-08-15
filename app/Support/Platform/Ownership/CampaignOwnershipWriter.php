<?php

namespace App\Support\Platform\Ownership;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use InvalidArgumentException;
use LogicException;

class CampaignOwnershipWriter
{
    public function __construct(
        private readonly ProductOwnershipResolver $ownership,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function createForProduct(Product $product, array $values): Campaign
    {
        $campaign = new Campaign;

        return $this->saveForProduct($campaign, $product, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateForProduct(Product $product, Campaign $campaign, array $values): Campaign
    {
        return $this->saveForProduct($campaign, $product, $values);
    }

    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $values
     */
    public function updateOrCreateForProduct(
        Product $product,
        array $identity,
        array $values,
    ): Campaign {
        if ($identity === []) {
            throw new InvalidArgumentException('Campaign identity attributes are required.');
        }

        $organisation = $this->ownership->organisationForProduct($product);
        $campaign = Campaign::query()->firstOrNew($identity);

        return $this->saveForProduct($campaign, $product, $values, $organisation);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveForProduct(
        Campaign $campaign,
        Product $product,
        array $values,
        ?Organisation $organisation = null,
    ): Campaign {
        $organisation ??= $this->ownership->organisationForProduct($product);

        $this->assertCompatibleOwnership($campaign, $product, $organisation);

        $campaign->fill($values);
        $campaign->product()->associate($product);
        $campaign->organisation()->associate($organisation);
        $campaign->save();

        return $campaign;
    }

    private function assertCompatibleOwnership(
        Campaign $campaign,
        Product $product,
        Organisation $organisation,
    ): void {
        if ($campaign->product_id !== null && (int) $campaign->product_id !== (int) $product->getKey()) {
            throw new LogicException('The campaign is already owned by another product.');
        }

        if ($campaign->organisation_id !== null && (int) $campaign->organisation_id !== (int) $organisation->getKey()) {
            throw new LogicException('The campaign is already owned by another organisation.');
        }
    }
}
