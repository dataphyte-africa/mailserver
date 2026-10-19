<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $form->headline ?: $form->name }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #f5f7fb; color: #172033; margin: 0; }
        .shell { max-width: 760px; margin: 0 auto; padding: 48px 20px 72px; }
        .card { background: #fff; border-radius: 18px; box-shadow: 0 24px 60px rgba(23, 32, 51, 0.08); padding: 32px; }
        .banner { display: block; width: 100%; max-height: 270px; object-fit: cover; border-radius: 12px; margin-bottom: 24px; }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { line-height: 1.5; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, textarea, select { width: 100%; border: 1px solid #cdd5e1; border-radius: 10px; padding: 12px 14px; font: inherit; box-sizing: border-box; }
        textarea { min-height: 130px; resize: vertical; }
        .field { margin-bottom: 20px; }
        .preferences { border: 1px solid #cdd5e1; border-radius: 12px; padding: 20px; margin: 0 0 24px; }
        .preferences legend { font-weight: 700; padding: 0 8px; }
        .preference-group { padding: 12px 0; border-top: 1px solid #e2e8f0; }
        .preference-group:first-of-type { border-top: 0; }
        .preference-group h2 { font-size: 1rem; margin: 0 0 8px; }
        .preference-option { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-weight: 400; }
        .preference-option input { width: auto; }
        .help { color: #556276; font-size: 0.9rem; margin-top: 6px; }
        .meta { color: #556276; font-size: 0.92rem; margin-top: 18px; }
        .success { background: #ecfdf3; border: 1px solid #86efac; color: #166534; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; }
        button { background: #0f4c81; color: #fff; border: 0; border-radius: 999px; padding: 14px 22px; font: inherit; cursor: pointer; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            @if(($form->settings['show_banner'] ?? true) && $form->organisation?->banner_path)
                <img class="banner" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($form->organisation->banner_path) }}" alt="{{ $form->organisation->name }} banner">
            @endif
            @if(session('product_form_success'))
                <div class="success">{{ session('product_form_success') }}</div>
            @endif

            <h1>{{ $form->headline ?: $form->name }}</h1>
            @if($form->description)
                <p>{{ $form->description }}</p>
            @endif

            <form method="POST" action="{{ $submitUrl }}">
                @csrf
                @foreach($form->field_definitions ?? [] as $field)
                    <div class="field">
                        @if($field['type'] !== 'country')
                            <label for="field-{{ $field['handle'] }}">
                                {{ $field['label'] }}
                                @if($field['required']) * @endif
                            </label>
                        @endif

                        @if($field['type'] === 'textarea')
                            <textarea id="field-{{ $field['handle'] }}" name="{{ $field['handle'] }}" @required($field['required'])>{{ old($field['handle']) }}</textarea>
                        @elseif($field['type'] === 'country')
                            @include('forms.public.country-picker', [
                                'fieldId' => 'field-'.$field['handle'], 'fieldName' => $field['handle'],
                                'label' => $field['label'], 'required' => $field['required'],
                                'options' => \Illuminate\Support\Facades\DB::table('countries')->orderBy('name')->get()->map(fn ($country): array => ['value' => $country->code, 'label' => $country->name]),
                                'selected' => old($field['handle']),
                            ])
                        @elseif($field['type'] === 'select')
                            <select id="field-{{ $field['handle'] }}" name="{{ $field['handle'] }}" @required($field['required'])>
                                <option value="">Select an option</option>
                                @foreach($field['options'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old($field['handle']) === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="field-{{ $field['handle'] }}" type="{{ $field['type'] === 'email' ? 'email' : 'text' }}" name="{{ $field['handle'] }}" value="{{ old($field['handle']) }}" @required($field['required'])>
                        @endif

                        @if($field['help_text'])
                            <div class="help">{{ $field['help_text'] }}</div>
                        @endif

                        @if(isset($errors) && $errors->has($field['handle']))
                            <div class="help" style="color: #b91c1c;">{{ $errors->first($field['handle']) }}</div>
                        @endif
                    </div>
                @endforeach

                @if($form->mode === 'subscription')
                    <fieldset class="preferences">
                        <legend>Your preferences *</legend>
                        <input type="hidden" name="preferences_present" value="1">
                        @forelse($publicPreferences as $group)
                            <div class="preference-group">
                                <h2>{{ $group->name }}</h2>
                                @foreach($group->subGroups as $subGroup)
                                    <label class="preference-option">
                                        <input type="checkbox" name="preferences[]" value="{{ $subGroup->getKey() }}" @checked(in_array((string) $subGroup->getKey(), array_map('strval', old('preferences', [])), true))>
                                        <span>{{ $subGroup->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @empty
                            <p>No public preferences are available for this organisation yet.</p>
                        @endforelse
                        @if(isset($errors) && $errors->has('preferences'))
                            <div class="help" style="color: #b91c1c;">{{ $errors->first('preferences') }}</div>
                        @endif
                    </fieldset>
                @endif

                <button type="submit">Submit</button>
            </form>

            <div class="meta">
                Hosted URL: {{ $hostedPageUrl }}<br>
                Schema endpoint: <a href="{{ $schemaUrl }}">JSON schema</a><br>
                Submit endpoint: {{ $submitUrl }}
            </div>
        </div>
    </div>
</body>
</html>
