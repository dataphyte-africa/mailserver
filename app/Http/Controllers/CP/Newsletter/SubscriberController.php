<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\CampaignLinkClick;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\PendingSubscriberLifecycleService;
use App\Services\Newsletter\SubscriberEngagementService;
use App\Services\Newsletter\SubscriptionFormService;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SubscriberController extends Controller
{
    private const STATUSES = [
        'pending' => 'Pending',
        'active' => 'Active',
        'unsubscribed' => 'Unsubscribed',
        'bounced' => 'Bounced',
        'complained' => 'Complained',
    ];

    public function index(Request $request)
    {
        $scope = app(CpOperatorScope::class);
        $productIds = $scope->productIds($request->user());
        $query = $scope->subscriberQuery($request->user())
            ->with(['subGroups' => fn ($q) => $q->when(! $scope->isSuper($request->user()), fn ($q) => $q->whereHas('group', fn ($g) => $g->whereIn('product_id', $productIds))), 'subGroups.group'])
            ->withCount([
                'campaignSends as campaigns_count' => fn ($q) => $this->scopeSends($q, $request),
                'campaignSends as delivered_count' => fn ($q) => $this->scopeSends($q, $request)->whereIn('status', ['delivered', 'opened', 'clicked']),
                'campaignSends as failed_count' => fn ($q) => $this->scopeSends($q, $request)->whereIn('status', ['failed', 'bounced']),
                'campaignSends as opened_count' => fn ($q) => $this->scopeSends($q, $request)->whereNotNull('opened_at'),
                'campaignSends as clicked_count' => fn ($q) => $this->scopeSends($q, $request)->whereNotNull('clicked_at'),
            ]);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sub_group')) {
            $query->whereHas('subGroups', fn ($q) =>
                $q->where('subscriber_sub_groups.id', $request->sub_group)->whereHas('group', fn ($g) => $g->whereIn('product_id', $productIds))
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
        if (! $scope->isSuper($request->user()) && in_array($sort, ['engagement_rating', 'engagement_score'], true)) {
            $sort = 'created_at';
        }
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
        if (! $scope->isSuper($request->user())) {
            foreach ($subscribers as $item) {
                $item->setAttribute('engagement_rating', null);
                $item->setAttribute('engagement_score', null);
                $item->setAttribute('last_engaged_at', null);
            }
        }
        $isSuper = $scope->isSuper($request->user());
        $sharedIds = $isSuper ? [] : Subscriber::query()
            ->whereIn('id', collect($subscribers->items())->pluck('id')->all())
            ->whereHas('allSubGroups.group', fn ($q) => $q->whereNotIn('product_id', $productIds))
            ->pluck('id')->all();
        $subGroups   = $this->assignableSubGroups();
        $statuses = self::STATUSES;
        $pendingLifecycles = $this->pendingLifecycleSnapshots(collect($subscribers->items()));

        return view('newsletter.cp.subscribers.index', compact('subscribers', 'subGroups', 'sort', 'direction', 'statuses', 'pendingLifecycles', 'isSuper', 'sharedIds'));
    }

    public function create()
    {
        $subGroups = $this->assignableSubGroups();
        $statuses = self::STATUSES;

        return view('newsletter.cp.subscribers.create', compact('subGroups', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email'      => 'required|email|unique:subscribers,email',
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'status'     => 'required|in:' . implode(',', array_keys(self::STATUSES)),
            'sub_groups' => 'required|array|min:1',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);
        $this->validateAssignableSubGroupIds($validated['sub_groups']);

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

        if ($subscriber->status === 'pending') {
            $this->pendingLifecycles()->resetForPending($subscriber);
        }

        app(SubscriberEngagementService::class)->persist($subscriber);

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('success', 'Subscriber created successfully.');
    }

    public function show(Subscriber $subscriber)
    {
        $scope = app(CpOperatorScope::class);
        $productIds = $scope->productIds(request()->user());
        $this->pendingLifecycles()->syncState($subscriber);
        $subscriber->load(['subGroups' => fn ($q) => $q->when(! $scope->isSuper(request()->user()), fn ($q) => $q->whereHas('group', fn ($g) => $g->whereIn('product_id', $productIds))), 'subGroups.group'])
            ->loadCount([
                'campaignSends as campaigns_count' => fn ($q) => $this->scopeSends($q, request()),
                'campaignSends as delivered_count' => fn ($q) => $this->scopeSends($q, request())->whereIn('status', ['delivered', 'opened', 'clicked']),
                'campaignSends as failed_count' => fn ($q) => $this->scopeSends($q, request())->whereIn('status', ['failed', 'bounced']),
                'campaignSends as opened_count' => fn ($q) => $this->scopeSends($q, request())->whereNotNull('opened_at'),
                'campaignSends as clicked_count' => fn ($q) => $this->scopeSends($q, request())->whereNotNull('clicked_at'),
            ]);

        $sendHistory = $subscriber->campaignSends()
            ->when(! $scope->isSuper(request()->user()), fn ($q) => $q->whereHas('campaign', fn ($c) => $scope->restrictCampaigns($c, request()->user())))
            ->with('campaign')
            ->orderByRaw('COALESCE(clicked_at, opened_at, sent_at, created_at) desc')
            ->paginate(20);

        $recentLinkClicks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $this->scopeSends($q->where('subscriber_id', $subscriber->id), request()))
            ->with(['campaignSend.campaign'])
            ->latest('clicked_at')
            ->limit(20)
            ->get();

        $totalLinkClicks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $this->scopeSends($q->where('subscriber_id', $subscriber->id), request()))
            ->count();

        $stats = [
            'total_sent' => (int) $subscriber->campaigns_count,
            'total_delivered' => (int) $subscriber->delivered_count,
            'total_failed' => (int) $subscriber->failed_count,
            'total_opened' => (int) $subscriber->opened_count,
            'total_clicked' => (int) $subscriber->clicked_count,
            'total_link_clicks' => (int) $totalLinkClicks,
            'last_engaged_at' => $subscriber->campaignSends()
                ->when(! $scope->isSuper(request()->user()), fn ($q) => $q->whereHas('campaign', fn ($c) => $scope->restrictCampaigns($c, request()->user())))
                ->selectRaw('MAX(COALESCE(clicked_at, opened_at)) as last_engaged_at')
                ->value('last_engaged_at'),
        ];
        $pendingLifecycle = $this->pendingLifecycles()->snapshot($subscriber);
        $isSuper = $scope->isSuper(request()->user());
        $canEdit = $isSuper || ! $subscriber->allSubGroups()
            ->whereHas('group', fn ($q) => $q->whereNotIn('product_id', $productIds))->exists();
        if (! $isSuper) {
            $subscriber->setAttribute('engagement_rating', null);
            $subscriber->setAttribute('engagement_score', null);
            $subscriber->setAttribute('last_engaged_at', null);
        }

        return view('newsletter.cp.subscribers.show', compact('subscriber', 'sendHistory', 'stats', 'recentLinkClicks', 'pendingLifecycle', 'isSuper', 'canEdit'));
    }

    public function edit(Subscriber $subscriber)
    {
        $this->assertExclusiveSubscriber($subscriber);
        $this->pendingLifecycles()->syncState($subscriber);
        $subscriber->load('subGroups');
        $subGroups = $this->assignableSubGroups();
        $statuses = $this->editableStatuses($subscriber);
        $pendingLifecycle = $this->pendingLifecycles()->snapshot($subscriber);

        return view('newsletter.cp.subscribers.edit', compact('subscriber', 'subGroups', 'statuses', 'pendingLifecycle'));
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $this->assertExclusiveSubscriber($subscriber);
        $statuses = $this->editableStatuses($subscriber);
        $validated = $request->validate([
            'email'      => 'required|email|unique:subscribers,email,' . $subscriber->id,
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'status'     => 'required|in:' . implode(',', array_keys($statuses)),
            'sub_groups' => 'required|array|min:1',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);
        $this->validateAssignableSubGroupIds($validated['sub_groups']);
        $previousStatus = $subscriber->status;

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

        if ($validated['status'] === 'pending') {
            if ($previousStatus !== 'pending') {
                $this->pendingLifecycles()->resetForPending(
                    $subscriber,
                    $previousStatus === 'unsubscribed' ? 'resubscribed' : 'subscribed',
                );
            } else {
                $this->pendingLifecycles()->syncState($subscriber);
            }
        }

        app(SubscriberEngagementService::class)->persist($subscriber);

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.show', $subscriber)
            ->with('success', 'Subscriber updated successfully.');
    }

    public function resendConfirmation(Subscriber $subscriber)
    {
        $this->assertExclusiveSubscriber($subscriber);
        $decision = $this->pendingLifecycles()->resendDecision($subscriber);

        if (! ($decision['eligible'] ?? false)) {
            return redirect()
                ->route('statamic.cp.newsletter.subscribers.show', $subscriber)
                ->with('error', $decision['message']);
        }

        try {
            app(SubscriptionFormService::class)->resendPendingConfirmation($subscriber);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('statamic.cp.newsletter.subscribers.show', $subscriber)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.show', $subscriber)
            ->with('success', 'Confirmation email resent. Activation still requires a delivered, opened, or clicked confirmation webhook from the signup lifecycle email.');
    }

    public function destroy(Subscriber $subscriber)
    {
        abort_unless(app(CpOperatorScope::class)->isSuper(request()->user()), 403);
        $subscriber->delete();

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('success', 'Subscriber deleted.');
    }

    /**
     * @return Collection<int, SubscriberSubGroup>
     */
    private function assignableSubGroups(): Collection
    {
        $scope = app(CpOperatorScope::class);
        $isSuper = $scope->isSuper(request()->user());
        $productIds = $scope->productIds(request()->user());
        return SubscriberSubGroup::query()
            ->with('group')
            ->whereNull('archived_at')
            ->whereHas('group', fn ($query) => $query->whereNull('archived_at')->when(! $isSuper, fn ($q) => $q->whereIn('product_id', $productIds)))
            ->orderBy('subscriber_group_id')
            ->get();
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    private function validateAssignableSubGroupIds(array $ids): void
    {
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $scope = app(CpOperatorScope::class);
        $isSuper = $scope->isSuper(request()->user());
        $productIds = $scope->productIds(request()->user());
        $assignableCount = SubscriberSubGroup::query()
            ->whereIn('id', $ids->all())
            ->whereNull('archived_at')
            ->whereHas('group', fn ($query) => $query->whereNull('archived_at')->when(! $isSuper, fn ($q) => $q->whereIn('product_id', $productIds)))
            ->count();

        if ($assignableCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'sub_groups' => 'Archived audience sub-groups cannot be assigned to subscribers.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Subscriber>  $subscribers
     * @return array<int, array<string, mixed>>
     */
    private function pendingLifecycleSnapshots(Collection $subscribers): array
    {
        $lifecycles = $this->pendingLifecycles();

        return $subscribers
            ->mapWithKeys(fn (Subscriber $subscriber) => [
                $subscriber->id => $lifecycles->snapshot($subscriber),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function editableStatuses(Subscriber $subscriber): array
    {
        if ($subscriber->status !== 'pending') {
            return self::STATUSES;
        }

        return array_diff_key(self::STATUSES, ['active' => true]);
    }

    private function pendingLifecycles(): PendingSubscriberLifecycleService
    {
        return app(PendingSubscriberLifecycleService::class);
    }

    private function scopeSends($query, Request $request)
    {
        $scope = app(CpOperatorScope::class);
        return $scope->isSuper($request->user()) ? $query : $query->whereHas('campaign', fn ($campaign) => $scope->restrictCampaigns($campaign, $request->user()));
    }

    private function assertExclusiveSubscriber(Subscriber $subscriber): void
    {
        $scope = app(CpOperatorScope::class);
        if ($scope->isSuper(request()->user())) {
            return;
        }
        $productIds = $scope->productIds(request()->user());
        abort_if($subscriber->allSubGroups()->whereHas('group', fn ($q) => $q->whereNotIn('product_id', $productIds))->exists(), 403,
            'This subscriber also belongs to another product. Ask a super admin to update the shared profile.');
    }
}
