@extends('statamic::layout')
@section('title', 'Operator Access')

@section('content')
<div class="operator-scopes-page">
    <header class="operator-scopes-header">
        <h1>Operator Access</h1>
        <p>Choose a user, then assign the organisations they may manage. Each assignment includes all active products in that organisation.</p>
    </header>

    @if(session('success'))
        <div class="operator-scopes-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="operator-scopes-error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if($selectedUser)
        <form class="operator-scopes-user-picker" method="GET" action="{{ cp_route('operator-scopes.index') }}">
            <label for="operator-scopes-user">User</label>
            <div class="operator-scopes-user-picker-row">
                <select id="operator-scopes-user" name="user" data-operator-user-select>
                    @foreach($users as $user)
                        <option value="{{ $user->id() }}" @selected($selectedUser->id() === $user->id())>{{ $user->name() ?: $user->email() }} ({{ $user->email() }})</option>
                    @endforeach
                </select>
                <button type="submit">View access</button>
            </div>
        </form>
        @php
            $selectedOrganisations = $errors->any()
                ? array_map('intval', old('organisation_ids', []))
                : ($identity?->organisationScopes->where('status', 'active')->pluck('organisation_id')->all() ?? []);
        @endphp
        <form class="operator-scopes-card" method="POST" action="{{ cp_route('operator-scopes.update', $selectedUser->id()) }}">
            @csrf @method('PUT')
            <div class="operator-scopes-title">
                <div><h2>{{ $selectedUser->name() ?: $selectedUser->email() }}</h2><p>{{ $selectedUser->email() }} · {{ count($selectedOrganisations) }} {{ count($selectedOrganisations) === 1 ? 'organisation' : 'organisations' }} assigned</p></div>
                <button type="submit">Save access</button>
            </div>
            @unless($selectedUser->can('access cp'))
                <p class="operator-scopes-warning">This user cannot sign in to the Control Panel yet. Assign a non-super role with CP access in Users.</p>
            @endunless
            <div class="operator-scopes-columns">
                <fieldset class="operator-scopes-panel">
                    <legend>Organisation access</legend>
                    <p class="operator-scopes-help">Grants access to every active product in the selected organisation.</p>
                    <div class="operator-scopes-organisation-list">
                        @foreach($organisations as $organisation)
                            <label class="operator-scopes-organisation-option">
                                <input type="checkbox" name="organisation_ids[]" value="{{ $organisation->id }}" @checked(in_array($organisation->id, $selectedOrganisations))>
                                <span><strong>{{ $organisation->name }}</strong><small>All {{ $products->where('organisation_id', $organisation->id)->count() }} active products included</small></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        </form>
    @else
        <div class="operator-scopes-card">No non-super Statamic users have been created yet.</div>
    @endif
</div>
@endsection
