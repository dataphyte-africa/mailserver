<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\SendNewsletterEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Services\Newsletter\CampaignFinalizer;
use Illuminate\Console\Command;

/**
 * Processes queued campaign sends directly (bypasses Redis queue).
 * Uses the shared send-rate throttle configured for the normal email queue.
 */
class SendQueuedEmails extends Command
{
    protected $signature   = 'campaigns:send-queued
        {--campaign= : Limit to specific campaign ID}
        {--retry-failed : Requeue retryable failed sends before processing}';
    protected $description = 'Send queued campaign emails directly, with optional retry of transient failures';

    public function handle(CampaignFinalizer $finalizer): void
    {
        $campaignId = $this->option('campaign');

        if ($this->option('retry-failed')) {
            $requeued = $this->requeueRetryableFailures($campaignId);
            $this->info("Re-queued {$requeued} retryable failed sends.");
        }

        $query = CampaignSend::where('status', 'queued');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $total = $query->count();
        $rate = max(1, (int) config('newsletter.send_rate', 50));
        $this->info("Processing {$total} queued sends at {$rate}/min...");

        $sent = 0;
        $failed = 0;
        $batchCount = 0;

        $query->chunkById(50, function ($sends) use (&$sent, &$failed, &$batchCount) {
            foreach ($sends as $send) {
                try {
                    $job = new SendNewsletterEmailJob($send->id);
                    $job->handle();
                    $sent++;
                } catch (\Throwable $e) {
                    $this->warn("Send #{$send->id} failed: " . $e->getMessage());
                    $failed++;
                }

                $batchCount++;

                if ($batchCount % 100 === 0) {
                    $this->line("Progress: {$batchCount} processed, {$sent} sent, {$failed} failed");
                }
            }
        });

        $this->info("Done. Sent: {$sent} | Failed: {$failed}");

        // Reconcile any sending campaign that has no queued sends left.
        $this->finalizeCampaigns($finalizer);
    }

    private function finalizeCampaigns(CampaignFinalizer $finalizer): void
    {
        $sending = Campaign::where('status', 'sending')->get();

        foreach ($sending as $campaign) {
            $status = $finalizer->finalize($campaign);

            if ($status !== null) {
                $this->line("Campaign #{$campaign->id} marked as {$status}.");
            }
        }
    }

    private function requeueRetryableFailures(?string $campaignId): int
    {
        $query = CampaignSend::query()
            ->where('status', 'failed')
            ->where(function ($inner) {
                $inner->where('bounce_reason', 'like', '%attempted too many times%')
                    ->orWhere('bounce_reason', 'like', '%Daily limit exceeded%')
                    ->orWhere('bounce_reason', 'like', '%limit exceeded%')
                    ->orWhere('bounce_reason', 'like', '%421%')
                    ->orWhere('bounce_reason', 'like', '%Too many requests%');
            });

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        return $query->update([
            'status' => 'queued',
            'failed_at' => null,
            'bounce_reason' => null,
            'updated_at' => now(),
        ]);
    }
}
