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
                            {{ $collectionOptions[$group->collection_handle] ?? 'Unlinked collection' }}
                        </p>
                        @if($group->description)
                            <p class="newsletter-groups-description">{{ $group->description }}</p>
                        @endif
                        @if($group->isArchived())
                            <p class="newsletter-groups-archive-label">Archived</p>
                        @endif
                    </div>
                    <div class="newsletter-groups-actions">
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
                    </div>
                </div>

                <div class="newsletter-groups-subgroups">
                    @foreach($group->subGroups as $subGroup)
                        <div class="newsletter-groups-chip">
                            <span>{{ $subGroup->name }}</span>
                            @if($subGroup->isArchived())
                                <span class="newsletter-groups-chip-muted">archived</span>
                            @endif
                            <span class="newsletter-groups-chip-muted">{{ $subGroup->subscribers_count }} subscribers</span>
                        </div>
                    @endforeach
                    @if($group->subGroups->isEmpty())
                        <span class="newsletter-groups-empty-inline">No sub-groups yet.</span>
                    @endif
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
