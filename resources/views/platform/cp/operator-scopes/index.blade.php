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
                <fieldset>
                    <legend>Organisations</legend>
                    <p class="operator-scopes-help">Selecting one includes every active product beneath it. No product checkbox is needed.</p>
                    @foreach($organisations as $organisation)
                        <label><input type="checkbox" name="organisation_ids[]" value="{{ $organisation->id }}" @checked(in_array($organisation->id, $selectedOrganisations))> <span>{{ $organisation->name }} <small>{{ $products->where('organisation_id', $organisation->id)->count() }} active products included</small></span></label>
                    @endforeach
                </fieldset>
                <fieldset>
                    <legend>Additional individual products</legend>
                    <p class="operator-scopes-help">Only select products from organisations not selected on the left. These are extra direct grants.</p>
                    @foreach($organisations as $organisation)
                        @php $organisationProducts = $products->where('organisation_id', $organisation->id); @endphp
                        @if($organisationProducts->isNotEmpty())
                            <div class="operator-scopes-product-group" data-products-for-organisation="{{ $organisation->id }}">
                                <h3>{{ $organisation->name }}</h3>
                                @foreach($organisationProducts as $product)
                                    <label><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selectedProducts))> {{ $product->name }}</label>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    <p class="operator-scopes-inherited" hidden>Products in selected organisations are already included and are hidden here.</p>
                </fieldset>
            </div>
        </form>
    @empty
        <div class="operator-scopes-card">No non-super Statamic users have been created yet.</div>
    @endforelse
</div>
<script>
document.querySelectorAll('form.operator-scopes-card').forEach((form) => {
    const syncProducts = () => {
        const selected = new Set(Array.from(form.querySelectorAll('input[name="organisation_ids[]"]:checked'), (input) => input.value);
        let inherited = false;
        form.querySelectorAll('[data-products-for-organisation]').forEach((group) => {
            const included = selected.has(group.dataset.productsForOrganisation);
            group.hidden = included;
            group.querySelectorAll('input[name="product_ids[]"]').forEach((input) => {
                if (included) input.checked = false;
                input.disabled = included;
            });
            inherited ||= included;
        });
        form.querySelector('.operator-scopes-inherited').hidden = !inherited;
    };
    form.querySelectorAll('input[name="organisation_ids[]"]').forEach((input) => input.addEventListener('change', syncProducts));
    syncProducts();
});
</script>
@endsection
