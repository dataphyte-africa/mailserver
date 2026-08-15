<?php

namespace App\Services\Newsletter;

use App\Exceptions\Newsletter\CampaignAudienceOwnershipException;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Eloquent\Collection;

class CampaignAudienceOwnershipService
{
    public function validatePersistedForProduct(
        Campaign $campaign,
        Product $product,
    ): ValidatedCampaignAudience {
        $audiences = $campaign->audiences()->get();

        if ($audiences->isEmpty()) {
            return $this->validateForProduct($product, false, []);
        }

        $groupType = (new SubscriberGroup)->getMorphClass();
        $subGroupType = (new SubscriberSubGroup)->getMorphClass();
        $groupAudiences = $audiences->where('targetable_type', $groupType);
        $subGroupAudiences = $audiences->where('targetable_type', $subGroupType);

        if ($groupAudiences->isNotEmpty()) {
            if (
                $groupAudiences->count() !== 1
                || $subGroupAudiences->isNotEmpty()
                || ! $groupAudiences->first()->send_to_all
                || $groupAudiences->count() + $subGroupAudiences->count() !== $audiences->count()
            ) {
                throw new CampaignAudienceOwnershipException(
                    'send_to_all',
                    'The campaign has an invalid persisted send-to-all audience.',
                );
            }

            $validated = $this->validateForProduct($product, true, []);

            if ((int) $groupAudiences->first()->targetable_id !== (int) $validated->group?->getKey()) {
                throw new CampaignAudienceOwnershipException(
                    'send_to_all',
                    'The campaign send-to-all audience does not belong to its product.',
                );
            }

            return $validated;
        }

        if (
            $subGroupAudiences->count() !== $audiences->count()
            || $subGroupAudiences->contains(fn (CampaignAudience $audience) => $audience->send_to_all)
        ) {
            throw new CampaignAudienceOwnershipException(
                'sub_groups',
                'The campaign has an invalid persisted subgroup audience.',
            );
        }

        return $this->validateForProduct(
            $product,
            false,
            $subGroupAudiences->pluck('targetable_id')->all(),
        );
    }

    /**
     * @param  array<int, mixed>  $subGroupIds
     */
    public function validateForProduct(
        Product $product,
        bool $sendToAll,
        array $subGroupIds,
    ): ValidatedCampaignAudience {
        $organisation = $this->activeOrganisationFor($product);

        if ($sendToAll) {
            return new ValidatedCampaignAudience(
                $this->sendToAllGroup($product, $organisation),
                new Collection,
            );
        }

        $ids = collect($subGroupIds)
            ->filter(fn (mixed $id) => is_int($id) || ctype_digit((string) $id))
            ->map(fn (mixed $id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return new ValidatedCampaignAudience(null, new Collection);
        }

        $subGroups = SubscriberSubGroup::query()
            ->with('group')
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->get()
            ->keyBy(fn (SubscriberSubGroup $subGroup) => $subGroup->getKey());

        if ($subGroups->count() !== $ids->count()) {
            throw new CampaignAudienceOwnershipException(
                'sub_groups',
                'One or more selected audience subgroups are unavailable.',
            );
        }

        $orderedSubGroups = new Collection;

        foreach ($ids as $id) {
            /** @var SubscriberSubGroup $subGroup */
            $subGroup = $subGroups->get($id);
            $this->assertGroupOwnedBy($subGroup->group, $product, $organisation, 'sub_groups');
            $orderedSubGroups->push($subGroup);
        }

        return new ValidatedCampaignAudience(null, $orderedSubGroups);
    }

    public function replace(Campaign $campaign, ValidatedCampaignAudience $audience): void
    {
        $product = $campaign->product;
        $organisation = $campaign->organisation;

        if (! $product instanceof Product || ! $organisation instanceof Organisation) {
            throw new CampaignAudienceOwnershipException(
                'product_id',
                'Campaign ownership is required before assigning an audience.',
            );
        }

        if ($audience->group !== null) {
            $this->assertGroupOwnedBy($audience->group, $product, $organisation, 'send_to_all');
        }

        foreach ($audience->subGroups as $subGroup) {
            $this->assertGroupOwnedBy($subGroup->group, $product, $organisation, 'sub_groups');
        }

        $campaign->audiences()->delete();

        if ($audience->group !== null) {
            CampaignAudience::query()->create([
                'campaign_id' => $campaign->getKey(),
                'targetable_type' => $audience->group->getMorphClass(),
                'targetable_id' => $audience->group->getKey(),
                'send_to_all' => true,
            ]);

            return;
        }

        foreach ($audience->subGroups as $subGroup) {
            CampaignAudience::query()->create([
                'campaign_id' => $campaign->getKey(),
                'targetable_type' => $subGroup->getMorphClass(),
                'targetable_id' => $subGroup->getKey(),
                'send_to_all' => false,
            ]);
        }
    }

    private function activeOrganisationFor(Product $product): Organisation
    {
        $organisation = $product->organisation;

        if (! $product->exists || $product->status !== 'active') {
            throw new CampaignAudienceOwnershipException(
                'product_id',
                'An active product is required to validate campaign audiences.',
            );
        }

        if (! $organisation instanceof Organisation || ! $organisation->exists || $organisation->status !== 'active') {
            throw new CampaignAudienceOwnershipException(
                'product_id',
                'An active product organisation is required to validate campaign audiences.',
            );
        }

        if (trim((string) $product->primary_collection_handle) === '') {
            throw new CampaignAudienceOwnershipException(
                'product_id',
                'The selected product has no primary campaign collection.',
            );
        }

        return $organisation;
    }

    private function sendToAllGroup(Product $product, Organisation $organisation): SubscriberGroup
    {
        $groups = SubscriberGroup::query()
            ->withinOwnership($organisation, $product)
            ->where('collection_handle', $product->primary_collection_handle)
            ->whereNull('archived_at')
            ->limit(2)
            ->get();

        if ($groups->count() !== 1) {
            throw new CampaignAudienceOwnershipException(
                'send_to_all',
                'Send to all requires exactly one owned audience group for the selected product.',
            );
        }

        return $groups->first();
    }

    private function assertGroupOwnedBy(
        ?SubscriberGroup $group,
        Product $product,
        Organisation $organisation,
        string $input,
    ): void {
        $matchesOwnership = $group instanceof SubscriberGroup
            && ! $group->isArchived()
            && (int) $group->product_id === (int) $product->getKey()
            && (int) $group->organisation_id === (int) $organisation->getKey()
            && $group->collection_handle === $product->primary_collection_handle;

        if (! $matchesOwnership) {
            throw new CampaignAudienceOwnershipException(
                $input,
                'The selected audience does not belong to the campaign product.',
            );
        }
    }
}
