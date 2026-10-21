<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Jobs\Newsletter\RecomputeOrganisationEngagementJob;
use App\Models\Organisation;
use App\Models\OrganisationEngagementSetting;
use App\Models\SubscriberGroupEngagement;
use App\Services\Newsletter\ScopedSubscriberGroupProductSelector;
use Illuminate\Http\Request;

class EngagementRatingController extends Controller
{
    public function index(Request $request, ScopedSubscriberGroupProductSelector $selector)
    {
        $organisations = $selector->productsFor($request->user())
            ->pluck('organisation')->filter()->unique('id')->sortBy('name')->values();
        abort_if($organisations->isEmpty(), 403);

        $organisation = $request->filled('organisation_id')
            ? $organisations->firstWhere('id', (int) $request->query('organisation_id'))
            : $organisations->first();
        abort_if($organisation === null, 403);
        $setting = OrganisationEngagementSetting::query()->firstOrCreate(
            ['organisation_id' => $organisation->getKey()], ['interval_days' => 30],
        );
        $groups = $selector->groupsFor($request->user())
            ->filter(fn ($group) => (int) $group->organisation_id === (int) $organisation->getKey() && ! $group->isArchived());
        $counts = SubscriberGroupEngagement::query()
            ->selectRaw('subscriber_group_id, rating, COUNT(*) AS total')
            ->whereIn('subscriber_group_id', $groups->modelKeys())
            ->where(fn ($query) => $query->where('rating', 'suppressed')
                ->orWhere(fn ($members) => $members->where('is_member', true)
                    ->whereHas('subscriber', fn ($subscriber) => $subscriber->where('status', 'active'))))
            ->groupBy('subscriber_group_id', 'rating')->get()
            ->groupBy('subscriber_group_id');

        return view('newsletter.cp.engagement.index', compact('organisations', 'organisation', 'setting', 'groups', 'counts'));
    }

    public function update(Request $request, Organisation $organisation, ScopedSubscriberGroupProductSelector $selector)
    {
        $this->authorise($request, $organisation, $selector);
        $data = $request->validate(['interval_days' => 'required|integer|min:30|max:3650']);
        OrganisationEngagementSetting::query()->updateOrCreate(
            ['organisation_id' => $organisation->getKey()], ['interval_days' => $data['interval_days']],
        );

        return back()->with('success', 'Recalculation interval updated.');
    }

    public function recalculate(Request $request, Organisation $organisation, ScopedSubscriberGroupProductSelector $selector)
    {
        $this->authorise($request, $organisation, $selector);
        $setting = OrganisationEngagementSetting::query()->firstOrCreate(
            ['organisation_id' => $organisation->getKey()], ['interval_days' => 30],
        );
        if (in_array($setting->last_status, ['queued', 'running'], true)
            && $setting->last_requested_at?->greaterThan(now()->subHour())) {
            return back()->with('success', 'A recalculation is already in progress.');
        }
        $setting->update(['last_requested_at' => now(), 'last_status' => 'queued', 'last_error' => null]);
        RecomputeOrganisationEngagementJob::dispatch((int) $organisation->getKey());

        return back()->with('success', 'Engagement recalculation queued.');
    }

    private function authorise(Request $request, Organisation $organisation, ScopedSubscriberGroupProductSelector $selector): void
    {
        abort_unless($selector->productsFor($request->user())
            ->contains(fn ($product) => (int) $product->organisation_id === (int) $organisation->getKey()), 403);
    }
}
