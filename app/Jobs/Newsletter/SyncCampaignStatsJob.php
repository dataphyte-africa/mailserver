<?php

namespace App\Jobs\Newsletter;

use App\Models\Campaign;
use App\Services\Newsletter\CampaignStatsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCampaignStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(public readonly int $campaignId) {}

    public function handle(CampaignStatsSyncService $syncService): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning("SyncCampaignStatsJob: campaign {$this->campaignId} not found");
            return;
        }

        $result = $syncService->sync(
            campaignId: $campaign->id,
            hours: null,
            days: 30,
            limit: 0,
            dryRun: false,
            applyWindow: false,
        );

        if (! ($result['ok'] ?? false)) {
            Log::warning("SyncCampaignStatsJob: campaign {$campaign->id} could not sync stats");
            return;
        }

        Log::info(
            "SyncCampaignStatsJob: campaign {$campaign->id} queued "
            . (($result['synced'] ?? 0)) . ' / ' . (($result['total'] ?? 0))
            . ' sync webhook jobs'
        );
    }
}
