@extends('statamic::layout')
@section('title', 'Newsletter Groups')

@section('content')
    <div class="newsletter-groups-header">
        <h1 class="newsletter-groups-title">Groups</h1>
        <a href="{{ cp_route('newsletter.groups.create') }}" class="newsletter-groups-button-primary">Add Group</a>
    </div>

    @if(session('success'))
        <div class="newsletter-groups-notice">
            {{ session('success') }}
        </div>
    @endif

    <div class="newsletter-groups-list">
        @forelse($groups as $group)
            <div class="newsletter-groups-card">
                <div class="newsletter-groups-card-header">
                    <div class="newsletter-groups-card-main">
                        <h2 class="newsletter-groups-card-title">{{ $group->name }}</h2>
                        <p class="newsletter-groups-meta">
                            {{ $group->isPrivateAudience() ? $group->organisation?->name.' · Private audience' : ($collectionOptions[$group->collection_handle] ?? 'Unlinked collection') }}
                        </p>
                        @if($group->description)
                            <p class="newsletter-groups-description">{{ $group->description }}</p>
                        @endif
                        <p class="newsletter-groups-meta">
                            {{ $activeCounts->get($group->id, 0) }} active subscribers ·
                            {{ $engagements->get($group->id)?->new_this_month ?? 0 }} new this month ·
                            {{ $engagements->get($group->id)?->new_last_month ?? 0 }} last month
                        </p>
                        @if($group->isArchived())
                            <p class="newsletter-groups-archive-label">Archived</p>
                        @endif
                    </div>
                    <div class="newsletter-groups-actions">
                        @if(! app(\App\Services\Platform\CpOperatorScope::class)->isVisibleLegacyGroup(request()->user(), $group))
                        <a href="{{ cp_route('newsletter.groups.edit', $group) }}"
                           class="newsletter-groups-button">Manage</a>
                        @if($group->isArchived())
                            <form method="POST" action="{{ cp_route('newsletter.groups.restore', $group) }}"
                                  onsubmit="return confirm('Restore this group? Eligible targeting and assignment paths will pick it up again automatically.')">
                                @csrf
                                <button type="submit" class="newsletter-groups-button">Restore</button>
                            </form>
                        @else
                            <form method="POST" action="{{ cp_route('newsletter.groups.archive', $group) }}"
                                  onsubmit="return confirm('Archive this group? It will be hidden from new targeting and form assignment.')">
                                @csrf
                                <button type="submit" class="newsletter-groups-button">Archive</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ cp_route('newsletter.groups.destroy', $group) }}"
                              onsubmit="return confirm('Delete this group and all its sub-groups?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="newsletter-groups-button newsletter-groups-button-danger">Delete</button>
                        </form>
                        @else
                            <span class="newsletter-groups-meta">Historical audience · read only</span>
                        @endif
                    </div>
                </div>

                <div class="newsletter-groups-subgroups">
                    @foreach(\App\Services\Newsletter\GroupEngagementService::SEGMENTS as $rating => $label)
                        <div class="newsletter-groups-chip">
                            <span>{{ $label }}:</span>
                            <span class="newsletter-groups-chip-muted">{{ $ratingCounts->get($group->id)?->firstWhere('rating', $rating)?->total ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="newsletter-groups-empty-card">
                No groups found.
                <a href="{{ cp_route('newsletter.groups.create') }}" class="newsletter-groups-link">Create one.</a>
            </div>
        @endforelse
    </div>
@endsection
