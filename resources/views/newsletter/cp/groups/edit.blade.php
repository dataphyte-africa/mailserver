@extends('statamic::layout')
@section('title', 'Edit Group')

@section('content')
    @push('head')
    <style>
        .audience-detail { --audience-ink:#17233b; --audience-line:#cbd8ea; --audience-muted:#526785; max-width:1100px; }
        .audience-detail-head { display:flex; align-items:end; justify-content:space-between; gap:1rem; margin-bottom:1.4rem; }
        .audience-detail-head h1 { color:var(--audience-ink); font-size:1.8rem; font-weight:750; line-height:1.2; margin:.2rem 0; }
        .audience-detail-kicker { color:var(--audience-muted); font-size:.75rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; }
        .audience-detail-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; margin-bottom:1.25rem; }
        .audience-detail-stat, .audience-detail-panel { background:#fff; border:1px solid var(--audience-line); border-radius:12px; padding:1.2rem; }
        .audience-detail-stat span { color:var(--audience-muted); display:block; font-size:.8rem; font-weight:650; }
        .audience-detail-stat strong { color:var(--audience-ink); display:block; font-size:1.7rem; line-height:1.1; margin-top:.45rem; }
        .audience-detail-panel { margin-bottom:1.25rem; }
        .audience-detail-panel h2 { color:var(--audience-ink); font-size:1.1rem; font-weight:700; margin:0 0 .35rem; }
        .audience-detail-help { color:var(--audience-muted); font-size:.84rem; margin-bottom:1rem; }
        .audience-detail-table-wrap { overflow-x:auto; }
        .audience-detail-table { border-collapse:collapse; width:100%; min-width:430px; }
        .audience-detail-table th, .audience-detail-table td { border-bottom:1px solid #e4ebf5; padding:.65rem .5rem; text-align:left; }
        .audience-detail-table th { background:#f1f5fb; color:#33445f; font-size:.75rem; font-weight:700; }
        .audience-detail-table td:not(:first-child), .audience-detail-table th:not(:first-child) { text-align:right; }
        .audience-detail-table tr:last-child td { border-bottom:0; }
        .audience-detail-ratings { display:flex; flex-wrap:wrap; gap:.45rem; }
        .audience-detail-rating { background:#edf3fa; border:1px solid #d9e4f1; border-radius:999px; color:#263c59; font-size:.8rem; padding:.4rem .65rem; }
        .audience-detail-rating strong { margin-left:.35rem; }
        @media(max-width:700px) { .audience-detail-grid { grid-template-columns:1fr; } .audience-detail-head { align-items:start; flex-direction:column; } }
    </style>
    @endpush
    <div class="audience-detail">
    <div class="audience-detail-head">
        <div>
        <a href="{{ cp_route('newsletter.groups.index') }}"
           class="text-sm text-gray-500 hover:underline mb-1 block">← Groups</a>
        <span class="audience-detail-kicker">{{ $group->isPrivateAudience() ? 'Private audience' : 'Product audience' }}</span>
        <h1>{{ $group->name }}</h1>
        @if($group->isArchived())
            <p class="text-sm text-gray-500 mt-1">Archived</p>
        @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="audience-detail-grid" aria-label="Audience growth summary">
        <div class="audience-detail-stat"><span>New this month</span><strong>{{ number_format($growth['months'][11]['new']) }}</strong></div>
        <div class="audience-detail-stat"><span>Total joined</span><strong>{{ number_format($growth['joined']) }}</strong></div>
        <div class="audience-detail-stat"><span>Current subscribers</span><strong>{{ number_format($growth['current']) }}</strong></div>
    </div>

    <section class="audience-detail-panel" aria-labelledby="audience-growth-title">
        <h2 id="audience-growth-title">Subscriber growth · last 12 months</h2>
        <p class="audience-detail-help">Each person is counted once when they first join this audience. Total joined is cumulative; current subscribers excludes people who have left or are suppressed.</p>
        <div class="audience-detail-table-wrap">
            <table class="audience-detail-table">
                <thead><tr><th scope="col">Month</th><th scope="col">New subscribers</th><th scope="col">Total joined</th></tr></thead>
                <tbody>
                @foreach($growth['months'] as $month)
                    <tr><td>{{ $month['label'] }}</td><td>{{ number_format($month['new']) }}</td><td>{{ number_format($month['total_joined']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="audience-detail-panel" aria-labelledby="audience-ratings-title">
        <h2 id="audience-ratings-title">Rating breakdown</h2>
        <p class="audience-detail-help">Ratings are assigned automatically. Historical preference buckets remain stored but are not managed here.</p>
        <div class="audience-detail-ratings">
            @foreach(\App\Services\Newsletter\GroupEngagementService::SEGMENTS as $rating => $label)
                <span class="audience-detail-rating">{{ $label }} <strong>{{ number_format($ratingCounts->get($rating, 0)) }}</strong></span>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Edit group --}}
        <div>
            <h2 class="font-semibold mb-3">Group Details</h2>
            <form method="POST" action="{{ cp_route('newsletter.groups.update', $group) }}" class="card p-5 space-y-4">
                @csrf @method('PUT')
                @if($product)<input type="hidden" name="product_id" value="{{ $product->getKey() }}">@endif
                <div>
                    <label class="publish-field-label">Group Name</label>
                    <input type="text" name="name" value="{{ old('name', $group->name) }}"
                           class="input-text w-full" required>
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                @if($product)
                <div>
                    <label class="publish-field-label">Product</label>
                    <div class="input-text w-full bg-gray-50 text-gray-700">
                        {{ $product->organisation->name }} / {{ $product->name }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Group ownership cannot be changed during editing.</p>
                    @error('product_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="publish-field-label">Newsletter Collection</label>
                    <div class="input-text w-full bg-gray-50 text-gray-700">{{ $collectionLabel }}</div>
                </div>
                @else
                    <p class="text-sm text-gray-500">Private audience for {{ $group->organisation?->name }}. Not shown on public preference pages.</p>
                @endif
                <div>
                    <label class="publish-field-label">Description</label>
                    <textarea name="description" rows="2"
                              class="input-text w-full">{{ old('description', $group->description) }}</textarea>
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>

        @if($group->isPrivateAudience())
        <div>
            <h2 class="font-semibold mb-3">Add member</h2>
            <form method="POST" action="{{ cp_route('newsletter.groups.members.store', $group) }}" class="card p-5 space-y-4">
                @csrf
                <label class="publish-field-label">Email <input type="email" name="email" class="input-text w-full" required></label>
                <label class="publish-field-label">First name <input type="text" name="first_name" class="input-text w-full"></label>
                <label class="publish-field-label">Last name <input type="text" name="last_name" class="input-text w-full"></label>
                <label class="publish-field-label">Permission source <input type="text" name="source" class="input-text w-full" placeholder="e.g. Event signup on 21 October" required></label>
                <label class="text-sm"><input type="checkbox" name="permission_confirmed" value="1" required> I confirm this address may receive email from this organisation.</label>
                @if($errors->any())<p class="text-red-500 text-sm">{{ $errors->first() }}</p>@endif
                <button class="btn-primary" type="submit">Add member</button>
            </form>
        </div>
        <div>
            <h2 class="font-semibold mb-3">Import members</h2>
            <form method="POST" action="{{ cp_route('newsletter.groups.members.import', $group) }}" enctype="multipart/form-data" class="card p-5 space-y-4">
                @csrf
                <p class="text-sm text-gray-500">CSV headers: email, first_name, last_name. Maximum 2,000 rows. Existing opt-outs are skipped.</p>
                <input type="file" name="csv_file" accept=".csv,.txt" required>
                <label class="publish-field-label">Permission source <input type="text" name="source" class="input-text w-full" required></label>
                <label class="text-sm"><input type="checkbox" name="permission_confirmed" value="1" required> I confirm these addresses may receive email from this organisation.</label>
                <button class="btn-primary" type="submit">Import CSV</button>
            </form>
        </div>
        @endif

    </div>
    @if($group->isPrivateAudience())
    <div class="card p-5 mt-6">
        <h2 class="font-semibold mb-3">Members</h2>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead><tr><th class="text-left p-2">Email</th><th class="text-left p-2">Rating</th><th class="text-left p-2">Product subscriptions</th><th class="text-left p-2">Source</th><th class="text-left p-2">Status</th><th class="p-2"></th></tr></thead>
            <tbody>
            @forelse($members as $member)
                @php
                    $membership = $member->allSubGroups->first();
                    $products = $member->subGroups->filter(fn ($sub) => $sub->purpose === 'preference' && $sub->group?->audience_scope === 'product_public' && (int) $sub->group?->organisation_id === (int) $group->organisation_id)->pluck('group.product.name')->filter()->unique();
                @endphp
                <tr class="border-t"><td class="p-2">{{ $member->email }}</td>
                    <td class="p-2">{{ $member->groupEngagements()->where('subscriber_group_id', $group->id)->value('rating') ?? 'New / Unrated' }}</td>
                    <td class="p-2">{{ $products->isEmpty() ? 'None' : $products->join(', ') }}</td>
                    <td class="p-2">{{ $membership?->pivot?->private_source ?? 'Not recorded' }}</td>
                    <td class="p-2">{{ $membership?->pivot?->unsubscribed_at ? 'Removed' : 'Active' }}</td>
                    <td class="p-2">@if(! $membership?->pivot?->unsubscribed_at)<form method="POST" action="{{ cp_route('newsletter.groups.members.destroy', [$group, $member]) }}">@csrf @method('DELETE')<button class="text-red-500" type="submit">Remove</button></form>@endif</td>
                </tr>
            @empty
                <tr><td class="p-2" colspan="6">No members yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $members->links() }}
    </div>
    @endif
    </div>
@endsection
