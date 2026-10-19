<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Choose an organisation</title><link rel="stylesheet" href="{{ asset('css/newsletter-preferences.css') }}"></head>
<body><main class="preference-shell preference-result"><div class="preference-result-content"><p class="preference-kicker">Email preferences</p><h1>Choose an organisation</h1>
@if($organisations->isEmpty())
    <p>No organisation subscriptions remain for <strong>{{ $subscriber->email }}</strong>.</p>
@else
    <p>Select whose newsletters you want to manage for <strong>{{ $subscriber->email }}</strong>.</p>
    <nav class="preference-organisation-list" aria-label="Organisations">
        @foreach($organisations as $organisation)
            <a href="{{ URL::signedRoute($routeName, ['token' => $subscriber->confirmation_token, 'organisation' => $organisation->id]) }}">{{ $organisation->name }} &rarr;</a>
        @endforeach
    </nav>
@endif
</div></main></body></html>
