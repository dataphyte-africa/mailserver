@extends('statamic::layout')
@section('title', 'Create Group')

@section('content')
    <div class="mb-6">
        <a href="{{ cp_route('newsletter.groups.index') }}"
           class="text-sm text-gray-500 hover:underline mb-1 block">← Groups</a>
        <h1 class="text-3xl font-bold">Create Group</h1>
    </div>

    <form method="POST" action="{{ cp_route('newsletter.groups.store') }}" class="max-w-lg">
        @csrf
        <div class="card p-6 space-y-5">
            <div>
                <label class="publish-field-label">Group Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="input-text w-full @error('name') border-red-400 @enderror"
                       placeholder="e.g. Culture Newsletter" required>
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="publish-field-label">Product <span class="text-red-500">*</span></label>
                <select name="product_id" class="input-text w-full @error('product_id') border-red-400 @enderror" required>
                    <option value="">Select a product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->getKey() }}" @selected((string) old('product_id') === (string) $product->getKey())>
                            {{ $product->organisation->name }} / {{ $product->name }}
                            ({{ $collectionOptions[$product->primary_collection_handle] }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">The product determines the group's newsletter collection.</p>
                @error('product_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="publish-field-label">Description</label>
                <textarea name="description" rows="2"
                          class="input-text w-full">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="mt-4 flex gap-3">
            <button type="submit" class="btn-primary">Create Group</button>
            <a href="{{ cp_route('newsletter.groups.index') }}" class="btn-default">Cancel</a>
        </div>
    </form>
@endsection
