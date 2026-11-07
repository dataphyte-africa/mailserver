<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $form->headline ?: $form->name }}</title>
    <style>
        :root { color-scheme: light; --ink: #091e41; --muted: #536078; --line: #dbe3f2; --field: #eef2ff; --surface: #fff; --mint: #dff5f7; --teal: #096f67; --page: #f7f8fe; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--page); color: var(--ink); font: 15px/1.5 "Avenir Next", "Trebuchet MS", sans-serif; }
        .topbar { min-height: 58px; background: rgba(255,255,255,.68); border-bottom: 1px solid #edf0fa; display: flex; align-items: center; justify-content: center; padding: 7px 20px; }
        .brand { display: inline-flex; align-items: center; justify-content: center; color: var(--ink); font-size: 16px; font-weight: 700; text-align: center; }
        .brand img { display: block; width: auto; max-width: min(260px, 80vw); max-height: 44px; object-fit: contain; }
        main { width: min(100% - 32px, 590px); margin: 28px auto 68px; }
        .intro, .details { background: var(--surface); border-radius: 13px; box-shadow: 0 8px 28px rgba(21, 38, 86, .035); }
        .intro { padding: 23px 24px 20px; position: relative; overflow: hidden; }
        .intro::after { content: ""; width: 150px; height: 150px; border-radius: 50%; background: #f2f6fa; position: absolute; right: -54px; top: -84px; pointer-events: none; }
        h1 { margin: 0 0 4px; font-size: clamp(25px, 4vw, 29px); line-height: 1.22; letter-spacing: -.035em; position: relative; z-index: 1; }
        .intro p { color: #4e596b; margin: 0; font-size: 15px; position: relative; z-index: 1; }
        .intro-note { margin-top: 19px; padding: 11px 13px; border-radius: 6px; background: #eef2ff; color: #39465e; font-size: 12px; letter-spacing: .02em; }
        .section-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 15px; margin: 21px 3px 9px; }
        .section-heading h2 { margin: 0; font-size: 15px; font-weight: 700; }
        .section-heading small { color: #59647a; font-size: 12px; }
        .required { color: #bb323b; }
        .preferences { border: 0; margin: 0; padding: 0; display: grid; gap: 12px; min-width: 0; }
        .preference { display: block; position: relative; cursor: pointer; }
        .preference input { position: absolute; width: 1px; height: 1px; opacity: 0; }
        .preference-content { display: grid; grid-template-columns: 46px minmax(0, 1fr) 27px; gap: 12px; align-items: start; min-height: 104px; padding: 18px 16px; background: #fff; border: 1px solid #e5eafa; border-radius: 10px; box-shadow: 0 5px 16px rgba(24, 42, 94, .04); transition: background .18s, border-color .18s, transform .18s; }
        .preference:hover .preference-content { transform: translateY(-2px); border-color: #b9d4db; }
        .preference input:focus-visible + .preference-content { outline: 3px solid #3886ae; outline-offset: 2px; }
        .preference input:checked + .preference-content { background: var(--mint); border-color: #bce9e5; }
        .preference-icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 7px; background: #e9eeff; color: #44617b; }
        .preference input:checked + .preference-content .preference-icon { background: #d9f0ee; color: var(--teal); }
        .symbol { font-family: "Material Symbols Outlined"; font-weight: normal; font-style: normal; font-size: 23px; line-height: 1; letter-spacing: normal; white-space: nowrap; direction: ltr; font-feature-settings: "liga"; -webkit-font-smoothing: antialiased; }
        .preference-copy { min-width: 0; }
        .preference-kicker { display: block; color: #60718e; text-transform: uppercase; font-size: 10px; letter-spacing: .08em; font-weight: 700; margin-bottom: 3px; }
        .preference-name { display: block; font-size: 18px; line-height: 1.25; font-weight: 700; letter-spacing: -.025em; }
        .preference-description { display: block; margin-top: 5px; color: #5c6980; font-size: 13px; line-height: 1.35; }
        .preference-mark { width: 26px; height: 26px; border-radius: 50%; background: #e9eeff; color: #6d7b95; display: grid; place-items: center; font-size: 19px; line-height: 1; }
        .preference-mark::before { content: "+"; }
        .preference input:checked + .preference-content .preference-mark { background: var(--teal); color: white; font-size: 15px; }
        .preference input:checked + .preference-content .preference-mark::before { content: "✓"; }
        .details { margin-top: 18px; padding: 26px 24px 28px; }
        .details-title { margin: 0 0 21px; font-size: 17px; letter-spacing: -.02em; }
        .fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 18px; }
        .field { min-width: 0; }
        .field--wide { grid-column: 1 / -1; }
        .field label, .country-picker label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 6px; }
        .field input:not([type="checkbox"]), .field textarea, .field select, .country-picker select, .country-picker__search { width: 100%; min-height: 51px; padding: 12px 14px; border: 1px solid #e8edf9; border-radius: 5px; background: var(--field); color: var(--ink); font: inherit; }
        .field textarea { min-height: 110px; resize: vertical; }
        .field input:focus, .field textarea:focus, .field select:focus, .country-picker__search:focus { outline: 2px solid #4a85b0; outline-offset: 1px; }
        .field input::placeholder { color: #8290a7; }
        .field-help { display: block; margin-top: 5px; color: var(--muted); font-size: 12px; }
        .error { display: block; margin-top: 6px; color: #a62031; font-size: 12px; }
        .consent { display: flex; align-items: flex-start; gap: 10px; margin: 25px 0 10px; font-size: 13px; font-weight: 600; }
        .consent input { width: 17px; height: 17px; accent-color: var(--teal); flex: none; margin-top: 2px; }
        .privacy-note { color: var(--muted); font-size: 12px; line-height: 1.5; margin: 0 0 18px; }
        .privacy-note a, footer a { color: #235d8f; }
        .submit { border: 0; background: #071f47; color: #fff; border-radius: 7px; min-height: 51px; width: 100%; padding: 12px 20px; font: 700 15px "Avenir Next", "Trebuchet MS", sans-serif; cursor: pointer; }
        .submit:hover { background: #143d69; }
        .submit:focus-visible { outline: 3px solid #4389bf; outline-offset: 3px; }
        .success { background: #e6f7ed; border: 1px solid #8bc8a5; color: #145c38; border-radius: 8px; padding: 14px 16px; margin: 0 0 16px; }
        .empty { color: var(--muted); background: #fff; border-radius: 9px; padding: 20px; }
        footer { width: min(100% - 32px, 590px); margin: 0 auto 28px; display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; font-size: 12px; color: #758099; }
        @media (max-width: 560px) { .topbar { min-height: 54px; padding-block: 6px; } .brand img { max-height: 40px; } main { width: min(100% - 24px, 590px); margin-top: 16px; } .intro, .details { padding: 21px 18px; } .preference-content { grid-template-columns: 39px minmax(0, 1fr) 26px; gap: 9px; padding: 15px 12px; } .preference-icon { width: 36px; height: 36px; } .fields { grid-template-columns: 1fr; gap: 15px; } .field--wide { grid-column: auto; } .section-heading { margin-inline: 1px; } }
        @media (prefers-reduced-motion: reduce) { .preference-content { transition: none; } }
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" media="print" onload="this.media='all'">
</head>
<body>
    @php
        $identity = $form->organisation;
        $identityImage = $identity?->banner_path ?: $identity?->logo_path;
        $isAcademy = $identity?->primary_collection_handle === 'academy_newsletters';
        $isInsight = $identity?->primary_collection_handle === 'insight_newsletters';
        $isPolicyPoint = $identity?->primary_collection_handle === 'policy_point_newsletters';
    @endphp
    <header class="topbar">
        <div class="brand">
            @if($identityImage)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($identityImage) }}" alt="{{ $identity->name }}">
            @else
                {{ $identity?->name ?? $form->name }}
            @endif
        </div>
    </header>

    <main>
        @if(session('product_form_success'))
            <div class="success" role="status">{{ session('product_form_success') }}</div>
        @endif

        <div class="intro">
            <h1>{{ $isAcademy ? ($form->headline ?: 'Keep learning with Dataphyte Academy') : ($isInsight ? ($form->headline ?: 'Explore the stories behind the data') : ($isPolicyPoint ? ($form->headline ?: 'Stay informed with Policy Point') : ($form->headline === 'Subscribe to Dataphyte Foundation' ? 'Stay Ahead with Rigorous Insights' : ($form->headline ?: 'Stay Ahead with Rigorous Insights')))) }}</h1>
            <p>{{ $isAcademy ? ($form->description ?: 'Choose the Academy learning updates you want to receive.') : ($isInsight ? ($form->description ?: 'Choose the Insight newsletters and analysis you want to receive.') : ($isPolicyPoint ? ($form->description ?: 'Choose how often you want to receive Policy Point analysis and updates.') : ($form->description === 'Choose the newsletters you want to receive.' ? 'Choose the Foundation newsletters and updates that matter to you.' : $form->description))) }}</p>
            <div class="intro-note">Choose your updates below. You can change your preferences or unsubscribe using a link in any email.</div>
        </div>

        <form method="POST" action="{{ $submitUrl }}">
            @csrf
            <div class="section-heading">
                <h2 id="newsletter-choices">{{ $isPolicyPoint ? 'Delivery frequency' : 'Select Newsletters' }} <span class="required">*</span></h2>
                <small>{{ $isPolicyPoint ? 'Choose one' : 'Choose at least one' }}</small>
            </div>
            <fieldset class="preferences" aria-labelledby="newsletter-choices">
                <input type="hidden" name="preferences_present" value="1">
                @forelse($publicPreferences as $group)
                    @foreach($group->subGroups as $subGroup)
                        @php
                            $productName = $group->product?->name ?? $group->name;
                            $isPolicy = strcasecmp($productName, 'Policy Point') === 0;
                            $description = $isAcademy ? match (strtolower($subGroup->name)) {
                                'datalab' => 'Data skills, tools, and learning opportunities from DataLab.',
                                'academy update' => 'News, programmes, and opportunities from Dataphyte Academy.',
                                default => $group->description,
                            } : ($isInsight ? match (strtolower($productName)) {
                                'data dive' => 'Deeper analysis and data-driven reporting.',
                                'pocket science' => 'Science stories made accessible.',
                                'senorrita' => 'Stories from SenorRita.',
                                'marina and maitama' => 'Reporting on life, place, and society.',
                                default => $group->description,
                            } : ($isPolicyPoint ? match (strtolower($subGroup->slug)) {
                                'regular' => 'Receive Policy Point updates as they are published.',
                                'monthly' => 'Receive a monthly Policy Point digest.',
                                default => $group->description,
                            } : match (strtolower($productName)) {
                                'behind the bytes' => 'Foundation processes, activities, and the people behind the work.',
                                'policy point' => 'Evidence-led policy analysis and editorial updates.',
                                'foundation update' => 'News and updates from Dataphyte Foundation.',
                                default => $group->description,
                            }));
                        @endphp
                        <label class="preference">
                            <input type="{{ $group->subGroups->count() > 1 ? 'radio' : 'checkbox' }}" name="{{ $group->subGroups->count() > 1 ? 'preferences['.$group->id.']' : 'preferences[]' }}" value="{{ $subGroup->getKey() }}" aria-label="Subscribe to receive {{ $isAcademy ? $subGroup->name : ($group->subGroups->count() === 1 ? $productName : $productName.' '.$subGroup->name) }}" @checked(in_array((string) $subGroup->getKey(), array_map('strval', old('preferences', [])), true))>
                            <span class="preference-content">
                                <span class="preference-icon symbol" aria-hidden="true">{{ $isAcademy ? 'school' : ($isInsight ? 'analytics' : (($isPolicy || $isPolicyPoint) ? 'account_balance' : 'database')) }}</span>
                                <span class="preference-copy">
                                    <span class="preference-kicker">{{ $isAcademy ? 'Academy learning' : ($isInsight ? 'Dataphyte Insight' : (($isPolicy || $isPolicyPoint) ? 'Public policy' : 'Foundation update')) }}</span>
                                    <span class="preference-name">{{ $group->subGroups->count() === 1 ? $productName : ($isPolicyPoint && $subGroup->slug === 'monthly' ? 'Monthly' : $subGroup->name) }}</span>
                                    @if($description)<span class="preference-description">{{ $description }}</span>@endif
                                </span>
                                <span class="preference-mark" aria-hidden="true"></span>
                            </span>
                        </label>
                    @endforeach
                @empty
                    <p class="empty">No public newsletters are available right now.</p>
                @endforelse
            </fieldset>
            @if(isset($errors) && $errors->has('preferences'))<span class="error" role="alert">{{ $errors->first('preferences') }}</span>@endif

            <section class="details" aria-labelledby="details-heading">
                <h2 class="details-title" id="details-heading">Your details</h2>
                <div class="fields">
                    @foreach($form->field_definitions ?? [] as $field)
                        @continue($field['type'] === 'checkbox')
                        <div class="field {{ in_array($field['handle'], ['first_name', 'last_name'], true) ? '' : 'field--wide' }}">
                            @if($field['type'] === 'country')
                                @include('forms.public.country-picker', [
                                    'fieldId' => 'field-'.$field['handle'], 'fieldName' => $field['handle'],
                                    'label' => $field['label'], 'required' => $field['required'],
                                    'options' => \Illuminate\Support\Facades\DB::table('countries')->orderBy('name')->get()->map(fn ($country): array => ['value' => $country->code, 'label' => $country->name]),
                                    'selected' => old($field['handle']),
                                ])
                            @else
                                <label for="field-{{ $field['handle'] }}">{{ $field['label'] }} @if($field['required'])<span class="required">*</span>@endif</label>
                                @if($field['type'] === 'textarea')
                                    <textarea id="field-{{ $field['handle'] }}" name="{{ $field['handle'] }}" @required($field['required'])>{{ old($field['handle']) }}</textarea>
                                @elseif($field['type'] === 'select')
                                    <select id="field-{{ $field['handle'] }}" name="{{ $field['handle'] }}" @required($field['required'])>
                                        <option value="">Select an option</option>
                                        @foreach($field['options'] ?? [] as $option)
                                            <option value="{{ $option['value'] }}" @selected(old($field['handle']) === $option['value'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="field-{{ $field['handle'] }}" type="{{ $field['type'] === 'email' ? 'email' : 'text' }}" name="{{ $field['handle'] }}" value="{{ old($field['handle']) }}" @required($field['required']) @if($field['handle'] === 'first_name') autocomplete="given-name" placeholder="e.g. Jane" @elseif($field['handle'] === 'last_name') autocomplete="family-name" placeholder="e.g. Doe" @elseif($field['handle'] === 'email') autocomplete="email" placeholder="jane.doe@example.org" @endif>
                                @endif
                            @endif
                            @if($field['handle'] === 'email')<small class="field-help">We send updates only for the newsletters you choose.</small>@endif
                            @if($field['help_text'])<small class="field-help">{{ $field['help_text'] }}</small>@endif
                            @if(isset($errors) && $errors->has($field['handle']))<span class="error" role="alert">{{ $errors->first($field['handle']) }}</span>@endif
                        </div>
                    @endforeach
                </div>

                @foreach($form->field_definitions ?? [] as $field)
                    @if($field['type'] === 'checkbox')
                        <label class="consent" for="field-{{ $field['handle'] }}">
                            <input id="field-{{ $field['handle'] }}" type="checkbox" name="{{ $field['handle'] }}" value="1" @checked((bool) old($field['handle'])) @required($field['required'])>
                            <span>{{ $field['label'] }} @if($field['required'])<span class="required">*</span>@endif</span>
                        </label>
                        @if(isset($errors) && $errors->has($field['handle']))<span class="error" role="alert">{{ $errors->first($field['handle']) }}</span>@endif
                    @endif
                @endforeach
                <p class="privacy-note">Your subscription starts immediately. You can update your choices or unsubscribe using the link in any email.
                    @if($form->settings['privacy_url'] ?? null)<a href="{{ $form->settings['privacy_url'] }}">Privacy policy</a>.@endif
                </p>
                <button class="submit" type="submit">Subscribe to Newsletters</button>
            </section>
        </form>
    </main>

    <footer><span>&copy; {{ now()->year }} {{ $identity?->name ?? 'Dataphyte Foundation' }}</span></footer>
</body>
</html>
