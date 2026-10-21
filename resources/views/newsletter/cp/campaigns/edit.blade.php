@extends('statamic::layout')
@section('title', 'Edit Campaign')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
       class="text-grey-60 hover:text-grey-80">&larr; {{ $campaign->name }}</a>
    <h1 class="text-3xl font-bold">Edit Campaign</h1>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ cp_route('newsletter.campaigns.update', $campaign) }}">
    @csrf
    @method('PUT')

    <div class="flex gap-6">

        {{-- Main column --}}
        <div class="flex-1 space-y-6">

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">Campaign Details</h2>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Internal Name <span class="text-red">*</span>
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $campaign->name) }}"
                           class="input-text w-full">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">{{ $campaign->audience_scope === 'organisation_private' ? 'Email template' : 'Product' }}</label>
                    <div class="input-text w-full bg-grey-10 text-grey-80">
                        {{ $product->organisation->name }} / {{ $product->name }}
                    </div>
                    <p class="text-xs text-grey-60 mt-1">Campaign ownership cannot be changed during editing.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">Collection <span class="text-red">*</span></label>
                    <select id="collection-select" name="collection" class="input-text w-full">
                        @foreach($collections as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('collection', $campaign->collection) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">Content Entry</label>
                    <select id="entry-select" name="entry_id" class="input-text w-full">
                        <option value="">— No entry linked —</option>
                        @foreach($entries[$collectionHandle] ?? [] as $entryOption)
                            <option value="{{ $entryOption['id'] }}" @selected((string) old('entry_id', $campaign->entry_id) === (string) $entryOption['id'])>
                                {{ $entryOption['date'] }} — {{ $entryOption['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-grey-80 mb-1">Subject Line <span class="text-red">*</span></label>
                    <input type="text" id="subject-input" name="subject" value="{{ old('subject', $campaign->subject) }}" class="input-text w-full">
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">Sender Overrides</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Name</label>
                        <input type="text" name="from_name"
                               value="{{ old('from_name', $campaign->from_name) }}"
                               class="input-text w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Email</label>
                        <input type="email" name="from_email"
                               value="{{ old('from_email', $campaign->from_email) }}"
                               class="input-text w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">Reply-To</label>
                        <input type="email" name="reply_to"
                               value="{{ old('reply_to', $campaign->reply_to) }}"
                               class="input-text w-full">
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="w-72 space-y-6">

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Audience</h2>

                <p class="text-sm mb-3">{{ $campaign->audience_scope === 'organisation_private' ? 'Private organisation audience' : 'Product subscribers' }}</p>
                @if($campaign->audience_scope === 'organisation_private')
                    <label class="block text-sm font-medium mb-1">Private audience</label>
                    <select name="private_group_id" class="input-text w-full mb-4">
                        @foreach($privateGroups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('private_group_id', $selectedPrivateGroupId) === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                @endif

                <label class="flex items-center gap-2 mb-4 cursor-pointer">
                    <input type="hidden" name="send_to_all" value="0">
                    <input type="checkbox" name="send_to_all" value="1"
                           @checked(old('send_to_all', $sendToAll)) class="toggle-input">
                    <span class="text-sm font-medium">Send to All</span>
                </label>

                @if($campaign->audience_scope === 'organisation_private')
                <div>
                    @foreach($privateGroups as $group)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-grey-60 mb-1">{{ $group->name }}</p>
                            @foreach($group->subGroups as $sub)
                                <label class="flex items-center gap-2 py-1 cursor-pointer">
                                    <input type="checkbox" name="sub_groups[]" value="{{ $sub->id }}"
                                           @checked(in_array($sub->id, old('sub_groups', $selectedSubGroupIds))) class="checkbox">
                                    <span class="text-sm">{{ $sub->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                @else
                <div>
                    @foreach($subGroups as $group)
                    @continue((int) $group->product_id !== (int) $product->id || $group->collection_handle !== $collectionHandle)
                    <div class="mb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-grey-60 mb-1">
                            {{ $group->name }}
                        </p>
                        @foreach($group->subGroups as $sub)
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" name="sub_groups[]"
                                   value="{{ $sub->id }}"
                                   @checked(in_array($sub->id, old('sub_groups', $selectedSubGroupIds)))
                                   class="checkbox">
                            <span class="text-sm">{{ $sub->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Send</h2>

                <div class="space-y-2 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="draft"
                               @checked(old('action', 'draft') === 'draft') class="radio">
                        <span class="text-sm">Save as Draft</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="schedule"
                               @checked(old('action') === 'schedule') class="radio">
                        <span class="text-sm">Schedule</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="send"
                               @checked(old('action') === 'send') class="radio">
                        <span class="text-sm font-medium text-red">Send Now</span>
                    </label>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">Send at <span class="text-red">*</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $campaign->scheduled_at?->format('Y-m-d\TH:i')) }}"
                           class="input-text w-full text-sm">
                </div>

                <div class="bg-blue-100 border border-blue-300 rounded p-3 mb-4 text-xs text-blue-800">
                    Proofread the campaign and send a test email from the <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}" class="underline font-semibold">campaign page</a> before sending or scheduling. Send Now starts delivery to the selected audience immediately.
                </div>
                <label class="flex items-start gap-2 mb-4 text-sm">
                    <input type="checkbox" name="send_confirmation" value="1" @checked(old('send_confirmation')) class="checkbox mt-1">
                    <span>I have checked the content and recipients and confirm this campaign is ready to send or schedule. Required for Send Now and Schedule.</span>
                </label>

                <button type="submit" class="btn-primary w-full">Save campaign</button>
            </div>

            {{-- Danger --}}
            @if($campaign->status === 'scheduled')
            <div class="card p-6 border-yellow-400">
                <h2 class="text-sm font-semibold mb-2 text-yellow-dark">Cancel Schedule</h2>
                <form method="POST" action="{{ cp_route('newsletter.campaigns.cancel', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm w-full text-yellow-dark border-yellow-400"
                            onclick="return confirm('Move this campaign back to draft?')">
                        Cancel Schedule
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</form>

@endsection
