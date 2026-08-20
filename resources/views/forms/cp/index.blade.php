@extends('statamic::layout')
@section('title', 'Hosted Forms')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">Hosted Forms</h1>
            <p class="form-platform-description">
                Product-owned forms for hosted collection pages and approved embeds.
            </p>
        </div>
        <a href="{{ cp_route('product-forms.create') }}" class="form-platform-button-primary">Create Form</a>
    </div>

    <div class="form-platform-stack">
        @forelse($forms as $form)
            <div class="form-platform-card">
                <div class="form-platform-row">
                    <div>
                        <h2>{{ $form->name }}</h2>
                        <p class="form-platform-meta">
                            {{ $form->product?->name ?? 'Unlinked product' }}
                            &middot;
                            {{ $form->organisation?->name ?? 'Unlinked organisation' }}
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
                        <a href="{{ cp_route('product-forms.edit', $form) }}" class="form-platform-button">
                            Edit
                        </a>
                        <a href="{{ cp_route('product-forms.submissions.index', $form) }}" class="form-platform-button">
                            View submissions
                        </a>
                        <a href="{{ cp_route('product-forms.submissions.export', $form) }}" class="form-platform-button">
                            Export CSV
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="form-platform-card form-platform-empty">
                No product-owned hosted forms have been created yet.
            </div>
        @endforelse
    </div>
@endsection
