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
    public int $timeout = 120;
    private const CHUNK_SIZE = 100;

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
            $syncToken = optional($campaign->last_stats_sync_requested_at)?->toIso8601String();

            $sendIds = $syncService->eligibleSendIds(
                campaignId: $campaign->id,
                hours: null,
                days: 30,
                limit: 0,
                applyWindow: false,
            );

            $total = count($sendIds);

            if ($total === 0) {
                $campaign->forceFill([
                    'last_stats_sync_status' => 'completed',
                    'last_stats_sync_total' => 0,
                    'last_stats_sync_processed' => 0,
                    'last_stats_sync_completed_at' => now(),
                ])->save();
                return;
            }

            $campaign->forceFill([
                'last_stats_sync_status' => 'processing',
                'last_stats_sync_total' => $total,
                'last_stats_sync_processed' => 0,
            ])->save();

            foreach (array_chunk($sendIds, self::CHUNK_SIZE) as $chunk) {
                SyncCampaignStatsChunkJob::dispatch($campaign->id, $chunk, (string) $syncToken)
                    ->onQueue('campaigns');
            }

            Log::info(
                "SyncCampaignStatsJob: campaign {$campaign->id} dispatched "
                . ceil($total / self::CHUNK_SIZE) . " chunk job(s) for {$total} sends"
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
