<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Preferences saved · {{ $organisation->name }}</title><link rel="stylesheet" href="{{ asset('css/newsletter-preferences.css') }}"></head>
@php
    $primary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $organisation->brand_primary_color) ? $organisation->brand_primary_color : '#14213D';
    $secondary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $organisation->brand_secondary_color) ? $organisation->brand_secondary_color : '#315CE8';
@endphp
<body style="--brand-primary:{{ $primary }};--brand-secondary:{{ $secondary }}"><main class="preference-shell preference-result"><div class="preference-brand">@if($organisation->logo_path)<img src="{{ Storage::disk('public')->url($organisation->logo_path) }}" alt="{{ $organisation->name }} logo">@endif<span>{{ $organisation->name }}</span></div><div class="preference-result-content"><p class="preference-kicker">Saved</p><h1>Preferences saved</h1><p>Your newsletter choices for <strong>{{ $subscriber->email }}</strong> have been updated.</p><a href="{{ $preferencesUrl }}">Review preferences</a></div></main></body></html>
