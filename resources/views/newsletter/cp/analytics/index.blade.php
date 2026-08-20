@extends('statamic::layout')
@section('title', 'Analytics')

@section('content')
<div class="analytics-cp-header">
    <h1 class="analytics-cp-title">Analytics</h1>

    <form method="GET" class="analytics-cp-filters">
        <select name="collection" onchange="this.form.submit()" class="input-text analytics-cp-filter-control">
            <option value="">All Collections</option>
            @foreach($collections as $value => $label)
                <option value="{{ $value }}" {{ $collection === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="days" onchange="this.form.submit()" class="input-text analytics-cp-filter-control">
            @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $val => $label)
                <option value="{{ $val }}" {{ $days == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Summary KPI cards --}}
<div class="analytics-cp-kpi-grid">
    @php
        $kpis = [
            ['label' => 'Campaigns Sent',  'value' => number_format($totals['campaigns']),  'sub' => "last {$days} days",    'tone' => 'neutral'],
            ['label' => 'Delivery Rate',   'value' => $totals['delivery_rate'] . '%',       'sub' => number_format($totals['delivered']) . ' delivered', 'tone' => 'green'],
            ['label' => 'Open Rate',       'value' => $totals['open_rate'] . '%',            'sub' => number_format($totals['opened']) . ' opened',      'tone' => 'blue'],
            ['label' => 'Click Rate',      'value' => $totals['click_rate'] . '%',           'sub' => number_format($totals['clicked']) . ' clicked',    'tone' => 'amber'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="analytics-cp-kpi-card analytics-cp-kpi-card-{{ $kpi['tone'] }}">
        <p class="analytics-cp-kpi-label">{{ $kpi['label'] }}</p>
        <p class="analytics-cp-kpi-value">{{ $kpi['value'] }}</p>
        <p class="analytics-cp-kpi-sub">{{ $kpi['sub'] }}</p>
    </div>
    @endforeach
</div>

<div class="analytics-cp-chart-row">
    {{-- Daily send volume (text-based bar chart — no JS libs needed) --}}
    @if($dailyVolume->isNotEmpty())
    <div class="analytics-cp-card analytics-cp-chart-card">
        <h2 class="analytics-cp-card-title">Daily Send Volume</h2>
        @php
            $maxVolume = $dailyVolume->max('total') ?: 1;
            $chartDays = collect();
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $chartDays->push(['date' => $date, 'data' => $dailyVolume->get($date)]);
            }
        @endphp
        <div class="analytics-cp-chart-bars">
            @foreach($chartDays as $day)
            @php
                $total  = $day['data']->total  ?? 0;
                $opened = $day['data']->opened ?? 0;
                $height = $maxVolume > 0 ? round(($total / $maxVolume) * 100) : 0;
                $label  = \Carbon\Carbon::parse($day['date'])->format('M j');
            @endphp
            <div class="analytics-cp-chart-bar"
                 title="{{ $label }}: {{ number_format($total) }} sent, {{ number_format($opened) }} opened">
                <div class="analytics-cp-chart-bar-fill"
                     style="height: {{ $height }}%"></div>
            </div>
            @endforeach
        </div>
        <div class="analytics-cp-chart-axis">
            <span>{{ now()->subDays($days - 1)->format('M j') }}</span>
            <span>Today</span>
        </div>
    </div>
    @endif
</div>

<div class="analytics-cp-overview-row analytics-cp-overview-row-two">
    <div class="analytics-cp-card analytics-cp-health-card">
            <h2 class="analytics-cp-card-title">Webhook Health <span>(24h)</span></h2>
            @php
                $wh = $webhookHealth;
                $successRate = $wh['total'] > 0 ? round((($wh['processed']) / $wh['total']) * 100) : 100;
            @endphp
            <div class="analytics-cp-health-meter">
                <div class="analytics-cp-health-track">
                    <div class="analytics-cp-health-fill"
                         style="width:{{ $successRate }}%"></div>
                </div>
                <span>{{ $successRate }}%</span>
            </div>
            <div class="analytics-cp-stat-list">
                <div class="analytics-cp-stat-row">
                    <span>Total received</span>
                    <strong>{{ number_format($wh['total']) }}</strong>
                </div>
                <div class="analytics-cp-stat-row">
                    <span>Processed</span>
                    <strong class="analytics-cp-success">{{ number_format($wh['processed']) }}</strong>
                </div>
                <div class="analytics-cp-stat-row">
                    <span>Pending</span>
                    <strong class="analytics-cp-warning">{{ number_format($wh['pending']) }}</strong>
                </div>
                <div class="analytics-cp-stat-row">
                    <span>Failed</span>
                    <strong class="{{ $wh['failed'] > 0 ? 'analytics-cp-danger' : 'analytics-cp-muted' }}">
                        {{ number_format($wh['failed']) }}
                    </strong>
                </div>
            </div>
            <div class="analytics-cp-card-footer">
                <a href="{{ cp_route('newsletter.analytics.webhooks') }}"
                   class="analytics-cp-link">View webhook log &rarr;</a>
            </div>
    </div>

    <div class="analytics-cp-card analytics-cp-total-card">
        <h2 class="analytics-cp-card-title">Total Activity</h2>
        <div class="analytics-cp-stat-list analytics-cp-stat-list-large">
            @php
                $rows = [
                    ['Emails Sent',    number_format($totals['sent'])],
                    ['Delivered',      number_format($totals['delivered'])],
                    ['Opened',         number_format($totals['opened'])],
                    ['Clicked',        number_format($totals['clicked'])],
                    ['Bounced/Failed', number_format($totals['bounced'])],
                ];
            @endphp
            @foreach($rows as [$label, $val])
            <div class="analytics-cp-stat-row">
                <span>{{ $label }}</span>
                <strong>{{ $val }}</strong>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Campaigns table --}}
<div class="analytics-cp-table-card">
    <div class="analytics-cp-table-header">
        <h2 class="analytics-cp-card-title">Campaign Performance</h2>
        <span class="analytics-cp-count">{{ $campaigns->count() }} campaigns</span>
    </div>
    <div class="analytics-cp-table-scroll">
    <table class="data-table analytics-cp-table">
        <thead>
            <tr>
                <th class="analytics-cp-sticky-col">Campaign</th>
                <th>Sent</th>
                <th>Delivered</th>
                <th>Open Rate</th>
                <th>Click Rate</th>
                <th>Bounced</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns as $campaign)
            @php
                $delivRate = $campaign->sends_count > 0
                    ? round(($campaign->delivered_count / $campaign->sends_count) * 100, 1) : 0;
                $openRate  = $campaign->delivered_count > 0
                    ? round(($campaign->opened_count / $campaign->delivered_count) * 100, 1) : 0;
                $clickRate = $campaign->opened_count > 0
                    ? round(($campaign->clicked_count / $campaign->opened_count) * 100, 1) : 0;
            @endphp
            <tr>
                <td class="analytics-cp-sticky-col analytics-cp-campaign-cell">
                    <a href="{{ cp_route('newsletter.analytics.campaign', $campaign) }}"
                       class="analytics-cp-link analytics-cp-campaign-link">
                        {{ $campaign->name }}
                    </a>
                    <p class="analytics-cp-campaign-meta">
                        {{ $campaign->sent_at?->format('M j, Y') }}
                        &middot;
                        {{ $campaign->collectionShortLabel() }}
                    </p>
                </td>
                <td class="analytics-cp-number">{{ number_format($campaign->sends_count) }}</td>
                <td class="analytics-cp-number">
                    {{ number_format($campaign->delivered_count) }}
                    <span class="analytics-cp-muted">({{ $delivRate }}%)</span>
                </td>
                <td>
                    <div class="analytics-cp-rate">
                        <div class="analytics-cp-rate-track">
                            <div class="analytics-cp-rate-fill analytics-cp-rate-fill-blue" style="width:{{ min($openRate, 100) }}%"></div>
                        </div>
                        <span>{{ $openRate }}%</span>
                    </div>
                </td>
                <td>
                    <div class="analytics-cp-rate">
                        <div class="analytics-cp-rate-track">
                            <div class="analytics-cp-rate-fill analytics-cp-rate-fill-amber" style="width:{{ min($clickRate, 100) }}%"></div>
                        </div>
                        <span>{{ $clickRate }}%</span>
                    </div>
                </td>
                <td class="{{ $campaign->bounced_count > 0 ? 'analytics-cp-danger' : 'analytics-cp-number' }}">
                    {{ number_format($campaign->bounced_count) }}
                </td>
                <td class="analytics-cp-actions-cell">
                    <a href="{{ cp_route('newsletter.analytics.campaign', $campaign) }}"
                       class="analytics-cp-link">Details &rarr;</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="analytics-cp-empty">
                    No sent campaigns in the last {{ $days }} days.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@endsection
