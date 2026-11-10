<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Jobs\Newsletter\ProcessSubscriberCsvImport;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\SubscriberAudienceFilter;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function form()
    {
        $subGroups = $this->assignableSubGroups();

        return view('newsletter.cp.subscribers.import', compact('subGroups'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file'          => 'required|file|mimes:csv,txt|max:10240',
            'default_sub_groups' => 'nullable|array',
            'default_sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);
        $this->validateAssignableSubGroupIds((array) $request->input('default_sub_groups', []), 'default_sub_groups');

        $handle = fopen($request->file('csv_file')->getRealPath(), 'rb');
        $rawHeaders = fgetcsv($handle);
        if (! is_array($rawHeaders)) {
            fclose($handle);
            throw ValidationException::withMessages(['csv_file' => 'The CSV file is empty.']);
        }
        $headers = array_map([$this, 'normaliseHeader'], array_map('trim', $rawHeaders));
        fclose($handle);
        if (! in_array('email', $headers, true) || count($headers) !== count(array_unique($headers))) {
            throw ValidationException::withMessages(['csv_file' => 'The CSV needs one email column and unique column names.']);
        }

        $importId = (string) Str::uuid();
        $path = $request->file('csv_file')->storeAs('newsletter-imports', "{$importId}.csv", 'local');
        if ($path === false) {
            throw ValidationException::withMessages(['csv_file' => 'The CSV file could not be stored for import.']);
        }
        Cache::put(ProcessSubscriberCsvImport::cacheKey($importId), [
            'user_id' => (string) $request->user()->id(),
            'status' => 'queued',
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ], now()->addDays(7));
        $allowedIds = $this->assignableSubGroups()->modelKeys();
        ProcessSubscriberCsvImport::dispatch(
            $importId,
            $path,
            $headers,
            $this->headerOffset($path),
            array_map('intval', (array) $request->input('default_sub_groups', [])),
            $allowedIds,
        );

        return redirect()->route('statamic.cp.newsletter.subscribers.import.status', $importId);
    }

    public function status(Request $request, string $importId)
    {
        $result = Cache::get(ProcessSubscriberCsvImport::cacheKey($importId));
        abort_unless(is_array($result), 404);
        abort_unless($result['user_id'] === (string) $request->user()->id(), 403);

        return view('newsletter.cp.subscribers.import-status', compact('result', 'importId'));
    }

    private function headerOffset(string $path): int
    {
        $handle = fopen(\Illuminate\Support\Facades\Storage::disk('local')->path($path), 'rb');
        fgetcsv($handle);
        $offset = ftell($handle);
        fclose($handle);

        return $offset;
    }

    public function export(Request $request)
    {
        $scope = app(CpOperatorScope::class);
        $audienceFilter = app(SubscriberAudienceFilter::class);
        $query = $scope->subscriberQuery($request->user())
            ->with(['subGroups' => fn ($q) => $q->when(! $scope->isSuper($request->user()), fn ($q) => $q->whereHas('group', fn ($g) => $scope->restrictVisibleGroups($g, $request->user()))), 'subGroups.group'])
            ->withCount([
                'campaignSends as campaigns_count' => fn ($q) => $this->scopeSends($q, $request),
                'campaignSends as delivered_count' => fn ($q) => $this->scopeSends($q, $request)->whereIn('status', ['delivered', 'opened', 'clicked']),
                'campaignSends as failed_count' => fn ($q) => $this->scopeSends($q, $request)->whereIn('status', ['failed', 'bounced']),
                'campaignSends as opened_count' => fn ($q) => $this->scopeSends($q, $request)->whereNotNull('opened_at'),
                'campaignSends as clicked_count' => fn ($q) => $this->scopeSends($q, $request)->whereNotNull('clicked_at'),
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $audienceFilter->apply($query, $request, $audienceFilter->visibleGroups($request));

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

        $subscribers = $query->get();

        $filename = 'subscribers-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $isSuper = $scope->isSuper($request->user());
        $callback = function () use ($subscribers, $isSuper) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'email',
                'first_name',
                'last_name',
                'status',
                'sub_groups',
                'engagement_rating',
                'engagement_score',
                'last_engaged_at',
                'campaigns',
                'delivered',
                'failed',
                'opened',
                'clicked',
                'subscribed_at',
            ], ',', '"', '\\');

            foreach ($subscribers as $subscriber) {
                $subGroupSlugs = $subscriber->subGroups->pluck('slug')->implode(',');

                fputcsv($handle, [
                    $subscriber->email,
                    $subscriber->first_name,
                    $subscriber->last_name,
                    $subscriber->status,
                    $subGroupSlugs,
                    $isSuper ? $subscriber->engagement_rating : '',
                    $isSuper ? $subscriber->engagement_score : '',
                    $isSuper ? $subscriber->last_engaged_at?->toDateTimeString() : '',
                    $subscriber->campaigns_count,
                    $subscriber->delivered_count,
                    $subscriber->failed_count,
                    $subscriber->opened_count,
                    $subscriber->clicked_count,
                    $subscriber->created_at->toDateString(),
                ], ',', '"', '\\');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Normalise a CSV header to snake_case and resolve common aliases.
     * e.g. "Email Address" → "email", "First Name" → "first_name"
     */
    private function normaliseHeader(string $header): string
    {
        $aliases = [
            'email address'  => 'email',
            'email_address'  => 'email',
            'e-mail'         => 'email',
            'first name'     => 'first_name',
            'firstname'      => 'first_name',
            'last name'      => 'last_name',
            'lastname'       => 'last_name',
            'surname'        => 'last_name',
            'sub groups'     => 'sub_groups',
            'subgroups'      => 'sub_groups',
            'group'          => 'sub_groups',
            'groups'         => 'sub_groups',
        ];

        $normalised = strtolower(trim($header));
        $normalised = preg_replace('/\s+/', ' ', $normalised); // collapse spaces

        return $aliases[$normalised] ?? str_replace(' ', '_', $normalised);
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
            ->where('purpose', 'preference')
            ->whereNull('archived_at')
            ->whereHas('group', fn ($query) => $query->whereNull('archived_at')->when(! $isSuper, fn ($q) => $q->whereIn('product_id', $productIds)))
            ->orderBy('subscriber_group_id')
            ->get();
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    private function validateAssignableSubGroupIds(array $ids, string $input): void
    {
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $scope = app(CpOperatorScope::class);
        $isSuper = $scope->isSuper(request()->user());
        $productIds = $scope->productIds(request()->user());
        $assignableCount = SubscriberSubGroup::query()
            ->whereIn('id', $ids->all())
            ->where('purpose', 'preference')
            ->whereNull('archived_at')
            ->whereHas('group', fn ($query) => $query->whereNull('archived_at')->when(! $isSuper, fn ($q) => $q->whereIn('product_id', $productIds)))
            ->count();

        if ($assignableCount !== $ids->count()) {
            throw ValidationException::withMessages([
                $input => 'Archived audience sub-groups cannot be assigned during subscriber import.',
            ]);
        }
    }

    private function scopeSends($query, Request $request)
    {
        $scope = app(CpOperatorScope::class);
        return $scope->isSuper($request->user()) ? $query : $query->whereHas('campaign', fn ($campaign) => $scope->restrictCampaigns($campaign, $request->user()));
    }
}
