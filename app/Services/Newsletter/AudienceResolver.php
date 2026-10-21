<?php

namespace App\Services\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Collection;

/**
 * Resolves the final deduplicated list of active subscriber IDs
 * for a given campaign, based on its CampaignAudience rows.
 *
 * Audience rows can target:
 *   • SubscriberGroup  (send_to_all = true  → entire group)
 *   • SubscriberSubGroup (send_to_all = false → specific sub-group)
 *
 * Only 'active' subscribers are included.
 */
class AudienceResolver
{
    /**
     * Returns a deduplicated Collection of Subscriber models.
     *
     * @param  Campaign  $campaign
     * @return Collection<int, Subscriber>
     */
    public function resolve(Campaign $campaign): Collection
    {
        $subscriberIds = collect();

        foreach ($campaign->audiences as $audience) {
            $ids = $this->resolveAudienceRow($audience);
            $subscriberIds = $subscriberIds->merge($ids);
        }

        // Deduplicate, then load the full Subscriber models
        $uniqueIds = $subscriberIds->unique()->values();

        if ($uniqueIds->isEmpty()) {
            return collect();
        }

        return Subscriber::active()
            ->whereIn('id', $uniqueIds)
            ->whereNotIn('id', $this->optedOutSubscriberIds((int) $campaign->organisation_id))
            ->get();
    }

    public function includesSubscriber(Campaign $campaign, Subscriber $subscriber): bool
    {
        if ($subscriber->status !== 'active'
            || $this->optedOut((int) $campaign->organisation_id, (int) $subscriber->getKey())) {
            return false;
        }

        foreach ($campaign->audiences as $audience) {
            $target = $audience->targetable;
            $group = $target instanceof SubscriberGroup ? $target : $target?->group;
            if (! $group || $group->isArchived()
                || (int) $group->organisation_id !== (int) $campaign->organisation_id) {
                continue;
            }
            if ($subscriber->groupEngagements()->where('subscriber_group_id', $group->getKey())
                ->whereIn('rating', ['unsubscribed', 'suppressed'])->exists()) {
                continue;
            }
            if (! $subscriber->subGroups()->where('subscriber_group_id', $group->getKey())
                ->where('purpose', 'preference')->exists()) {
                continue;
            }
            if ($target instanceof SubscriberGroup) {
                return true;
            }
            if ($target instanceof SubscriberSubGroup && ! $target->isArchived()
                && ! in_array($target->slug, ['rating-unsubscribed', 'rating-suppressed'], true)
                && $subscriber->subGroups()->whereKey($target->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    public function includesPrivateMember(Campaign $campaign, Subscriber $subscriber): bool
    {
        return $campaign->audience_scope === 'organisation_private'
            && $this->includesSubscriber($campaign, $subscriber);
    }

    /**
     * Returns subscriber IDs for a single audience row.
     */
    private function resolveAudienceRow(CampaignAudience $audience): Collection
    {
        $target = $audience->targetable;

        if (! $target) {
            return collect();
        }

        // Target is an entire group (send_to_all)
        if ($target instanceof SubscriberGroup) {
            return $this->resolveGroup($target);
        }

        // Target is a specific sub-group
        if ($target instanceof SubscriberSubGroup) {
            return $this->resolveSubGroup($target);
        }

        return collect();
    }

    private function resolveGroup(SubscriberGroup $group): Collection
    {
        return Subscriber::active()
            ->whereNotIn('id', $this->optedOutSubscriberIds((int) $group->organisation_id))
            ->whereDoesntHave('groupEngagements', fn ($q) => $q
                ->where('subscriber_group_id', $group->getKey())->whereIn('rating', ['unsubscribed', 'suppressed']))
            ->whereHas('allSubGroups', function ($q) use ($group) {
                $q->where('subscriber_group_id', $group->id)
                  ->where('purpose', 'preference')
                  ->whereNull('subscriber_sub_group.unsubscribed_at');
            })
            ->pluck('id');
    }

    private function resolveSubGroup(SubscriberSubGroup $subGroup): Collection
    {
        if ($subGroup->purpose === 'engagement' && in_array($subGroup->slug, ['rating-unsubscribed', 'rating-suppressed'], true)) {
            return collect();
        }

        return Subscriber::active()
            ->whereNotIn('id', $this->optedOutSubscriberIds((int) $subGroup->group?->organisation_id))
            ->whereDoesntHave('groupEngagements', fn ($q) => $q
                ->where('subscriber_group_id', $subGroup->subscriber_group_id)->whereIn('rating', ['unsubscribed', 'suppressed']))
            ->whereHas('subGroups', fn ($members) => $members
                ->where('subscriber_group_id', $subGroup->subscriber_group_id)
                ->where('purpose', 'preference'))
            ->whereHas('subGroups', function ($q) use ($subGroup) {
                $q->where('subscriber_sub_groups.id', $subGroup->id);
            })
            ->pluck('id');
    }

    private function optedOutSubscriberIds(int $organisationId)
    {
        return \Illuminate\Support\Facades\DB::table('organisation_subscriber_opt_outs')
            ->where('organisation_id', $organisationId)->select('subscriber_id');
    }

    private function optedOut(int $organisationId, int $subscriberId): bool
    {
        return app(OrganisationMailOptOut::class)->has($organisationId, $subscriberId);
    }
}
