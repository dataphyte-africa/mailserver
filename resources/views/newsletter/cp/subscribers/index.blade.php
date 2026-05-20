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

    <style>
        .subscriber-table-wrap {
            overflow-x: auto;
            max-width: 100%;
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

        .subscriber-email-col {
            min-width: 280px;
            max-width: 280px;
        }

        .subscriber-name-col {
            min-width: 220px;
        }

        .subscriber-subgroup-col {
            min-width: 220px;
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
    </style>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Subscribers</h1>
        <div class="flex gap-2">
            <a href="{{ cp_route('newsletter.subscribers.import.form') }}"
               class="btn-default">Import CSV</a>
            <a href="{{ cp_route('newsletter.subscribers.export') . '?' . http_build_query(request()->only('search', 'status', 'sub_group', 'sort', 'direction')) }}"
               class="btn-default">Export CSV</a>
            <a href="{{ cp_route('newsletter.subscribers.create') }}"
               class="btn-primary">Add Subscriber</a>
        </div>
    </div>

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-4 p-4 rounded-lg {{ $result['skipped'] > 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200' }}">
            <p class="font-medium">Import complete: {{ $result['imported'] }} imported, {{ $result['skipped'] }} skipped.</p>
            @if(count($result['errors']))
                <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
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
    <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Email or name…"
                   class="input-text w-56">
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Status</label>
            <select name="status" class="input-text">
                <option value="">All statuses</option>
                @foreach(['active','unsubscribed','bounced','complained'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Sub-group</label>
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
        <button type="submit" class="btn-primary">Filter</button>
        @if(request()->hasAny(['search','status','sub_group']))
            <a href="{{ cp_route('newsletter.subscribers.index') }}" class="btn-default">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card p-0 overflow-visible">
        <div class="subscriber-table-wrap">
        <table class="data-table subscriber-table">
            <thead>
                <tr>
                    <th class="subscriber-sticky-col subscriber-sticky-head subscriber-email-col">
                        <a href="{{ $sortLink('email') }}" class="hover:underline">
                            Email{!! $sortIndicator('email') !!}
                        </a>
                    </th>
                    <th class="subscriber-name-col">
                        <a href="{{ $sortLink('name') }}" class="hover:underline">
                            Name{!! $sortIndicator('name') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $sortLink('status') }}" class="hover:underline">
                            Status{!! $sortIndicator('status') !!}
                        </a>
                    </th>
                    <th class="subscriber-subgroup-col">Sub-groups</th>
                    <th>
                        <a href="{{ $sortLink('engagement_rating') }}" class="hover:underline">
                            Rating{!! $sortIndicator('engagement_rating') !!}
                        </a>
                    </th>
                    <th class="text-right">
                        <a href="{{ $sortLink('campaigns_count') }}" class="hover:underline">
                            Campaigns{!! $sortIndicator('campaigns_count') !!}
                        </a>
                    </th>
                    <th class="text-right">
                        <a href="{{ $sortLink('delivered_count') }}" class="hover:underline">
                            Delivered{!! $sortIndicator('delivered_count') !!}
                        </a>
                    </th>
                    <th class="text-right">
                        <a href="{{ $sortLink('failed_count') }}" class="hover:underline">
                            Failed{!! $sortIndicator('failed_count') !!}
                        </a>
                    </th>
                    <th class="text-right">
                        <a href="{{ $sortLink('opened_count') }}" class="hover:underline">
                            Opened{!! $sortIndicator('opened_count') !!}
                        </a>
                    </th>
                    <th class="text-right">
                        <a href="{{ $sortLink('clicked_count') }}" class="hover:underline">
                            Clicked{!! $sortIndicator('clicked_count') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $sortLink('created_at') }}" class="hover:underline">
                            Added{!! $sortIndicator('created_at') !!}
                        </a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr>
                        <td class="subscriber-sticky-col subscriber-email-col">
                            <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}"
                               class="text-blue font-medium hover:underline">
                                {{ $subscriber->email }}
                            </a>
                        </td>
                        <td class="subscriber-name-col">{{ $subscriber->full_name }}</td>
                        <td>
                            <span class="badge-sm
                                {{ $subscriber->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($subscriber->status) }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-600 subscriber-subgroup-col">
                            {{ $subscriber->subGroups->pluck('name')->implode(', ') ?: '—' }}
                        </td>
                        <td>
                            @php
                                $rating = $subscriber->engagement_rating ?: '—';
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
                                {{ $rating === '—' ? $rating : ucfirst($rating) }}
                            </span>
                        </td>
                        <td class="text-sm text-right text-gray-700">{{ $subscriber->campaigns_count }}</td>
                        <td class="text-sm text-right text-gray-700">{{ $subscriber->delivered_count }}</td>
                        <td class="text-sm text-right text-gray-700">{{ $subscriber->failed_count }}</td>
                        <td class="text-sm text-right text-gray-700">{{ $subscriber->opened_count }}</td>
                        <td class="text-sm text-right text-gray-700">{{ $subscriber->clicked_count }}</td>
                        <td class="text-sm text-gray-500">
                            {{ $subscriber->created_at->format('d M Y') }}
                        </td>
                        <td class="text-right">
                            <a href="{{ cp_route('newsletter.subscribers.edit', $subscriber) }}"
                               class="text-sm text-blue hover:underline mr-3">Edit</a>
                            <form method="POST"
                                  action="{{ cp_route('newsletter.subscribers.destroy', $subscriber) }}"
                                  class="inline"
                                  onsubmit="return confirm('Delete this subscriber?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-gray-500 py-8">No subscribers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $subscribers->links() }}
    </div>
@endsection
