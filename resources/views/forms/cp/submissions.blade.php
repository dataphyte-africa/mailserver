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
            <a href="{{ cp_route($form->mode === 'subscription' ? 'subscription-forms.index' : 'product-forms.index') }}" class="form-platform-button">Back to forms</a>
            <a href="{{ cp_route($form->mode === 'subscription' ? 'subscription-forms.submissions.export' : 'product-forms.submissions.export', $form) }}" class="form-platform-button-primary">Export CSV</a>
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
                    @if($form->mode === 'subscription')
                        <th>Email address</th>
                        <th>Selected preferences</th>
                    @endif
                    @foreach($form->field_definitions ?? [] as $field)
                        @if($form->mode === 'subscription' && $field['handle'] === 'email')
                            @continue
                        @endif
                        <th>{{ $field['label'] }}</th>
                    @endforeach
                    @if($form->mode !== 'subscription')
                        <th>Review</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>{{ $submission->id }}</td>
                        <td>{{ $submission->status }}</td>
                        <td>{{ optional($submission->submitted_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $submission->submission_origin ?: 'Hosted / direct' }}</td>
                        @if($form->mode === 'subscription')
                            <td>{{ data_get($submission->payload, 'email') }}</td>
                            <td>{{ ($preferenceLabels[$submission->id] ?? '') ?: '—' }}</td>
                        @endif
                        @foreach($form->field_definitions ?? [] as $field)
                            @if($form->mode === 'subscription' && $field['handle'] === 'email')
                                @continue
                            @endif
                            <td>{{ data_get($submission->payload, $field['handle']) }}</td>
                        @endforeach
                        @if($form->mode !== 'subscription')
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
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + count($form->field_definitions ?? []) + (int) ($form->mode === 'subscription' && ! collect($form->field_definitions ?? [])->contains('handle', 'email')) }}">
                            No submissions stored yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
