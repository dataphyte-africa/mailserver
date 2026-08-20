@extends('statamic::layout')
@section('title', 'Create Hosted Form')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">Create Hosted Form</h1>
            <p class="form-platform-description">
                Create a product-owned hosted form for application or data collection.
            </p>
        </div>
        <a href="{{ cp_route('product-forms.index') }}" class="form-platform-button">Back to forms</a>
    </div>

    @if($products->isEmpty())
        <div class="form-platform-card">
            <h2>No active product is available</h2>
            <p class="form-platform-description">
                A hosted form must belong to an active product under an active organisation before it can be created.
            </p>
            <p class="form-platform-description">
                Set up the organisation/product ownership record first, then return here to create the hosted form.
            </p>
        </div>
    @else
        <form method="POST" action="{{ cp_route('product-forms.store') }}" class="form-platform-stack">
            @csrf
            @include('forms.cp._form')

            <div class="form-platform-actions">
                <a href="{{ cp_route('product-forms.index') }}" class="form-platform-button">Cancel</a>
                <button type="submit" class="form-platform-button-primary">Create Form</button>
            </div>
        </form>
    @endif
@endsection
