@extends('statamic::layout')
@section('title', 'Working Organisation')

@section('content')
<div class="organisation-profile-page">
    <header class="organisation-profile-heading">
        <p class="organisation-profile-eyebrow">Workspace</p>
        <h1>Working Organisation</h1>
        <p>Choose which organisation to work in. Your delegated access does not change.</p>
    </header>

    <form method="post" action="{{ cp_route('newsletter.organisation-context.update') }}" class="organisation-profile-card organisation-context-form">
        @csrf
        <fieldset class="organisation-context-list">
            <legend>Available organisations</legend>
            @forelse($organisations as $organisation)
                <label class="organisation-context-option">
                    <span><strong>{{ $organisation->name }}</strong><small>{{ (int) $activeOrganisationId === (int) $organisation->id ? 'Current workspace' : 'Available to switch' }}</small></span>
                    <input type="radio" name="organisation_id" value="{{ $organisation->id }}" @checked((int) $activeOrganisationId === (int) $organisation->id)>
                </label>
            @empty
                <p>No active organisation is delegated to your account.</p>
            @endforelse
        </fieldset>
        @if($organisations->isNotEmpty())<button type="submit" class="organisation-context-submit">Use selected organisation</button>@endif
    </form>
</div>
@endsection
