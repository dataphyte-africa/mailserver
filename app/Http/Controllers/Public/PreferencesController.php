<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    public function show(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This preferences link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)
            ->with('subGroups.group')
            ->firstOrFail();

        [$scopedGroup, $scopedCollection, $scopedLabel] = $this->resolveScope($request);

        if ($scopedGroup) {
            $visibleSubGroups = $this->visibleSubGroupsForCollection($scopedGroup, $scopedCollection);
            $visibleSubGroupIds = $visibleSubGroups->pluck('id')->map(fn ($id) => (int) $id)->all();

            $allSubGroups = collect([$scopedGroup->name => $visibleSubGroups]);
            $activeSubGroupIds = $subscriber->subGroups
                ->filter(fn ($subGroup) => in_array((int) $subGroup->id, $visibleSubGroupIds, true))
                ->pluck('id')
                ->values()
                ->all();
        } else {
            $allSubGroups = SubscriberSubGroup::with('group')
                ->orderBy('subscriber_group_id')
                ->get()
                ->groupBy('group.name');
            $activeSubGroupIds = $subscriber->subGroups->pluck('id')->values()->all();
        }

        return view('newsletter.public.preferences', compact(
            'subscriber',
            'token',
            'allSubGroups',
            'activeSubGroupIds',
            'scopedCollection',
            'scopedLabel'
        ));
    }

    public function update(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This preferences link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)
            ->with('subGroups.group')
            ->firstOrFail();

        [$scopedGroup, $scopedCollection, $scopedLabel] = $this->resolveScope($request);

        $request->validate([
            'sub_groups' => 'nullable|array',
            'sub_groups.*' => 'integer',
        ]);

        $incoming = collect($request->input('sub_groups', []))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($scopedGroup) {
            $visibleSubGroups = $this->visibleSubGroupsForCollection($scopedGroup, $scopedCollection);
            $allowedIds = $visibleSubGroups->pluck('id')->map(fn ($id) => (int) $id)->all();
            $invalidIds = array_diff($incoming->all(), $allowedIds);

            if ($invalidIds !== []) {
                abort(422, 'One or more selected preferences are invalid for this collection.');
            }

            $current = $subscriber->subGroups()
                ->whereIn('subscriber_sub_groups.id', $allowedIds)
                ->pluck('subscriber_sub_groups.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $toRemove = array_diff($current, $incoming->all());
            $toAdd = array_diff($incoming->all(), $current);

            if ($toRemove !== []) {
                $subscriber->allSubGroups()->updateExistingPivot($toRemove, [
                    'unsubscribed_at' => now(),
                ]);
            }

            if ($toAdd !== []) {
                $existingIds = $subscriber->allSubGroups()
                    ->pluck('subscriber_sub_groups.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($toAdd as $subGroupId) {
                    if (in_array($subGroupId, $existingIds, true)) {
                        $subscriber->allSubGroups()->updateExistingPivot($subGroupId, [
                            'subscribed_at' => now(),
                            'unsubscribed_at' => null,
                        ]);
                    } else {
                        $subscriber->allSubGroups()->attach($subGroupId, [
                            'subscribed_at' => now(),
                            'unsubscribed_at' => null,
                        ]);
                    }
                }
            }

            $this->syncSubscriberStatus($subscriber);

            if ($incoming->isEmpty() && ! $subscriber->subGroups()->exists()) {
                return view('newsletter.public.unsubscribed', compact(
                    'subscriber',
                    'scopedCollection',
                    'scopedLabel'
                ));
            }

            return view('newsletter.public.preferences-saved', compact(
                'subscriber',
                'scopedCollection',
                'scopedLabel'
            ));
        }

        $request->validate([
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        if ($incoming->isEmpty()) {
            $subscriber->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
            $subscriber->allSubGroups()->update(['unsubscribed_at' => now()]);

            return view('newsletter.public.unsubscribed', compact(
                'subscriber',
                'scopedCollection',
                'scopedLabel'
            ));
        }

        $current = $subscriber->subGroups()->pluck('subscriber_sub_groups.id')->toArray();
        $toRemove = array_diff($current, $incoming->all());
        $toAdd = array_diff($incoming->all(), $current);

        if ($toRemove) {
            $subscriber->allSubGroups()->updateExistingPivot($toRemove, [
                'unsubscribed_at' => now(),
            ]);
        }

        if ($toAdd) {
            $existingIds = $subscriber->allSubGroups()->pluck('subscriber_sub_groups.id')->toArray();

            foreach ($toAdd as $subGroupId) {
                if (in_array($subGroupId, $existingIds, true)) {
                    $subscriber->allSubGroups()->updateExistingPivot($subGroupId, [
                        'subscribed_at' => now(),
                        'unsubscribed_at' => null,
                    ]);
                } else {
                    $subscriber->allSubGroups()->attach($subGroupId, [
                        'subscribed_at' => now(),
                        'unsubscribed_at' => null,
                    ]);
                }
            }
        }

        $this->syncSubscriberStatus($subscriber);

        return view('newsletter.public.preferences-saved', compact(
            'subscriber',
            'scopedCollection',
            'scopedLabel'
        ));
    }

    private function resolveScope(Request $request): array
    {
        $collectionHandle = $request->query('collection');

        if (! is_string($collectionHandle) || trim($collectionHandle) === '') {
            return [null, null, null];
        }

        $group = SubscriberGroup::with('subGroups')
            ->where('collection_handle', $collectionHandle)
            ->first();

        if (! $group) {
            return [null, null, null];
        }

        $label = config("newsletter.collections.{$collectionHandle}.label", $group->name);

        return [$group, $collectionHandle, $label];
    }

    private function syncSubscriberStatus(Subscriber $subscriber): void
    {
        $hasActiveSubscriptions = $subscriber->subGroups()->exists();

        $subscriber->update([
            'status' => $hasActiveSubscriptions ? 'active' : 'unsubscribed',
            'unsubscribed_at' => $hasActiveSubscriptions ? null : now(),
        ]);
    }

    private function visibleSubGroupsForCollection(SubscriberGroup $group, ?string $collectionHandle)
    {
        $query = $group->subGroups()->orderBy('name');

        if ($collectionHandle !== 'foundation_newsletters') {
            return $query->get();
        }

        return $query->whereIn('slug', [
            'activities',
            'update',
            'updates',
            'project-update',
            'project_update',
        ])->get();
    }
}
