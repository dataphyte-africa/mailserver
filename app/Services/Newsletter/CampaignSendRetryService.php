<?php

namespace App\Services\Newsletter;

use App\Models\CampaignSend;
use Illuminate\Database\Eloquent\Builder;

class CampaignSendRetryService
{
    public function retryableFailuresQuery(int|string|null $campaignId = null): Builder
    {
        $query = CampaignSend::query()
            ->where('status', 'failed')
            ->where(function (Builder $inner) {
                $inner->where('bounce_reason', 'like', '%attempted too many times%')
                    ->orWhere('bounce_reason', 'like', '%Daily limit exceeded%')
                    ->orWhere('bounce_reason', 'like', '%limit exceeded%')
                    ->orWhere('bounce_reason', 'like', '%421%')
                    ->orWhere('bounce_reason', 'like', '%Too many requests%');
            });

        if ($campaignId !== null) {
            $query->where('campaign_id', $campaignId);
        }

        return $query;
    }

    public function countRetryableFailures(int|string|null $campaignId = null): int
    {
        return (clone $this->retryableFailuresQuery($campaignId))->count();
    }

    public function requeueRetryableFailures(int|string|null $campaignId = null): int
    {
        return $this->retryableFailuresQuery($campaignId)->update([
            'status'       => 'queued',
            'failed_at'    => null,
            'bounced_at'   => null,
            'synced_at'    => null,
            'bounce_reason'=> null,
            'updated_at'   => now(),
        ]);
    }
}
