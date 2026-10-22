<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AlignFoundationProductAudiences extends Command
{
    protected $signature = 'newsletter:align-foundation-product-audiences {--apply : Link the two restored Foundation audiences to their products}';

    protected $description = 'Preview or link Weekly Update and Activities audiences to their renamed Foundation products without copying memberships.';

    private const ACTIVE = [
        'foundation-newsletters-weekly' => [
            'name' => 'Foundation Update',
            'blueprint' => 'weekly',
            'source_slug' => 'foundation-private-weekly',
            'target_slug' => 'foundation-update-subscribers',
        ],
        'foundation-newsletters-activities' => [
            'name' => 'Behind the Bytes',
            'blueprint' => 'activities',
            'source_slug' => 'foundation-private-activities',
            'target_slug' => 'behind-the-bytes-subscribers',
        ],
    ];

    private const RETIRED = [
        'foundation-newsletters-foundation-newsletters',
        'foundation-newsletters-project-update',
    ];

    public function handle(): int
    {
        $organisation = Organisation::query()
            ->where('primary_collection_handle', 'foundation_newsletters')->sole();
        $products = Product::query()->where('organisation_id', $organisation->id)
            ->whereIn('slug', array_merge(array_keys(self::ACTIVE), self::RETIRED))
            ->get()->keyBy('slug');
        foreach (array_merge(array_keys(self::ACTIVE), self::RETIRED) as $slug) {
            if (! $products->has($slug) || $products->get($slug)->primary_collection_handle !== 'foundation_newsletters') {
                $this->error("Missing or mismatched Foundation product: {$slug}. No changes made.");

                return self::FAILURE;
            }
        }

        $groups = [];
        foreach (self::ACTIVE as $slug => $definition) {
            $product = $products->get($slug);
            if ($product->blueprint_handle !== $definition['blueprint']) {
                $this->error("Unexpected blueprint for {$product->name}. No changes made.");

                return self::FAILURE;
            }

            $source = SubscriberGroup::query()->where('slug', $definition['source_slug'])->first();
            $linked = SubscriberGroup::query()->where('product_id', $product->id)->first();
            if ($source && $linked && $source->id !== $linked->id) {
                $this->error("{$product->name} already has a different product audience. No changes made.");

                return self::FAILURE;
            }
            $group = $source ?? $linked;
            if (! $group || (int) $group->organisation_id !== (int) $organisation->id
                || $group->collection_handle !== 'foundation_newsletters' || $group->isArchived()
                || ($group->product_id !== null && (int) $group->product_id !== (int) $product->id)) {
                $this->error("Restored audience for {$product->name} is missing or has conflicting ownership. No changes made.");

                return self::FAILURE;
            }
            if ($group->product_id === null && ! $group->isPrivateAudience()) {
                $this->error("{$group->name} is not a restored private audience. No changes made.");

                return self::FAILURE;
            }
            if ($group->slug !== $definition['target_slug']
                && SubscriberGroup::query()->where('slug', $definition['target_slug'])->exists()) {
                $this->error("Target audience slug is already in use: {$definition['target_slug']}. No changes made.");

                return self::FAILURE;
            }

            $members = $group->subGroups()->where('purpose', 'preference')
                ->whereNull('archived_at')->get();
            if ($members->count() !== 1 || ! in_array($members->first()->slug, ['members', 'newsletter'], true)
                || $group->subGroups()->where('slug', 'newsletter')->whereKeyNot($members->first()->id)->exists()) {
                $this->error("{$group->name} has an unexpected preference structure. No changes made.");

                return self::FAILURE;
            }
            if ($group->isPrivateAudience() && $this->hasCampaignTargets($group)) {
                $this->error("{$group->name} is already targeted by a campaign. No changes made.");

                return self::FAILURE;
            }

            $count = DB::table('subscriber_sub_group as membership')
                ->join('subscribers', 'subscribers.id', '=', 'membership.subscriber_id')
                ->where('membership.subscriber_sub_group_id', $members->first()->id)
                ->whereNull('membership.unsubscribed_at')->where('subscribers.status', 'active')
                ->distinct()->count('subscribers.id');
            $this->line("{$group->name} -> {$definition['name']}: {$count} active subscribers; no memberships will be copied.");
            $groups[$slug] = [$group, $members->first()];
        }

        $this->line('Foundation Newsletter and Project Update will become inactive. Other private audiences and legacy campaign targets remain unchanged.');
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply after reviewing the counts and backup.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($organisation, $products, $groups): void {
            foreach (self::RETIRED as $slug) {
                $products->get($slug)->update(['status' => 'inactive']);
            }
            foreach (self::ACTIVE as $slug => $definition) {
                [$group, $members] = $groups[$slug];
                $product = $products->get($slug);
                $product->update(['name' => $definition['name'], 'status' => 'active']);
                $group->update([
                    'organisation_id' => $organisation->id,
                    'product_id' => $product->id,
                    'audience_scope' => 'product_public',
                    'name' => $definition['name'].' Subscribers',
                    'slug' => $definition['target_slug'],
                    'collection_handle' => 'foundation_newsletters',
                    'description' => "Audience group for {$organisation->name} / {$definition['name']}.",
                    'is_public_preference' => true,
                ]);
                $members->update(['name' => 'Newsletter', 'slug' => 'newsletter']);
            }
        });

        $this->info('The two Foundation products now use their restored public audiences. No subscriber membership was copied or removed.');

        return self::SUCCESS;
    }

    private function hasCampaignTargets(SubscriberGroup $group): bool
    {
        $subGroupIds = $group->subGroups()->pluck('id');

        return DB::table('campaign_audiences')->where(function ($query) use ($group, $subGroupIds): void {
            $query->where(fn ($target) => $target->where('targetable_type', 'subscriber_group')
                ->where('targetable_id', $group->id))
                ->orWhere(fn ($target) => $target->where('targetable_type', 'subscriber_sub_group')
                    ->whereIn('targetable_id', $subGroupIds));
        })->exists();
    }
}
