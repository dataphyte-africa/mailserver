<?php

namespace App\Jobs\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Services\Newsletter\CampaignSendRetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResumeFailedCampaignSendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(public readonly int $campaignId) {}

    public function handle(CampaignSendRetryService $retryService): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning("ResumeFailedCampaignSendsJob: campaign {$this->campaignId} not found");
            return;
        }

        $requeued = $retryService->requeueRetryableFailures($campaign->id);

        if ($requeued === 0) {
            Log::info("ResumeFailedCampaignSendsJob: campaign {$campaign->id} had no retryable failed sends");
            return;
        }

        $campaign->forceFill([
            'status' => 'sending',
        ])->save();

        $dispatched = 0;

        CampaignSend::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->orderBy('id')
            ->chunkById(500, function ($sends) use (&$dispatched) {
                foreach ($sends as $send) {
                    SendNewsletterEmailJob::dispatch($send->id)->onQueue('emails');
                    $dispatched++;
                }
            });

        Log::info("ResumeFailedCampaignSendsJob: campaign {$campaign->id} requeued {$requeued} sends and dispatched {$dispatched} resend jobs");
    }
}
