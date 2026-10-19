@extends('statamic::layout')
@section('title', 'Organisation Profile')

@section('content')
<div class="organisation-profile-page">
    <header class="organisation-profile-heading">
        <p class="organisation-profile-eyebrow">Public identity</p>
        <h1>Organisation Profile</h1>
        <p>Manage the identity and audience choices shown on your newsletter preference and unsubscribe pages.</p>
    </header>

    @if(session('success')) <p class="organisation-profile-notice">{{ session('success') }}</p> @endif
    @if($errors->any())
        <div class="organisation-profile-errors" role="alert">
            @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    @forelse($organisations as $organisation)
        <form class="organisation-profile-form" method="post" enctype="multipart/form-data"
              action="{{ cp_route('organisation-profiles.update', $organisation) }}">
            @csrf @method('PUT')
            <div class="organisation-profile-form-heading">
                <div><span>Organisation</span><h2>{{ $organisation->name }}</h2></div>
                <button type="submit">Save profile</button>
            </div>

            <section class="organisation-profile-card">
                <div class="organisation-profile-section-title"><h3>Company details</h3><p>Shown on public preference pages and in newsletter footers.</p></div>
                <div class="organisation-profile-grid">
                    <label>Company name<input name="name" required maxlength="255" value="{{ old('name', $organisation->name) }}"></label>
                    <label>Website URL<input name="website_url" type="url" placeholder="https://example.org" value="{{ old('website_url', $organisation->website_url) }}"></label>
                </div>
                <div class="organisation-profile-addresses" data-address-editor>
                    <div class="organisation-profile-address-heading"><div><h4>Addresses</h4><p>Add a location label and its postal address for each office.</p></div><button type="button" data-add-address>Add address</button></div>
                    <div data-address-rows>
                        @foreach(old('addresses', $organisation->publicAddresses()) ?: [['label' => '', 'address' => '']] as $index => $office)
                            <div class="organisation-profile-address-row" data-address-row>
                                <label>Label<input name="addresses[{{ $index }}][label]" maxlength="100" placeholder="Nigeria" value="{{ $office['label'] ?? '' }}"></label>
                                <label>Address<textarea name="addresses[{{ $index }}][address]" rows="2" maxlength="1000" placeholder="22 Example Street, Lagos">{{ $office['address'] ?? '' }}</textarea></label>
                                <button type="button" class="organisation-profile-remove" data-remove-address aria-label="Remove address">Remove</button>
                            </div>
                        @endforeach
                    </div>
                    <div hidden data-address-prototype><div class="organisation-profile-address-row" data-address-row>
                        <label>Label<input name="addresses[__INDEX__][label]" maxlength="100" placeholder="UK"></label>
                        <label>Address<textarea name="addresses[__INDEX__][address]" rows="2" maxlength="1000" placeholder="34 Example Road, London"></textarea></label>
                        <button type="button" class="organisation-profile-remove" data-remove-address aria-label="Remove address">Remove</button>
                    </div></div>
                </div>
                <div class="organisation-profile-grid organisation-profile-social">
                    @foreach(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'x' => 'X', 'youtube' => 'YouTube'] as $key => $label)
                        <label>{{ $label }}<input name="social_links[{{ $key }}]" type="url" placeholder="https://" value="{{ old('social_links.'.$key, $organisation->social_links[$key] ?? '') }}"></label>
                    @endforeach
                </div>
            </section>

            <section class="organisation-profile-card">
                <div class="organisation-profile-section-title"><h3>Visual identity</h3><p>Upload a logo and banner. The two colours style subscriber-facing pages.</p></div>
                <div class="organisation-profile-grid">
                    <label>Logo <small>PNG, JPG or WebP, up to 2 MB</small><input name="logo" type="file" accept="image/png,image/jpeg,image/webp"></label>
                    <label>Default banner <small>PNG, JPG or WebP, up to 4 MB</small><input name="banner" type="file" accept="image/png,image/jpeg,image/webp"></label>
                    @if($organisation->logo_path)<img class="organisation-profile-preview" src="{{ Storage::disk('public')->url($organisation->logo_path) }}" alt="Current {{ $organisation->name }} logo">@endif
                    @if($organisation->banner_path)<img class="organisation-profile-preview organisation-profile-banner" src="{{ Storage::disk('public')->url($organisation->banner_path) }}" alt="Current {{ $organisation->name }} banner">@endif
                    <label>Primary colour<input name="brand_primary_color" type="text" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#14213D" value="{{ old('brand_primary_color', $organisation->brand_primary_color) }}"></label>
                    <label>Secondary colour<input name="brand_secondary_color" type="text" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#315CE8" value="{{ old('brand_secondary_color', $organisation->brand_secondary_color) }}"></label>
                </div>
            </section>

            <section class="organisation-profile-card">
                <div class="organisation-profile-section-title"><h3>Public preferences</h3><p>Choose which product audience groups visitors may newly select. Existing subscriptions remain visible for removal even if you hide a group.</p></div>
                <div class="organisation-profile-group-list">
                    @forelse($organisation->subscriberGroups as $group)
                        <label><input type="checkbox" name="public_group_ids[]" value="{{ $group->id }}" @checked(in_array($group->id, array_map('intval', old('public_group_ids', $organisation->subscriberGroups->where('is_public_preference', true)->pluck('id')->all()))))>
                            <span><strong>{{ $group->name }}</strong><small>{{ $group->product?->name }}</small></span></label>
                    @empty
                        <p>No active product audience groups are available yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="organisation-profile-card">
                <div class="organisation-profile-section-title"><h3>Unsubscribe message</h3><p>Shown before someone saves changed preferences. It does not change consent or automatically unsubscribe them.</p></div>
                <label>Message<textarea name="unsubscribe_message" rows="4" maxlength="2000" placeholder="You can choose which updates to keep receiving, or uncheck everything to leave all our newsletters.">{{ old('unsubscribe_message', $organisation->unsubscribe_message) }}</textarea></label>
            </section>
            <div class="organisation-profile-bottom"><button type="submit">Save profile</button></div>
        </form>
    @empty
        <div class="organisation-profile-card">No active organisation is assigned to your account.</div>
    @endforelse
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('click', function(event) {
    var add = event.target.closest('[data-add-address]');
    if (add) {
        var editor = add.closest('[data-address-editor]');
        var rows = editor.querySelector('[data-address-rows]');
        if (rows.querySelectorAll('[data-address-row]').length >= 10) return;
        var nextIndex = Math.max(0, ...Array.from(rows.querySelectorAll('input[name^="addresses["]')).map(function(input) {
            return Number(input.name.match(/^addresses\[(\d+)\]/)?.[1] || 0) + 1;
        }));
        var row = editor.querySelector('[data-address-prototype] [data-address-row]').cloneNode(true);
        row.querySelectorAll('[name]').forEach(function(input) { input.name = input.name.replace('__INDEX__', nextIndex); });
        rows.appendChild(row);
        row.querySelector('input').focus();
    }
    var remove = event.target.closest('[data-remove-address]');
    if (remove) remove.closest('[data-address-row]').remove();
});
</script>
@endsection
