@extends('statamic::layout')
@section('title', 'Assign Campaign Product')

@section('content')
<div class="campaign-form-page campaign-ownership-page">
    <div class="campaign-ownership-header">
        <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}" class="campaign-ownership-back">&larr; Campaign</a>
        <div>
            <h1>Assign Campaign Product</h1>
            <p>This legacy campaign must be assigned to one product before it can be edited in the v2 workflow.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="campaign-ownership-alert">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="campaign-ownership-card">
        <div class="campaign-ownership-summary">
            <div>
                <span>Campaign</span>
                <strong>{{ $campaign->name }}</strong>
            </div>
            <div>
                <span>Collection</span>
                <strong>{{ $campaign->collection ?: 'not set' }}</strong>
            </div>
            <div>
                <span>Status</span>
                <strong>{{ str_replace('_', ' ', $campaign->status ?: 'draft') }}</strong>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="campaign-ownership-empty">
                No active product currently owns this campaign collection. Run the product sync or create the collection/blueprint product mapping first.
            </div>
        @else
            <form method="POST" action="{{ cp_route('newsletter.campaigns.assign-product.store', $campaign) }}" class="campaign-ownership-form">
                @csrf
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" class="input-text">
                    <option value="">Select the product this campaign belongs to</option>
                    @foreach($products as $product)
                        <option value="{{ $product->getKey() }}" @selected(old('product_id') == $product->getKey())>
                            {{ $product->organisation?->name }} / {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <p>Only active products mapped to the campaign collection are listed. This assignment is required once; normal edit checks continue after it is saved.</p>

                <button type="submit" class="btn-primary">Assign Product</button>
            </form>
        @endif
    </div>
</div>
@endsection
