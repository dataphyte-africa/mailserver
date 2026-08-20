@extends('statamic::layout')
@section('title', 'Campaigns')

@section('content')
<div class="campaign-index-header">
    <h1 class="campaign-index-title">Campaigns</h1>
    <a href="{{ cp_route('newsletter.campaigns.create') }}"
       class="campaign-index-button-primary">New Campaign</a>
</div>

{{-- Filters --}}
<form method="GET" class="campaign-index-filters">
    <select name="collection" onchange="this.form.submit()"
            class="input-text campaign-index-filter-control">
        <option value="">All Collections</option>
        @foreach($collections as $value => $label)
            <option value="{{ $value }}" {{ request('collection') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    <select name="status" onchange="this.form.submit()"
            class="input-text campaign-index-filter-control">
        <option value="">All Statuses</option>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if(request()->hasAny(['collection','status']))
        <a href="{{ cp_route('newsletter.campaigns.index') }}"
           class="campaign-index-button">Clear</a>
    @endif
</form>

@if(session('success'))
    <div class="campaign-index-notice">
        {{ session('success') }}
    </div>
@endif

{{-- Table --}}
<div class="campaign-index-table-card">
    <div class="campaign-index-table-scroll">
    <table class="data-table campaign-index-table">
        <thead>
            <tr>
                <th class="campaign-index-sticky-col">Name</th>
                <th>Collection</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Recipients</th>
                <th>Scheduled / Sent</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns as $campaign)
            <tr>
                <td class="campaign-index-sticky-col campaign-index-name-cell">
                    <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
                       class="campaign-index-link">
                        {{ $campaign->name }}
                    </a>
                </td>
                <td class="campaign-index-muted">
                    {{ $campaign->collectionShortLabel() }}
                </td>
                <td class="campaign-index-subject">{{ $campaign->subject }}</td>
                <td>
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
                    <span class="badge {{ $badge }}">{{ ucfirst($campaign->status) }}</span>
                </td>
                <td class="campaign-index-muted">
                    {{ number_format($campaign->total_recipients ?? 0) }}
                </td>
                <td class="campaign-index-muted campaign-index-date-cell">
                    @if($campaign->scheduled_at && $campaign->status === 'scheduled')
                        {{ $campaign->scheduled_at->format('M j, Y g:i A') }}
                    @elseif($campaign->sent_at)
                        {{ $campaign->sent_at->format('M j, Y') }}
                    @else
                        &mdash;
                    @endif
                </td>
                <td class="campaign-index-actions-cell">
                    @if(in_array($campaign->status, ['draft','scheduled']))
                        <a href="{{ cp_route('newsletter.campaigns.edit', $campaign) }}"
                           class="campaign-index-link campaign-index-action-link">Edit</a>
                    @endif
                    <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
                       class="campaign-index-link campaign-index-action-link">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="campaign-index-empty">
                    No campaigns yet.
                    <a href="{{ cp_route('newsletter.campaigns.create') }}" class="campaign-index-link">
                        Create your first campaign.
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Pagination --}}
@if($campaigns->hasPages())
    <div class="campaign-index-pagination">{{ $campaigns->links() }}</div>
@endif

@endsection
