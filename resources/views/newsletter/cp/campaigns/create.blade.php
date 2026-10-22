@extends('statamic::layout')
@section('title', 'New Campaign')

@section('content')
<div class="campaign-form-page">
<div class="flex items-center gap-3 mb-6">
    <a href="{{ cp_route('newsletter.campaigns.index') }}" class="text-grey-60 hover:text-grey-80">&larr; Campaigns</a>
    <h1 class="text-3xl font-bold">New Campaign</h1>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ cp_route('newsletter.campaigns.store') }}"
      x-data="campaignForm()" x-init="refreshEntries(OLD_ENTRY_ID)">
    @csrf

    <div class="flex gap-6">

        {{-- Main column --}}
        <div class="flex-1 space-y-6">

            {{-- Campaign identity --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">Campaign Details</h2>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Internal Name <span class="text-red">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="input-text w-full"
                           placeholder="e.g. Insight Weekly — April 2025">
                    <p class="text-xs text-grey-60 mt-1">For internal reference only. Not shown to subscribers.</p>
                </div>

                <input type="hidden" name="product_id" x-bind:value="productId">
                <input type="hidden" name="collection" x-bind:value="collection">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Newsletter Content
                    </label>
                    <select id="entry-select" name="entry_id" x-model="entryId"
                            x-on:change="onEntryChange($event.target.value)"
                            x-bind:required="audienceScope === 'organisation_private' || action !== 'draft'"
                            class="input-text w-full">
                        <option value="">— Select newsletter content —</option>
                    </select>
                    <p class="text-xs text-grey-60 mt-1">
                        Entries are grouped by product. Select one to use its existing template and content.
                    </p>
                </div>

                <div class="mb-0">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Subject Line <span class="text-red">*</span>
                    </label>
                    <input type="text" id="subject-input" name="subject" x-model="subject"
                           class="input-text w-full"
                           placeholder="Email subject shown in the inbox">
                </div>
            </div>

            {{-- Sender overrides --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-1">Sender (optional overrides)</h2>
                <p class="text-sm text-grey-60 mb-4">
                    Leave blank to use the collection defaults
                    <template x-if="defaultFromEmail">
                        <span x-text="'(' + defaultFromEmail + ')'"></span>
                    </template>
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Name</label>
                        <input type="text" name="from_name" value="{{ old('from_name') }}"
                               class="input-text w-full" placeholder="Dataphyte Insight">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Email</label>
                        <input type="email" name="from_email" value="{{ old('from_email') }}"
                               class="input-text w-full" placeholder="newsletter@dataphyte.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">Reply-To</label>
                        <input type="email" name="reply_to" value="{{ old('reply_to') }}"
                               class="input-text w-full" placeholder="editor@dataphyte.com">
                    </div>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="w-72 space-y-6">

            {{-- Audience --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Audience</h2>

                <label class="block text-sm font-medium mb-1" for="audience-select">Select audience</label>
                <select id="audience-select" x-model="selectedAudience" x-on:change="onAudienceChange()"
                        x-bind:required="action !== 'draft'" class="input-text w-full mb-3">
                    <option value="">— Select an audience —</option>
                    <optgroup label="Public audiences">
                        @foreach($subGroups as $group)
                            <option value="public:{{ $group->id }}">{{ $group->organisation?->name }} / {{ $group->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Private audiences">
                        @foreach($privateGroups as $group)
                            <option value="private:{{ $group->id }}">{{ $group->organisation?->name }} / {{ $group->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <input type="hidden" name="audience_scope" x-bind:value="audienceScope">
                <input type="hidden" name="private_group_id" x-bind:value="privateGroupId" x-bind:disabled="audienceScope !== 'organisation_private'">
                <p class="text-xs text-grey-60 mb-4" x-show="audienceScope === 'organisation_private'">
                    Choose newsletter content from the same organisation. Selecting a private audience does not subscribe its members to a product.
                </p>

                {{-- Send to all toggle --}}
                <label class="flex items-center gap-2 mb-4 cursor-pointer">
                    <input type="hidden" name="send_to_all" value="0">
                    <input type="checkbox" name="send_to_all" value="1"
                           x-model="sendToAll"
                           class="toggle-input">
                    <span class="text-sm font-medium">Send to All</span>
                </label>
                <p class="text-xs text-grey-60 mb-4" x-show="sendToAll">
                    Every eligible member of the selected audience will receive this campaign.
                </p>

                {{-- Sub-group checkboxes --}}
                <div x-show="!sendToAll && audienceScope === 'product_public'">
                    @foreach($subGroups as $group)
                    <div class="mb-3" x-show="selectedAudience === @js('public:'.$group->id)">
                        <p class="text-xs font-semibold uppercase tracking-wide text-grey-60 mb-1">
                            {{ $group->name }}
                        </p>
                        @foreach($group->subGroups as $sub)
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" name="sub_groups[]"
                                   value="{{ $sub->id }}"
                                   x-bind:disabled="selectedAudience !== @js('public:'.$group->id) || sendToAll"
                                   class="checkbox">
                            <span class="text-sm">{{ $sub->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                <div x-show="!sendToAll && audienceScope === 'organisation_private'">
                    @foreach($privateGroups as $group)
                        <div x-show="String(privateGroupId) === @js((string) $group->id)" class="mb-3">
                            <p class="text-xs font-semibold mb-1">{{ $group->name }} ratings</p>
                            @foreach($group->subGroups as $sub)
                                <label class="flex items-center gap-2 py-1 cursor-pointer">
                                    <input type="checkbox" name="sub_groups[]" value="{{ $sub->id }}"
                                           x-bind:disabled="selectedAudience !== @js('private:'.$group->id) || sendToAll" class="checkbox">
                                    <span class="text-sm">{{ $sub->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Schedule / Send --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Send</h2>

                <div class="space-y-2 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="draft"
                               x-model="action" class="radio">
                        <span class="text-sm">Save as Draft</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="schedule"
                               x-model="action" class="radio">
                        <span class="text-sm">Schedule</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="send"
                               x-model="action" class="radio">
                        <span class="text-sm font-medium text-red">Send Now</span>
                    </label>
                </div>

                <div x-show="action === 'schedule'" class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Send at <span class="text-red">*</span>
                    </label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at') }}"
                           class="input-text w-full text-sm">
                </div>

                <div x-show="action === 'send'"
                     class="bg-red-100 border border-red-300 rounded p-3 mb-4 text-xs text-red-800">
                    This will immediately send to all selected audiences. This cannot be undone.
                </div>
                <p class="text-xs text-grey-60 mb-3">Proofread the campaign and send a test email before sending or scheduling.</p>
                <label class="flex items-start gap-2 mb-4 text-sm">
                    <input type="checkbox" name="send_confirmation" value="1" @checked(old('send_confirmation')) class="checkbox mt-1">
                    <span>I have checked the content and recipients and confirm this campaign is ready to send or schedule. Required for Send Now and Schedule.</span>
                </label>

                <button type="submit" class="btn-primary w-full"
                        x-bind:class="{ 'bg-red border-red hover:bg-red-dark': action === 'send' }">
                    <span x-text="action === 'send' ? 'Send Campaign' : (action === 'schedule' ? 'Schedule Campaign' : 'Save Draft')">Save Draft</span>
                </button>
            </div>

        </div>
    </div>
</form>

<script>
const PRODUCT_ENTRIES = @json($entriesByProduct);
const COLLECTION_META = @json($collectionMeta);
const PUBLIC_AUDIENCES = @json($subGroups->mapWithKeys(fn ($group) => [$group->id => ['product_id' => $group->product_id]]));
const PRIVATE_AUDIENCES = @json($privateGroups->mapWithKeys(fn ($group) => [$group->id => ['organisation_id' => $group->organisation_id]]));

const OLD_ENTRY_ID = @js((string) old('entry_id', ''));

function campaignForm() {
    return {
        productId: @js((string) old('product_id', '')),
        audienceScope: @js(old('audience_scope', 'product_public')),
        privateGroupId: @js((string) old('private_group_id', '')),
        selectedAudience: @js(old('audience_scope') === 'organisation_private'
            ? (old('private_group_id') ? 'private:'.old('private_group_id') : '')
            : (($oldPublicGroup = $subGroups->firstWhere('product_id', old('product_id')))
                ? 'public:'.$oldPublicGroup->id : '')),
        collection: @js((string) old('collection', '')),
        entryId: OLD_ENTRY_ID,
        entrySubject: '',
        subject: @js(old('subject', '')),
        sendToAll: {{ old('send_to_all', 0) ? 'true' : 'false' }},
        action: '{{ old('action', 'draft') }}',

        get defaultFromEmail() {
            return COLLECTION_META[this.collection]?.from_email || '';
        },

        refreshEntries(selectedId = this.entryId) {
            const [type, id] = this.selectedAudience.split(':');
            const selectedProductId = type === 'public' ? String(PUBLIC_AUDIENCES[id]?.product_id || '') : '';
            const selectedOrganisationId = type === 'private' ? String(PRIVATE_AUDIENCES[id]?.organisation_id || '') : '';
            const select = document.getElementById('entry-select');
            select.replaceChildren(new Option('— Select newsletter content —', ''));

            for (const [productId, product] of Object.entries(PRODUCT_ENTRIES)) {
                if ((selectedProductId && productId !== selectedProductId)
                    || (selectedOrganisationId && String(product.organisation_id) !== selectedOrganisationId)) continue;

                const group = document.createElement('optgroup');
                group.label = product.name;
                for (const entry of product.entries) {
                    group.appendChild(new Option((entry.date ? entry.date + ' — ' : '') + entry.title, entry.id));
                }
                if (group.children.length) select.appendChild(group);
            }

            const available = [...select.options].some(option => option.value && option.value === selectedId);
            if (!available && this.entryId && this.subject === this.entrySubject) this.subject = '';
            this.entryId = available ? selectedId : '';
            if (!available) this.entrySubject = '';
            select.value = this.entryId;
        },

        onEntryChange(entryId) {
            if (!entryId) {
                if (this.subject === this.entrySubject) this.subject = '';
                this.entrySubject = '';
                if (this.audienceScope === 'organisation_private') {
                    this.productId = '';
                    this.collection = '';
                } else if (!this.selectedAudience) {
                    this.productId = '';
                    this.collection = '';
                }
                return;
            }

            for (const [productId, product] of Object.entries(PRODUCT_ENTRIES)) {
                const entry = product.entries.find(item => item.id === entryId);
                if (!entry) continue;

                this.productId = productId;
                this.collection = product.collection;
                if ((!this.subject || this.subject === this.entrySubject) && entry.subject) this.subject = entry.subject;
                this.entrySubject = entry.subject;
                const [type, id] = this.selectedAudience.split(':');
                if ((type === 'public' && String(PUBLIC_AUDIENCES[id]?.product_id) !== productId)
                    || (type === 'private' && String(PRIVATE_AUDIENCES[id]?.organisation_id) !== String(product.organisation_id))) {
                    this.selectedAudience = '';
                    this.privateGroupId = '';
                    this.audienceScope = 'product_public';
                }
                return;
            }
        },

        onAudienceChange() {
            const [type, id] = this.selectedAudience.split(':');
            if (type === 'public' && PUBLIC_AUDIENCES[id]) {
                this.audienceScope = 'product_public';
                this.privateGroupId = '';
                this.productId = String(PUBLIC_AUDIENCES[id].product_id);
                this.collection = PRODUCT_ENTRIES[this.productId]?.collection || '';
            } else if (type === 'private' && PRIVATE_AUDIENCES[id]) {
                this.audienceScope = 'organisation_private';
                this.privateGroupId = id;
            } else {
                this.audienceScope = 'product_public';
                this.privateGroupId = '';
            }
            this.refreshEntries();
            if (this.entryId) {
                this.onEntryChange(this.entryId);
            } else if (this.audienceScope === 'organisation_private') {
                this.productId = '';
                this.collection = '';
            }
        },
    }
}
</script>

</div>
@endsection
