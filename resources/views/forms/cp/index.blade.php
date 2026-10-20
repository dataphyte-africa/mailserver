@extends('statamic::layout')
@section('title', $subscriptionWorkspace ? 'Subscription Forms' : 'Application Forms')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">{{ $subscriptionWorkspace ? 'Subscription Forms' : 'Application Forms' }}</h1>
            <p class="form-platform-description">
                {{ $subscriptionWorkspace ? 'Manage organisation subscription pages and their automated messages.' : 'Manage admin-owned application and data-collection forms.' }}
            </p>
        </div>
        <a href="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.create' : 'product-forms.create') }}" class="form-platform-button-primary">Create Form</a>
    </div>

    <div class="form-platform-stack">
        @forelse($forms as $form)
            <div class="form-platform-card">
                <div class="form-platform-row">
                    <div>
                        <h2>{{ $form->name }}</h2>
                        <p class="form-platform-meta">
                            {{ $form->form_scope === 'organisation' ? ($form->organisation?->name ?? 'Unlinked organisation') : ($form->product?->name ?? 'Unlinked product') }}
                            @if($form->form_scope === 'product')
                                &middot; {{ $form->organisation?->name ?? 'Unlinked organisation' }}
                            @endif
                            &middot;
                            {{ $form->template_family }}
                        </p>
                        <p class="form-platform-meta">
                            {{ ucfirst($form->mode) }}
                            &middot;
                            {{ ucfirst($form->status) }}
                            &middot;
                            {{ $form->submissions_count }} submissions
                        </p>
                    </div>
                    <div class="form-platform-actions">
                        <a href="{{ $formService->hostedPageUrl($form) }}" target="_blank" rel="noopener" class="form-platform-button">
                            Preview public page
                        </a>
                        <a href="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.edit' : 'product-forms.edit', $form) }}" class="form-platform-button">
                            Edit
                        </a>
                        <a href="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.submissions.index' : 'product-forms.submissions.index', $form) }}" class="form-platform-button">
                            View submissions
                        </a>
                        <a href="{{ cp_route($subscriptionWorkspace ? 'subscription-forms.submissions.export' : 'product-forms.submissions.export', $form) }}" class="form-platform-button">
                            Export CSV
                        </a>
                    </div>
                </div>
                <label class="form-platform-label" for="form-url-{{ $form->getKey() }}">Shareable subscription link</label>
                <input id="form-url-{{ $form->getKey() }}" class="form-platform-control" type="text" readonly value="{{ $formService->hostedPageUrl($form) }}" onclick="this.select()">
            </div>
        @empty
            @if($legacyForms->isEmpty() && $bespokeForms->isEmpty())
                <div class="form-platform-card form-platform-empty">No {{ $subscriptionWorkspace ? 'subscription' : 'application' }} forms have been created yet.</div>
            @endif
        @endforelse

        @foreach($legacyForms as $legacy)
            <div class="form-platform-card">
                <div class="form-platform-row">
                    <div>
                        <h2>{{ $legacy['name'] }}</h2>
                        <p class="form-platform-meta">{{ $legacy['collection'] }} &middot; Existing Statamic {{ $subscriptionWorkspace ? 'subscription' : 'application' }} form</p>
                        <p class="form-platform-help">{{ $subscriptionWorkspace ? 'Its public page and API remain active; automated messages can be managed here.' : 'Custom public pages and application rules remain in the Statamic form workflow.' }}</p>
                    </div>
                    <div class="form-platform-actions">
                        <a href="{{ $legacy['url'] }}" target="_blank" rel="noopener" class="form-platform-button">Preview public page</a>
                        @if($subscriptionWorkspace)
                            <a href="{{ cp_route('subscription-forms.legacy.edit', $legacy['handle']) }}" class="form-platform-button">Manage messages</a>
                        @else
                            <a href="{{ cp_route('forms.edit', $legacy['handle']) }}" class="form-platform-button">Manage in Statamic</a>
                        @endif
                    </div>
                </div>
                <label class="form-platform-label" for="legacy-url-{{ $legacy['handle'] }}">Public form link</label>
                <input id="legacy-url-{{ $legacy['handle'] }}" class="form-platform-control" type="text" readonly value="{{ $legacy['url'] }}" onclick="this.select()">
            </div>
        @endforeach

        @foreach($bespokeForms as $bespoke)
            <div class="form-platform-card">
                <div class="form-platform-row">
                    <div>
                        <h2>{{ $bespoke['name'] }}</h2>
                        <p class="form-platform-meta">Custom application form &middot; Admin-owned</p>
                        <p class="form-platform-help">{{ $bespoke['description'] }}</p>
                    </div>
                    <div class="form-platform-actions">
                        <a href="{{ $bespoke['url'] }}" target="_blank" rel="noopener" class="form-platform-button">Preview public page</a>
                        @if(isset($bespoke['manage_url']))
                            <a href="{{ $bespoke['manage_url'] }}" class="form-platform-button">Manage application</a>
                        @endif
                        @if(isset($bespoke['submissions_url']))
                            <a href="{{ $bespoke['submissions_url'] }}" class="form-platform-button">View submissions</a>
                        @endif
                    </div>
                </div>
                <label class="form-platform-label" for="bespoke-url-{{ $loop->index }}">Public form link</label>
                <input id="bespoke-url-{{ $loop->index }}" class="form-platform-control" type="text" readonly value="{{ $bespoke['url'] }}" onclick="this.select()">
            </div>
        @endforeach
    </div>
@endsection
