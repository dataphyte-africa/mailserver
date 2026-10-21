@php
    $workspaceName = $organisations->count() === 1 ? $organisations->first()->name : ($products->count() === 1 ? $products->first()->name : 'Your workspace');
    $sent = (int) ($totals->sent ?? 0);
    $delivered = (int) ($totals->delivered ?? 0);
    $opened = (int) ($totals->opened ?? 0);
    $deliveryRate = $sent > 0 ? round($delivered / $sent * 100, 1) : 0;
    $openRate = $delivered > 0 ? round($opened / $delivered * 100, 1) : 0;
@endphp
<div class="org-dashboard">
    <header class="org-dashboard-hero">
        <div class="org-dashboard-hero-copy">
            <span class="org-dashboard-eyebrow"><span class="org-dashboard-live-dot"></span> Organisation workspace</span>
            <h2>{{ $workspaceName }}</h2>
            <p>Everything you need to run your newsletters, grow your audience, and manage forms in one place.</p>
            <div class="org-dashboard-hero-actions">
                @if($products->isNotEmpty())
                    <a class="org-dashboard-button org-dashboard-button-primary" href="{{ cp_route('newsletter.entries.create') }}">Create newsletter <span aria-hidden="true">&rarr;</span></a>
                @endif
                <a class="org-dashboard-button org-dashboard-button-outline" href="{{ cp_route('newsletter.entries.index') }}">All newsletters</a>
                @if($organisations->isNotEmpty())
                    <a class="org-dashboard-button org-dashboard-button-outline" href="{{ cp_route('organisation-profiles.index') }}">Organisation profile</a>
                @endif
            </div>
        </div>
        <div class="org-dashboard-hero-aside">
            <span class="org-dashboard-aside-label">Your active scope</span>
            <strong>{{ $products->count() }} <small>product{{ $products->count() === 1 ? '' : 's' }}</small></strong>
            <span>{{ $organisations->count() }} organisation{{ $organisations->count() === 1 ? '' : 's' }} assigned</span>
        </div>
    </header>

    @if($products->isEmpty())
        <div class="org-dashboard-empty org-dashboard-empty-access">
            <h3>No active products assigned</h3>
            <p>Your account can access the Control Panel, but an administrator has not assigned an active organisation or product yet. Ask a super admin to review Operator Access.</p>
        </div>
    @else
        <section class="org-dashboard-section" aria-labelledby="org-dashboard-pulse">
            <div class="org-dashboard-section-heading"><div><span class="org-dashboard-kicker">Last {{ $days }} days</span><h3 id="org-dashboard-pulse">At a glance</h3></div><a href="{{ cp_route('newsletter.analytics.index') }}">Explore analytics &rarr;</a></div>
            <div class="org-dashboard-metrics">
                <div class="org-dashboard-metric"><span>Active subscribers</span><strong>{{ number_format($subscriberStats['active']) }}</strong><small>Across your assigned products</small></div>
                <div class="org-dashboard-metric"><span>Emails sent</span><strong>{{ number_format($sent) }}</strong><small>In the last {{ $days }} days</small></div>
                <div class="org-dashboard-metric"><span>Delivery rate</span><strong>{{ $deliveryRate }}%</strong><small>{{ number_format($delivered) }} delivered</small></div>
                <div class="org-dashboard-metric"><span>Open rate</span><strong>{{ $openRate }}%</strong><small>{{ number_format($opened) }} opened</small></div>
            </div>
        </section>

        @if($organisationGrowth->isNotEmpty())
            <section class="org-dashboard-section" aria-labelledby="org-dashboard-growth">
                <div class="org-dashboard-section-heading"><div><span class="org-dashboard-kicker">Audience growth</span><h3 id="org-dashboard-growth">Organisation subscribers</h3></div><a href="{{ cp_route('newsletter.groups.index') }}">View audiences &rarr;</a></div>
                @foreach($organisations as $organisation)
                    @php($growth = $organisationGrowth->get($organisation->getKey()))
                    <h4>{{ $organisation->name }}</h4>
                    <div class="org-dashboard-metrics">
                        <div class="org-dashboard-metric"><span>Joined this month</span><strong>{{ number_format(collect($growth['months'])->last()['new']) }}</strong><small>Unique people joining an audience</small></div>
                        <div class="org-dashboard-metric"><span>Total joined</span><strong>{{ number_format($growth['joined']) }}</strong><small>Unique people, all time</small></div>
                        <div class="org-dashboard-metric"><span>Current subscribers</span><strong>{{ number_format($growth['current']) }}</strong><small>After unsubscribes and suppression</small></div>
                    </div>
                    <div style="overflow-x:auto;margin:12px 0 24px;border:1px solid #d8e2f0;border-radius:12px;background:#fff">
                        <table style="width:100%;border-collapse:collapse;min-width:420px">
                            <thead><tr style="background:#eef3fa"><th style="padding:10px 14px;text-align:left">Month</th><th style="padding:10px 14px;text-align:right">New subscribers</th><th style="padding:10px 14px;text-align:right">Total joined</th></tr></thead>
                            <tbody>
                            @foreach($growth['months'] as $month)
                                <tr><td style="padding:8px 14px;border-top:1px solid #e5ecf5">{{ $month['label'] }}</td><td style="padding:8px 14px;border-top:1px solid #e5ecf5;text-align:right">{{ number_format($month['new']) }}</td><td style="padding:8px 14px;border-top:1px solid #e5ecf5;text-align:right">{{ number_format($month['total_joined']) }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </section>
        @endif

        <div class="org-dashboard-grid">
            <section class="org-dashboard-panel" aria-labelledby="org-dashboard-attention">
                <div class="org-dashboard-panel-heading"><div><span class="org-dashboard-kicker">Your next moves</span><h3 id="org-dashboard-attention">Needs attention</h3></div></div>
                <a class="org-dashboard-task" href="{{ cp_route('newsletter.groups.index') }}"><span class="org-dashboard-task-icon">01</span><span><strong>Audience growth</strong><small>Review new and current subscribers by audience</small></span><span aria-hidden="true">&rarr;</span></a>
                <a class="org-dashboard-task" href="{{ cp_route('newsletter.campaigns.index', ['status' => 'draft']) }}"><span class="org-dashboard-task-icon">02</span><span><strong>{{ number_format($draftCampaigns) }} draft campaigns</strong><small>Review and prepare your next send</small></span><span aria-hidden="true">&rarr;</span></a>
                <a class="org-dashboard-task" href="{{ cp_route('subscription-forms.index') }}"><span class="org-dashboard-task-icon">03</span><span><strong>{{ number_format($formsCount) }} subscription forms</strong><small>Manage signup pages and automated messages</small></span><span aria-hidden="true">&rarr;</span></a>
            </section>
            <section class="org-dashboard-panel" aria-labelledby="org-dashboard-products">
                <div class="org-dashboard-panel-heading"><div><span class="org-dashboard-kicker">Your portfolio</span><h3 id="org-dashboard-products">Products</h3></div></div>
                <div class="org-dashboard-product-list">
                    @foreach($products as $product)
                        <div class="org-dashboard-product"><span class="org-dashboard-product-mark">{{ strtoupper(mb_substr($product->name, 0, 1)) }}</span><span><strong>{{ $product->name }}</strong><small>{{ $product->organisation?->name }}</small></span><span class="org-dashboard-product-type">{{ ucfirst($product->product_type ?: 'product') }}</span></div>
                    @endforeach
                </div>
                @if($organisations->isNotEmpty())
                    <a class="org-dashboard-panel-link" href="{{ cp_route('organisation-domains.index') }}">Organisation domains &rarr;</a>
                @endif
            </section>
        </div>

        <section class="org-dashboard-panel org-dashboard-recent" aria-labelledby="org-dashboard-recent">
            <div class="org-dashboard-panel-heading"><div><span class="org-dashboard-kicker">Campaign activity</span><h3 id="org-dashboard-recent">Recent campaigns</h3></div><a href="{{ cp_route('newsletter.campaigns.index') }}">View all &rarr;</a></div>
            @forelse($recentCampaigns as $campaign)
                <a class="org-dashboard-campaign" href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"><span><strong>{{ $campaign->name }}</strong><small>{{ $campaign->collectionShortLabel() }} &middot; {{ $campaign->sent_at?->format('M j, Y') ?? ($campaign->scheduled_at?->format('M j, Y') ?? 'In progress') }}</small></span><span class="org-dashboard-status">{{ ucfirst($campaign->status) }}</span><span aria-hidden="true">&rarr;</span></a>
            @empty
                <div class="org-dashboard-empty"><p>No sent or scheduled campaigns yet. Your next campaign will appear here.</p></div>
            @endforelse
        </section>
    @endif
</div>
