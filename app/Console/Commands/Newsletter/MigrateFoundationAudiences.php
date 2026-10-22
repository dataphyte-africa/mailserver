<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MigrateFoundationAudiences extends Command
{
    protected $signature = 'newsletter:migrate-foundation-audiences {--apply : Apply the previewed changes}';

    protected $description = 'Preview or align legacy Foundation preferences with two product audiences, retaining the private audience.';

    private const PRODUCTS = [
        'foundation-newsletters-weekly' => ['name' => 'Foundation Update', 'source' => 'weekly'],
        'foundation-newsletters-activities' => ['name' => 'Behind the Bytes', 'source' => 'activities'],
    ];

    private const RETIRED = [
        'foundation-newsletters-foundation-newsletters',
        'foundation-newsletters-project-update',
    ];

    public function handle(GroupEngagementService $engagement): int
    {
        if (SubscriberGroup::query()->where('slug', 'like', 'foundation-private-%')->exists()) {
            $this->error('Named Foundation private audiences already exist. Choose source audiences explicitly before any product migration. No changes made.');

            return self::FAILURE;
        }

        $organisations = Organisation::query()->where('primary_collection_handle', 'foundation_newsletters')->get();
        if ($organisations->count() !== 1) {
            $this->error('Expected exactly one Foundation organisation. No changes made.');

            return self::FAILURE;
        }

        $organisation = $organisations->first();
        $legacy = SubscriberGroup::query()->where('slug', 'foundation')->first();
        if (! $legacy || $legacy->product_id !== null
            || ($legacy->organisation_id !== null && (int) $legacy->organisation_id !== (int) $organisation->id)) {
            $this->error('The Foundation legacy audience is missing or belongs elsewhere. No changes made.');

            return self::FAILURE;
        }

        $products = Product::query()->where('organisation_id', $organisation->id)
            ->whereIn('slug', array_merge(array_keys(self::PRODUCTS), self::RETIRED))
            ->get()->keyBy('slug');
        foreach (array_merge(array_keys(self::PRODUCTS), self::RETIRED) as $slug) {
            if (! $products->has($slug) || $products->get($slug)->primary_collection_handle !== 'foundation_newsletters') {
                $this->error("Missing or mismatched Foundation product: {$slug}. No changes made.");

                return self::FAILURE;
            }
        }

        $sources = [];
        foreach (self::PRODUCTS as $slug => $definition) {
            $source = $legacy->subGroups()->where('slug', $definition['source'])
                ->where('purpose', 'preference')->first();
            if (! $source) {
                $this->error("Missing legacy preference: {$definition['source']}. No changes made.");

                return self::FAILURE;
            }
            $sources[$slug] = $source;
            $count = DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $source->id)
                ->whereNull('unsubscribed_at')->count();
            $this->line("{$source->name} -> {$definition['name']}: {$count} active memberships to copy.");
        }

        $this->line('Foundation stays private with all original memberships. Other two products become inactive; their records are retained.');
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply after reviewing the counts and backup.');

            return self::SUCCESS;
        }

        $targets = DB::transaction(function () use ($legacy, $organisation, $products, $sources): array {
            $legacy->update([
                'organisation_id' => $organisation->id,
                'audience_scope' => 'organisation_private',
                'is_public_preference' => false,
                'archived_at' => null,
                'archived_by' => null,
            ]);

            foreach (self::RETIRED as $slug) {
                $product = $products->get($slug);
                $product->update(['status' => 'inactive']);
                $product->subscriberGroups()->update([
                    'is_public_preference' => false,
                    'archived_at' => now(),
                ]);
            }

            $targets = [];
            foreach (self::PRODUCTS as $slug => $definition) {
                $product = $products->get($slug);
                $product->update(['name' => $definition['name'], 'status' => 'active']);

                $group = SubscriberGroup::query()->where('product_id', $product->id)->first();
                if (! $group) {
                    $groupSlug = Str::slug($definition['name'].' Subscribers');
                    if (SubscriberGroup::query()->where('slug', $groupSlug)->exists()) {
                        throw new RuntimeException("Audience slug {$groupSlug} is already in use.");
                    }
                    $group = new SubscriberGroup(['slug' => $groupSlug]);
                }
                if ($group->organisation_id !== null && (int) $group->organisation_id !== (int) $organisation->id) {
                    throw new RuntimeException("Audience for {$product->name} belongs to another organisation.");
                }
                $group->fill([
                    'organisation_id' => $organisation->id,
                    'product_id' => $product->id,
                    'audience_scope' => 'product_public',
                    'name' => $definition['name'].' Subscribers',
                    'collection_handle' => 'foundation_newsletters',
                    'description' => "Audience group for {$organisation->name} / {$definition['name']}.",
                    'is_public_preference' => true,
                    'archived_at' => null,
                    'archived_by' => null,
                ]);
                $group->save();

                $newsletter = SubscriberSubGroup::query()->firstOrCreate(
                    ['subscriber_group_id' => $group->id, 'slug' => 'newsletter'],
                    ['name' => 'Newsletter', 'purpose' => 'preference'],
                );
                if ($newsletter->purpose !== 'preference' || $newsletter->archived_at !== null) {
                    throw new RuntimeException("Newsletter preference for {$product->name} is not active.");
                }

                foreach ($group->subGroups()->where('purpose', 'preference')->whereKeyNot($newsletter->id)->get() as $oldPreference) {
                    $this->copyMemberships($oldPreference, $newsletter, true);
                    $oldPreference->update(['archived_at' => now()]);
                }

                $this->copyMemberships($sources[$slug], $newsletter, false);
                $targets[] = $group->id;
            }

            return $targets;
        });

        foreach ($targets as $groupId) {
            $engagement->recalculateGroup(SubscriberGroup::query()->findOrFail($groupId));
        }
        $engagement->recalculateGroup($legacy->fresh());
        $this->info('Foundation audiences aligned. Original private memberships were retained.');

        return self::SUCCESS;
    }

    private function copyMemberships(SubscriberSubGroup $source, SubscriberSubGroup $target, bool $expireSource): void
    {
        DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $source->id)
            ->orderBy('id')->chunkById(500, function ($rows) use ($source, $target, $expireSource): void {
                DB::table('subscriber_sub_group')->insertOrIgnore($rows->map(fn ($row) => [
                    'subscriber_id' => $row->subscriber_id,
                    'subscriber_sub_group_id' => $target->id,
                    'subscribed_at' => $row->subscribed_at,
                    'unsubscribed_at' => $row->unsubscribed_at,
                ])->all());
                if ($expireSource) {
                    DB::table('subscriber_sub_group')->whereIn('id', $rows->pluck('id'))
                        ->whereNull('unsubscribed_at')->update(['unsubscribed_at' => now()]);
                }
            });
    }
}
