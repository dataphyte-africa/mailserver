@extends('statamic::layout')

@section('content')
<div class="newsletter-entry-page">
    <div class="newsletter-entry-heading">
        <div><span class="newsletter-entry-kicker">Newsletter studio</span><h1>Create newsletter</h1><p>Select a product to create a newsletter using its blueprint.</p></div>
        <a class="newsletter-entry-button newsletter-entry-button-light" href="{{ cp_route('newsletter.entries.index') }}">All newsletters</a>
    </div>

    @forelse($products->groupBy(fn ($product) => $product->organisation?->name ?? 'Organisation') as $organisation => $organisationProducts)
        <section class="newsletter-entry-section">
            <h2>{{ $organisation }}</h2>
            <div class="newsletter-entry-picker">
                @foreach($organisationProducts as $product)
                    <a class="newsletter-entry-product" href="{{ cp_route('collections.entries.create', [$product->primary_collection_handle, \Statamic\Facades\Site::selected()->handle(), 'blueprint' => $product->blueprint_handle]) }}">
                        <span class="newsletter-entry-product-mark">{{ strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                        <span><strong>{{ $product->name }}</strong><small>{{ $product->primary_collection_handle }}</small></span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="newsletter-entry-empty">No active newsletter product is assigned to your account. Ask a super admin to review Operator Access.</div>
    @endforelse
</div>
@endsection
