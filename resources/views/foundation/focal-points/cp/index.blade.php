@extends('statamic::layout')
@section('title', '2027 State Focal Point Applications')

@section('content')
    @include('forms.cp._styles')
    <div class="form-platform-header">
        <div><h1 class="form-platform-title">State Focal Point Applications</h1><p class="form-platform-description">2027 election observation · {{ $applications->total() }} submissions</p></div>
        <a class="form-platform-button-primary" href="{{ cp_route('focal-point-applications.export') }}">Export CSV</a>
    </div>
    <div class="form-platform-stack">
        @forelse($applications as $application)
            <div class="form-platform-card">
                <div class="form-platform-row">
                    <div>
                        <h2>{{ $application->full_name }}</h2>
                        <p class="form-platform-meta">{{ $application->email }} · {{ $application->answers['state'] ?? 'State unavailable' }} · {{ $application->created_at?->format('d M Y, H:i') }}</p>
                        <p class="form-platform-meta">{{ str_replace('_', ' ', ucfirst($application->status)) }} · {{ $application->uuid }}</p>
                    </div>
                    <div class="form-platform-actions"><a class="form-platform-button" href="{{ cp_route('focal-point-applications.show', $application) }}">Review</a></div>
                </div>
            </div>
        @empty
            <div class="form-platform-card form-platform-empty">No applications have been submitted yet.</div>
        @endforelse
    </div>
    <div style="margin-top:20px">{{ $applications->links() }}</div>
@endsection
