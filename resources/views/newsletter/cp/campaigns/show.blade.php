@extends('statamic::layout')
@section('title', $campaign->name)

@section('content')
@php
    $sortLink = function (string $column) use ($sort, $direction) {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => $nextDirection,
            'page' => 1,
        ]);
    };

    $sortIndicator = function (string $column) use ($sort, $direction) {
        if ($sort !== $column) {
            return null;
        }

        return $direction === 'asc' ? '↑' : '↓';
    };

    $exportUrl = cp_route('newsletter.campaigns.export-sends', $campaign) . '?' . http_build_query([
        'sort' => $sort,
        'direction' => $direction,
    ]);
@endphp

<style>
    .campaign-show-page {
        max-width: 100%;
        overflow-x: hidden;
    }

    .campaign-show-shell {
        display: block;
        max-width: 100%;
        overflow-x: hidden;
    }

    .campaign-show-main,
    .campaign-show-sidebar {
        min-width: 0;
    }

    .campaign-show-stats {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1px;
        background: #e5e7eb;
    }

    .campaign-show-stat {
        background: #fff;
    }

    .campaign-show-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    .campaign-show-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .campaign-show-table .campaign-show-sticky-col {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #fff;
        min-width: 320px;
        width: 320px;
        box-shadow: 1px 0 0 #e5e7eb;
    }

    .campaign-show-table thead .campaign-show-sticky-col {
        z-index: 4;
        background: #f9fafb;
    }

    .campaign-show-table tbody .campaign-show-sticky-col {
        vertical-align: top;
    }

    .campaign-show-sidebar {
        width: 100%;
    }

    @media (min-width: 900px) {
        .campaign-show-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1280px) {
        .campaign-show-shell {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .campaign-show-main {
            flex: 1 1 auto;
            min-width: 0;
            max-width: calc(100% - 344px);
            overflow-x: hidden;
        }

        .campaign-show-sidebar {
            width: 320px;
            flex: 0 0 320px;
            position: sticky;
            top: 1.5rem;
            align-self: flex-start;
        }

        .campaign-show-stats {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
</style>

{{-- Header --}}
<div class="campaign-show-page">
<div class="flex flex-col gap-4 mb-6 xl:flex-row xl:items-start xl:justify-between">
    <div>
        <div class="mb-1">
            <a href="{{ cp_route('newsletter.campaigns.index') }}"
               class="text-sm text-grey-60 hover:text-grey-80">&larr; All Campaigns</a>
        </div>
        <h1 class="text-3xl font-bold">{{ $campaign->name }}</h1>
        <p class="text-grey-60 text-sm mt-1">
            {{ $campaign->collectionLabel() }}
            &nbsp;&middot;&nbsp;
            Created {{ $campaign->created_at->format('M j, Y') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3 xl:justify-end">
        @php
            $badge = match($campaign->status) {
                'draft'     => 'bg-grey-30 text-grey-80',
                'scheduled' => 'bg-yellow-lighter text-yellow-dark',
                'sending'   => 'bg-blue-lighter text-blue-dark',
                'sent'      => 'bg-green-lighter text-green-dark',
                'partial'   => 'bg-orange-lighter text-orange-dark',
                'failed'    => 'bg-red-lighter text-red-dark',
                default     => 'bg-grey-30 text-grey-80',
            };
        @endphp
        <span class="badge {{ $badge }} text-sm px-3 py-1">{{ ucfirst($campaign->status) }}</span>

        @if(in_array($campaign->status, ['draft','scheduled']))
            <a href="{{ cp_route('newsletter.campaigns.edit', $campaign) }}"
               class="btn">Edit</a>
        @endif

        {{-- Preview in browser --}}
        <a href="{{ cp_route('newsletter.campaigns.preview', $campaign) }}"
           target="_blank"
           class="btn">
            Preview &rarr;
        </a>

        {{-- Test send --}}
        <div class="relative">
            <button onclick="document.getElementById('test-send-panel').classList.toggle('hidden')"
                    class="btn btn-primary" type="button">
                Send Test Email &hellip;
            </button>
            <div id="test-send-panel"
                 class="hidden absolute right-0 top-full mt-1 w-80 bg-white border border-grey-20 rounded shadow-lg z-50 p-4">
                <p class="text-sm font-medium mb-1">Send test email to:</p>
                <p class="text-xs text-grey-50 mb-3">
                    Merge tags (<code>@{{first_name}}</code>, <code>@{{full_name}}</code>, <code>@{{email}}</code>) are replaced with real subscriber data if the address matches a subscriber, otherwise blank.
                </p>
                <form method="POST"
                      action="{{ cp_route('newsletter.campaigns.test-send', $campaign) }}">
                    @csrf
                    <input type="email" name="email"
                           placeholder="recipient@example.com"
                           class="input-text w-full text-sm mb-2"
                           required>
                    <button type="submit" class="btn-primary w-full text-sm">
                        Send Test
                    </button>
                </form>
            </div>
        </div>

        @if(in_array($campaign->status, ['draft','scheduled']))
            <form method="POST" action="{{ cp_route('newsletter.campaigns.send', $campaign) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Send this campaign now to all selected audiences?')"
                        class="btn-primary bg-red border-red hover:bg-red-dark">
                    Send Now
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-300 text-green-800 rounded p-3 mb-6 text-sm">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-6 text-sm">
        {{ session('error') }}
    </div>
@endif

<div class="campaign-show-shell space-y-6 xl:space-y-0">

    {{-- Left: stats + sends --}}
    <div class="campaign-show-main space-y-6">

        {{-- Stats cards --}}
        <div class="card overflow-hidden">
            <div class="border-b border-grey-20 px-5 py-4">
                <h2 class="font-semibold text-base">Performance Snapshot</h2>
                <p class="text-sm text-grey-60 mt-1">Core campaign delivery and engagement totals.</p>
            </div>
            <div class="campaign-show-stats">
                @php
                    $totalRecipients = $stats['total_recipients'] ?? 0;
                    $sentCount = $stats['total_sent'] ?? 0;
                    $deliveredCount = $stats['total_delivered'] ?? 0;
                    $statCards = [
                        [
                            'label' => 'Sent',
                            'value' => $sentCount,
                            'color' => 'text-grey-80',
                            'percentage' => $totalRecipients > 0 ? round(($sentCount / $totalRecipients) * 100, 1) : null,
                        ],
                        [
                            'label' => 'Delivered',
                            'value' => $deliveredCount,
                            'color' => 'text-green-dark',
                            'percentage' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100, 1) : null,
                        ],
                        [
                            'label' => 'Opened',
                            'value' => $stats['total_opened'] ?? 0,
                            'color' => 'text-blue-dark',
                            'percentage' => $deliveredCount > 0 ? round((($stats['total_opened'] ?? 0) / $deliveredCount) * 100, 1) : null,
                        ],
                        [
                            'label' => 'Failed',
                            'value' => $stats['total_failed'] ?? 0,
                            'color' => 'text-red-dark',
                            'percentage' => $totalRecipients > 0 ? round((($stats['total_failed'] ?? 0) / $totalRecipients) * 100, 1) : null,
                        ],
                    ];
                @endphp
                @foreach($statCards as $card)
                <div class="campaign-show-stat px-5 py-5">
                    <p class="text-xs uppercase tracking-wide text-grey-50">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $card['color'] }}">
                        {{ number_format($card['value']) }}
                    </p>
                    @if($card['percentage'] !== null)
                    <p class="mt-1 text-sm text-grey-60">{{ $card['percentage'] }}%</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Sends --}}
        @if($sends->isNotEmpty())
        <div class="card p-0 overflow-hidden border border-grey-20 shadow">
            <div class="border-b border-grey-20 px-5 py-4 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="font-semibold text-base">Sends</h2>
                    <p class="text-sm text-grey-60 mt-1">Scroll horizontally inside this table for delivery detail columns.</p>
                </div>
                <div class="flex flex-col items-start gap-2 text-xs text-grey-50 lg:items-end">
                    <div>
                        {{ number_format($sends->total()) }} total
                        &nbsp;&middot;&nbsp;
                        page {{ $sends->currentPage() }} of {{ $sends->lastPage() }}
                    </div>
                    <a href="{{ $exportUrl }}" class="btn btn-sm">
                        Export CSV
                    </a>
                </div>
            </div>
            <div class="campaign-show-table-wrap px-0">
                <table class="data-table campaign-show-table w-full" style="min-width: 1500px;">
                    <thead>
                        <tr>
                            <th class="campaign-show-sticky-col whitespace-nowrap">Subscriber</th>
                            <th class="whitespace-nowrap">
                                <a href="{{ $sortLink('status') }}" class="inline-flex items-center gap-1 hover:text-blue">
                                    Status
                                    @if($sortIndicator('status'))
                                        <span class="text-xs">{{ $sortIndicator('status') }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="whitespace-nowrap">
                                <a href="{{ $sortLink('sent_at') }}" class="inline-flex items-center gap-1 hover:text-blue">
                                    Sent
                                    @if($sortIndicator('sent_at'))
                                        <span class="text-xs">{{ $sortIndicator('sent_at') }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="whitespace-nowrap">
                                <a href="{{ $sortLink('opened_at') }}" class="inline-flex items-center gap-1 hover:text-blue">
                                    Opened
                                    @if($sortIndicator('opened_at'))
                                        <span class="text-xs">{{ $sortIndicator('opened_at') }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="whitespace-nowrap">
                                <a href="{{ $sortLink('clicked_at') }}" class="inline-flex items-center gap-1 hover:text-blue">
                                    Clicked
                                    @if($sortIndicator('clicked_at'))
                                        <span class="text-xs">{{ $sortIndicator('clicked_at') }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="whitespace-nowrap">
                                <a href="{{ $sortLink('synced_at') }}" class="inline-flex items-center gap-1 hover:text-blue">
                                    Synced
                                    @if($sortIndicator('synced_at'))
                                        <span class="text-xs">{{ $sortIndicator('synced_at') }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="whitespace-nowrap">
                                <a href="{{ $sortLink('elastic_email_transaction_id') }}" class="inline-flex items-center gap-1 hover:text-blue">
                                    Transaction ID
                                    @if($sortIndicator('elastic_email_transaction_id'))
                                        <span class="text-xs">{{ $sortIndicator('elastic_email_transaction_id') }}</span>
                                    @endif
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sends as $send)
                        <tr>
                            <td class="campaign-show-sticky-col text-sm align-top">
                                @if($send->subscriber)
                                    @php
                                        $subscriberName = trim((string) $send->subscriber->full_name);
                                        $subscriberEmail = (string) $send->subscriber->email;
                                        $showName = $subscriberName !== '' && strcasecmp($subscriberName, $subscriberEmail) !== 0;
                                    @endphp
                                    <a href="{{ cp_route('newsletter.subscribers.show', $send->subscriber) }}"
                                       class="block text-blue hover:underline">
                                        {{ $showName ? $subscriberName : $subscriberEmail }}
                                    </a>
                                    @if($showName)
                                        <span class="mt-1 block text-grey-50 text-xs">{{ $subscriberEmail }}</span>
                                    @endif
                                @else
                                    <span class="text-grey-50">(deleted)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-xs {{ match($send->status) {
                                    'sent','delivered' => 'bg-green-lighter text-green-dark',
                                    'opened','clicked' => 'bg-blue-lighter text-blue-dark',
                                    'failed','bounced' => 'bg-red-lighter text-red-dark',
                                    default            => 'bg-grey-30 text-grey-80',
                                } }}">{{ $send->status }}</span>
                            </td>
                            <td class="text-sm text-grey-60 whitespace-nowrap">{{ $send->sent_at?->format('M j H:i') ?? '—' }}</td>
                            <td class="text-sm text-grey-60 whitespace-nowrap">
                                @if($send->opened_at)
                                    {{ $send->opened_at->format('M j H:i') }}
                                @elseif(in_array($send->status, ['opened', 'clicked']) && $send->synced_at)
                                    Unavailable
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-sm text-grey-60 whitespace-nowrap">
                                @if($send->clicked_at)
                                    {{ $send->clicked_at->format('M j H:i') }}
                                @elseif($send->status === 'clicked' && $send->synced_at)
                                    Unavailable
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-sm text-grey-60 whitespace-nowrap">{{ $send->synced_at?->format('M j H:i') ?? '—' }}</td>
                            <td class="text-xs text-grey-50 font-mono whitespace-nowrap">
                                <span class="inline-block max-w-xs truncate align-bottom" title="{{ $send->elastic_email_transaction_id ?? '—' }}">
                                    {{ $send->elastic_email_transaction_id ?? '—' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sends->hasPages())
            <div class="px-5 py-4 border-t border-grey-20 flex items-center justify-between">
                <span class="text-xs text-grey-50">
                    Showing {{ $sends->firstItem() }}–{{ $sends->lastItem() }} of {{ number_format($sends->total()) }}
                </span>
                <div class="flex items-center gap-1">
                    @if($sends->onFirstPage())
                        <span class="btn btn-sm opacity-40 cursor-not-allowed">&laquo; Prev</span>
                    @else
                        <a href="{{ $sends->previousPageUrl() }}" class="btn btn-sm">&laquo; Prev</a>
                    @endif

                    @foreach($sends->getUrlRange(max(1, $sends->currentPage()-2), min($sends->lastPage(), $sends->currentPage()+2)) as $page => $url)
                        @if($page === $sends->currentPage())
                            <span class="btn btn-sm btn-primary">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($sends->hasMorePages())
                        <a href="{{ $sends->nextPageUrl() }}" class="btn btn-sm">Next &raquo;</a>
                    @else
                        <span class="btn btn-sm opacity-40 cursor-not-allowed">Next &raquo;</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="campaign-show-sidebar space-y-6">

        {{-- Campaign info --}}
        <div class="card p-6 text-sm space-y-4 border border-grey-20 shadow">
            <div class="pb-3 border-b border-grey-20">
                <h2 class="font-semibold text-base">Details</h2>
                <p class="text-xs text-grey-50 mt-1">Sending configuration and linked editorial source.</p>
            </div>

            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Subject</p>
                <p class="font-medium">{{ $campaign->subject }}</p>
            </div>

            @if($entry)
            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Content Entry</p>
                <a href="{{ cp_route('collections.entries.edit', [$campaign->collection, $campaign->entry_id]) }}"
                   target="_blank"
                   class="text-blue hover:underline">
                    {{ $entry->get('title') ?: $entry->get('subject') ?: 'View Entry' }} &rarr;
                </a>
            </div>
            @endif

            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">From</p>
                @php $sender = $campaign->sender(); @endphp
                <p>{{ $sender['from_name'] }}</p>
                <p class="text-grey-60">{{ $sender['from_email'] }}</p>
            </div>

            @if($campaign->scheduled_at)
            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Scheduled</p>
                <p>{{ $campaign->scheduled_at->format('M j, Y g:i A') }}</p>
            </div>
            @endif

            @if($campaign->sent_at)
            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Sent</p>
                <p>{{ $campaign->sent_at->format('M j, Y g:i A') }}</p>
            </div>
            @endif

            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Total Recipients</p>
                <p>{{ number_format($campaign->total_recipients ?? 0) }}</p>
            </div>
        </div>

        {{-- Audiences --}}
        <div class="card p-6 text-sm border border-grey-20 shadow">
            <h2 class="font-semibold text-base mb-3">Audiences</h2>
            @forelse($campaign->audiences as $audience)
                @if($audience->targetable)
                <div class="flex items-center gap-2 py-1">
                    <span class="w-2 h-2 rounded-full bg-blue inline-block"></span>
                    <span>{{ $audience->targetable->name }}</span>
                    @if($audience->send_to_all)
                        <span class="text-xs text-grey-50">(all)</span>
                    @endif
                </div>
                @endif
            @empty
                <p class="text-grey-50">No audiences assigned.</p>
            @endforelse
        </div>

        {{-- Reset stuck campaign --}}
        @if(in_array($campaign->status, ['sending','failed']))
        <div class="card p-6 border-yellow-400">
            <h2 class="text-sm font-semibold text-yellow-dark mb-2">Stuck?</h2>
            <p class="text-xs text-grey-60 mb-3">If this campaign is stuck in "{{ $campaign->status }}" (e.g. queue worker not running), reset it to draft and send again.</p>
            <form method="POST" action="{{ cp_route('newsletter.campaigns.reset', $campaign) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Reset this campaign back to draft?')"
                        class="btn btn-sm w-full text-yellow-dark border-yellow-400">
                    Reset to Draft
                </button>
            </form>
        </div>
        @endif

        @if(($stats['total_failed'] ?? 0) > 0)
        <div class="card p-6 border-blue-300">
            <h2 class="text-sm font-semibold text-blue mb-2">Retry Failed Sends</h2>
            <p class="text-xs text-grey-60 mb-3">
                Requeue retryable failed sends for this campaign and resume processing from the control panel.
            </p>
            <form method="POST" action="{{ cp_route('newsletter.campaigns.retry-failed', $campaign) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Requeue retryable failed sends for this campaign and resume delivery?')"
                        class="btn btn-sm w-full text-blue border-blue-300 hover:bg-blue-50">
                    Requeue Failed Sends
                </button>
            </form>
        </div>
        @endif

        {{-- Danger zone --}}
        @if(in_array($campaign->status, ['draft','scheduled']))
        <div class="card p-6 border-red-300">
            <h2 class="text-sm font-semibold text-red mb-3">Danger Zone</h2>
            <form method="POST" action="{{ cp_route('newsletter.campaigns.destroy', $campaign) }}">
                @csrf @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Permanently delete this campaign?')"
                        class="btn btn-sm w-full text-red border-red-300 hover:bg-red-50">
                    Delete Campaign
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

<script>
// Close test-send panel when clicking outside it
document.addEventListener('click', function (e) {
    var panel  = document.getElementById('test-send-panel');
    var toggle = e.target.closest('[onclick*="test-send-panel"]');
    if (panel && !panel.contains(e.target) && !toggle) {
        panel.classList.add('hidden');
    }
});
</script>

</div>
@endsection
