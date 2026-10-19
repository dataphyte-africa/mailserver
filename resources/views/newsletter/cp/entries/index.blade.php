@extends('statamic::layout')

@section('content')
<div class="newsletter-entry-page">
    <div class="newsletter-entry-heading">
        <div><span class="newsletter-entry-kicker">Newsletter studio</span><h1>All newsletters</h1><p>Content across the products you can access.</p></div>
        <a class="newsletter-entry-button" href="{{ cp_route('newsletter.entries.create') }}">Create newsletter</a>
    </div>

    <form method="GET" action="{{ cp_route('newsletter.entries.index') }}" class="newsletter-entry-filter">
        <label for="newsletter-product-filter">Product</label>
        <select id="newsletter-product-filter" name="product" class="input-text">
            <option value="">All products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(request('product') == $product->id)>{{ $product->organisation?->name }} / {{ $product->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="newsletter-entry-button">Filter</button>
    </form>

    <div class="newsletter-entry-table-wrap">
        <table class="newsletter-entry-table">
            <thead><tr><th>Newsletter</th><th>Product</th><th>Organisation</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($entries as $entry)
                    @php($product = $pairs->get($entry->collection.'|'.$entry->blueprint))
                    <tr>
                        <td><a href="{{ cp_route('collections.entries.edit', [$entry->collection, $entry->id]) }}">{{ $entry->data['title'] ?? $entry->slug ?? 'Untitled newsletter' }}</a></td>
                        <td>{{ $product?->name ?? 'Unmapped' }}</td>
                        <td>{{ $product?->organisation?->name ?? '—' }}</td>
                        <td>{{ $entry->date?->format('M j, Y') ?? '—' }}</td>
                        <td><span class="newsletter-entry-status">{{ $entry->published ? 'Published' : 'Draft' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="newsletter-entry-empty">No newsletters found for your accessible products.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="newsletter-entry-pagination">{{ $entries->links() }}</div>
</div>
@endsection
