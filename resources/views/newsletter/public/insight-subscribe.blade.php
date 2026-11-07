<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe to {{ $displayName }}</title>
    <style>
        :root { color-scheme: light; --ink: #0b2145; --muted: #566681; --line: #dce5f4; --field: #eef2ff; --page: #f7f8fe; --accent: #275f9a; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--page); color: var(--ink); font: 15px/1.5 "Avenir Next", "Trebuchet MS", sans-serif; }
        .topbar { min-height: 58px; padding: 7px 20px; display: flex; align-items: center; justify-content: center; background: #ffffffad; border-bottom: 1px solid #edf0fa; }
        .brand { font-size: 16px; font-weight: 700; text-align: center; }
        .brand img { display: block; max-width: min(260px, 80vw); max-height: 44px; object-fit: contain; }
        main, footer { width: min(100% - 32px, 590px); margin-inline: auto; }
        main { margin-top: 28px; margin-bottom: 68px; }
        .intro, .details { background: #fff; border-radius: 13px; box-shadow: 0 8px 28px #15265609; }
        .intro { padding: 23px 24px 20px; position: relative; overflow: hidden; }
        .intro::after { content: ""; position: absolute; right: -54px; top: -84px; width: 150px; height: 150px; border-radius: 50%; background: #edf4fc; }
        h1 { position: relative; z-index: 1; margin: 0 0 4px; font-size: clamp(25px, 4vw, 29px); line-height: 1.22; letter-spacing: -.035em; }
        .intro p { position: relative; z-index: 1; margin: 0; color: #4e596b; }
        .intro-note { position: relative; z-index: 1; margin-top: 19px; padding: 11px 13px; background: #eef2ff; border-radius: 6px; color: #39465e; font-size: 12px; }
        .section-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 15px; margin: 21px 3px 9px; }
        .section-heading h2 { margin: 0; font-size: 15px; }
        .section-heading small { color: var(--muted); font-size: 12px; }
        .preference-select-all { margin-left: 10px; padding: 0; border: 0; background: none; color: var(--accent); font: 700 12px "Avenir Next", "Trebuchet MS", sans-serif; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
        .preference-select-all:focus-visible { outline: 2px solid #3886ae; outline-offset: 3px; }
        .required { color: #bb323b; }
        .preferences { display: grid; gap: 12px; min-width: 0; margin: 0; padding: 0; border: 0; }
        .preference { position: relative; display: block; cursor: pointer; }
        .preference input { position: absolute; width: 1px; height: 1px; opacity: 0; }
        .preference-content { display: grid; grid-template-columns: 46px minmax(0,1fr) 27px; gap: 12px; align-items: start; min-height: 104px; padding: 18px 16px; background: #fff; border: 1px solid #e5eafa; border-radius: 10px; box-shadow: 0 5px 16px #182a5e0a; transition: background .18s, border-color .18s, transform .18s; }
        .preference:hover .preference-content { transform: translateY(-2px); border-color: #b9d4db; }
        .preference input:focus-visible + .preference-content { outline: 3px solid #3886ae; outline-offset: 2px; }
        .preference input:checked + .preference-content { background: #e5f1fc; border-color: #bed6ef; }
        .preference-icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 7px; background: #e9eeff; color: #44617b; }
        .symbol { font-family: "Material Symbols Outlined"; font-size: 23px; line-height: 1; font-feature-settings: "liga"; }
        .preference-kicker { display: block; margin-bottom: 3px; color: #60718e; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .preference-name { display: block; font-size: 18px; line-height: 1.25; font-weight: 700; letter-spacing: -.025em; }
        .preference-description { display: block; margin-top: 5px; color: #5c6980; font-size: 13px; line-height: 1.35; }
        .preference-mark { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 50%; background: #e9eeff; color: #6d7b95; font-size: 19px; }
        .preference-mark::before { content: "+"; }
        .preference input:checked + .preference-content .preference-mark { background: var(--accent); color: #fff; }
        .preference input:checked + .preference-content .preference-mark::before { content: "✓"; }
        .details { margin-top: 18px; padding: 26px 24px 28px; }
        .details h2 { margin: 0 0 21px; font-size: 17px; }
        .fields { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px 18px; }
        .field { min-width: 0; }
        .field--wide { grid-column: 1 / -1; }
        .field label, .country-picker label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 700; }
        .field input:not([type=checkbox]), .field select, .country-picker select, .country-picker__search { width: 100%; min-height: 51px; padding: 12px 14px; border: 1px solid #e8edf9; border-radius: 5px; background: var(--field); color: var(--ink); font: inherit; }
        .field input:focus, .field select:focus, .country-picker__search:focus { outline: 2px solid #4a85b0; outline-offset: 1px; }
        .field-help, .error { display: block; margin-top: 5px; color: var(--muted); font-size: 12px; }
        .error { color: #a62031; }
        .consent { display: flex; align-items: flex-start; gap: 10px; margin: 25px 0 10px; font-size: 13px; font-weight: 600; }
        .consent input { width: 17px; height: 17px; margin-top: 2px; accent-color: var(--accent); flex: none; }
        .privacy-note { margin: 0 0 18px; color: var(--muted); font-size: 12px; }
        .submit { width: 100%; min-height: 51px; padding: 12px 20px; border: 0; border-radius: 7px; background: #071f47; color: #fff; font: 700 15px "Avenir Next", "Trebuchet MS", sans-serif; cursor: pointer; }
        .submit:hover { background: #143d69; }
        .notice { margin-bottom: 16px; padding: 14px 16px; border-radius: 8px; }
        .success { background: #e6f7ed; color: #145c38; }
        .notice.error { background: #fff0f0; color: #a62031; }
        footer { margin-bottom: 28px; color: #758099; font-size: 12px; }
        @media (max-width: 560px) { .topbar { min-height: 54px; padding-block: 6px; } .brand img { max-height: 40px; } main { width: min(100% - 24px,590px); margin-top: 16px; } .intro, .details { padding: 21px 18px; } .preference-content { grid-template-columns: 39px minmax(0,1fr) 26px; gap: 9px; padding: 15px 12px; } .fields { grid-template-columns: 1fr; } .field--wide { grid-column: auto; } }
        @media (prefers-reduced-motion: reduce) { .preference-content { transition: none; } }
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" media="print" onload="this.media='all'">
</head>
<body>
    <header class="topbar"><div class="brand">
        @if($organisation?->banner_path || $organisation?->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($organisation->banner_path ?: $organisation->logo_path) }}" alt="{{ $displayName }}">
        @else
            {{ $displayName }}
        @endif
    </div></header>
    <main>
        @if(session('legacy_subscribe_success'))<div class="notice success" role="status">{{ session('legacy_subscribe_success') }}</div>@endif
        @if(isset($errors) && $errors->any())<div class="notice error" role="alert">Please check your details and select at least one newsletter.</div>@endif
        <div class="intro">
            <h1>Explore the stories behind the data</h1>
            <p>Choose the Insight newsletters and analysis you want to receive.</p>
            <div class="intro-note">Choose your updates below. You can change your preferences or unsubscribe using a link in any email.</div>
        </div>
        <form method="post" action="{{ $submitEndpoint }}">
            @csrf
            @php $preferenceField = collect($schema['fields'])->firstWhere('handle', $schema['preference_field']); @endphp
            <div class="section-heading"><h2 id="newsletter-choices">Select Newsletters <span class="required">*</span></h2><span><small>Choose at least one</small> @if(count($preferenceField['options'] ?? []) > 1)@include('forms.public._preference-select-all')@endif</span></div>
            <fieldset class="preferences" aria-labelledby="newsletter-choices" data-preference-choices>
                @forelse($preferenceField['options'] ?? [] as $option)
                    <label class="preference">
                        <input type="checkbox" name="{{ $schema['preference_field'] }}[]" value="{{ $option['value'] }}" aria-label="Subscribe to receive {{ $option['label'] }}" @checked(in_array($option['value'], (array) old($schema['preference_field'], []), true))>
                        <span class="preference-content">
                            <span class="preference-icon symbol" aria-hidden="true">{{ $option['value'] === 'data-dive' ? 'analytics' : 'article' }}</span>
                            <span><span class="preference-kicker">Dataphyte Insight</span><span class="preference-name">{{ $option['label'] }}</span><span class="preference-description">{{ match ($option['value']) { 'data-dive' => 'Deeper analysis and data-driven reporting.', 'pocket-science' => 'Science stories made accessible.', 'senorrita' => 'Stories from SenorRita.', 'marina-maitama' => 'Reporting on life, place, and society.', default => 'News and analysis from Dataphyte Insight.' } }}</span></span>
                            <span class="preference-mark" aria-hidden="true"></span>
                        </span>
                    </label>
                @empty
                    <p>No public Insight newsletters are available right now.</p>
                @endforelse
            </fieldset>
            @if(isset($errors) && $errors->has($schema['preference_field']))<span class="error" role="alert">{{ $errors->first($schema['preference_field']) }}</span>@endif
            <section class="details" aria-labelledby="details-heading">
                <h2 id="details-heading">Your details</h2>
                <div class="fields">
                    @foreach($schema['fields'] as $field)
                        @continue(in_array($field['handle'], [$schema['preference_field'], 'consent'], true))
                        @php $handle = $field['handle']; $isName = in_array($handle, ['firstname', 'lastname', 'first_name', 'last_name'], true); @endphp
                        <div class="field {{ $isName ? '' : 'field--wide' }}">
                            @if($handle === 'country')
                                @include('forms.public.country-picker', ['fieldId' => 'field-country', 'fieldName' => 'country', 'label' => 'Country', 'required' => false, 'options' => $field['options'], 'selected' => old('country')])
                            @else
                                <label for="field-{{ $handle }}">{{ match ($handle) { 'firstname', 'first_name' => 'First name', 'lastname', 'last_name' => 'Last name', 'email' => 'Email address', default => $field['display'] ?: ucfirst($handle) } }} @if($field['required'] || $handle === 'email')<span class="required">*</span>@endif</label>
                                @if($field['type'] === 'select')
                                    <select id="field-{{ $handle }}" name="{{ $handle }}" @required($field['required'])><option value="">Select an option</option>@foreach($field['options'] as $option)<option value="{{ $option['value'] }}" @selected(old($handle) === $option['value'])>{{ $option['label'] }}</option>@endforeach</select>
                                @else
                                    <input id="field-{{ $handle }}" type="{{ $handle === 'email' ? 'email' : 'text' }}" name="{{ $handle }}" value="{{ old($handle) }}" @required($field['required'] || $handle === 'email') @if($isName) autocomplete="{{ in_array($handle, ['firstname', 'first_name'], true) ? 'given-name' : 'family-name' }}" @elseif($handle === 'email') autocomplete="email" @endif>
                                @endif
                            @endif
                            @if($handle === 'email')<small class="field-help">We send updates only for the newsletters you choose.</small>@endif
                            @if($field['instructions'])<small class="field-help">{{ $field['instructions'] }}</small>@endif
                            @if(isset($errors) && $errors->has($handle))<span class="error" role="alert">{{ $errors->first($handle) }}</span>@endif
                        </div>
                    @endforeach
                </div>
                @php $consent = collect($schema['fields'])->firstWhere('handle', 'consent'); @endphp
                @if($consent)
                    <label class="consent" for="field-consent"><input id="field-consent" type="checkbox" name="consent" value="1" @checked((bool) old('consent')) required><span>{{ $consent['display'] }} <span class="required">*</span></span></label>
                    @if(isset($errors) && $errors->has('consent'))<span class="error" role="alert">{{ $errors->first('consent') }}</span>@endif
                @endif
                <p class="privacy-note">Your subscription starts immediately. You can update your choices or unsubscribe using the link in any email. @if($schema['privacy_url'] ?? null)<a href="{{ $schema['privacy_url'] }}">Privacy policy</a>.@endif</p>
                <button class="submit" type="submit">Subscribe to Newsletters</button>
            </section>
        </form>
    </main>
    <footer>&copy; {{ now()->year }} {{ $displayName }}</footer>
</body>
</html>
