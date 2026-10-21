<?php

namespace App\Services\Newsletter;

use App\Models\OrganisationEngagementSetting;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberGroupEngagement;
use App\Models\SubscriberSubGroup;

class GroupEngagementService
{
    public const SEGMENTS = [
        'new' => 'New / Unrated',
        'high' => 'High',
        'moderate' => 'Moderate',
        'low' => 'Low',
        'inactive' => 'Inactive',
        'unsubscribed' => 'Unsubscribed',
        'suppressed' => 'Suppressed',
    ];

    public function __construct(private readonly SubscriberEngagementService $ratings) {}

    public function ensureSegments(SubscriberGroup $group): void
    {
        foreach (self::SEGMENTS as $rating => $name) {
            $segment = SubscriberSubGroup::query()->firstOrCreate(
                ['subscriber_group_id' => $group->getKey(), 'slug' => 'rating-'.$rating],
                ['name' => $name, 'purpose' => 'engagement', 'archived_at' => null],
            );
            if ($segment->purpose !== 'engagement' || $segment->archived_at !== null) {
                $segment->update(['name' => $name, 'purpose' => 'engagement', 'archived_at' => null]);
            }
        }
    }

    public function syncForSubscriber(Subscriber $subscriber, ?array $groupIds = null): void
    {
        $groupIds ??= $subscriber->allSubGroups()
            ->where('purpose', 'preference')
            ->pluck('subscriber_sub_groups.subscriber_group_id')->unique()->all();

        foreach (SubscriberGroup::query()->whereIn('id', $groupIds)->whereNull('archived_at')->get() as $group) {
            $this->sync($subscriber, $group);
        }
    }

    public function sync(Subscriber $subscriber, SubscriberGroup $group): void
    {
        $this->ensureSegments($group);
        $baseMemberships = $subscriber->allSubGroups()
            ->where('subscriber_group_id', $group->getKey())
            ->where('purpose', 'preference')
            ->get();
        $activeMembership = $baseMemberships->first(fn ($subGroup) => $subGroup->pivot->unsubscribed_at === null);
        $existing = SubscriberGroupEngagement::query()->where('subscriber_id', $subscriber->getKey())
            ->where('subscriber_group_id', $group->getKey())->first();

        if (! $activeMembership && ! ($existing && in_array($subscriber->status, ['unsubscribed', 'bounced', 'complained'], true))) {
            $existing?->update(['is_member' => false]);
            $this->setSegment($subscriber, $group, null);

            return;
        }

        $result = $this->ratings->computeForGroup($subscriber, $group);
        $firstJoinedAt = $existing?->first_joined_at
            ?? $baseMemberships->min(fn ($subGroup) => $subGroup->pivot->subscribed_at)
            ?? $subscriber->created_at ?? now();
        SubscriberGroupEngagement::query()->updateOrCreate(
            ['subscriber_id' => $subscriber->getKey(), 'subscriber_group_id' => $group->getKey()],
            [
                'rating' => $result['engagement_rating'],
                'is_member' => (bool) $activeMembership,
                'score' => $result['engagement_score'],
                'first_joined_at' => $firstJoinedAt,
                'last_engaged_at' => $result['last_engaged_at'],
                'last_recalculated_at' => now(),
            ],
        );
        $this->setSegment($subscriber, $group, $result['engagement_rating']);
    }

    public function recalculateOrganisation(int $organisationId): void
    {
        $groups = SubscriberGroup::query()->where('organisation_id', $organisationId)
            ->whereNull('archived_at')->get();

        foreach ($groups as $group) {
            $this->recalculateGroup($group);
        }

        OrganisationEngagementSetting::query()->updateOrCreate(
            ['organisation_id' => $organisationId],
            ['last_recalculated_at' => now()],
        );
    }

    public function recalculateGroup(SubscriberGroup $group): void
    {
        $this->ensureSegments($group);
        Subscriber::query()->whereHas('allSubGroups', fn ($query) => $query
            ->where('subscriber_group_id', $group->getKey())
            ->where('purpose', 'preference'))
            ->chunkById(250, function ($subscribers) use ($group) {
                foreach ($subscribers as $subscriber) {
                    $this->sync($subscriber, $group);
                }
            });
    }

    private function setSegment(Subscriber $subscriber, SubscriberGroup $group, ?string $rating): void
    {
        $segments = $group->subGroups()->where('purpose', 'engagement')->get();
        $existing = $subscriber->allSubGroups()->whereIn('subscriber_sub_groups.id', $segments->modelKeys())
            ->get()->keyBy('id');

        foreach ($segments as $segment) {
            $membership = $existing->get($segment->getKey());
            if ($segment->slug === 'rating-'.$rating) {
                if ($membership) {
                    if ($membership->pivot->unsubscribed_at !== null) {
                        $subscriber->allSubGroups()->updateExistingPivot($segment->getKey(), [
                            'subscribed_at' => now(), 'unsubscribed_at' => null,
                        ]);
                    }
                } else {
                    $subscriber->allSubGroups()->attach($segment->getKey(), ['subscribed_at' => now()]);
                }
            } elseif ($membership && $membership->pivot->unsubscribed_at === null) {
                $subscriber->allSubGroups()->updateExistingPivot($segment->getKey(), ['unsubscribed_at' => now()]);
            }
        }
    }
}
