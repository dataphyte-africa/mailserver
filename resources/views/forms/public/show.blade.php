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
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { line-height: 1.5; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, textarea, select { width: 100%; border: 1px solid #cdd5e1; border-radius: 10px; padding: 12px 14px; font: inherit; box-sizing: border-box; }
        textarea { min-height: 130px; resize: vertical; }
        .field { margin-bottom: 20px; }
        .help { color: #556276; font-size: 0.9rem; margin-top: 6px; }
        .meta { color: #556276; font-size: 0.92rem; margin-top: 18px; }
        .success { background: #ecfdf3; border: 1px solid #86efac; color: #166534; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; }
        button { background: #0f4c81; color: #fff; border: 0; border-radius: 999px; padding: 14px 22px; font: inherit; cursor: pointer; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
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
                        <label for="field-{{ $field['handle'] }}">
                            {{ $field['label'] }}
                            @if($field['required']) * @endif
                        </label>

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

                <button type="submit">Submit</button>
            </form>

            <div class="meta">
                Hosted URL: {{ $hostedPageUrl }}<br>
                Submit endpoint: {{ $submitUrl }}
            </div>
        </div>
    </div>
</body>
</html>
