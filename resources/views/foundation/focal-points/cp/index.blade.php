@extends('statamic::layout')
@section('title', '2027 State Coordinator Applications')

@section('content')
    @include('forms.cp._styles')
    <div class="form-platform-header">
        <div><h1 class="form-platform-title">State Coordinator Applications</h1><p class="form-platform-description">2027 election observation · {{ $applications->total() }} submissions</p></div>
        <a class="form-platform-button-primary" href="{{ cp_route('focal-point-applications.export') }}">Export CSV</a>
    </div>
    <div class="form-platform-card" style="overflow-x:auto">
        <table style="width:100%;min-width:760px;border-collapse:collapse;text-align:left">
            <thead>
                <tr style="border-bottom:1px solid #d9e2ee">
                    <th scope="col" style="padding:12px">Applicant</th>
                    <th scope="col" style="padding:12px">Review status</th>
                    <th scope="col" style="padding:12px">Email status</th>
                    <th scope="col" style="padding:12px">Submitted</th>
                    <th scope="col" style="padding:12px">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr style="border-bottom:1px solid #e8edf4">
                        <td style="padding:12px"><strong>{{ $application->full_name }}</strong><br><span class="form-platform-meta">{{ $application->email }} · {{ $application->answers['state'] ?? 'State unavailable' }}</span></td>
                        <td style="padding:12px">{{ str_replace('_', ' ', ucfirst($application->status)) }}</td>
                        <td style="padding:12px">{{ str_replace('_', ' ', ucfirst($application->confirmation_email_status)) }}</td>
                        <td style="padding:12px">{{ $application->created_at?->format('d M Y, H:i') }}</td>
                        <td style="padding:12px"><a class="form-platform-button" href="{{ cp_route('focal-point-applications.show', $application) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:20px">No applications have been submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:20px">{{ $applications->links() }}</div>
@endsection
