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
        .preference-actions { display: flex; align-items: center; gap: 12px; }
        .preference-select-all { border: 0; background: none; color: #235d8f; font: 700 12px "Avenir Next", "Trebuchet MS", sans-serif; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; }
        .preference-select-all:focus-visible { outline: 2px solid #3886ae; outline-offset: 3px; }
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
        .signup-email { margin-top: 18px; padding: 22px 24px; background: #fff; border-radius: 13px; }
        .manage-existing { margin-top: 10px; padding: 0; border: 0; background: none; color: #176890; font: 700 13px "Avenir Next", "Trebuchet MS", sans-serif; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; }
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
        .subscription-theme { --ink: #102e50; --muted: #526a80; --line: #cbdfe9; --field: #f0f8fb; --mint: #d9f2f0; --teal: #08736f; --page: #edf6fa; background: radial-gradient(circle at 12% 10%, #d9eef8 0, transparent 34%), radial-gradient(circle at 92% 58%, #d8f1ed 0, transparent 35%), #edf6fa; }
        .subscription-theme .topbar { background: #fff; border-bottom: 3px solid #3184ab; box-shadow: 0 3px 22px rgba(31, 91, 126, .08); }
        .subscription-theme main { margin-top: 30px; }
        .subscription-theme .intro { padding: 30px 28px 27px; color: #fff; background: linear-gradient(125deg, #123c67 0%, #246e9b 60%, #318b9c 100%); box-shadow: 0 18px 38px rgba(18, 60, 103, .17); }
        .subscription-theme .intro::before { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(135deg, transparent 0 44px, rgba(255,255,255,.055) 44px 45px); pointer-events: none; }
        .subscription-theme .intro::after { width: 210px; height: 210px; top: -95px; right: -70px; background: rgba(194, 238, 240, .14); border: 1px solid rgba(255,255,255,.18); }
        .subscription-theme .intro h1 { max-width: 440px; font-size: clamp(29px, 5vw, 36px); }
        .subscription-theme .intro p { color: #e4f3f8; }
        .subscription-theme .intro-note { background: rgba(255,255,255,.13); color: #fff; border: 1px solid rgba(255,255,255,.23); letter-spacing: 0; }
        .subscription-theme .signup-email, .subscription-theme .details { border: 1px solid #d8e8ee; box-shadow: 0 10px 30px rgba(25, 85, 115, .075); }
        .subscription-theme .signup-email { border-top: 4px solid #3170a9; }
        .subscription-theme .section-heading h2 { color: #123c67; }
        .subscription-theme .preference-select-all, .subscription-theme .privacy-note a, .subscription-theme footer a { color: #176890; }
        .subscription-theme .preference-content { border-color: #cfe2e9; box-shadow: 0 7px 20px rgba(25, 85, 115, .065); }
        .subscription-theme .preference:hover .preference-content { border-color: #388caf; }
        .subscription-theme .preference input:checked + .preference-content { background: linear-gradient(110deg, #e1f7f3, #e7f5fc); border-color: #69b5b1; box-shadow: inset 4px 0 0 #08736f, 0 8px 22px rgba(8, 115, 111, .11); }
        .subscription-theme .preference-icon { background: #e4f0f8; color: #3170a9; }
        .subscription-theme .preference input:checked + .preference-content .preference-icon { background: #c9ebe8; color: #08736f; }
        .subscription-theme .preference-mark { background: #e4f0f8; color: #3170a9; }
        .subscription-theme .field input:not([type="checkbox"]), .subscription-theme .field textarea, .subscription-theme .field select { border-color: #cbdfe9; }
        .subscription-theme .submit { background: linear-gradient(100deg, #123c67, #246e9b); box-shadow: 0 8px 18px rgba(18, 60, 103, .18); }
        .subscription-theme .submit:hover { background: linear-gradient(100deg, #0d3054, #1c5d85); }
        .subscription-theme footer { color: #57768a; }
        @media (max-width: 560px) { .topbar { min-height: 54px; padding-block: 6px; } .brand img { max-height: 40px; } main { width: min(100% - 24px, 590px); margin-top: 16px; } .intro, .details { padding: 21px 18px; } .preference-content { grid-template-columns: 39px minmax(0, 1fr) 26px; gap: 9px; padding: 15px 12px; } .preference-icon { width: 36px; height: 36px; } .fields { grid-template-columns: 1fr; gap: 15px; } .field--wide { grid-column: auto; } .section-heading { margin-inline: 1px; } }
        @media (max-width: 560px) { .subscription-theme main { margin-top: 16px; } .subscription-theme .intro { padding: 25px 20px; } }
        @media (prefers-reduced-motion: reduce) { .preference-content { transition: none; } }
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" media="print" onload="this.media='all'">
</head>
<body class="subscription-theme">
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
        @if(session('subscription_profile_saved'))
            <div class="success" role="status">{{ session('subscription_profile_saved') }}</div>
        @endif

        <div class="intro">
            <h1>{{ $isAcademy ? ($form->headline ?: 'Keep learning with Dataphyte Academy') : ($isInsight ? ($form->headline ?: 'Explore the stories behind the data') : ($isPolicyPoint ? ($form->headline ?: 'Stay informed with Policy Point') : ($form->headline === 'Subscribe to Dataphyte Foundation' ? 'Stay Ahead with Rigorous Insights' : ($form->headline ?: 'Stay Ahead with Rigorous Insights')))) }}</h1>
            <p>{{ $isAcademy ? ($form->description ?: 'Choose the Academy learning updates you want to receive.') : ($isInsight ? ($form->description ?: 'Choose the Insight newsletters and analysis you want to receive.') : ($isPolicyPoint ? ($form->description ?: 'Choose how often you want to receive Policy Point analysis and updates.') : ($form->description === 'Choose the newsletters you want to receive.' ? 'Choose the Foundation newsletters and updates that matter to you.' : $form->description))) }}</p>
            <div class="intro-note">Choose your updates below. You can change your preferences or unsubscribe using a link in any email.</div>
        </div>

        <form method="POST" action="{{ $submitUrl }}">
            @csrf
            @php $emailField = collect($form->field_definitions ?? [])->firstWhere('handle', 'email'); @endphp
            @if($emailField)
                <div class="signup-email field">
                    <label for="field-email">{{ $emailField['label'] }} <span class="required">*</span></label>
                    <input id="field-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="jane.doe@example.org" required>
                    <small class="field-help">We send updates only for the newsletters you choose.</small>
                    <button class="manage-existing" type="submit" name="action" value="manage_existing" formnovalidate>Already subscribed? Email me my preferences</button>
                    @error('email')<span class="error" role="alert">{{ $message }}</span>@enderror
                </div>
            @endif
            <div class="section-heading">
                <h2 id="newsletter-choices">{{ $isPolicyPoint ? 'Delivery frequency' : 'Select Newsletters' }} <span class="required">*</span></h2>
                <span class="preference-actions"><small>{{ $isPolicyPoint ? 'Choose one' : 'Choose at least one' }}</small>@if($publicPreferences->sum(fn ($group) => $group->subGroups->count() === 1 ? 1 : 0) > 1)@include('forms.public._preference-select-all')@endif</span>
            </div>
            <fieldset class="preferences" aria-labelledby="newsletter-choices" data-preference-choices>
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

            <section class="details">
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
    @include('forms.public._subscription-profile-modal')
</body>
</html>
