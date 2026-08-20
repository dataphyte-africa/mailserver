@extends('statamic::layout')
@section('title', $campaign->name . ' - Analytics')

@section('content')
@php
    $kpis = [
        ['label' => 'Total Sent', 'value' => number_format($stats['total_sent']), 'sub' => 'Campaign recipients', 'tone' => 'neutral'],
        ['label' => 'Delivery Rate', 'value' => $stats['delivery_rate'] . '%', 'sub' => number_format($stats['delivered']) . ' emails', 'tone' => 'green'],
        ['label' => 'Open Rate', 'value' => $stats['open_rate'] . '%', 'sub' => number_format($stats['opened']) . ' opened', 'tone' => 'blue'],
        ['label' => 'Click Rate', 'value' => $stats['click_rate'] . '%', 'sub' => number_format($stats['clicked']) . ' clicks', 'tone' => 'amber'],
        ['label' => 'Bounced', 'value' => number_format($stats['bounced']), 'sub' => number_format($stats['failed']) . ' failed', 'tone' => $stats['bounced'] > 0 ? 'red' : 'neutral'],
    ];
    $statusRows = [
        ['label' => 'Clicked', 'count' => $stats['clicked'], 'color' => '#d97706'],
        ['label' => 'Opened', 'count' => $stats['opened'], 'color' => '#2563eb'],
        ['label' => 'Unread', 'count' => $stats['unread'], 'color' => '#65a30d'],
        ['label' => 'Bounced', 'count' => $stats['bounced'], 'color' => '#f59e0b'],
        ['label' => 'Failed', 'count' => $stats['failed'], 'color' => '#ef4444'],
        ['label' => 'Complained', 'count' => $stats['complained'], 'color' => '#b91c1c'],
    ];
    $statusTotal = max(1, (int) ($stats['total_sent'] ?? 0));
    $syncStatus = $campaign->last_stats_sync_status;
    $syncTotal = (int) ($campaign->last_stats_sync_total ?? 0);
    $syncProcessed = (int) ($campaign->last_stats_sync_processed ?? 0);
    $syncProgress = $campaign->statsSyncProgress();
    $syncTone = match ($syncStatus) {
        'queued' => 'warning',
        'processing' => 'blue',
        'completed' => 'success',
        'failed' => 'danger',
        default => 'neutral',
    };
    $opensOverTimeBuckets = collect();
    $opensOverTimePeak = null;
    if ($opensOverTime->isNotEmpty()) {
        $bucketRanges = [
            ['label' => '0-4h', 'start' => 0, 'end' => 3],
            ['label' => '4-8h', 'start' => 4, 'end' => 7],
            ['label' => '8-12h', 'start' => 8, 'end' => 11],
            ['label' => '12-24h', 'start' => 12, 'end' => 23],
            ['label' => '24-48h', 'start' => 24, 'end' => 48],
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

<div class="analytics-campaign-hero">
    <div class="analytics-campaign-hero-main">
        <a href="{{ cp_route('newsletter.analytics.index') }}" class="analytics-campaign-back">&larr; Analytics</a>
        <h1 class="analytics-campaign-title">{{ $campaign->name }}</h1>
        <p class="analytics-campaign-meta">
            {{ $campaign->collectionLabel() }}
            <span>&middot;</span>
            Sent {{ $campaign->sent_at?->format('M j, Y g:i A') ?? '-' }}
        </p>
    </div>
    <div class="analytics-campaign-hero-actions">
        <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}" class="analytics-campaign-button">View campaign</a>
        <form method="POST" action="{{ cp_route('newsletter.analytics.campaign.sync', $campaign) }}">
            @csrf
            <button type="submit" class="analytics-campaign-button analytics-campaign-button-primary">
                {{ in_array($syncStatus, ['queued', 'processing', 'failed'], true) ? 'Re-queue sync' : 'Sync stats' }}
            </button>
        </form>
    </div>
</div>

<div class="analytics-campaign-kpi-grid">
    @foreach($kpis as $kpi)
        <div class="analytics-campaign-kpi analytics-campaign-kpi-{{ $kpi['tone'] }}">
            <p class="analytics-campaign-kpi-label">{{ $kpi['label'] }}</p>
            <p class="analytics-campaign-kpi-value">{{ $kpi['value'] }}</p>
            <p class="analytics-campaign-kpi-sub">{{ $kpi['sub'] }}</p>
        </div>
    @endforeach
</div>

<div class="analytics-campaign-overview-grid analytics-campaign-overview-grid-two">
    @if($opensOverTimeBuckets->isNotEmpty())
        <div class="analytics-campaign-card">
            <div class="analytics-campaign-card-header">
                <h2 class="analytics-campaign-card-title">Opens Over Time</h2>
                <span class="analytics-campaign-card-subtitle">First 48 hours</span>
            </div>
            @if($opensOverTimePeak && $opensOverTimePeak['count'] > 0)
                <p class="analytics-campaign-insight">
                    Most opens happened in <strong>{{ $opensOverTimePeak['label'] }}</strong>
                    after send, with {{ number_format($opensOverTimePeak['count']) }} opens.
                </p>
            @endif
            <div class="analytics-campaign-bucket-list">
                @foreach($opensOverTimeBuckets as $bucket)
                    <div class="analytics-campaign-bucket-row">
                        <span>{{ $bucket['label'] }}</span>
                        <strong>{{ number_format($bucket['count']) }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="analytics-campaign-card">
        <div class="analytics-campaign-card-header">
            <h2 class="analytics-campaign-card-title">Status Breakdown</h2>
            <span class="analytics-campaign-card-subtitle">{{ number_format($statusTotal) }} sends</span>
        </div>
        <div class="analytics-campaign-status-list">
            @foreach($statusRows as $row)
            @php $pct = round(($row['count'] / $statusTotal) * 100, 1); @endphp
                <div class="analytics-campaign-status-row">
                    <div class="analytics-campaign-status-label">
                        <span>{{ $row['label'] }}</span>
                        <strong>{{ number_format($row['count']) }} <em>{{ $pct }}%</em></strong>
                    </div>
                    <div class="analytics-campaign-progress-track">
                        <div class="analytics-campaign-progress-fill" style="width:{{ min($pct, 100) }}%; background:{{ $row['color'] }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

<div class="analytics-campaign-secondary-grid analytics-campaign-secondary-grid-two">
    <div class="analytics-campaign-card">
        <div class="analytics-campaign-card-header">
            <h2 class="analytics-campaign-card-title">Audiences</h2>
        </div>
        <div class="analytics-campaign-chip-list">
            @forelse($campaign->audiences as $audience)
                @if($audience->targetable)
                    <span class="analytics-campaign-chip">{{ $audience->targetable->name }}</span>
                @endif
            @empty
                <p class="analytics-campaign-empty">None</p>
            @endforelse
        </div>
    </div>

    <div class="analytics-campaign-card">
        <div class="analytics-campaign-card-header">
            <h2 class="analytics-campaign-card-title">Sync Status</h2>
            <span class="analytics-campaign-badge analytics-campaign-badge-{{ $syncTone }}">{{ $syncStatus ?: 'idle' }}</span>
        </div>
        <div class="analytics-campaign-sync-list">
            @if($campaign->last_stats_sync_requested_at)
                <p>Requested {{ $campaign->last_stats_sync_requested_at->format('M j, Y g:i A') }}</p>
            @endif
            @if($syncStatus === 'processing' || $syncStatus === 'completed')
                <p>Progress {{ number_format($syncProcessed) }} / {{ number_format($syncTotal) }} @if($syncTotal > 0) ({{ $syncProgress }}%) @endif</p>
                <div class="analytics-campaign-progress-track">
                    <div class="analytics-campaign-progress-fill analytics-campaign-progress-fill-blue" style="width:{{ $syncProgress }}%;"></div>
                </div>
            @endif
            @if($campaign->last_stats_sync_completed_at)
                <p>Completed {{ $campaign->last_stats_sync_completed_at->format('M j, Y g:i A') }}</p>
            @endif
            @if($syncStatus === 'failed' && $campaign->last_stats_sync_error)
                <p class="analytics-campaign-danger-text">{{ \Illuminate\Support\Str::limit($campaign->last_stats_sync_error, 180) }}</p>
            @endif
            @if(! $campaign->last_stats_sync_requested_at && ! $campaign->last_stats_sync_completed_at)
                <p>No sync has been requested yet.</p>
            @endif
        </div>
    </div>
</div>

@if($opensByHour->isNotEmpty())
    <div class="analytics-campaign-card analytics-campaign-hour-callout-card">
        <div class="analytics-campaign-card-header">
            <div>
                <h2 class="analytics-campaign-card-title">Opens By Hour</h2>
                <span class="analytics-campaign-card-subtitle">Local time</span>
            </div>
            @php
                $maxByHour = $opensByHour->max() ?: 1;
                $peakHour = $opensByHour->sortDesc()->keys()->first();
                $peakCount = $peakHour !== null ? (int) $opensByHour->get($peakHour, 0) : 0;
            @endphp
            <div class="analytics-campaign-hour-callout">
                <span>Peak hour</span>
                <strong>{{ $peakHour !== null ? str_pad((string) $peakHour, 2, '0', STR_PAD_LEFT) . ':00' : '-' }}</strong>
                <em>{{ number_format($peakCount) }} opens</em>
            </div>
        </div>
        <div class="analytics-campaign-hour-layout">
            <div class="analytics-campaign-hour-bars">
                @for($h = 0; $h < 24; $h++)
                @php $cnt = $opensByHour->get($h, 0); $pct = round(($cnt / $maxByHour) * 100); @endphp
                    <div class="analytics-campaign-hour-bar"
                         style="height:{{ max($pct, $cnt > 0 ? 10 : 0) }}%;"
                         title="{{ $h }}:00 - {{ $cnt }} opens"></div>
                @endfor
            </div>
            <div class="analytics-campaign-hour-axis">
                <span>12am</span><span>6am</span><span>12pm</span><span>6pm</span><span>12am</span>
            </div>
        </div>
    </div>
@endif

<div class="analytics-campaign-card analytics-campaign-export-card">
    <div class="analytics-campaign-card-header">
        <div>
            <h2 class="analytics-campaign-card-title">Exports</h2>
            <span class="analytics-campaign-card-subtitle">Download campaign analytics datasets</span>
        </div>
    </div>
    <div class="analytics-campaign-action-list analytics-campaign-action-list-two">
        <a href="{{ cp_route('newsletter.analytics.campaign.export-summary', $campaign) }}" class="analytics-campaign-action-link">Export Summary CSV <span>&rarr;</span></a>
        <a href="{{ cp_route('newsletter.analytics.campaign.export-top-links', $campaign) }}" class="analytics-campaign-action-link">Export Top Links CSV <span>&rarr;</span></a>
        <a href="{{ cp_route('newsletter.analytics.campaign.export-open-timing', $campaign) }}" class="analytics-campaign-action-link">Export Open Timing CSV <span>&rarr;</span></a>
        <a href="{{ cp_route('newsletter.analytics.campaign.export-failures', $campaign) }}" class="analytics-campaign-action-link">Export Failed/Bounced CSV <span>&rarr;</span></a>
    </div>
</div>

@if($topLinks->isNotEmpty())
<div class="analytics-campaign-table-card">
    <div class="analytics-campaign-table-header">
        <h2 class="analytics-campaign-card-title">Top Clicked Links</h2>
    </div>
    <div class="analytics-campaign-table-scroll">
        <table class="data-table analytics-campaign-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Total Clicks</th>
                    <th>Unique</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topLinks as $link)
                <tr>
                    <td>
                        <a href="{{ $link->url }}" target="_blank" class="analytics-campaign-link" title="{{ $link->url }}">
                            {{ parse_url($link->url, PHP_URL_HOST) }}{{ parse_url($link->url, PHP_URL_PATH) }}
                        </a>
                    </td>
                    <td class="analytics-campaign-number">{{ number_format($link->clicks) }}</td>
                    <td class="analytics-campaign-muted-number">{{ number_format($link->unique_clicks) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($failedSends->isNotEmpty())
<div class="analytics-campaign-table-card">
    <div class="analytics-campaign-table-header">
        <h2 class="analytics-campaign-card-title analytics-campaign-danger-text">Failed &amp; Bounced</h2>
        <span class="analytics-campaign-card-subtitle">Up to 50</span>
    </div>
    <div class="analytics-campaign-table-scroll">
        <table class="data-table analytics-campaign-table analytics-campaign-failure-table">
            <thead>
                <tr>
                    <th>Subscriber</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($failedSends as $send)
                <tr>
                    <td>
                        @if($send->subscriber)
                            <a href="{{ cp_route('newsletter.subscribers.show', $send->subscriber) }}" class="analytics-campaign-link">{{ $send->subscriber->email }}</a>
                        @else
                            <span class="analytics-campaign-muted">(deleted)</span>
                        @endif
                    </td>
                    <td><span class="analytics-campaign-badge analytics-campaign-badge-danger">{{ $send->status }}</span></td>
                    <td class="analytics-campaign-muted">{{ $send->bounce_reason ?? '-' }}</td>
                    <td class="analytics-campaign-muted">{{ ($send->bounced_at ?? $send->failed_at)?->format('M j H:i') ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
