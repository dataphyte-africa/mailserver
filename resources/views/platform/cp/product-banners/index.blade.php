@extends('statamic::layout')
@section('title', 'Product Banners')

@section('content')
<div class="organisation-profile-page product-banner-page">
    <header class="organisation-profile-heading">
        <p class="organisation-profile-eyebrow">Newsletter identity</p>
        <h1>Product Banners</h1>
        <p>Set a default hero image for each product. Newsletter entries can replace it once; products without a banner use their organisation banner.</p>
    </header>

    @if(session('success')) <p class="organisation-profile-notice">{{ session('success') }}</p> @endif
    @if($errors->any())
        <div class="organisation-profile-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif

    @forelse($products as $product)
        <form method="post" enctype="multipart/form-data" class="organisation-profile-card product-banner-card" action="{{ cp_route('product-banners.update', $product) }}">
            @csrf @method('PUT')
            <div class="organisation-profile-form-heading">
                <div><span>{{ $product->organisation?->name }}</span><h2>{{ $product->name }}</h2></div>
                <button type="submit">Save banner</button>
            </div>
            @php($effectivePath = $product->banner_path ?: $product->organisation?->banner_path)
            @if($effectivePath)
                <div class="product-banner-current">
                    <img src="{{ Storage::disk('public')->url($effectivePath) }}" alt="Current banner for {{ $product->name }}">
                    <p>{{ $product->banner_path ? 'Product banner' : 'Using organisation banner' }}</p>
                </div>
            @else
                <p class="product-banner-empty">No product or organisation banner is configured.</p>
            @endif
            <label>Replace product banner <small>PNG, JPG or WebP, up to 4 MB</small>
                <input name="banner" type="file" accept="image/png,image/jpeg,image/webp">
            </label>
            @if($product->banner_path)
                <label class="product-banner-remove"><input name="remove_banner" type="checkbox" value="1"> Remove product banner and use the organisation default</label>
            @endif
        </form>
    @empty
        <div class="organisation-profile-card">No active products are assigned to your account.</div>
    @endforelse
</div>
@endsection
