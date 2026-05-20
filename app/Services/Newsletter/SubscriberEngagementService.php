<?php

namespace App\Services\Newsletter;

use App\Models\Subscriber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SubscriberEngagementService
{
    private const WINDOW_SIZE = 10;
    private const SUPPRESSED_STATUSES = ['unsubscribed', 'bounced', 'complained'];

    public function compute(Subscriber $subscriber): array
    {
        $recentSends = $subscriber->campaignSends()
            ->orderByRaw('COALESCE(clicked_at, opened_at, sent_at, created_at) desc')
            ->limit(self::WINDOW_SIZE)
            ->get(['status', 'sent_at', 'opened_at', 'clicked_at', 'failed_at', 'bounced_at']);

        $lastEngagedAt = $subscriber->campaignSends()
            ->selectRaw('MAX(COALESCE(clicked_at, opened_at)) as last_engaged_at')
            ->value('last_engaged_at');

        $lastEngagedAt = filled($lastEngagedAt) ? Carbon::parse($lastEngagedAt) : null;

        if (in_array($subscriber->status, self::SUPPRESSED_STATUSES, true)) {
            return [
                'engagement_score' => 0,
                'engagement_rating' => 'suppressed',
                'last_engaged_at' => $lastEngagedAt,
            ];
        }

        $score = $this->scoreWindow($recentSends);
        $recentClick = $this->hasRecentClick($recentSends, 30);
        $engagementCount = $recentSends->filter(fn ($send) => $send->clicked_at || $send->opened_at)->count();
        $sentCount = (int) $subscriber->campaignSends()->count();

        $rating = match (true) {
            $recentClick && $score >= 8 => 'engaged',
            $this->isAtRisk($sentCount, $engagementCount, $lastEngagedAt) => 'at_risk',
            $score >= 4 && $this->engagedRecently($lastEngagedAt, 90) => 'warm',
            default => 'cold',
        };

        return [
            'engagement_score' => $score,
            'engagement_rating' => $rating,
            'last_engaged_at' => $lastEngagedAt,
        ];
    }

    public function persist(Subscriber $subscriber): Subscriber
    {
        $payload = $this->compute($subscriber);

        $subscriber->forceFill($payload)->save();

        return $subscriber->refresh();
    }

    private function scoreWindow(Collection $recentSends): int
    {
        return (int) $recentSends->sum(function ($send) {
            if ($send->clicked_at) {
                return 5;
            }

            if ($send->opened_at) {
                return 2;
            }

            if (in_array($send->status, ['failed', 'bounced'], true)) {
                return -2;
            }

            return 0;
        });
    }

    private function hasRecentClick(Collection $recentSends, int $days): bool
    {
        $cutoff = now()->subDays($days);

        return $recentSends->contains(function ($send) use ($cutoff) {
            return $send->clicked_at && Carbon::parse($send->clicked_at)->gte($cutoff);
        });
    }

    private function engagedRecently(?Carbon $lastEngagedAt, int $days): bool
    {
        return $lastEngagedAt?->gte(now()->subDays($days)) ?? false;
    }

    private function isAtRisk(int $sentCount, int $engagementCount, ?Carbon $lastEngagedAt): bool
    {
        if ($sentCount >= self::WINDOW_SIZE && $engagementCount === 0) {
            return true;
        }

        if (! $lastEngagedAt && $sentCount >= self::WINDOW_SIZE) {
            return true;
        }

        return $lastEngagedAt?->lt(now()->subDays(90)) ?? false;
    }
}
