<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe to {{ $displayName }}</title>
    <style>
        :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; color: #17233c; background: radial-gradient(circle at 10% 10%, #e3ebff, transparent 32%), #f4f6fa; }
        main { max-width: 700px; margin: 0 auto; padding: clamp(24px, 6vw, 72px) 18px; }
        .card { background: #fff; border: 1px solid #d6deeb; border-radius: 18px; box-shadow: 0 18px 48px #15284812; padding: clamp(24px, 5vw, 44px); }
        .banner { width: 100%; max-height: 260px; object-fit: cover; border-radius: 10px; margin-bottom: 24px; }
        .eyebrow { color: #3158ae; font-size: .75rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        h1 { font-size: clamp(2rem, 5vw, 3rem); line-height: 1.1; margin: 12px 0; }
        p { color: #566681; line-height: 1.6; }
        .field { margin: 24px 0; }
        label, legend { display: block; font-weight: 700; margin-bottom: 8px; }
        input[type=text], input[type=email], select { box-sizing: border-box; width: 100%; padding: 12px 14px; border: 1px solid #aebdd5; border-radius: 8px; font: inherit; }
        fieldset { border: 1px solid #cbd5e1; border-radius: 10px; margin: 24px 0; padding: 18px; }
        fieldset label { display: flex; align-items: center; gap: 10px; font-weight: 400; margin: 10px 0; }
        fieldset input { width: 18px; height: 18px; }
        button { border: 0; border-radius: 8px; background: #142849; color: #fff; cursor: pointer; font: inherit; font-weight: 700; padding: 13px 22px; }
        .notice { border-radius: 8px; padding: 12px 16px; margin: 18px 0; }
        .success { background: #e5f6ea; color: #14532d; }
        .error { background: #fff0f0; color: #9f1239; }
    </style>
</head>
<body>
<main>
    <div class="card">
        @if($organisation?->banner_path)
            <img class="banner" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($organisation->banner_path) }}" alt="{{ $organisation->name }} banner">
        @endif
        <span class="eyebrow">Newsletter subscription</span>
        <h1>Subscribe to {{ $displayName }}</h1>
        <p>Select the newsletters you want to receive. We will email you to confirm your subscription.</p>

        @if(session('legacy_subscribe_success'))
            <div class="notice success" role="status">{{ session('legacy_subscribe_success') }}</div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="notice error" role="alert">Please check your details and select at least one preference.</div>
        @endif

        <form method="post" action="{{ $submitEndpoint }}">
            @csrf
            @foreach($schema['fields'] as $field)
                @if($field['handle'] === $schema['preference_field'])
                    @if($form->fields()->get($field['handle'])?->get('multiple'))
                        <fieldset>
                            <legend>{{ $field['display'] ?: 'Preferences' }} *</legend>
                            @foreach($field['options'] as $option)
                                <label>
                                    <input type="checkbox" name="{{ $field['handle'] }}[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], (array) old($field['handle'], []), true))>
                                    {{ $option['label'] }}
                                </label>
                            @endforeach
                        </fieldset>
                    @else
                        <div class="field">
                            <label for="field-{{ $field['handle'] }}">{{ $field['display'] ?: 'Preference' }} *</label>
                            <select id="field-{{ $field['handle'] }}" name="{{ $field['handle'] }}" required>
                                <option value="">Select a preference</option>
                                @foreach($field['options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old($field['handle']) === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(isset($errors) && $errors->has($field['handle'])) <p class="error notice">{{ $errors->first($field['handle']) }}</p> @endif
                @elseif($field['handle'] === 'country' && $form->handle() === 'insight_subscribe')
                    <div class="field">
                        @include('forms.public.country-picker', [
                            'fieldId' => 'field-country', 'fieldName' => 'country', 'label' => 'Country (optional)',
                            'required' => false, 'options' => $field['options'], 'selected' => old('country'),
                        ])
                        @if(isset($errors) && $errors->has('country')) <p class="error notice">{{ $errors->first('country') }}</p> @endif
                    </div>
                @elseif($field['handle'] === 'gender' && $form->handle() === 'insight_subscribe')
                    <div class="field">
                        <label for="field-gender">Gender (optional)</label>
                        <select id="field-gender" name="gender">
                            <option value="">Select an option</option>
                            @foreach($field['options'] as $option)
                                <option value="{{ $option['value'] }}" @selected(old('gender') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @if(isset($errors) && $errors->has('gender')) <p class="error notice">{{ $errors->first('gender') }}</p> @endif
                    </div>
                @else
                    <div class="field">
                        <label for="field-{{ $field['handle'] }}">{{ $field['display'] }}</label>
                        <input id="field-{{ $field['handle'] }}" type="{{ $field['handle'] === 'email' ? 'email' : 'text' }}" name="{{ $field['handle'] }}" value="{{ old($field['handle']) }}" @required($field['handle'] === 'email' || $field['required'])>
                        @if(isset($errors) && $errors->has($field['handle'])) <p class="error notice">{{ $errors->first($field['handle']) }}</p> @endif
                    </div>
                @endif
            @endforeach
            <button type="submit">Subscribe</button>
        </form>
    </div>
</main>
</body>
</html>
