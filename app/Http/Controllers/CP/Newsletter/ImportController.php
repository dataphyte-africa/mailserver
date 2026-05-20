<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\SubscriberEngagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function form()
    {
        $subGroups = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.import', compact('subGroups'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file'          => 'required|file|mimes:csv,txt|max:10240',
            'default_sub_groups' => 'nullable|array',
            'default_sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row — normalise to snake_case and resolve aliases
        $rawHeaders = array_map('trim', fgetcsv($handle));
        $headers    = array_map([$this, 'normaliseHeader'], $rawHeaders);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        // Resolve available sub-group slugs for mapping
        $subGroupMap = SubscriberSubGroup::pluck('id', 'slug')->toArray();

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (count($data) < 1 || empty(trim($data[0]))) {
                continue;
            }

            $rowData = array_combine(
                $headers,
                array_pad($data, count($headers), null)
            );

            $email = strtolower(trim($rowData['email'] ?? ''));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$row}: Invalid email — {$email}";
                $skipped++;
                continue;
            }

            // Resolve sub-groups from CSV column or fall back to defaults
            $subGroupIds = [];

            if (! empty($rowData['sub_groups'])) {
                $slugs = array_map('trim', explode(',', $rowData['sub_groups']));
                foreach ($slugs as $slug) {
                    if (isset($subGroupMap[$slug])) {
                        $subGroupIds[] = $subGroupMap[$slug];
                    } else {
                        $errors[] = "Row {$row}: Unknown sub-group slug '{$slug}' — skipped for {$email}";
                    }
                }
            }

            // Merge with any defaults selected in the form
            if ($request->filled('default_sub_groups')) {
                $subGroupIds = array_unique(
                    array_merge($subGroupIds, $request->default_sub_groups)
                );
            }

            if (empty($subGroupIds)) {
                $errors[] = "Row {$row}: No valid sub-groups for {$email} — skipped";
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($email, $rowData, $subGroupIds) {
                    $subscriber = Subscriber::firstOrCreate(
                        ['email' => $email],
                        [
                            'first_name'         => trim($rowData['first_name'] ?? ''),
                            'last_name'          => trim($rowData['last_name'] ?? ''),
                            'status'             => 'active',
                            'confirmation_token' => Str::uuid()->toString(),
                        ]
                    );

                    // Backfill token for existing subscribers that were imported without one
                    if (! $subscriber->confirmation_token) {
                        $subscriber->update(['confirmation_token' => Str::uuid()->toString()]);
                    }

                    // Only attach sub-groups not already attached
                    $existing = $subscriber->allSubGroups()
                        ->pluck('subscriber_sub_groups.id')
                        ->toArray();

                    $toAttach = array_diff($subGroupIds, $existing);

                    if ($toAttach) {
                        $subscriber->subGroups()->attach(
                            $toAttach,
                            ['subscribed_at' => now()]
                        );
                    }

                    app(SubscriberEngagementService::class)->persist($subscriber);
                });

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: Failed to import {$email} — {$e->getMessage()}";
                $skipped++;
            }
        }

        fclose($handle);

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('import_result', [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ]);
    }

    public function export(Request $request)
    {
        $query = Subscriber::with('subGroups.group')
            ->withCount([
                'campaignSends as campaigns_count',
                'campaignSends as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'opened', 'clicked']),
                'campaignSends as failed_count' => fn ($q) => $q->whereIn('status', ['failed', 'bounced']),
                'campaignSends as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
                'campaignSends as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ]);

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

        $subscribers = $query->get();

        $filename = 'subscribers-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($subscribers) {
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
                    $subscriber->engagement_rating,
                    $subscriber->engagement_score,
                    $subscriber->last_engaged_at?->toDateTimeString(),
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
}
