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
        @if(session('success'))
            <div class="form-platform-alert form-platform-alert-success">
                {{ session('success') }}
            </div>
        @endif

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
                    <th>Review</th>
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
                        <td>
                            <form method="POST" action="{{ cp_route('product-forms.submissions.status', [$form, $submission]) }}" class="form-platform-inline-form">
                                @csrf
                                <select name="status" class="form-platform-control form-platform-control-sm">
                                    @foreach(['submitted', 'pending_review', 'under_review', 'approved', 'rejected'] as $status)
                                        <option value="{{ $status }}" @selected($submission->status === $status)>
                                            {{ str_replace('_', ' ', ucfirst($status)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="form-platform-button form-platform-button-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + count($form->field_definitions ?? []) }}">
                            No submissions stored yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
