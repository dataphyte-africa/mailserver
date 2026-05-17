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
        ['key' => 'clicked', 'label' => 'Clicked', 'count' => $stats['clicked'], 'color' => 'bg-purple'],
        ['key' => 'opened', 'label' => 'Opened', 'count' => $stats['opened'], 'color' => 'bg-blue'],
        ['key' => 'unread', 'label' => 'Unread', 'count' => $stats['unread'], 'color' => 'bg-green-lighter'],
        ['key' => 'bounced', 'label' => 'Bounced', 'count' => $stats['bounced'], 'color' => 'bg-orange'],
        ['key' => 'failed', 'label' => 'Failed', 'count' => $stats['failed'], 'color' => 'bg-red-lighter'],
        ['key' => 'complained', 'label' => 'Complained', 'count' => $stats['complained'], 'color' => 'bg-red'],
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

<div id="campaign-analytics-root"
     data-status-endpoint="{{ cp_route('newsletter.analytics.campaign.status', $campaign) }}"
     data-initial-sync-status="{{ $syncStatus }}">

    <div class="grid grid-cols-5 gap-4 mb-8">
        @foreach($kpis as $kpi)
        <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-4">
            <p class="text-xs text-grey-50 uppercase tracking-wide mb-1">{{ $kpi['label'] }}</p>
            <p class="text-2xl font-bold {{ $kpi['color'] }} mb-0.5" data-kpi-value="{{ $kpi['key'] }}">{{ $kpi['value'] }}</p>
            <p class="text-xs text-grey-50" data-kpi-sub="{{ $kpi['key'] }}">{{ $kpi['sub'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="flex gap-6">
        <div class="flex-1 space-y-6">

            @if($opensOverTime->isNotEmpty())
            <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-6">
                <h2 class="font-semibold mb-4">Opens Over Time <span class="text-xs text-grey-50 font-normal">(first 48 hours)</span></h2>
                @php $maxOpens = $opensOverTime->max() ?: 1; @endphp
                <div class="flex items-end gap-0.5 h-20">
                    @for($h = 0; $h <= 48; $h++)
                    @php
                        $count = $opensOverTime->get($h, 0);
                        $pct = round(($count / $maxOpens) * 100);
                    @endphp
                    <div class="flex-1 bg-blue-lighter rounded-t"
                         style="height:{{ $pct }}%"
                         data-open-time-bar="{{ $h }}"
                         title="{{ $h }}h: {{ number_format($count) }} opens"></div>
                    @endfor
                </div>
                <div class="flex justify-between mt-1 text-xs text-grey-40">
                    <span>0h</span><span>12h</span><span>24h</span><span>36h</span><span>48h</span>
                </div>
            </div>
            @endif

            <div class="card border border-grey-20 rounded-lg shadow-sm bg-white p-6">
                <h2 class="font-semibold mb-4">Status Breakdown</h2>
                <div class="space-y-3">
                    @foreach($statusRows as $row)
                    @php $pct = round(($row['count'] / $statusTotal) * 100, 1); @endphp
                    <div data-status-row="{{ $row['key'] }}">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-grey-70">{{ $row['label'] }}</span>
                            <span class="font-medium" data-status-count="{{ $row['key'] }}">
                                {{ number_format($row['count']) }} <span class="text-grey-50 font-normal" data-status-percent="{{ $row['key'] }}">({{ $pct }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-grey-20 rounded-full h-2 overflow-hidden border border-grey-20">
                            <div class="{{ $row['color'] }} h-2 rounded-full" data-status-bar="{{ $row['key'] }}" style="width:{{ min($pct, 100) }}%"></div>
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
                    <tbody id="top-links-body">
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
                    <tbody id="failed-sends-body">
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
                    <div class="flex-1 bg-blue-lighter rounded-t"
                         data-hour-bar="{{ $h }}"
                         style="height:{{ $pct }}%"
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
                        <span class="badge text-xs {{ $syncBadgeClasses }}" data-sync-status-badge>{{ $syncStatus ?: 'idle' }}</span>
                    </div>
                    <p data-sync-requested @class(['hidden' => ! $campaign->last_stats_sync_requested_at])>
                        Requested <span data-sync-requested-value>{{ $campaign->last_stats_sync_requested_at?->format('M j, Y g:i A') }}</span>
                    </p>
                    <div class="space-y-2" data-sync-progress-wrap @class(['hidden' => $syncStatus !== 'processing' && $syncStatus !== 'completed'])>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-grey-70" data-sync-progress-text>
                                Progress {{ number_format($syncProcessed) }} / {{ number_format($syncTotal) }} @if($syncTotal > 0) ({{ $syncProgress }}%) @endif
                            </p>
                            <span class="font-semibold text-grey-80" data-sync-progress-percent>{{ $syncProgress }}%</span>
                        </div>
                        <div class="w-full bg-grey-20 rounded-full h-2.5 overflow-hidden border border-grey-20">
                            <div class="bg-blue h-2.5 rounded-full transition-all duration-300" data-sync-progress-bar style="width:{{ $syncProgress }}%"></div>
                        </div>
                    </div>
                    <p data-sync-completed @class(['hidden' => ! $campaign->last_stats_sync_completed_at])>
                        Completed <span data-sync-completed-value>{{ $campaign->last_stats_sync_completed_at?->format('M j, Y g:i A') }}</span>
                    </p>
                    <p class="text-red-dark hidden" data-sync-error></p>
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
                                class="inline-flex w-full items-center justify-between rounded-md border border-grey-20 bg-white px-3 py-2 text-sm font-medium text-grey-80 shadow-sm hover:bg-grey-10"
                                data-sync-submit>
                            <span data-sync-submit-label>{{ in_array($syncStatus, ['queued', 'processing', 'failed'], true) ? 'Re-queue Stats Sync' : 'Sync Stats Now' }}</span>
                            <span>&rarr;</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.getElementById('campaign-analytics-root');
    if (!root) return;

    const statusEndpoint = root.dataset.statusEndpoint;
    let syncStatus = root.dataset.initialSyncStatus || '';
    let pollTimer = null;
    let requestInFlight = false;

    const formatNumber = (value) => new Intl.NumberFormat().format(value || 0);
    const formatPercent = (value) => `${Number(value || 0).toFixed(1).replace(/\.0$/, '')}%`;

    const formatDate = (value) => {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';

        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(date);
    };

    const syncBadgeClasses = {
        queued: 'bg-yellow-lighter text-yellow-dark',
        processing: 'bg-blue-lighter text-blue-dark',
        completed: 'bg-green-lighter text-green-dark',
        failed: 'bg-red-lighter text-red-dark',
        idle: 'bg-grey-20 text-grey-70',
    };

    function setText(selector, value) {
        const el = root.querySelector(selector);
        if (el) el.textContent = value;
    }

    function toggleHidden(selector, hidden) {
        const el = root.querySelector(selector);
        if (el) el.classList.toggle('hidden', hidden);
    }

    function setWidth(selector, value) {
        const el = root.querySelector(selector);
        if (el) el.style.width = `${Math.max(0, Math.min(100, value || 0))}%`;
    }

    function updateSync(sync) {
        syncStatus = sync.status || 'idle';

        const badge = root.querySelector('[data-sync-status-badge]');
        if (badge) {
            badge.textContent = syncStatus;
            badge.className = `badge text-xs ${syncBadgeClasses[syncStatus] || syncBadgeClasses.idle}`;
        }

        toggleHidden('[data-sync-requested]', !sync.requested_at);
        setText('[data-sync-requested-value]', formatDate(sync.requested_at));

        const showProgress = ['queued', 'processing', 'completed'].includes(syncStatus) && Number(sync.total || 0) > 0;
        toggleHidden('[data-sync-progress-wrap]', !showProgress);
        setText('[data-sync-progress-text]', `Progress ${formatNumber(sync.processed)} / ${formatNumber(sync.total)} (${sync.progress_percent || 0}%)`);
        setText('[data-sync-progress-percent]', `${sync.progress_percent || 0}%`);
        setWidth('[data-sync-progress-bar]', sync.progress_percent || 0);

        toggleHidden('[data-sync-completed]', !sync.completed_at);
        setText('[data-sync-completed-value]', formatDate(sync.completed_at));

        const errorEl = root.querySelector('[data-sync-error]');
        if (errorEl) {
            if (sync.error) {
                errorEl.textContent = sync.error;
                errorEl.classList.remove('hidden');
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
        }

        setText('[data-sync-submit-label]', ['queued', 'processing', 'failed'].includes(syncStatus) ? 'Re-queue Stats Sync' : 'Sync Stats Now');
    }

    function updateMetrics(metrics) {
        setText('[data-kpi-value="total_sent"]', formatNumber(metrics.total_sent));
        setText('[data-kpi-value="delivery_rate"]', formatPercent(metrics.delivery_rate));
        setText('[data-kpi-sub="delivery_rate"]', `${formatNumber(metrics.delivered)} emails`);
        setText('[data-kpi-value="open_rate"]', formatPercent(metrics.open_rate));
        setText('[data-kpi-sub="open_rate"]', `${formatNumber(metrics.opened)} opened`);
        setText('[data-kpi-value="click_rate"]', formatPercent(metrics.click_rate));
        setText('[data-kpi-sub="click_rate"]', `${formatNumber(metrics.clicked)} clicks`);
        setText('[data-kpi-value="bounced"]', formatNumber(metrics.bounced));
        setText('[data-kpi-sub="bounced"]', `${formatNumber(metrics.failed)} failed`);
    }

    function updateStatusBreakdown(rows) {
        rows.forEach((row) => {
            setText(`[data-status-count="${row.key}"]`, formatNumber(row.count));
            setText(`[data-status-percent="${row.key}"]`, `(${formatPercent(row.percent)})`);
            setWidth(`[data-status-bar="${row.key}"]`, row.percent);
        });
    }

    function updateOpensByHour(values) {
        const max = Math.max(1, ...Object.values(values || {}).map((value) => Number(value || 0)));
        for (let hour = 0; hour < 24; hour++) {
            const count = Number(values?.[hour] || 0);
            const pct = Math.round((count / max) * 100);
            const bar = root.querySelector(`[data-hour-bar="${hour}"]`);
            if (bar) {
                bar.style.height = `${pct}%`;
                bar.title = `${hour}:00 — ${count} opens`;
            }
        }
    }

    function updateOpensOverTime(values) {
        const counts = Object.values(values || {}).map((value) => Number(value || 0));
        const max = Math.max(1, ...counts, 0);
        for (let hour = 0; hour <= 48; hour++) {
            const count = Number(values?.[hour] || 0);
            const pct = Math.round((count / max) * 100);
            const bar = root.querySelector(`[data-open-time-bar="${hour}"]`);
            if (bar) {
                bar.style.height = `${pct}%`;
                bar.title = `${hour}h: ${count} opens`;
            }
        }
    }

    async function pollStatus() {
        if (requestInFlight) return;
        requestInFlight = true;

        try {
            const response = await fetch(statusEndpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Polling failed with ${response.status}`);
            }

            const payload = await response.json();

            updateSync(payload.sync);
            updateMetrics(payload.metrics);
            updateStatusBreakdown(payload.status_breakdown || []);
            updateOpensByHour(payload.opens_by_hour || {});
            updateOpensOverTime(payload.opens_over_time || {});

            if (!['queued', 'processing'].includes(payload.sync?.status)) {
                stopPolling();
            }
        } catch (error) {
            console.error('Analytics status polling failed', error);
        } finally {
            requestInFlight = false;
        }
    }

    function startPolling() {
        if (pollTimer || !['queued', 'processing'].includes(syncStatus)) return;
        pollTimer = window.setInterval(pollStatus, 5000);
    }

    function stopPolling() {
        if (!pollTimer) return;
        window.clearInterval(pollTimer);
        pollTimer = null;
    }

    if (['queued', 'processing'].includes(syncStatus)) {
        startPolling();
        pollStatus();
    }
})();
</script>

@endsection
