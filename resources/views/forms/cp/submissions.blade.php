@extends('statamic::layout')
@section('title', $form->name . ' Submissions')

@section('content')
    @include('forms.cp._styles')

    <div class="form-platform-header">
        <div>
            <h1 class="form-platform-title">{{ $form->name }}</h1>
            <p class="form-platform-description">
                {{ ucfirst($form->mode) }} &middot; {{ $form->template_family }}
            </p>
        </div>
        <div class="form-platform-actions">
            <a href="{{ cp_route('product-forms.index') }}" class="form-platform-button">Back to forms</a>
            <a href="{{ cp_route('product-forms.submissions.export', $form) }}" class="form-platform-button-primary">Export CSV</a>
        </div>
    </div>

    <div class="form-platform-card-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Origin</th>
                    @foreach($form->field_definitions ?? [] as $field)
                        <th>{{ $field['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>{{ $submission->id }}</td>
                        <td>{{ $submission->status }}</td>
                        <td>{{ optional($submission->submitted_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $submission->submission_origin ?: 'Hosted / direct' }}</td>
                        @foreach($form->field_definitions ?? [] as $field)
                            <td>{{ data_get($submission->payload, $field['handle']) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + count($form->field_definitions ?? []) }}">
                            No submissions stored yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
