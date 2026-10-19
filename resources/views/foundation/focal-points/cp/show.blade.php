@extends('statamic::layout')
@section('title', $application->full_name.' · State Focal Point Application')

@section('content')
    @include('forms.cp._styles')
    <div class="form-platform-header">
        <div><a href="{{ cp_route('focal-point-applications.index') }}">← All applications</a><h1 class="form-platform-title">{{ $application->full_name }}</h1><p class="form-platform-description">{{ $application->email }} · {{ $application->uuid }}</p></div>
        <a class="form-platform-button" href="{{ cp_route('focal-point-applications.export') }}">Export CSV</a>
    </div>
    @if(session('success'))<div class="form-platform-card" role="status">{{ session('success') }}</div>@endif
    <div class="form-platform-stack">
        <div class="form-platform-card">
            <h2>Review</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:16px 0">
                <div><span class="form-platform-meta">Review status</span><br><strong>{{ str_replace('_', ' ', ucfirst($application->status)) }}</strong></div>
                <div><span class="form-platform-meta">Email status</span><br><strong>{{ str_replace('_', ' ', ucfirst($application->confirmation_email_status)) }}</strong></div>
                <div><span class="form-platform-meta">Submitted</span><br><strong>{{ $application->created_at?->format('d M Y, H:i') }}</strong></div>
            </div>
            @if($application->confirmation_email_delivered_at || $application->confirmation_email_opened_at || $application->confirmation_email_failed_at)
                <p class="form-platform-meta">
                    @if($application->confirmation_email_delivered_at) Delivered {{ $application->confirmation_email_delivered_at->format('d M Y, H:i') }} · @endif
                    @if($application->confirmation_email_opened_at) Open detected {{ $application->confirmation_email_opened_at->format('d M Y, H:i') }} · @endif
                    @if($application->confirmation_email_failed_at) Failed {{ $application->confirmation_email_failed_at->format('d M Y, H:i') }} @endif
                </p>
            @endif
            <p class="form-platform-help">Confirmed means an email-open event was detected; it does not prove the message was read.</p>
            <p class="form-platform-meta">Terms accepted: {{ $application->tor_accepted_at?->format('d M Y, H:i') ?? 'Not recorded' }} · Version: {{ $application->tor_version ?? 'Unknown' }}</p>
            @if(!in_array($application->status, ['approved', 'rejected']))
                <form method="post" action="{{ cp_route('focal-point-applications.status', $application) }}" class="form-platform-actions" style="margin-top:18px">
                    @csrf
                    <select name="status" class="input-text form-platform-control" aria-label="New review status" required>
                        <option value="under_review">Under review</option><option value="approved">Approved</option><option value="rejected">Rejected</option>
                    </select>
                    <button class="form-platform-button-primary" type="submit">Update status</button>
                </form>
            @endif
        </div>
        <div class="form-platform-card">
            <h2>Supporting documents</h2>
            <p class="form-platform-help">Google Drive checks each reviewer's account and folder permissions before opening these files.</p>
            <div class="form-platform-actions" style="margin-top:18px">
                <a href="{{ $application->cv_drive_url }}" target="_blank" rel="noopener noreferrer" class="form-platform-button">View CV · {{ $application->cv_filename }}</a>
                <a href="{{ $application->certificate_drive_url }}" target="_blank" rel="noopener noreferrer" class="form-platform-button">View certificate · {{ $application->certificate_filename }}</a>
            </div>
        </div>
        <div class="form-platform-card">
            <h2>Application answers</h2>
            <dl style="display:grid;grid-template-columns:minmax(150px,220px) minmax(0,1fr);gap:12px 20px;margin-top:18px">
                @foreach($application->answers ?? [] as $key => $value)
                    @unless(in_array($key, ['state_id','lga_id','ward_id']))
                        <dt style="font-weight:700">{{ str($key)->replace('_', ' ')->headline() }}</dt>
                        <dd style="margin:0;white-space:pre-wrap;overflow-wrap:anywhere">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                    @endunless
                @endforeach
            </dl>
        </div>
    </div>
@endsection
