<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\CampaignLinkClick;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\SubscriberEngagementService;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscriber::with('subGroups.group')
            ->withCount([
                'campaignSends as campaigns_count',
                'campaignSends as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'opened', 'clicked']),
                'campaignSends as failed_count' => fn ($q) => $q->whereIn('status', ['failed', 'bounced']),
                'campaignSends as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
                'campaignSends as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ]);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sub_group')) {
            $query->whereHas('subGroups', fn ($q) =>
                $q->where('subscriber_sub_groups.id', $request->sub_group)
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) =>
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
            );
        }

        $sort = $request->string('sort')->value() ?: 'created_at';
        $direction = strtolower($request->string('direction')->value() ?: 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $sortable = [
            'email' => 'email',
            'status' => 'status',
            'engagement_score' => 'engagement_score',
            'campaigns_count' => 'campaigns_count',
            'delivered_count' => 'delivered_count',
            'failed_count' => 'failed_count',
            'opened_count' => 'opened_count',
            'clicked_count' => 'clicked_count',
            'created_at' => 'created_at',
        ];

        if ($sort === 'name') {
            $query->orderByRaw(
                "COALESCE(NULLIF(TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))), ''), email) {$direction}"
            );
        } elseif ($sort === 'engagement_rating') {
            $query->orderByRaw("
                CASE engagement_rating
                    WHEN 'high' THEN 5
                    WHEN 'moderate' THEN 4
                    WHEN 'low' THEN 3
                    WHEN 'inactive' THEN 2
                    WHEN 'suppressed' THEN 1
                    ELSE 0
                END {$direction}
            ")->orderBy('engagement_score', $direction);
        } else {
            $query->orderBy($sortable[$sort] ?? 'created_at', $direction);
        }

        $subscribers = $query->paginate(50)->withQueryString();
        $subGroups   = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.index', compact('subscribers', 'subGroups', 'sort', 'direction'));
    }

    public function create()
    {
        $subGroups = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.create', compact('subGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email'      => 'required|email|unique:subscribers,email',
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'status'     => 'required|in:active,unsubscribed,bounced,complained',
            'sub_groups' => 'required|array|min:1',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $subscriber = Subscriber::create([
            'email'      => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name'  => $validated['last_name'] ?? null,
            'status'     => $validated['status'],
        ]);

        $subscriber->subGroups()->attach(
            $validated['sub_groups'],
            ['subscribed_at' => now()]
        );

        app(SubscriberEngagementService::class)->persist($subscriber);

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('success', 'Subscriber created successfully.');
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load('subGroups.group')
            ->loadCount([
                'campaignSends as campaigns_count',
                'campaignSends as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'opened', 'clicked']),
                'campaignSends as failed_count' => fn ($q) => $q->whereIn('status', ['failed', 'bounced']),
                'campaignSends as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
                'campaignSends as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ]);

        $sendHistory = $subscriber->campaignSends()
            ->with('campaign')
            ->orderByRaw('COALESCE(clicked_at, opened_at, sent_at, created_at) desc')
            ->paginate(20);

        $recentLinkClicks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $q->where('subscriber_id', $subscriber->id))
            ->with(['campaignSend.campaign'])
            ->latest('clicked_at')
            ->limit(20)
            ->get();

        $totalLinkClicks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $q->where('subscriber_id', $subscriber->id))
            ->count();

        $stats = [
            'total_sent' => (int) $subscriber->campaigns_count,
            'total_delivered' => (int) $subscriber->delivered_count,
            'total_failed' => (int) $subscriber->failed_count,
            'total_opened' => (int) $subscriber->opened_count,
            'total_clicked' => (int) $subscriber->clicked_count,
            'total_link_clicks' => (int) $totalLinkClicks,
            'last_engaged_at' => $subscriber->campaignSends()
                ->selectRaw('MAX(COALESCE(clicked_at, opened_at)) as last_engaged_at')
                ->value('last_engaged_at'),
        ];

        return view('newsletter.cp.subscribers.show', compact('subscriber', 'sendHistory', 'stats', 'recentLinkClicks'));
    }

    public function edit(Subscriber $subscriber)
    {
        $subscriber->load('subGroups');
        $subGroups = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.edit', compact('subscriber', 'subGroups'));
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $validated = $request->validate([
            'email'      => 'required|email|unique:subscribers,email,' . $subscriber->id,
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'status'     => 'required|in:active,unsubscribed,bounced,complained',
            'sub_groups' => 'required|array|min:1',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $subscriber->update([
            'email'      => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name'  => $validated['last_name'] ?? null,
            'status'     => $validated['status'],
        ]);

        // Sync sub-groups: detach removed, attach new ones
        $current     = $subscriber->subGroups()->pluck('subscriber_sub_groups.id')->toArray();
        $incoming    = $validated['sub_groups'];
        $toDetach    = array_diff($current, $incoming);
        $toAttach    = array_diff($incoming, $current);

        if ($toDetach) {
            $subscriber->subGroups()->updateExistingPivot($toDetach, ['unsubscribed_at' => now()]);
        }

        if ($toAttach) {
            $subscriber->subGroups()->attach($toAttach, ['subscribed_at' => now()]);
        }

        app(SubscriberEngagementService::class)->persist($subscriber);

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.show', $subscriber)
            ->with('success', 'Subscriber updated successfully.');
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('success', 'Subscriber deleted.');
    }
}
