<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Campaign;
use App\Services\Newsletter\CampaignFinalizer;
use Illuminate\Console\Command;

/**
 * Reconciles any 'sending' campaign once all sends have left the
 * 'queued' state. Partial failures remain visible as `partial`.
 */
class FinalizeCampaigns extends Command
{
    protected $signature   = 'campaigns:finalize';
    protected $description = 'Finalize sending campaigns once no queued sends remain';

    public function handle(CampaignFinalizer $finalizer): void
    {
        $sending = Campaign::where('status', 'sending')->get();

        if ($sending->isEmpty()) {
            return;
        }

        foreach ($sending as $campaign) {
            $status = $finalizer->finalize($campaign);

            if ($status !== null) {
                $this->line("Campaign #{$campaign->id} \"{$campaign->name}\" → {$status}");
                \Illuminate\Support\Facades\Log::info("FinalizeCampaigns: campaign {$campaign->id} marked {$status}");
            }
        }
    }
}
