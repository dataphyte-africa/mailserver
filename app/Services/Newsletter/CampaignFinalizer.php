<?php

namespace App\Services\Newsletter;

use App\Models\Campaign;

class CampaignFinalizer
{
    /**
     * Finalize a campaign once no sends remain queued.
     *
     * Returns the new terminal status when a transition happens, otherwise null.
     */
    public function finalize(Campaign $campaign): ?string
    {
        $summary = $campaign->sends()
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = "queued") as queued,
                SUM(status IN ("failed","bounced","complained")) as failed
            ')
            ->first();

        if (! $summary) {
            return null;
        }

        $total = (int) ($summary->total ?? 0);
        $queued = (int) ($summary->queued ?? 0);
        $failed = (int) ($summary->failed ?? 0);

        if ($total === 0 || $queued > 0) {
            return null;
        }

        $status = $failed > 0 ? 'partial' : 'sent';

        if ($campaign->status !== $status) {
            $campaign->update(['status' => $status]);
        }

        return $status;
    }
}
