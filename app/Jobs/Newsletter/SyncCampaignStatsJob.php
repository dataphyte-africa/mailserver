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

        $campaign->forceFill([
            'last_stats_sync_status' => 'processing',
            'last_stats_sync_completed_at' => null,
            'last_stats_sync_total' => 0,
            'last_stats_sync_processed' => 0,
            'last_stats_sync_error' => null,
        ])->save();

        try {
            $result = $syncService->sync(
                campaignId: $campaign->id,
                hours: null,
                days: 30,
                limit: 0,
                dryRun: false,
                applyWindow: false,
                onProgress: function (int $processed, int $total) use ($campaign): void {
                    if ($processed === 0 || $processed === $total || $processed % 25 === 0) {
                        $campaign->forceFill([
                            'last_stats_sync_status' => 'processing',
                            'last_stats_sync_total' => $total,
                            'last_stats_sync_processed' => $processed,
                        ])->save();
                    }
                },
            );

            if (! ($result['ok'] ?? false)) {
                $campaign->forceFill([
                    'last_stats_sync_status' => 'failed',
                    'last_stats_sync_error' => (string) ($result['error'] ?? 'Unable to sync campaign stats.'),
                ])->save();

                Log::warning("SyncCampaignStatsJob: campaign {$campaign->id} could not sync stats");
                return;
            }

            $campaign->forceFill([
                'last_stats_sync_status' => 'completed',
                'last_stats_sync_total' => (int) ($result['total'] ?? 0),
                'last_stats_sync_processed' => (int) ($result['processed'] ?? 0),
                'last_stats_sync_completed_at' => now(),
                'last_stats_sync_error' => empty($result['errors']) ? null : implode("\n", $result['errors']),
            ])->save();

            Log::info(
                "SyncCampaignStatsJob: campaign {$campaign->id} queued "
                . (($result['synced'] ?? 0)) . ' / ' . (($result['total'] ?? 0))
                . ' sync webhook jobs'
            );
        } catch (\Throwable $e) {
            $campaign->forceFill([
                'last_stats_sync_status' => 'failed',
                'last_stats_sync_error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }
}
