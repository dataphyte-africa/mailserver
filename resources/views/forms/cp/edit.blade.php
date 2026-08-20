@extends('statamic::layout')
@section('title', 'Edit Hosted Form')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">Edit Hosted Form</h1>
            <p class="form-platform-description">
                Update product ownership, fields, embed origins, and submission handling.
            </p>
        </div>
        <a href="{{ cp_route('product-forms.index') }}" class="form-platform-button">Back to forms</a>
    </div>

    <form method="POST" action="{{ cp_route('product-forms.update', $form) }}" class="form-platform-stack">
        @csrf
        @method('PUT')
        @include('forms.cp._form')

        <div class="form-platform-actions">
            <a href="{{ cp_route('product-forms.index') }}" class="form-platform-button">Cancel</a>
            <button type="submit" class="form-platform-button-primary">Save Changes</button>
        </div>
    </form>
@endsection
