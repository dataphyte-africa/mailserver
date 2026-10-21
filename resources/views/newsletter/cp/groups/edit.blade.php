@extends('statamic::layout')
@section('title', 'Edit Group')

@section('content')
    <div class="mb-6">
        <a href="{{ cp_route('newsletter.groups.index') }}"
           class="text-sm text-gray-500 hover:underline mb-1 block">← Groups</a>
        <h1 class="text-3xl font-bold">{{ $group->name }}</h1>
        @if($group->isArchived())
            <p class="text-sm text-gray-500 mt-1">Archived</p>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

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
        @else
        {{-- Product preference sub-groups --}}
        <div>
            <h2 class="font-semibold mb-3">Sub-groups</h2>
            <div class="card p-5 space-y-3">
                @forelse($group->subGroups as $subGroup)
                    <div class="flex items-center justify-between py-1" x-data="{ editing: false }">
                        <div x-show="!editing">
                            <span class="font-medium text-sm">{{ $subGroup->name }}</span>
                            @if($subGroup->isArchived())
                                <span class="text-xs text-gray-400 ml-2">archived</span>
                            @endif
                            <span class="text-xs text-gray-400 ml-2">{{ $subGroup->subscribers_count }} subscribers</span>
                        </div>

                        {{-- Inline edit --}}
                        <form x-show="editing"
                              method="POST"
                              action="{{ cp_route('newsletter.groups.sub-groups.update', [$group, $subGroup]) }}">
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $subGroup->name }}"
                                   class="input-text text-sm py-1 px-2 w-36" required>
                            <button type="submit" class="text-xs text-blue ml-1">Save</button>
                        </form>

                        <div class="flex gap-2 text-xs">
                            <button @click="editing = !editing" class="text-blue hover:underline">
                                <span x-text="editing ? 'Cancel' : 'Rename'"></span>
                            </button>
                            @if($subGroup->isArchived())
                                <form method="POST"
                                      action="{{ cp_route('newsletter.groups.sub-groups.restore', [$group, $subGroup]) }}"
                                      onsubmit="return confirm('Restore sub-group {{ $subGroup->name }}? Its parent group must already be active.')">
                                    @csrf
                                    <button type="submit" class="text-gray-500 hover:underline">Restore</button>
                                </form>
                            @else
                                <form method="POST"
                                      action="{{ cp_route('newsletter.groups.sub-groups.archive', [$group, $subGroup]) }}"
                                      onsubmit="return confirm('Archive sub-group {{ $subGroup->name }}?')">
                                    @csrf
                                    <button type="submit" class="text-gray-500 hover:underline">Archive</button>
                                </form>
                            @endif
                            <form method="POST"
                                  action="{{ cp_route('newsletter.groups.sub-groups.destroy', [$group, $subGroup]) }}"
                                  onsubmit="return confirm('Delete sub-group {{ $subGroup->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No sub-groups yet.</p>
                @endforelse

                {{-- Add sub-group --}}
                <form method="POST"
                      action="{{ cp_route('newsletter.groups.sub-groups.store', $group) }}"
                      class="mt-3 pt-3 border-t flex gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="New sub-group name"
                           class="input-text flex-1 text-sm" required>
                    <button type="submit" class="btn-primary text-sm">Add</button>
                </form>
            </div>
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
@endsection
