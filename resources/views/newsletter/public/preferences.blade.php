<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'unsubscribe' ? 'Manage or unsubscribe' : 'Email preferences' }} · {{ $organisation->name }}</title>
    @include('partials.google-tag')
    <link rel="stylesheet" href="{{ asset('css/newsletter-preferences.css') }}">
    @php
        $primary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $organisation->brand_primary_color) ? $organisation->brand_primary_color : '#14213D';
        $secondary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $organisation->brand_secondary_color) ? $organisation->brand_secondary_color : '#315CE8';
    @endphp
</head>
<body style="--brand-primary:{{ $primary }};--brand-secondary:{{ $secondary }}">
<main class="preference-shell">
    <div class="preference-brand">
        @if($organisation->logo_path)<img src="{{ Storage::disk('public')->url($organisation->logo_path) }}" alt="{{ $organisation->name }} logo">@endif
        <span>{{ $organisation->name }}</span>
    </div>
    @if($organisation->banner_path)<img class="preference-banner" src="{{ Storage::disk('public')->url($organisation->banner_path) }}" alt="">@endif
    <div class="preference-content">
        <p class="preference-kicker">Your inbox, your choice</p>
        <h1>{{ $mode === 'unsubscribe' ? 'Choose what to keep' : 'Email preferences' }}</h1>
        <p class="preference-intro">Manage newsletters for <strong>{{ $subscriber->email }}</strong> from {{ $organisation->name }}.</p>
        @if($mode === 'unsubscribe')
            <p class="preference-callout">{{ $organisation->unsubscribe_message ?: 'Uncheck any updates you no longer want. Uncheck all to leave every newsletter from this organisation.' }}</p>
        @endif

        <form id="preference-form" method="post" action="{{ $formUrl }}">
            @csrf
            <div class="preference-toolbar"><h2>Product newsletters</h2><button type="button" id="uncheck-all">Uncheck all products</button></div>
            @forelse($groups as $group)
                <fieldset class="preference-group">
                    <legend>{{ $group->product?->name ?? $group->name }}</legend>
                    @if($group->description)<p>{{ $group->description }}</p>@endif
                    @if($group->subGroups->count() > 1)
                        <label class="preference-choice">
                            <input type="radio" name="sub_groups[{{ $group->id }}]" value="" @checked($group->subGroups->pluck('id')->intersect($activeSubGroupIds)->isEmpty())>
                            <span>Do not receive {{ $group->product?->name ?? $group->name }} emails</span>
                        </label>
                    @endif
                    @foreach($group->subGroups as $subGroup)
                        <label class="preference-choice">
                            <input type="{{ $group->subGroups->count() > 1 ? 'radio' : 'checkbox' }}" name="{{ $group->subGroups->count() > 1 ? 'sub_groups['.$group->id.']' : 'sub_groups[]' }}" value="{{ $subGroup->id }}" @checked(in_array((int) $subGroup->id, $activeSubGroupIds, true))>
                            <span>{{ $group->subGroups->count() > 1 ? $subGroup->name : 'Receive '.($group->product?->name ?? $group->name).' emails' }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @empty
                <p class="preference-empty">There are no current or public newsletter choices for this organisation.</p>
            @endforelse
            <p class="preference-note">Unchecking every product choice stops public product newsletters. It does not change private audiences.</p>
            <fieldset class="preference-group">
                <legend>Stop all non-essential emails</legend>
                <label class="preference-choice">
                    <input type="checkbox" name="organisation_opt_out" value="1" @checked($organisationOptedOut)>
                    <span>I do not want to receive any newsletters, promotional emails, or product updates from {{ $organisation->name }}.</span>
                </label>
                <p>Essential messages such as application receipts are unaffected.</p>
            </fieldset>
            <fieldset class="preference-group reader-profile">
                <legend>Reader details</legend>
                <p>Optional. Update the details associated with this email address.</p>
                <label>First name <input type="text" name="first_name" value="{{ old('first_name', $subscriber->first_name) }}" autocomplete="given-name"></label>
                <label>Last name <input type="text" name="last_name" value="{{ old('last_name', $subscriber->last_name) }}" autocomplete="family-name"></label>
                <label>Country <select name="country">
                    <option value="">Select a country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->code }}" @selected(old('country', $readerProfile['country'] ?? '') === $country->code)>{{ $country->name }}</option>
                    @endforeach
                </select></label>
                <label>Organisation <input type="text" name="reader_organisation" value="{{ old('reader_organisation', $readerProfile['organisation'] ?? '') }}"></label>
                <label>Profession <input type="text" name="profession" value="{{ old('profession', $readerProfile['profession'] ?? '') }}"></label>
                <label>Gender <select name="gender">
                    <option value="">Select an option</option>
                    @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $readerProfile['gender'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
            </fieldset>
            <button class="preference-submit" type="submit">Save my choices</button>
            @if($mode !== 'unsubscribe')<a class="preference-website" href="{{ $unsubscribeUrl }}">Manage or unsubscribe</a>@endif
        </form>
        @if($organisation->website_url)<a class="preference-website" href="{{ $organisation->website_url }}" rel="noopener noreferrer">Visit {{ $organisation->name }}</a>@endif
        @foreach($organisation->publicAddresses() as $office)
            <address><strong>{{ $office['label'] }}</strong><br>{{ $office['address'] }}</address>
        @endforeach
        @if($organisation->social_links)
            <nav class="preference-social" aria-label="Organisation social links">
                @foreach($organisation->social_links as $platform => $url)
                    @if(in_array($platform, ['facebook','instagram','linkedin','x','youtube'], true) && str_starts_with($url, 'https://'))<a href="{{ $url }}" rel="noopener noreferrer">{{ ucfirst($platform) }}</a>@endif
                @endforeach
            </nav>
        @endif
    </div>
</main>
<script>document.getElementById('uncheck-all').addEventListener('click',function(){document.querySelectorAll('#preference-form input[name^="sub_groups"]').forEach(function(input){input.checked=false;});});</script>
</body>
</html>
