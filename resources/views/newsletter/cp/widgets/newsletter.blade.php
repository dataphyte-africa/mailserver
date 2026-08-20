<style>
    .newsletter-widget {
        background: #fff;
        border: 1px solid #d7dee8 !important;
        border-radius: .5rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .newsletter-widget-header,
    .newsletter-widget-footer {
        align-items: center;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        padding: .75rem 1rem;
    }

    .newsletter-widget-footer {
        border-bottom: 0;
        border-top: 1px solid #e2e8f0;
        font-size: .75rem;
    }

    .newsletter-widget-title {
        align-items: center;
        display: flex;
        gap: .5rem;
    }

    .newsletter-widget-icon {
        color: #64748b;
        display: inline-block;
        flex: 0 0 1rem;
        height: 1rem;
        max-height: 1rem;
        max-width: 1rem;
        min-height: 1rem;
        min-width: 1rem;
        overflow: hidden;
        vertical-align: middle;
        width: 1rem;
    }

    .newsletter-widget-icon svg,
    svg.newsletter-widget-icon {
        height: 1rem !important;
        max-height: 1rem !important;
        max-width: 1rem !important;
        width: 1rem !important;
    }

    .newsletter-widget-name {
        font-size: .875rem;
        font-weight: 700;
    }

    .newsletter-widget-muted {
        color: #64748b;
        font-size: .75rem;
    }

    .newsletter-widget-link {
        color: #2563eb;
        font-size: .75rem;
        text-decoration: none;
    }

    .newsletter-widget-link:hover {
        text-decoration: underline;
    }

    .newsletter-widget-kpis {
        border-bottom: 1px solid #e2e8f0;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .newsletter-widget-kpi {
        padding: .75rem 1rem;
        text-align: center;
    }

    .newsletter-widget-kpi + .newsletter-widget-kpi {
        border-left: 1px solid #e2e8f0;
    }

    .newsletter-widget-kpi-value {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0;
    }

    .newsletter-widget-kpi-label {
        color: #64748b;
        font-size: .75rem;
        margin: .15rem 0 0;
    }

    .newsletter-widget-status {
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        display: flex;
        flex-wrap: wrap;
        font-size: .75rem;
        gap: .75rem;
        padding: .5rem 1rem;
    }

    .newsletter-widget-status-label,
    .newsletter-widget-strong {
        font-weight: 700;
    }

    .newsletter-widget-error {
        color: #dc2626;
        font-weight: 700;
        margin-left: auto;
    }

    .newsletter-widget-campaign {
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        gap: .75rem;
        padding: .5rem 1rem;
    }

    .newsletter-widget-campaign:hover {
        background: #f8fafc;
    }

    .newsletter-widget-campaign-main {
        flex: 1;
        min-width: 0;
    }

    .newsletter-widget-campaign-title {
        color: #2563eb;
        display: block;
        font-size: .875rem;
        font-weight: 600;
        overflow: hidden;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .newsletter-widget-campaign-title:hover {
        text-decoration: underline;
    }

    .newsletter-widget-campaign-meta,
    .newsletter-widget-campaign-stats {
        color: #64748b;
        font-size: .75rem;
    }

    .newsletter-widget-campaign-meta {
        margin: .15rem 0 0;
    }

    .newsletter-widget-campaign-side {
        align-items: center;
        display: flex;
        flex-shrink: 0;
        gap: .75rem;
        font-size: .75rem;
    }

    .newsletter-widget-empty {
        color: #64748b;
        font-size: .875rem;
        padding: 1.5rem 1rem;
        text-align: center;
    }

    .newsletter-widget-text-default { color: #334155; }
    .newsletter-widget-text-green { color: #15803d; }
    .newsletter-widget-text-blue { color: #1d4ed8; }
    .newsletter-widget-text-purple { color: #6d28d9; }
    .newsletter-widget-text-yellow { color: #a16207; }
    .newsletter-widget-text-red { color: #dc2626; }
    .newsletter-widget-text-muted { color: #64748b; }

    .newsletter-widget-badge {
        border-radius: 999px;
        display: inline-block;
        font-size: .75rem;
        font-weight: 700;
        line-height: 1;
        padding: .3rem .55rem;
    }

    .newsletter-widget-badge-sent {
        background: #dcfce7;
        color: #15803d;
    }

    .newsletter-widget-badge-partial {
        background: #ffedd5;
        color: #c2410c;
    }

    .newsletter-widget-badge-sending {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .newsletter-widget-badge-scheduled {
        background: #fef3c7;
        color: #a16207;
    }

    .newsletter-widget-badge-default {
        background: #e2e8f0;
        color: #334155;
    }
</style>

<div class="newsletter-widget" style="border:1px solid #d7dee8;border-radius:.5rem;overflow:hidden;">
    <div class="newsletter-widget-header">
        <div class="newsletter-widget-title">
            <svg class="newsletter-widget-icon" width="16" height="16" style="width:16px;height:16px;max-width:16px;max-height:16px;display:inline-block;flex:0 0 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
            </svg>
            <span class="newsletter-widget-name">Newsletter</span>
            <span class="newsletter-widget-muted">last {{ $days }} days</span>
        </div>
        <a href="{{ cp_route('newsletter.analytics.index') }}" class="newsletter-widget-link">Analytics &rarr;</a>
    </div>

    <div class="newsletter-widget-kpis">
        @php
            $sent      = (int) ($totals->sent ?? 0);
            $delivered = (int) ($totals->delivered ?? 0);
            $opened    = (int) ($totals->opened ?? 0);
            $clicked   = (int) ($totals->clicked ?? 0);
            $openRate  = $delivered > 0 ? round(($opened / $delivered) * 100, 1) : 0;
            $clickRate = $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0;

            $kpis = [
                ['label' => 'Sent',      'value' => number_format($sent),     'class' => 'newsletter-widget-text-default'],
                ['label' => 'Delivered', 'value' => number_format($delivered), 'class' => 'newsletter-widget-text-green'],
                ['label' => 'Open Rate', 'value' => $openRate . '%',           'class' => 'newsletter-widget-text-blue'],
                ['label' => 'CTR',       'value' => $clickRate . '%',          'class' => 'newsletter-widget-text-purple'],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <div class="newsletter-widget-kpi">
                <p class="newsletter-widget-kpi-value {{ $kpi['class'] }}">{{ $kpi['value'] }}</p>
                <p class="newsletter-widget-kpi-label">{{ $kpi['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="newsletter-widget-status">
        <span class="newsletter-widget-status-label">Subscriber status:</span>
        <span><span class="newsletter-widget-strong newsletter-widget-text-yellow">{{ number_format($subscriberStats['pending'] ?? 0) }}</span> pending</span>
        <span>&middot;</span>
        <span><span class="newsletter-widget-strong newsletter-widget-text-green">{{ number_format($subscriberStats['active']) }}</span> active</span>
        <span>&middot;</span>
        <span><span class="newsletter-widget-strong newsletter-widget-text-muted">{{ number_format($subscriberStats['unsubscribed']) }}</span> unsubscribed</span>
        <span>&middot;</span>
        <span>
            <span class="newsletter-widget-strong {{ $subscriberStats['bounced'] > 0 ? 'newsletter-widget-text-red' : 'newsletter-widget-text-muted' }}">
                {{ number_format($subscriberStats['bounced']) }}
            </span> bounced
        </span>
        <span>&middot;</span>
        <span>
            <span class="newsletter-widget-strong {{ ($subscriberStats['complained'] ?? 0) > 0 ? 'newsletter-widget-text-red' : 'newsletter-widget-text-muted' }}">
                {{ number_format($subscriberStats['complained'] ?? 0) }}
            </span> complained
        </span>
        @if($webhookFailed > 0)
            <span class="newsletter-widget-error">{{ $webhookFailed }} webhook error{{ $webhookFailed > 1 ? 's' : '' }}</span>
        @endif
    </div>

    @forelse($recentCampaigns as $campaign)
        @php
            $openRate = $campaign->sends_count > 0
                ? round(($campaign->opened_count / $campaign->sends_count) * 100, 1) : 0;
        @endphp

        <div class="newsletter-widget-campaign">
            <div class="newsletter-widget-campaign-main">
                <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}" class="newsletter-widget-campaign-title">
                    {{ $campaign->name }}
                </a>
                <p class="newsletter-widget-campaign-meta">
                    {{ $campaign->sent_at?->format('M j') ?? ($campaign->scheduled_at?->format('M j') ?? '—') }}
                    &middot;
                    {{ $campaign->collectionShortLabel() }}
                </p>
            </div>
            <div class="newsletter-widget-campaign-side">
                <span class="newsletter-widget-badge {{ match($campaign->status) {
                    'sent'      => 'newsletter-widget-badge-sent',
                    'partial'   => 'newsletter-widget-badge-partial',
                    'sending'   => 'newsletter-widget-badge-sending',
                    'scheduled' => 'newsletter-widget-badge-scheduled',
                    default     => 'newsletter-widget-badge-default',
                } }}">{{ $campaign->status }}</span>

                @if(in_array($campaign->status, ['sent', 'partial']))
                    <span class="newsletter-widget-campaign-stats">
                        {{ number_format($campaign->sends_count) }} sent
                        &middot;
                        <span class="newsletter-widget-text-blue">{{ $openRate }}% open</span>
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="newsletter-widget-empty">
            No campaigns in the last {{ $days }} days.
            <a href="{{ cp_route('newsletter.campaigns.create') }}" class="newsletter-widget-link">Create one &rarr;</a>
        </div>
    @endforelse

    <div class="newsletter-widget-footer">
        <a href="{{ cp_route('newsletter.campaigns.index') }}" class="newsletter-widget-link">All campaigns</a>
        <a href="{{ cp_route('newsletter.subscribers.index') }}" class="newsletter-widget-link">Subscribers</a>
        <a href="{{ cp_route('newsletter.analytics.webhooks') }}" class="newsletter-widget-link">Webhook log</a>
    </div>
</div>
