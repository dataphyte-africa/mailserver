@extends('statamic::layout')
@section('title', $subscriber->email)

@section('content')
    @php
        $lastEngagedAt = filled($stats['last_engaged_at'] ?? null)
            ? \Illuminate\Support\Carbon::parse($stats['last_engaged_at'])
            : null;
    @endphp

    <style>
        .subscriber-show-stat {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #fff;
            overflow: hidden;
        }

        .subscriber-show-block {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #fff;
            overflow: hidden;
        }

        .subscriber-show-list > div,
        .subscriber-show-subgroups > li {
            border-top: 1px solid #e5e7eb;
        }

        .subscriber-show-list > div:first-child,
        .subscriber-show-subgroups > li:first-child {
            border-top: 0;
        }

        .subscriber-show-list > div {
            display: grid;
            grid-template-columns: minmax(0, 220px) minmax(0, 1fr);
            align-items: center;
            min-height: 58px;
        }

        .subscriber-show-list > div dt {
            border-right: 1px solid #e5e7eb;
            padding: 1rem 1.25rem;
            background: #fafbfd;
        }

        .subscriber-show-list > div dd {
            padding: 1rem 1.5rem 1rem 1.25rem;
            text-align: right;
        }

        .subscriber-show-subgroups > li {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 220px);
            align-items: center;
            min-height: 58px;
        }

        .subscriber-show-subgroups > li span:first-child {
            padding: 1rem 1.25rem;
            background: #fafbfd;
            border-right: 1px solid #e5e7eb;
        }

        .subscriber-show-subgroups > li span:last-child {
            padding: 1rem 1.5rem 1rem 1.25rem;
            text-align: right;
        }

        .subscriber-show-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            padding: 0.9rem 1.25rem;
        }

        .subscriber-show-table thead th + th,
        .subscriber-show-table tbody td + td {
            border-left: 1px solid #eef2f7;
        }

        .subscriber-show-table tbody tr {
            border-bottom: 1px solid #eef2f7;
        }

        .subscriber-show-table tbody tr:last-child {
            border-bottom: 0;
        }

        .subscriber-show-table td,
        .subscriber-show-table th {
            padding-right: 1rem;
        }

        .subscriber-show-table tbody td {
            padding: 1rem 1.25rem;
        }

        .subscriber-show-section-title {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .subscriber-show-section-body {
            padding: 0;
        }

        .subscriber-show-table-wrap {
            padding: 0 1.25rem 1.25rem;
        }

        .subscriber-show-meta {
            padding: 1rem 1.25rem 0.75rem;
        }
    </style>

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ cp_route('newsletter.subscribers.index') }}"
               class="text-sm text-gray-500 hover:underline mb-1 block">← Subscribers</a>
            <h1 class="text-3xl font-bold">{{ $subscriber->full_name }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <p class="text-gray-500">{{ $subscriber->email }}</p>
                @if($subscriber->engagement_rating)
                    @php
                        $ratingClasses = match ($subscriber->engagement_rating) {
                            'high' => 'bg-green-100 text-green-700',
                            'moderate' => 'bg-blue-100 text-blue-700',
                            'low' => 'bg-gray-100 text-gray-600',
                            'inactive' => 'bg-yellow-100 text-yellow-700',
                            'suppressed' => 'bg-red-100 text-red-600',
                            default => 'bg-gray-50 text-gray-400',
                        };
                    @endphp
                    <span class="badge-sm {{ $ratingClasses }}">
                        {{ ucfirst($subscriber->engagement_rating) }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ cp_route('newsletter.subscribers.edit', $subscriber) }}"
               class="btn-primary">Edit</a>
            <a href="{{ cp_route('newsletter.subscribers.gdpr.export', $subscriber) }}"
               class="btn" title="Download all personal data (GDPR export)">
                Export Data
            </a>
            @if($subscriber->status !== 'erased')
            <a href="{{ cp_route('newsletter.subscribers.gdpr.erase-form', $subscriber) }}"
               class="btn text-red border-red-300 hover:bg-red-50"
               title="Right to erasure (GDPR Art. 17)">
                Erase
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
        {{-- Stats --}}
        @foreach([
            ['label' => 'Total Sent',      'value' => $stats['total_sent']      ?? 0],
            ['label' => 'Delivered',        'value' => $stats['total_delivered'] ?? 0],
            ['label' => 'Opened',           'value' => $stats['total_opened']    ?? 0],
            ['label' => 'Clicked',          'value' => $stats['total_clicked']   ?? 0],
            ['label' => 'Links Clicked',    'value' => $stats['total_link_clicks'] ?? 0],
            ['label' => 'Failed / Bounced', 'value' => $stats['total_failed']    ?? 0],
        ] as $stat)
            <div class="subscriber-show-stat p-4 text-center shadow-sm">
                <div class="text-2xl font-bold text-blue">{{ $stat['value'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Subscriber info --}}
        <div class="space-y-6">
        <div class="subscriber-show-block shadow-sm">
            <div class="subscriber-show-section-title">
                <h2 class="font-semibold text-gray-700">Details</h2>
            </div>
            <div class="subscriber-show-section-body">
            <dl class="subscriber-show-list text-sm">
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <span class="font-medium {{ $subscriber->status === 'active' ? 'text-green-600' : 'text-red-500' }}">
                            {{ ucfirst($subscriber->status) }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Rating</dt>
                    <dd>
                        @if($subscriber->engagement_rating)
                            <span class="font-medium capitalize">{{ $subscriber->engagement_rating }}</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Engagement score</dt>
                    <dd>{{ $subscriber->engagement_score ?? '—' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Added</dt>
                    <dd>{{ $subscriber->created_at->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Campaigns</dt>
                    <dd>{{ $stats['total_sent'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Last engaged</dt>
                    <dd>{{ $lastEngagedAt?->format('d M Y H:i') ?? '—' }}</dd>
                </div>
                @if($subscriber->unsubscribed_at)
                    <div class="flex justify-between py-3">
                        <dt class="text-gray-500">Unsubscribed</dt>
                        <dd>{{ $subscriber->unsubscribed_at->format('d M Y') }}</dd>
                    </div>
                @endif
            </dl>

            <div class="subscriber-show-section-title border-t">
                <h2 class="font-semibold text-gray-700">Sub-groups</h2>
            </div>
            <ul class="subscriber-show-subgroups text-sm">
                @forelse($subscriber->subGroups as $sg)
                    <li class="py-3">
                        <span>{{ $sg->name }}</span>
                        <span class="text-gray-400 text-xs">{{ $sg->group->name }}</span>
                    </li>
                @empty
                    <li class="text-gray-400 py-3">None</li>
                @endforelse
            </ul>
            </div>
        </div>
        <div class="subscriber-show-block shadow-sm">
            <div class="subscriber-show-section-title">
                <h2 class="font-semibold text-gray-700">Engagement Snapshot</h2>
            </div>
            <div class="subscriber-show-section-body">
            <dl class="subscriber-show-list text-sm">
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Delivered</dt>
                    <dd>{{ $stats['total_delivered'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Opened</dt>
                    <dd>{{ $stats['total_opened'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Clicked</dt>
                    <dd>{{ $stats['total_clicked'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Links clicked</dt>
                    <dd>{{ $stats['total_link_clicks'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-gray-500">Failed / bounced</dt>
                    <dd>{{ $stats['total_failed'] ?? 0 }}</dd>
                </div>
            </dl>
            </div>
        </div>
        </div>

        {{-- Send history --}}
        <div class="space-y-6 xl:col-span-2">
        <div class="subscriber-show-block shadow-sm">
            <div class="subscriber-show-section-title">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700">Campaign History</h2>
                    <p class="text-xs text-gray-400">20 rows per page</p>
                </div>
            </div>
            @if($sendHistory->count())
                <div class="subscriber-show-table-wrap overflow-x-auto">
                <table class="w-full text-sm min-w-[760px] subscriber-show-table">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Sent</th>
                            <th>Opened</th>
                            <th>Clicked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sendHistory as $send)
                            <tr>
                                <td>{{ $send->campaign->name }}</td>
                                <td>
                                    <span class="capitalize text-xs font-medium
                                        {{ in_array($send->status, ['opened', 'clicked']) ? 'text-green-600' : '' }}
                                        {{ in_array($send->status, ['bounced','failed']) ? 'text-red-500' : '' }}">
                                        {{ $send->status }}
                                    </span>
                                </td>
                                <td class="text-gray-400">
                                    {{ $send->sent_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                                <td class="text-gray-400">
                                    {{ $send->opened_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                                <td class="text-gray-400">
                                    {{ $send->clicked_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="subscriber-show-meta">{{ $sendHistory->links() }}</div>
            @else
                <p class="subscriber-show-meta text-gray-400 text-sm">No campaigns sent yet.</p>
            @endif
        </div>
        <div class="subscriber-show-block shadow-sm">
            <div class="subscriber-show-section-title">
                <h2 class="font-semibold text-gray-700">Recent Clicked Links</h2>
            </div>
            @if($recentLinkClicks->count())
                <div class="subscriber-show-table-wrap overflow-x-auto">
                <table class="w-full text-sm min-w-[760px] subscriber-show-table">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th>Campaign</th>
                            <th>Clicked At</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLinkClicks as $click)
                            <tr>
                                <td>{{ $click->campaignSend?->campaign?->name ?? '—' }}</td>
                                <td class="text-gray-400">{{ $click->clicked_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>
                                    <a href="{{ $click->url }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="text-blue hover:underline break-all">
                                        {{ $click->url }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <p class="subscriber-show-meta text-gray-400 text-sm">No clicked links recorded yet.</p>
            @endif
        </div>
        </div>
    </div>
@endsection
