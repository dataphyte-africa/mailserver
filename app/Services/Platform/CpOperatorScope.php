<?php

namespace App\Services\Platform;

use App\Contracts\Authorization\ScopeResolverInterface;
use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Services\Newsletter\NewsletterEntryProduct;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Statamic\Contracts\Auth\User as StatamicUser;

class CpOperatorScope
{
    public function __construct(
        private readonly StatamicUserIdentityBridgeInterface $identity,
        private readonly ScopeResolverInterface $scopes,
    ) {}

    public function isSuper(?Authenticatable $operator): bool
    {
        return $operator instanceof StatamicUser && $operator->isSuper();
    }

    public function organisationIds(?Authenticatable $operator): array
    {
        if ($this->isSuper($operator)) {
            return \App\Models\Organisation::query()->pluck('id')->all();
        }

        $user = $this->identity->resolve($operator);

        return $user ? $this->scopes->organisationIds($user) : [];
    }

    public function productIds(?Authenticatable $operator): array
    {
        if ($this->isSuper($operator)) {
            return Product::query()->where('status', 'active')->whereHas('organisation', fn ($q) => $q->where('status', 'active'))->pluck('id')->all();
        }

        $user = $this->identity->resolve($operator);
        if (! $user) {
            return [];
        }

        $organisationIds = $this->scopes->organisationIds($user);
        $directIds = $this->scopes->productIds($user);

        return Product::query()
            ->where('status', 'active')
            ->whereHas('organisation', fn ($q) => $q->where('status', 'active'))
            ->where(fn ($q) => $q->whereIn('id', $directIds)->orWhereIn('organisation_id', $organisationIds))
            ->pluck('id')->all();
    }

    public function campaignQuery(?Authenticatable $operator): Builder
    {
        return $this->restrictCampaigns(Campaign::query(), $operator);
    }

    public function restrictCampaigns(Builder $query, ?Authenticatable $operator): Builder
    {
        return $this->isSuper($operator) ? $query : $query
            ->whereIn('product_id', $this->productIds($operator))
            ->whereHas('product', fn ($product) => $product
                ->whereColumn('products.organisation_id', 'campaigns.organisation_id')
                ->whereColumn('products.primary_collection_handle', 'campaigns.collection'));
    }

    public function canCampaign(?Authenticatable $operator, Campaign $campaign): bool
    {
        return $this->isSuper($operator) || $this->campaignQuery($operator)->whereKey($campaign->getKey())->exists();
    }

    public function canGroup(?Authenticatable $operator, SubscriberGroup $group): bool
    {
        return $this->isSuper($operator) || ($group->product_id !== null
            && Product::query()->whereKey($group->product_id)->whereKey($this->productIds($operator))
                ->where('organisation_id', $group->organisation_id)
                ->where('primary_collection_handle', $group->collection_handle)->exists());
    }

    public function canCollection(?Authenticatable $operator, string $handle): bool
    {
        if ($this->isSuper($operator)) {
            return true;
        }

        return Product::query()->whereKey($this->productIds($operator))
            ->where('primary_collection_handle', $handle)->exists();
    }

    /** Null is reserved for super admins; non-super entry lists are product-blueprint scoped. */
    public function blueprintHandlesForCollection(?Authenticatable $operator, string $handle): ?array
    {
        if ($this->isSuper($operator)) {
            return null;
        }

        return Product::query()->whereKey($this->productIds($operator))
            ->where('primary_collection_handle', $handle)
            ->whereNotNull('blueprint_handle')->get()
            ->filter(fn (Product $product) => app(NewsletterEntryProduct::class)
                ->find($handle, $product->blueprint_handle)?->is($product))
            ->pluck('blueprint_handle')->unique()->values()->all();
    }

    public function canEntry(?Authenticatable $operator, string $collectionHandle, ?string $blueprintHandle): bool
    {
        if (! $this->canCollection($operator, $collectionHandle)) {
            return false;
        }

        $handles = $this->blueprintHandlesForCollection($operator, $collectionHandle);

        return $handles === null || ($blueprintHandle !== null && in_array($blueprintHandle, $handles, true));
    }

    public function subscriberQuery(?Authenticatable $operator): Builder
    {
        $query = Subscriber::query();

        return $this->isSuper($operator) ? $query : $query->whereHas('allSubGroups.group', fn ($group) => $group
            ->whereIn('product_id', $this->productIds($operator))
            ->whereHas('product', fn ($product) => $product
                ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle')));
    }

    public function canSubscriber(?Authenticatable $operator, Subscriber $subscriber): bool
    {
        return $this->isSuper($operator) || $this->subscriberQuery($operator)->whereKey($subscriber->getKey())->exists();
    }
}
