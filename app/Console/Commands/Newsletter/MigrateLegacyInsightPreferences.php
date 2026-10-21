<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use App\Support\Platform\Ownership\SubscriberGroupOwnershipWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MigrateLegacyInsightPreferences extends Command
{
    protected $signature = 'newsletter:migrate-legacy-insight-preferences {--apply : Copy memberships and publish matching product audiences}';

    protected $description = 'Preview or migrate the four legacy Insight product preferences without deleting historical records.';

    private const PRODUCTS = [
        'data-dive' => 'insight-newsletters-data-dive',
        'marina-maitama' => 'insight-newsletters-marina-maitama',
        'pocket-science' => 'insight-newsletters-pocket-science',
        'senorrita' => 'insight-newsletters-senorrita',
    ];

    public function handle(SubscriberGroupOwnershipWriter $writer, GroupEngagementService $engagement): int
    {
        $organisation = Organisation::query()->where('primary_collection_handle', 'insight_newsletters')->get();
        if ($organisation->count() !== 1) {
            $this->error('Expected exactly one Insight organisation. No changes made.');

            return self::FAILURE;
        }

        $legacy = SubscriberGroup::query()->where('slug', 'insight-subscribers')
            ->where('collection_handle', 'insight_newsletters')
            ->whereNull('organisation_id')->whereNull('product_id')->first();
        if (! $legacy) {
            $this->error('The unowned historical Insight audience was not found. No changes made.');

            return self::FAILURE;
        }

        $plans = [];
        foreach (self::PRODUCTS as $sourceSlug => $productSlug) {
            $source = $legacy->subGroups()->where('slug', $sourceSlug)->where('purpose', 'preference')->first();
            $product = Product::query()->where('organisation_id', $organisation->first()->getKey())
                ->where('slug', $productSlug)->where('status', 'active')
                ->where('primary_collection_handle', 'insight_newsletters')->first();
            if (! $source || ! $product) {
                $this->error("Missing or mismatched source preference or product: {$sourceSlug}. No changes made.");

                return self::FAILURE;
            }
            $plans[] = [$source, $product];
        }

        foreach ($plans as [$source, $product]) {
            $memberships = DB::table('subscriber_sub_group')
                ->where('subscriber_sub_group_id', $source->getKey());
            $this->line("{$source->name} -> {$product->name}: {$memberships->count()} historical memberships, "
                .$memberships->clone()->whereNull('unsubscribed_at')->count().' currently active.');
        }

        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply after reviewing the counts and backup.');

            return self::SUCCESS;
        }

        foreach ($plans as [$source, $product]) {
            $group = DB::transaction(function () use ($writer, $engagement, $source, $product): SubscriberGroup {
                $slug = \Illuminate\Support\Str::slug($product->name.' Subscribers');
                $group = SubscriberGroup::query()->where('slug', $slug)->first();
                if ($group && ($group->product_id !== null && (int) $group->product_id !== (int) $product->getKey())) {
                    throw new RuntimeException("Audience slug {$slug} belongs to another product.");
                }
                $group = $writer->updateForProduct($product, $group ?? new SubscriberGroup, [
                    'name' => $product->name.' Subscribers',
                    'slug' => $slug,
                    'collection_handle' => $product->primary_collection_handle,
                    'audience_scope' => 'product_public',
                    'description' => "Audience group for {$product->organisation?->name} / {$product->name}.",
                    'is_public_preference' => true,
                    'archived_at' => null,
                    'archived_by' => null,
                ]);
                $target = SubscriberSubGroup::query()->firstOrCreate(
                    ['subscriber_group_id' => $group->getKey(), 'slug' => 'newsletter'],
                    ['name' => 'Newsletter', 'purpose' => 'preference'],
                );
                if ($target->purpose !== 'preference' || $target->archived_at !== null) {
                    throw new RuntimeException("Newsletter subgroup for {$product->name} is not an active preference.");
                }

                DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $source->getKey())
                    ->orderBy('id')->chunkById(500, function ($rows) use ($source, $target): void {
                        $existing = DB::table('subscriber_sub_group')
                            ->where('subscriber_sub_group_id', $target->getKey())
                            ->whereIn('subscriber_id', $rows->pluck('subscriber_id'))
                            ->pluck('subscriber_id')->all();
                        $insert = $rows->reject(fn ($row) => in_array($row->subscriber_id, $existing))
                            ->map(fn ($row) => [
                                'subscriber_id' => $row->subscriber_id,
                                'subscriber_sub_group_id' => $target->getKey(),
                                'subscribed_at' => $row->subscribed_at,
                                'unsubscribed_at' => $row->unsubscribed_at,
                            ])->all();
                        if ($insert !== []) {
                            DB::table('subscriber_sub_group')->insertOrIgnore($insert);
                        }
                        DB::table('subscriber_sub_group')->whereIn('id', $rows->pluck('id'))
                            ->whereNull('unsubscribed_at')->update(['unsubscribed_at' => now()]);
                    });
                $engagement->ensureSegments($group);

                return $group;
            });
            $this->info("Migrated {$source->name} to {$group->name}; original rows retained as opted out.");
        }

        $this->info('Webinars and Insight Update remain in the historical audience. Run newsletter:backfill-engagement-segments to populate the new rating buckets.');

        return self::SUCCESS;
    }
}
