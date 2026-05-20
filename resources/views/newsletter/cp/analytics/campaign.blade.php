@extends('statamic::layout')
@section('title', $campaign->name . ' — Analytics')

@section('content')
@php
    $kpis = [
        ['key' => 'total_sent', 'label' => 'Total Sent', 'value' => number_format($stats['total_sent']), 'sub' => '', 'color' => 'text-grey-80'],
        ['key' => 'delivery_rate', 'label' => 'Delivery Rate', 'value' => $stats['delivery_rate'] . '%', 'sub' => number_format($stats['delivered']) . ' emails', 'color' => 'text-green-dark'],
        ['key' => 'open_rate', 'label' => 'Open Rate', 'value' => $stats['open_rate'] . '%', 'sub' => number_format($stats['opened']) . ' opened', 'color' => 'text-blue-dark'],
        ['key' => 'click_rate', 'label' => 'Click Rate', 'value' => $stats['click_rate'] . '%', 'sub' => number_format($stats['clicked']) . ' clicks', 'color' => 'text-purple-dark'],
        ['key' => 'bounced', 'label' => 'Bounced', 'value' => number_format($stats['bounced']), 'sub' => number_format($stats['failed']) . ' failed', 'color' => $stats['bounced'] > 0 ? 'text-red-dark' : 'text-grey-60'],
    ];
    $statusRows = [
        ['key' => 'clicked', 'label' => 'Clicked', 'count' => $stats['clicked'], 'color' => '#6d28d9'],
        ['key' => 'opened', 'label' => 'Opened', 'count' => $stats['opened'], 'color' => '#2563eb'],
        ['key' => 'unread', 'label' => 'Unread', 'count' => $stats['unread'], 'color' => '#84cc16'],
        ['key' => 'bounced', 'label' => 'Bounced', 'count' => $stats['bounced'], 'color' => '#f59e0b'],
        ['key' => 'failed', 'label' => 'Failed', 'count' => $stats['failed'], 'color' => '#ef4444'],
        ['key' => 'complained', 'label' => 'Complained', 'count' => $stats['complained'], 'color' => '#b91c1c'],
    ];
    $statusTotal = max(1, (int) ($stats['total_sent'] ?? 0));
    $syncStatus = $campaign->last_stats_sync_status;
    $syncTotal = (int) ($campaign->last_stats_sync_total ?? 0);
    $syncProcessed = (int) ($campaign->last_stats_sync_processed ?? 0);
    $syncProgress = $campaign->statsSyncProgress();
    $syncBadgeClasses = match ($syncStatus) {
        'queued' => 'bg-yellow-lighter text-yellow-dark',
        'processing' => 'bg-blue-lighter text-blue-dark',
        'completed' => 'bg-green-lighter text-green-dark',
        'failed' => 'bg-red-lighter text-red-dark',
        default => 'bg-grey-20 text-grey-70',
    };
    $opensOverTimeBuckets = collect();
    $opensOverTimePeak = null;
    if ($opensOverTime->isNotEmpty()) {
        $bucketRanges = [
            ['label' => '0–4h', 'start' => 0, 'end' => 3],
            ['label' => '4–8h', 'start' => 4, 'end' => 7],
            ['label' => '8–12h', 'start' => 8, 'end' => 11],
            ['label' => '12–24h', 'start' => 12, 'end' => 23],
            ['label' => '24–48h', 'start' => 24, 'end' => 48],
        ];

        $opensOverTimeBuckets = collect($bucketRanges)->map(function ($bucket) use ($opensOverTime) {
            return [
                'label' => $bucket['label'],
                'count' => $opensOverTime->filter(function ($count, $hour) use ($bucket) {
                    return (int) $hour >= $bucket['start'] && (int) $hour <= $bucket['end'];
                })->sum(),
            ];
        });

        $opensOverTimePeak = $opensOverTimeBuckets->sortByDesc('count')->first();
    }
@endphp

<div class="flex items-center gap-3 mb-6">
    <a href="{{ cp_route('newsletter.analytics.index') }}" class="text-grey-60 hover:text-grey-80 text-sm">&larr; Analytics</a>
    <div>
        <h1 class="text-3xl font-bold">{{ $campaign->name }}</h1>
        <p class="text-sm text-grey-60 mt-0.5">
            {{ $campaign->collectionLabel() }}
            &middot; Sent {{ $campaign->sent_at?->format('M j, Y g:i A') ?? '—' }}
        </p>
    </div>
</div>

<div class="grid grid-cols-5 gap-4 mb-8">
    @foreach($kpis as $kpi)
    <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-4">
        <p class="text-xs text-grey-50 uppercase tracking-wide mb-1">{{ $kpi['label'] }}</p>
        <p class="text-2xl font-bold {{ $kpi['color'] }} mb-0.5">{{ $kpi['value'] }}</p>
        <p class="text-xs text-grey-50">{{ $kpi['sub'] }}</p>
    </div>
    @endforeach
</div>

<div class="flex gap-6">
    <div class="flex-1 space-y-6">

        @if($opensOverTimeBuckets->isNotEmpty())
        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-6">
            <h2 class="font-semibold mb-4">Opens Over Time <span class="text-xs text-grey-50 font-normal">(first 48 hours)</span></h2>
            @if($opensOverTimePeak && $opensOverTimePeak['count'] > 0)
                <p class="text-sm text-grey-70 mb-5">
                    Most opens happened within the first
                    <strong>{{ $opensOverTimePeak['label'] }}</strong>
                    after send
                    <span class="text-grey-50">({{ number_format($opensOverTimePeak['count']) }} opens)</span>.
                </p>
            @endif
            <div class="divide-y divide-grey-20 border border-grey-20 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between gap-4 bg-grey-10 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-grey-50">
                    <span>Hour Range</span>
                    <span class="text-right">Opens</span>
                </div>
                @foreach($opensOverTimeBuckets as $bucket)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <span class="text-grey-70">{{ $bucket['label'] }}</span>
                        <span class="font-semibold text-grey-80 text-right">{{ number_format($bucket['count']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-6">
            <h2 class="font-semibold mb-4">Status Breakdown</h2>
            <div class="space-y-3">
                @foreach($statusRows as $row)
                @php $pct = round(($row['count'] / $statusTotal) * 100, 1); @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-grey-70">{{ $row['label'] }}</span>
                        <span class="font-medium">
                            {{ number_format($row['count']) }} <span class="text-grey-50 font-normal">({{ $pct }}%)</span>
                        </span>
                    </div>
                    <div class="w-full bg-grey-20 rounded-full h-2 overflow-hidden border border-grey-20">
                        <div class="h-2 rounded-full" style="width:{{ min($pct, 100) }}%; background:{{ $row['color'] }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if($topLinks->isNotEmpty())
        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-0 overflow-hidden">
            <div class="p-4 border-b border-grey-20 bg-grey-10">
                <h2 class="font-semibold">Top Clicked Links</h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th class="text-right">Total Clicks</th>
                        <th class="text-right">Unique</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topLinks as $link)
                    <tr>
                        <td class="text-sm">
                            <a href="{{ $link->url }}" target="_blank"
                               class="text-blue hover:underline truncate block max-w-sm"
                               title="{{ $link->url }}">
                                {{ parse_url($link->url, PHP_URL_HOST) }}{{ parse_url($link->url, PHP_URL_PATH) }}
                            </a>
                        </td>
                        <td class="text-sm text-right font-medium">{{ number_format($link->clicks) }}</td>
                        <td class="text-sm text-right text-grey-60">{{ number_format($link->unique_clicks) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($failedSends->isNotEmpty())
        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-0 overflow-hidden">
            <div class="p-4 border-b border-grey-20 bg-grey-10">
                <h2 class="font-semibold text-red">Failed &amp; Bounced <span class="text-grey-50 text-sm font-normal">(up to 50)</span></h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Subscriber</th><th>Status</th><th>Reason</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @foreach($failedSends as $send)
                    <tr>
                        <td class="text-sm">
                            @if($send->subscriber)
                                <a href="{{ cp_route('newsletter.subscribers.show', $send->subscriber) }}"
                                   class="text-blue hover:underline">{{ $send->subscriber->email }}</a>
                            @else
                                <span class="text-grey-50">(deleted)</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-xs {{ $send->status === 'bounced' ? 'bg-red-lighter text-red-dark' : 'bg-orange-lighter text-orange-dark' }}">
                                {{ $send->status }}
                            </span>
                        </td>
                        <td class="text-xs text-grey-60 max-w-xs truncate">{{ $send->bounce_reason ?? '—' }}</td>
                        <td class="text-xs text-grey-50">{{ ($send->bounced_at ?? $send->failed_at)?->format('M j H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

    <div class="w-72 space-y-6">
        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-5 text-sm">
            <h2 class="font-semibold mb-3">Audiences</h2>
            @forelse($campaign->audiences as $audience)
                @if($audience->targetable)
                <div class="flex items-center gap-2 py-1">
                    <span class="w-2 h-2 rounded-full bg-blue inline-block flex-shrink-0"></span>
                    <span>{{ $audience->targetable->name }}</span>
                </div>
                @endif
            @empty
                <p class="text-grey-50">None</p>
            @endforelse
        </div>

        @if($opensByHour->isNotEmpty())
        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-5">
            <h2 class="font-semibold mb-3 text-sm">Opens by Hour of Day</h2>
            @php $maxByHour = $opensByHour->max() ?: 1; @endphp
            <div class="flex items-end gap-px h-14">
                @for($h = 0; $h < 24; $h++)
                @php $cnt = $opensByHour->get($h, 0); $pct = round(($cnt / $maxByHour) * 100); @endphp
                <div class="flex-1 rounded-t"
                     style="height:{{ max($pct, $cnt > 0 ? 10 : 0) }}%; background:#60a5fa;"
                     title="{{ $h }}:00 — {{ $cnt }} opens"></div>
                @endfor
            </div>
            <div class="flex justify-between text-xs text-grey-40 mt-1">
                <span>12am</span><span>6am</span><span>12pm</span><span>6pm</span><span>12am</span>
            </div>
        </div>
        @endif

        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-5 text-sm space-y-4">
            <div class="space-y-3 text-xs text-grey-60">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-grey-80">Sync status</span>
                    <span class="badge text-xs {{ $syncBadgeClasses }}">{{ $syncStatus ?: 'idle' }}</span>
                </div>
                @if($campaign->last_stats_sync_requested_at)
                    <p>Requested {{ $campaign->last_stats_sync_requested_at->format('M j, Y g:i A') }}</p>
                @endif
                @if($syncStatus === 'processing' || $syncStatus === 'completed')
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-grey-70">
                                Progress {{ number_format($syncProcessed) }} / {{ number_format($syncTotal) }} @if($syncTotal > 0) ({{ $syncProgress }}%) @endif
                            </p>
                            <span class="font-semibold text-grey-80">{{ $syncProgress }}%</span>
                        </div>
                        <div class="w-full bg-grey-20 rounded-full h-2.5 overflow-hidden border border-grey-20">
                            <div class="h-2.5 rounded-full" style="width:{{ $syncProgress }}%; background:#2563eb;"></div>
                        </div>
                    </div>
                @endif
                @if($campaign->last_stats_sync_completed_at)
                    <p>Completed {{ $campaign->last_stats_sync_completed_at->format('M j, Y g:i A') }}</p>
                @endif
                @if($syncStatus === 'failed' && $campaign->last_stats_sync_error)
                    <p class="text-red-dark">{{ \Illuminate\Support\Str::limit($campaign->last_stats_sync_error, 180) }}</p>
                @endif
            </div>

            <div class="space-y-2 border-t border-grey-20 pt-4">
                <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
                   class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10">
                    <span>View campaign</span>
                    <span>&rarr;</span>
                </a>
                <form method="POST" action="{{ cp_route('newsletter.analytics.campaign.sync', $campaign) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10">
                        <span>{{ in_array($syncStatus, ['queued', 'processing', 'failed'], true) ? 'Re-queue Stats Sync' : 'Sync Stats Now' }}</span>
                        <span>&rarr;</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-5 text-sm space-y-2">
            <h2 class="font-semibold mb-2">Exports</h2>
            <a href="{{ cp_route('newsletter.analytics.campaign.export-summary', $campaign) }}"
               class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10">
                <span>Export Summary CSV</span>
                <span>&rarr;</span>
            </a>
            <a href="{{ cp_route('newsletter.analytics.campaign.export-top-links', $campaign) }}"
               class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10">
                <span>Export Top Links CSV</span>
                <span>&rarr;</span>
            </a>
            <a href="{{ cp_route('newsletter.analytics.campaign.export-open-timing', $campaign) }}"
               class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10">
                <span>Export Open Timing CSV</span>
                <span>&rarr;</span>
            </a>
            <a href="{{ cp_route('newsletter.analytics.campaign.export-failures', $campaign) }}"
               class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10">
                <span>Export Failed/Bounced CSV</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
</div>

@endsection
