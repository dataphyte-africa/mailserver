<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\CampaignAudienceOwnershipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairLegacyInsightDraftAudiences extends Command
{
    protected $signature = 'newsletter:repair-legacy-insight-drafts {--apply : Replace exact legacy draft targets}';

    protected $description = 'Preview or repair Insight drafts targeting unowned product-named legacy preferences.';

    private const PREFERENCES = [
        'data-dive' => 'insight-newsletters-data-dive',
        'marina-maitama' => 'insight-newsletters-marina-maitama',
        'pocket-science' => 'insight-newsletters-pocket-science',
        'senorrita' => 'insight-newsletters-senorrita',
    ];

    public function handle(CampaignAudienceOwnershipService $audiences): int
    {
        $legacy = SubscriberGroup::query()->where('slug', 'insight-subscribers')
            ->where('collection_handle', 'insight_newsletters')
            ->whereNull('organisation_id')->whereNull('product_id')->first();
        if (! $legacy) {
            $this->warn('No unowned Insight legacy audience found. Nothing changed.');

            return self::SUCCESS;
        }

        $repaired = 0;
        foreach (self::PREFERENCES as $sourceSlug => $productSlug) {
            $source = $legacy->subGroups()->where('slug', $sourceSlug)->where('purpose', 'preference')->first();
            if (! $source) {
                continue;
            }

            $candidates = Campaign::query()->where('status', 'draft')
                ->where('collection', 'insight_newsletters')
                ->whereHas('product', fn ($query) => $query->where('slug', $productSlug))
                ->whereHas('audiences', fn ($query) => $query
                    ->where('targetable_type', $source->getMorphClass())
                    ->where('targetable_id', $source->getKey())
                    ->where('send_to_all', false))
                ->with(['product', 'audiences'])->get();

            foreach ($candidates as $campaign) {
                $product = $campaign->product;
                if (! $product instanceof Product || $product->status !== 'active'
                    || $product->primary_collection_handle !== 'insight_newsletters'
                    || (int) $campaign->organisation_id !== (int) $product->organisation_id
                    || $campaign->audience_scope !== 'product_public'
                    || $campaign->audiences->count() !== 1) {
                    $this->warn("Skipped campaign {$campaign->id}: ownership or audience is ambiguous.");
                    continue;
                }

                $groups = SubscriberGroup::query()->where('product_id', $product->id)
                    ->where('organisation_id', $product->organisation_id)
                    ->where('collection_handle', 'insight_newsletters')
                    ->whereNull('archived_at')->limit(2)->get();
                if ($groups->count() !== 1) {
                    $this->warn("Skipped campaign {$campaign->id}: expected exactly one active product audience.");
                    continue;
                }

                $target = $groups->first();
                $this->line("Campaign {$campaign->id}: {$source->name} -> {$target->name} (send to all)");
                if (! $this->option('apply')) {
                    continue;
                }

                DB::transaction(function () use ($campaign, $source, $target, $product, $audiences): void {
                    $saved = CampaignAudience::query()->where('campaign_id', $campaign->id)->lockForUpdate()->get();
                    if ($saved->count() !== 1 || $campaign->fresh()->status !== 'draft'
                        || $saved->first()->targetable_type !== $source->getMorphClass()
                        || (int) $saved->first()->targetable_id !== (int) $source->id
                        || $saved->first()->send_to_all) {
                        throw new \RuntimeException("Campaign {$campaign->id} changed during repair; no target was replaced.");
                    }
                    $saved->first()->update([
                        'targetable_type' => $target->getMorphClass(),
                        'targetable_id' => $target->id,
                        'send_to_all' => true,
                    ]);
                    $audiences->validatePersistedForProduct($campaign, $product);
                });
                $repaired++;
            }
        }

        $this->info($this->option('apply') ? "Repaired {$repaired} draft audience(s)." : 'Preview only. Re-run with --apply after reviewing.');

        return self::SUCCESS;
    }
}
