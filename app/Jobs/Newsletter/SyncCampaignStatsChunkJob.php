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

class SyncCampaignStatsChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 420;

    public function __construct(
        public readonly int $campaignId,
        public readonly array $sendIds,
        public readonly string $syncToken,
    ) {}

    public function handle(CampaignStatsSyncService $syncService): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign || ! $this->matchesCurrentRun($campaign)) {
            return;
        }

        $result = $syncService->syncSendIds($this->sendIds, false);

        if (! ($result['ok'] ?? false)) {
            $this->markFailedState($campaign, (string) ($result['error'] ?? 'Unable to sync campaign stats.'));
            return;
        }

        $processed = (int) ($result['processed'] ?? 0);

        if ($processed > 0) {
            Campaign::whereKey($campaign->id)->increment('last_stats_sync_processed', $processed);
        }

        if (! empty($result['errors'])) {
            $this->appendError($campaign, implode("\n", $result['errors']));
        }

        $campaign->refresh();

        if (! $this->matchesCurrentRun($campaign)) {
            return;
        }

        if ((int) $campaign->last_stats_sync_processed >= (int) $campaign->last_stats_sync_total) {
            $campaign->forceFill([
                'last_stats_sync_status' => filled($campaign->last_stats_sync_error) ? 'failed' : 'completed',
                'last_stats_sync_completed_at' => now(),
            ])->save();
        }
    }

    public function failed(\Throwable $e): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign || ! $this->matchesCurrentRun($campaign)) {
            return;
        }

        $this->markFailedState($campaign, $e->getMessage());
        Log::warning("SyncCampaignStatsChunkJob: campaign {$this->campaignId} failed — {$e->getMessage()}");
    }

    private function matchesCurrentRun(Campaign $campaign): bool
    {
        return optional($campaign->last_stats_sync_requested_at)?->toIso8601String() === $this->syncToken;
    }

    private function appendError(Campaign $campaign, string $error): void
    {
        $campaign->refresh();

        if (! $this->matchesCurrentRun($campaign)) {
            return;
        }

        $combined = trim(implode("\n", array_filter([
            $campaign->last_stats_sync_error,
            $error,
        ])));

        $campaign->forceFill([
            'last_stats_sync_error' => mb_substr($combined, 0, 65000),
        ])->save();
    }

    private function markFailedState(Campaign $campaign, string $error): void
    {
        $campaign->refresh();

        if (! $this->matchesCurrentRun($campaign)) {
            return;
        }

        $combined = trim(implode("\n", array_filter([
            $campaign->last_stats_sync_error,
            $error,
        ])));

        $campaign->forceFill([
            'last_stats_sync_status' => 'failed',
            'last_stats_sync_error' => mb_substr($combined, 0, 65000),
            'last_stats_sync_completed_at' => now(),
        ])->save();
    }
}
