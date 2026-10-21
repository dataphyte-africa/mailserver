@extends('statamic::layout')
@section('title', 'Operator Access')

@section('content')
<div class="operator-scopes-page">
    <header class="operator-scopes-header">
        <h1>Operator Access</h1>
        <p>Select an organisation for access to all its active products. Use individual products only for additional, product-specific access outside selected organisations.</p>
    </header>

    @if(session('success'))
        <div class="operator-scopes-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="operator-scopes-error" role="alert">{{ $errors->first() }}</div>
    @endif

    @forelse($users as $user)
        @php
            $identity = $identities->get($user->id());
            $selectedOrganisations = $identity?->organisationScopes->where('status', 'active')->pluck('organisation_id')->all() ?? [];
            $selectedProducts = $identity?->productScopes->where('status', 'active')->pluck('product_id')->all() ?? [];
        @endphp
        <form class="operator-scopes-card" method="POST" action="{{ cp_route('operator-scopes.update', $user->id()) }}">
            @csrf @method('PUT')
            <div class="operator-scopes-title">
                <div><h2>{{ $user->name() ?: $user->email() }}</h2><p>{{ $user->email() }}</p></div>
                <button type="submit">Save access</button>
            </div>
            @unless($user->can('access cp'))
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
                <fieldset class="operator-scopes-panel">
                    <legend>Additional product access</legend>
                    <p class="operator-scopes-help">Choose individual products only where the organisation is not selected.</p>
                    @foreach($organisations as $organisation)
                        @php
                            $organisationProducts = $products->where('organisation_id', $organisation->id);
                            $organisationSelected = in_array($organisation->id, $selectedOrganisations);
                        @endphp
                        @if($organisationProducts->isNotEmpty())
                            <div class="operator-scopes-product-group" data-products-for-organisation="{{ $organisation->id }}" @if($organisationSelected) hidden @endif>
                                <h3>{{ $organisation->name }} <span>{{ $organisationProducts->count() }} products</span></h3>
                                <div class="operator-scopes-product-list">
                                    @foreach($organisationProducts as $product)
                                        <label><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(! $organisationSelected && in_array($product->id, $selectedProducts)) @disabled($organisationSelected)> <span>{{ $product->name }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    <p class="operator-scopes-inherited" @if($selectedOrganisations === []) hidden @endif>Products covered by organisation access are hidden from this list.</p>
                </fieldset>
            </div>
        </form>
    @empty
        <div class="operator-scopes-card">No non-super Statamic users have been created yet.</div>
    @endforelse
</div>
@endsection
