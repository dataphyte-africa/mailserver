@extends('statamic::layout')
@section('title', 'Subscribers')

@section('content')
    @php
        $sortLink = function (string $column) use ($sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return cp_route('newsletter.subscribers.index') . '?' . http_build_query(array_merge(
                request()->except('page'),
                ['sort' => $column, 'direction' => $nextDirection]
            ));
        };

        $sortIndicator = function (string $column) use ($sort, $direction) {
            if ($sort !== $column) {
                return '';
            }

            return $direction === 'asc' ? ' ↑' : ' ↓';
        };
    @endphp

    @push('head')
    <style>
        .subscriber-cp-shell {
            max-width: 100%;
            min-width: 0;
        }

        .subscriber-cp-header {
            align-items: flex-start;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .subscriber-cp-title {
            font-size: 1.875rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .subscriber-cp-actions,
        .subscriber-cp-filter,
        .subscriber-cp-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .subscriber-cp-actions {
            justify-content: flex-end;
        }

        .subscriber-cp-filter {
            align-items: flex-end;
            margin-bottom: 1rem;
        }

        .subscriber-cp-filter-field {
            min-width: 12rem;
        }

        .subscriber-cp-filter-field .input-text {
            min-width: 0;
            width: 100%;
        }

        .subscriber-cp-label {
            color: #334155;
            display: block;
            font-size: .875rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .subscriber-cp-button,
        .subscriber-cp-button-primary {
            align-items: center;
            border-radius: .375rem;
            display: inline-flex;
            font-size: .875rem;
            font-weight: 600;
            justify-content: center;
            line-height: 1.25;
            min-height: 2.25rem;
            padding: .5rem .875rem;
            text-decoration: none;
        }

        .subscriber-cp-button {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #334155;
        }

        .subscriber-cp-button:hover {
            background: #f8fafc;
            color: #0f172a;
            text-decoration: none;
        }

        .subscriber-cp-button-primary {
            background: #111827;
            border: 1px solid #111827;
            color: #fff;
        }

        .subscriber-cp-button-primary:hover {
            background: #374151;
            border-color: #374151;
            color: #fff;
            text-decoration: none;
        }

        .subscriber-cp-notice {
            border: 1px solid;
            border-radius: .5rem;
            font-size: .875rem;
            margin-bottom: 1rem;
            padding: 1rem;
        }

        .subscriber-cp-notice-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #15803d;
        }

        .subscriber-cp-notice-warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .subscriber-cp-notice-title {
            font-weight: 700;
            margin: 0;
        }

        .subscriber-cp-notice-list {
            margin: .5rem 0 0 1.25rem;
        }

        .subscriber-cp-card {
            background: #fff;
            border-radius: .5rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
        }

        .subscriber-table-wrap {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .subscriber-table {
            min-width: 1560px;
            width: max-content;
            table-layout: auto;
        }

        .subscriber-table th,
        .subscriber-table td {
            white-space: nowrap;
        }

        .subscriber-table .subscriber-cp-wrap {
            white-space: normal;
        }

        .subscriber-email-col {
            min-width: 260px;
            width: 260px;
        }

        .subscriber-name-col {
            min-width: 180px;
            width: 180px;
        }

        .subscriber-subgroup-col {
            min-width: 220px;
            width: 220px;
        }

        .subscriber-sticky-col {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #fff;
            box-shadow: 1px 0 0 #e5e7eb;
        }

        .subscriber-sticky-head {
            z-index: 3;
            background: #fff;
        }

        .subscriber-cp-link {
            color: #2563eb;
            display: inline-block;
            font-weight: 600;
            margin-right: .75rem;
            text-decoration: none;
        }

        .subscriber-cp-link:hover {
            text-decoration: underline;
        }

        .subscriber-cp-link-danger {
            background: transparent;
            border: 0;
            color: #dc2626;
            cursor: pointer;
            font-size: .875rem;
            padding: 0;
            text-decoration: none;
        }

        .subscriber-cp-link-danger:hover {
            text-decoration: underline;
        }

        .subscriber-cp-text-muted {
            color: #64748b;
            font-size: .875rem;
        }

        .subscriber-cp-text-strong {
            color: #334155;
            font-size: .875rem;
        }

        .subscriber-cp-text-right {
            text-align: right;
        }

        .subscriber-cp-sort {
            color: #0f172a;
            text-decoration: none;
        }

        .subscriber-cp-sort:hover {
            text-decoration: underline;
        }

        .subscriber-cp-row-actions {
            justify-content: flex-end;
        }

        .subscriber-cp-inline-form {
            display: inline;
        }

        .subscriber-cp-lifecycle-title {
            font-weight: 600;
        }

        .subscriber-cp-lifecycle-warning {
            color: #a16207;
        }

        .subscriber-cp-lifecycle-danger {
            color: #dc2626;
        }

        .subscriber-cp-lifecycle-meta {
            color: #64748b;
            font-size: .75rem;
            margin-top: .25rem;
        }

        .subscriber-cp-empty {
            color: #64748b;
            padding: 2rem;
            text-align: center;
        }

        .subscriber-cp-badge {
            border-radius: 999px;
            display: inline-block;
            font-size: .75rem;
            font-weight: 700;
            line-height: 1;
            padding: .3rem .55rem;
        }

        .subscriber-cp-badge-active {
            background: #dcfce7;
            color: #15803d;
        }

        .subscriber-cp-badge-pending,
        .subscriber-cp-badge-inactive {
            background: #fef3c7;
            color: #a16207;
        }

        .subscriber-cp-badge-danger,
        .subscriber-cp-badge-suppressed {
            background: #fee2e2;
            color: #dc2626;
        }

        .subscriber-cp-badge-muted,
        .subscriber-cp-badge-low {
            background: #e2e8f0;
            color: #475569;
        }

        .subscriber-cp-badge-moderate {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .subscriber-cp-badge-high {
            background: #dcfce7;
            color: #15803d;
        }

        .subscriber-cp-pagination {
            margin-top: 1rem;
        }

        @media (max-width: 760px) {
            .subscriber-cp-header {
                display: block;
            }

            .subscriber-cp-actions {
                justify-content: flex-start;
                margin-top: 1rem;
            }

            .subscriber-cp-filter-field {
                min-width: 100%;
            }
        }
    </style>
    @endpush

    <div class="subscriber-cp-shell">
        <div class="subscriber-cp-header">
            <h1 class="subscriber-cp-title">Subscribers</h1>
            <div class="subscriber-cp-actions">
                <a href="{{ cp_route('newsletter.subscribers.import.form') }}"
                   class="subscriber-cp-button">Import CSV</a>
                <a href="{{ cp_route('newsletter.subscribers.export') . '?' . http_build_query(request()->only('search', 'status', 'sub_group', 'sort', 'direction')) }}"
                   class="subscriber-cp-button">Export CSV</a>
                <a href="{{ cp_route('newsletter.subscribers.create') }}"
                   class="subscriber-cp-button-primary">Add Subscriber</a>
            </div>
        </div>

        @if(session('import_result'))
            @php $result = session('import_result'); @endphp
            <div class="subscriber-cp-notice {{ $result['skipped'] > 0 ? 'subscriber-cp-notice-warning' : 'subscriber-cp-notice-success' }}">
                <p class="subscriber-cp-notice-title">Import complete: {{ $result['imported'] }} imported, {{ $result['skipped'] }} skipped.</p>
                @if(count($result['errors']))
                    <ul class="subscriber-cp-notice-list">
                        @foreach(array_slice($result['errors'], 0, 10) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @if(count($result['errors']) > 10)
                            <li>...and {{ count($result['errors']) - 10 }} more</li>
                        @endif
                    </ul>
                @endif
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="subscriber-cp-filter">
            <div class="subscriber-cp-filter-field">
                <label class="subscriber-cp-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Email or name..."
                       class="input-text">
            </div>
            <div class="subscriber-cp-filter-field">
                <label class="subscriber-cp-label">Status</label>
                <select name="status" class="input-text">
                    <option value="">All statuses</option>
                    @foreach($statuses as $statusValue => $statusLabel)
                        <option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>
                            {{ $statusLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="subscriber-cp-filter-field">
                <label class="subscriber-cp-label">Sub-group</label>
                <select name="sub_group" class="input-text">
                    <option value="">All groups</option>
                    @foreach($subGroups->groupBy('group.name') as $groupName => $subs)
                        <optgroup label="{{ $groupName }}">
                            @foreach($subs as $subGroup)
                                <option value="{{ $subGroup->id }}" @selected(request('sub_group') == $subGroup->id)>
                                    {{ $subGroup->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="subscriber-cp-button-primary">Filter</button>
            @if(request()->hasAny(['search','status','sub_group']))
                <a href="{{ cp_route('newsletter.subscribers.index') }}" class="subscriber-cp-button">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="subscriber-cp-card">
            <div class="subscriber-table-wrap">
                <table class="data-table subscriber-table">
                    <thead>
                        <tr>
                            <th class="subscriber-sticky-col subscriber-sticky-head subscriber-email-col">
                                <a href="{{ $sortLink('email') }}" class="subscriber-cp-sort">
                                    Email{!! $sortIndicator('email') !!}
                                </a>
                            </th>
                    <th class="subscriber-name-col">
                        <a href="{{ $sortLink('name') }}" class="subscriber-cp-sort">
                            Name{!! $sortIndicator('name') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $sortLink('status') }}" class="subscriber-cp-sort">
                            Status{!! $sortIndicator('status') !!}
                        </a>
                    </th>
                    <th class="subscriber-subgroup-col">Sub-groups</th>
                    <th>
                        <a href="{{ $sortLink('engagement_rating') }}" class="subscriber-cp-sort">
                            Rating{!! $sortIndicator('engagement_rating') !!}
                        </a>
                    </th>
                    <th class="subscriber-cp-text-right">
                        <a href="{{ $sortLink('campaigns_count') }}" class="subscriber-cp-sort">
                            Campaigns{!! $sortIndicator('campaigns_count') !!}
                        </a>
                    </th>
                    <th class="subscriber-cp-text-right">
                        <a href="{{ $sortLink('delivered_count') }}" class="subscriber-cp-sort">
                            Delivered{!! $sortIndicator('delivered_count') !!}
                        </a>
                    </th>
                    <th class="subscriber-cp-text-right">
                        <a href="{{ $sortLink('failed_count') }}" class="subscriber-cp-sort">
                            Failed{!! $sortIndicator('failed_count') !!}
                        </a>
                    </th>
                    <th class="subscriber-cp-text-right">
                        <a href="{{ $sortLink('opened_count') }}" class="subscriber-cp-sort">
                            Opened{!! $sortIndicator('opened_count') !!}
                        </a>
                    </th>
                    <th class="subscriber-cp-text-right">
                        <a href="{{ $sortLink('clicked_count') }}" class="subscriber-cp-sort">
                            Clicked{!! $sortIndicator('clicked_count') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $sortLink('created_at') }}" class="subscriber-cp-sort">
                            Added{!! $sortIndicator('created_at') !!}
                        </a>
                    </th>
                    <th>Pending lifecycle</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr>
                        <td class="subscriber-sticky-col subscriber-email-col">
                            <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}"
                               class="subscriber-cp-link">
                                {{ $subscriber->email }}
                            </a>
                        </td>
                        <td class="subscriber-name-col">{{ $subscriber->full_name }}</td>
                        <td>
                            @php
                                $statusClasses = match ($subscriber->status) {
                                    'active' => 'subscriber-cp-badge-active',
                                    'pending' => 'subscriber-cp-badge-pending',
                                    'bounced', 'complained' => 'subscriber-cp-badge-danger',
                                    'unsubscribed' => 'subscriber-cp-badge-muted',
                                    default => 'subscriber-cp-badge-muted',
                                };
                            @endphp
                            <span class="subscriber-cp-badge {{ $statusClasses }}">
                                {{ ucfirst($subscriber->status) }}
                            </span>
                        </td>
                        <td class="subscriber-cp-text-muted subscriber-subgroup-col subscriber-cp-wrap">
                            {{ $subscriber->subGroups->pluck('name')->implode(', ') ?: '—' }}
                        </td>
                        <td>
                            @php
                                $rating = $subscriber->engagement_rating ?: '—';
                                $ratingClasses = match ($subscriber->engagement_rating) {
                                    'high' => 'subscriber-cp-badge-high',
                                    'moderate' => 'subscriber-cp-badge-moderate',
                                    'low' => 'subscriber-cp-badge-low',
                                    'inactive' => 'subscriber-cp-badge-inactive',
                                    'suppressed' => 'subscriber-cp-badge-suppressed',
                                    default => 'subscriber-cp-badge-muted',
                                };
                            @endphp
                            <span class="subscriber-cp-badge {{ $ratingClasses }}">
                                {{ $rating === '—' ? $rating : ucfirst($rating) }}
                            </span>
                        </td>
                        <td class="subscriber-cp-text-strong subscriber-cp-text-right">{{ $subscriber->campaigns_count }}</td>
                        <td class="subscriber-cp-text-strong subscriber-cp-text-right">{{ $subscriber->delivered_count }}</td>
                        <td class="subscriber-cp-text-strong subscriber-cp-text-right">{{ $subscriber->failed_count }}</td>
                        <td class="subscriber-cp-text-strong subscriber-cp-text-right">{{ $subscriber->opened_count }}</td>
                        <td class="subscriber-cp-text-strong subscriber-cp-text-right">{{ $subscriber->clicked_count }}</td>
                        <td class="subscriber-cp-text-muted">
                            {{ $subscriber->created_at->format('d M Y') }}
                        </td>
                        <td class="subscriber-cp-text-muted">
                            @php($pendingLifecycle = $pendingLifecycles[$subscriber->id] ?? null)
                            @if(($pendingLifecycle['is_pending'] ?? false) && $pendingLifecycle)
                                <div class="subscriber-cp-lifecycle-title {{ ($pendingLifecycle['is_expired'] ?? false) ? 'subscriber-cp-lifecycle-danger' : 'subscriber-cp-lifecycle-warning' }}">
                                    {{ $pendingLifecycle['label'] }}
                                </div>
                                <div class="subscriber-cp-lifecycle-meta">
                                    {{ $pendingLifecycle['age_label'] }}
                                </div>
                                <div class="subscriber-cp-lifecycle-meta">
                                    {{ $pendingLifecycle['expiry_label'] }}
                                </div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="subscriber-cp-text-right">
                            <a href="{{ cp_route('newsletter.subscribers.edit', $subscriber) }}"
                               class="subscriber-cp-link">Edit</a>
                            <form method="POST"
                                  action="{{ cp_route('newsletter.subscribers.destroy', $subscriber) }}"
                                  class="subscriber-cp-inline-form"
                                  onsubmit="return confirm('Delete this subscriber?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="subscriber-cp-link-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="subscriber-cp-empty">No subscribers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
            </div>
        </div>

        <div class="subscriber-cp-pagination">
            {{ $subscribers->links() }}
        </div>
    </div>
@endsection
