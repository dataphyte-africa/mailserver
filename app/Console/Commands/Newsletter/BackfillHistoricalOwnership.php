<?php

namespace App\Console\Commands\Newsletter;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillHistoricalOwnership extends Command
{
    protected $signature = 'newsletter:backfill-historical-ownership
        {--apply : Persist deterministic ownership updates}
        {--json : Emit JSON report}';

    protected $description = 'Backfill deterministic newsletter ownership without guessing ambiguous historical records.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $report = $apply
            ? DB::transaction(fn () => $this->buildReport(true))
            : $this->buildReport(false);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(['Area', 'Updated', 'Skipped'], [
            ['Subscriber groups', (string) $report['subscriber_groups']['updated_count'], (string) $report['subscriber_groups']['skipped_count']],
            ['Campaigns', (string) $report['campaigns']['updated_count'], (string) $report['campaigns']['skipped_count']],
        ]);

        if (! empty($report['remaining_blockers'])) {
            $this->warn('Remaining blockers:');

            foreach ($report['remaining_blockers'] as $blocker) {
                $this->line("- {$blocker}");
            }
        }

        $this->info($apply ? 'Deterministic historical ownership backfill applied.' : 'Dry-run complete. Re-run with --apply to persist deterministic updates.');

        return self::SUCCESS;
    }

    private function buildReport(bool $apply): array
    {
        $products = $this->products();
        $groups = $this->backfillGroups($products, $apply);
        $campaigns = $this->backfillCampaigns($products, $apply);

        return [
            'applied' => $apply,
            'subscriber_groups' => $groups,
            'campaigns' => $campaigns,
            'remaining_blockers' => [
                'Collections with multiple active products still require product-specific signals before ownership can be inferred.',
                'Missing campaign audience targets are retained for historical readability and require explicit deletion/remap approval.',
                'Subscribers without membership are not auto-assigned to avoid unsafe audience guessing.',
            ],
        ];
    }

    private function products(): array
    {
        $products = DB::table('products as p')
            ->join('organisations as o', 'o.id', '=', 'p.organisation_id')
            ->where('p.status', 'active')
            ->where('o.status', 'active')
            ->get([
                'p.id',
                'p.name',
                'p.slug',
                'p.blueprint_handle',
                'p.primary_collection_handle',
                'p.organisation_id',
            ]);

        return [
            'by_collection' => $products->groupBy('primary_collection_handle'),
            'by_collection_and_signal' => $products
                ->flatMap(function ($product) {
                    $signals = collect([
                        $product->blueprint_handle,
                        $product->slug,
                        Str::slug((string) $product->name),
                    ])
                        ->filter()
                        ->flatMap(fn ($signal) => [$this->normaliseSignal((string) $signal)])
                        ->unique()
                        ->values();

                    return $signals->mapWithKeys(fn ($signal) => [
                        $product->primary_collection_handle.'|'.$signal => $product,
                    ]);
                }),
        ];
    }

    private function backfillGroups(array $products, bool $apply): array
    {
        $updated = [];
        $skipped = [];

        DB::table('subscriber_groups')
            ->where(function ($query) {
                $query->whereNull('organisation_id')->orWhereNull('product_id');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($group) use ($products, $apply, &$updated, &$skipped) {
                $product = $this->exactCollectionProduct($products, $group->collection_handle);

                if (! $product) {
                    $skipped[] = [
                        'id' => (int) $group->id,
                        'slug' => $group->slug,
                        'reason' => 'collection_not_exactly_one_active_product',
                    ];

                    return;
                }

                $payload = [
                    'organisation_id' => (int) $product->organisation_id,
                    'product_id' => (int) $product->id,
                    'updated_at' => now(),
                ];

                if ($apply) {
                    DB::table('subscriber_groups')->where('id', $group->id)->update($payload);
                }

                $updated[] = [
                    'id' => (int) $group->id,
                    'slug' => $group->slug,
                    'product_id' => (int) $product->id,
                    'organisation_id' => (int) $product->organisation_id,
                    'reason' => 'exact_collection_product',
                ];
            });

        return [
            'updated_count' => count($updated),
            'skipped_count' => count($skipped),
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function backfillCampaigns(array $products, bool $apply): array
    {
        $updated = [];
        $skipped = [];

        DB::table('campaigns')
            ->where(function ($query) {
                $query->whereNull('organisation_id')->orWhereNull('product_id');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($campaign) use ($products, $apply, &$updated, &$skipped) {
                [$product, $reason] = $this->resolveCampaignProduct($products, $campaign);

                if (! $product) {
                    $skipped[] = [
                        'id' => (int) $campaign->id,
                        'name' => $campaign->name,
                        'collection' => $campaign->collection,
                        'reason' => 'ambiguous_or_missing_product_signal',
                    ];

                    return;
                }

                $payload = [
                    'organisation_id' => (int) $product->organisation_id,
                    'product_id' => (int) $product->id,
                    'updated_at' => now(),
                ];

                if ($apply) {
                    DB::table('campaigns')->where('id', $campaign->id)->update($payload);
                }

                $updated[] = [
                    'id' => (int) $campaign->id,
                    'name' => $campaign->name,
                    'collection' => $campaign->collection,
                    'product_id' => (int) $product->id,
                    'organisation_id' => (int) $product->organisation_id,
                    'reason' => $reason,
                ];
            });

        return [
            'updated_count' => count($updated),
            'skipped_count' => count($skipped),
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function resolveCampaignProduct(array $products, object $campaign): array
    {
        $exact = $this->exactCollectionProduct($products, $campaign->collection);

        if ($exact) {
            return [$exact, 'exact_collection_product'];
        }

        $signals = DB::table('campaign_audiences as ca')
            ->leftJoin('subscriber_sub_groups as ssg', function ($join) {
                $join->on('ssg.id', '=', 'ca.targetable_id')
                    ->where('ca.targetable_type', '=', 'subscriber_sub_group');
            })
            ->where('ca.campaign_id', $campaign->id)
            ->pluck('ssg.slug')
            ->filter()
            ->map(fn ($signal) => $this->normaliseSignal((string) $signal))
            ->unique();

        foreach ($signals as $signal) {
            $product = $products['by_collection_and_signal']->get($campaign->collection.'|'.$signal);

            if ($product) {
                return [$product, 'campaign_audience_subgroup_signal'];
            }
        }

        return [null, null];
    }

    private function exactCollectionProduct(array $products, ?string $collectionHandle): ?object
    {
        if (! is_string($collectionHandle) || trim($collectionHandle) === '') {
            return null;
        }

        $matches = $products['by_collection']->get($collectionHandle, collect());

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normaliseSignal(string $signal): string
    {
        return Str::of($signal)->replace('_', '-')->slug()->toString();
    }
}
