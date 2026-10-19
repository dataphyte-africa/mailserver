@extends('statamic::layout')
@section('title', $subscriptionWorkspace ? 'Create Subscription Form' : 'Create Application Form')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">Create {{ $subscriptionWorkspace ? 'Subscription' : 'Application' }} Form</h1>
            <p class="form-platform-description">
                {{ $subscriptionWorkspace ? 'Create an organisation subscription page where subscribers choose approved public audience preferences.' : 'Create an admin-managed application or data-collection form.' }}
            </p>
        </div>
        <a href="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.index' : 'product-forms.index') }}" class="form-platform-button">Back to forms</a>
    </div>

    @if($subscriptionWorkspace ? $organisations->isEmpty() : $products->isEmpty())
        <div class="form-platform-card">
            <h2>No active {{ $subscriptionWorkspace ? 'organisation' : 'product' }} is available</h2>
            <p class="form-platform-description">
                {{ $subscriptionWorkspace ? 'A subscription form needs an active organisation in your access scope.' : 'A hosted form must belong to an active product under an active organisation before it can be created.' }}
            </p>
            <p class="form-platform-description">
                Set up access first, then return here to create the form.
            </p>
        </div>
    @else
        <form method="POST" action="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.store' : 'product-forms.store') }}" class="form-platform-stack">
            @csrf
            @include('forms.cp._form')

            <div class="form-platform-actions">
                <a href="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.index' : 'product-forms.index') }}" class="form-platform-button">Cancel</a>
                <button type="submit" class="form-platform-button-primary">Create Form</button>
            </div>
        </form>
    @endif
@endsection
