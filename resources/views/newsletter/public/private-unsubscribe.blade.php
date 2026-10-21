<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Unsubscribe · {{ $organisation->name }}</title><link rel="stylesheet" href="{{ asset('css/newsletter-preferences.css') }}"></head>
<body><main class="preference-shell preference-result"><div class="preference-brand"><span>{{ $organisation->name }}</span></div><div class="preference-result-content">
    @if($completed)
        <h1>You have left {{ $group->name }}</h1>
        <p>{{ $subscriber->email }} will no longer receive emails sent to this private audience. Your public newsletter choices are unchanged.</p>
    @else
        <h1>Leave {{ $group->name }}?</h1>
        <p>Stop emails sent to this private audience at {{ $subscriber->email }}. Your public newsletter choices will not change.</p>
        <form method="POST" action="{{ $formUrl }}">@csrf<button class="preference-submit" type="submit">Unsubscribe from this audience</button></form>
    @endif
    <a href="{{ $preferencesUrl }}">Review public newsletter preferences</a>
</div></main></body>
</html>
