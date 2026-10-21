<?php

namespace App\Services\Newsletter;

use App\Models\SubscriberGroup;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubscriberAudienceFilter
{
    public function __construct(private readonly CpOperatorScope $scope) {}

    public function visibleGroups(Request $request): Collection
    {
        return $this->scope->restrictVisibleGroups(
            SubscriberGroup::query()->whereNull('archived_at'),
            $request->user(),
        )->orderBy('name')->get();
    }

    public function apply(Builder $query, Request $request, Collection $groups): Builder
    {
        $groupId = $request->input('audience_group');
        $rating = $request->input('rating');
        $visibleIds = $groups->modelKeys();

        if ($groupId !== null && $groupId !== '' && (! is_scalar($groupId) || ! ctype_digit((string) $groupId) || ! in_array((int) $groupId, $visibleIds, true))) {
            return $query->whereRaw('1 = 0');
        }

        if ($rating !== null && $rating !== '' && (! is_string($rating) || ! array_key_exists($rating, GroupEngagementService::SEGMENTS))) {
            return $query->whereRaw('1 = 0');
        }

        if ($groupId !== null && $groupId !== '') {
            $id = (int) $groupId;
            $query->where(function (Builder $subscribers) use ($id) {
                $subscribers->whereHas('subGroups', fn (Builder $subGroups) => $subGroups
                    ->where('subscriber_group_id', $id))
                    ->orWhereHas('groupEngagements', fn (Builder $engagements) => $engagements
                        ->where('subscriber_group_id', $id)
                        ->whereIn('rating', ['unsubscribed', 'suppressed']));
            });
        }

        if ($rating !== null && $rating !== '') {
            $query->whereHas('groupEngagements', fn (Builder $engagements) => $engagements
                ->whereIn('subscriber_group_id', $groupId ? [(int) $groupId] : $visibleIds)
                ->where('rating', $rating)
                ->where(fn (Builder $member) => $member->where('is_member', true)
                    ->orWhereIn('rating', ['unsubscribed', 'suppressed'])));
        }

        return $query;
    }
}
