@extends('statamic::layout')

@section('title', $flow->title)

@section('content')
@include('tor.cp._styles')
<div class="tor-shell tor-manage-shell">
    <a class="tor-back" href="{{ cp_route('tor-flows.index') }}">← Back to TOR Signing</a>

    <header class="tor-manage-header">
        <div>
            <p class="tor-eyebrow">TOR signing flow</p>
            <h1 class="tor-page-title">{{ $flow->title }}</h1>
            <div class="tor-manage-meta"><span>{{ $flow->organisation->name }}</span><span aria-hidden="true">•</span><span class="tor-badge {{ $flow->acceptsSignatures() ? 'tor-badge-active' : 'tor-badge-draft' }}">{{ $flow->deadlineHasPassed() ? 'Signing closed' : ($flow->signingHasNotOpened() ? 'Not open yet' : ucfirst($flow->status)) }}</span><span aria-hidden="true">•</span><span>{{ $flow->signing_opens_at ? 'Opens '.$flow->signing_opens_at->format('d M Y, H:i') : 'Open immediately' }}</span><span aria-hidden="true">•</span><span>{{ $flow->signing_deadline ? 'Closes '.$flow->signing_deadline->format('d M Y, H:i') : 'No closing date' }}</span><span aria-hidden="true">•</span><span>Last updated {{ $flow->updated_at?->format('d M Y, H:i') }}</span></div>
        </div>
        <div class="tor-header-actions">
            <a class="tor-secondary-button tor-edit-button" href="{{ cp_route('tor-flows.edit', $flow) }}">Edit TOR</a>
            @if($flow->canInviteSigners())
                <form method="POST" action="{{ cp_route('tor-flows.send', $flow) }}">@csrf<button class="tor-primary-button" {{ $pendingCount === 0 ? 'disabled' : '' }}>Send {{ $pendingCount ? $pendingCount.' unsent' : 'unsent' }} invitation{{ $pendingCount === 1 ? '' : 's' }}</button></form>
                <form method="POST" action="{{ cp_route('tor-flows.signer-register', $flow) }}">@csrf<input type="hidden" name="version" value="initial"><button class="tor-secondary-button" {{ $stats['authorised'] === 0 ? 'disabled title="Import signers before sending the register"' : '' }}>Send initial CSV</button></form>
                @if($stats['authorised'] === 0)<p class="tor-action-help">Import signers to enable delivery controls.</p>@endif
            @elseif($flow->status === 'active')
                <form method="POST" action="{{ cp_route('tor-flows.signer-register', $flow) }}">@csrf<input type="hidden" name="version" value="final"><button class="tor-primary-button">Send final updated CSV</button></form>
                <p class="tor-action-help">The signing deadline has passed. Edit the TOR to extend it.</p>
            @endif
        </div>
    </header>

    @if(session('success'))<div class="tor-notice" role="status">{{ session('success') }}</div>@endif
    @if($errors->has('signers_csv'))<div class="tor-notice tor-notice-error" role="alert"><strong>Signer import could not be completed.</strong><span>{{ $errors->first('signers_csv') }}</span></div>@endif
    @if($errors->has('signer.full_name') || $errors->has('signer.email'))<div class="tor-notice tor-notice-error" role="alert"><strong>Signer could not be added.</strong><span>{{ $errors->first('signer.full_name') ?: $errors->first('signer.email') }}</span></div>@endif
    @if(session('import_warning'))<div class="tor-notice tor-notice-error" role="alert"><strong>Some invitations were not sent.</strong><span>{{ session('import_warning') }}</span></div>@endif
    @if($importStatus)<div class="tor-notice {{ $importStatus['status'] === 'failed' ? 'tor-notice-error' : '' }}" role="status"><strong>{{ ucfirst($importStatus['status']) }} signer import{{ isset($importStatus['file_name']) ? ': '.$importStatus['file_name'] : '' }}.</strong><span>{{ $importStatus['processed'] ?? 0 }} row(s) processed, {{ $importStatus['invitations_queued'] ?? 0 }} invitation(s) queued{{ ($importStatus['skipped'] ?? 0) ? ', '.($importStatus['skipped']).' skipped' : '' }}{{ ($importStatus['failed'] ?? 0) ? ', '.($importStatus['failed']).' needing attention' : '' }}.</span>@if(! empty($importStatus['errors']))<span>{{ implode(' ', $importStatus['errors']) }}</span>@endif</div>@endif

    <section class="tor-manage-stats" aria-label="Invitation progress">
        <div class="tor-stat"><span class="tor-stat-value">{{ $stats['authorised'] }}</span><span class="tor-stat-label">Authorised signers</span></div>
        <div class="tor-stat"><span class="tor-stat-value">{{ $stats['delivered'] }}</span><span class="tor-stat-label">Delivered</span></div>
        <div class="tor-stat"><span class="tor-stat-value">{{ $stats['clicked'] }}</span><span class="tor-stat-label">Clicked</span></div>
        <div class="tor-stat"><span class="tor-stat-value">{{ $stats['signed'] }}</span><span class="tor-stat-label">Signed</span></div>
    </section>

    <section class="tor-manage-grid">
        <div class="tor-import-stack">
            <form class="tor-import-card" method="POST" enctype="multipart/form-data" action="{{ cp_route('tor-flows.import', $flow) }}">@csrf
                <p class="tor-eyebrow">Step 1</p>
                <h2>Import authorised signers</h2>
                <p>Upload a CSV of people who can sign this TOR. The CSV and invitation emails are processed in the background, so this page stays responsive.</p>
                <label class="tor-csv-upload"><span>Signer CSV</span><span class="tor-file-picker"><span class="tor-file-button">Choose CSV file</span><span class="tor-file-name" data-csv-file-name aria-live="polite">No file selected</span><input class="tor-file-input" name="signers_csv" type="file" accept=".csv,text/csv" required onchange="this.closest('.tor-csv-upload').querySelector('[data-csv-file-name]').textContent = this.files[0] ? this.files[0].name : 'No file selected'" {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}></span><small>Choose a UTF-8 <code>.csv</code> file. Required columns: <code>full_name</code>, <code>email</code>. Optional: <code>state</code>, <code>position</code>, <code>phone_number</code>.</small></label>
                <div class="tor-import-footer"><span>{{ $flow->deadlineHasPassed() ? 'Extend the signing close date before importing signers.' : 'Use UTF-8 CSV. Duplicate emails update the existing unsigned signer record.' }}</span><button class="tor-secondary-button" type="submit" {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}>Queue CSV import</button></div>
            </form>
            <form class="tor-manual-signer-card" method="POST" action="{{ cp_route('tor-flows.signers.store', $flow) }}">@csrf
                <p class="tor-eyebrow">Or add individually</p>
                <h2>Add one authorised signer</h2>
                <p>Add a person without preparing a CSV. Their unique signing link is queued by email immediately.</p>
                <div class="tor-manual-signer-grid">
                    <label><span>Full name <em>*</em></span><input class="tor-control" name="signer[full_name]" value="{{ old('signer.full_name') }}" required {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}></label>
                    <label><span>Email address <em>*</em></span><input class="tor-control" type="email" name="signer[email]" value="{{ old('signer.email') }}" required {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}></label>
                    <label><span>State</span><input class="tor-control" name="signer[state]" value="{{ old('signer.state') }}" {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}></label>
                    <label><span>Position</span><input class="tor-control" name="signer[position]" value="{{ old('signer.position') }}" {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}></label>
                    <label><span>Phone number</span><input class="tor-control" type="tel" name="signer[phone_number]" value="{{ old('signer.phone_number') }}" {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}></label>
                </div>
                <div class="tor-manual-signer-actions"><span>{{ $flow->deadlineHasPassed() ? 'Extend the signing close date before adding signers.' : 'Required fields are marked with an asterisk.' }}</span><button class="tor-secondary-button" type="submit" {{ ! $flow->canInviteSigners() ? 'disabled' : '' }}>Add signer</button></div>
            </form>
        </div>
        <aside class="tor-send-card">
            <p class="tor-eyebrow">Step 2</p>
            <h2>Invitation delivery</h2>
            <p>Each valid import sends every unsigned signer their unique secure TOR link automatically. Delivery, link clicks, and completed signatures are tracked below.</p>
            <dl class="tor-send-list"><div><dt>Signing opens</dt><dd>{{ $flow->signing_opens_at ? $flow->signing_opens_at->format('d M Y, H:i') : 'Immediately' }}</dd></div><div><dt>Signing closes</dt><dd>{{ $flow->signing_deadline ? $flow->signing_deadline->format('d M Y, H:i') : 'No deadline' }}</dd></div><div><dt>Signing record</dt><dd>PDF + verification code</dd></div><div><dt>Completed copy</dt><dd>Signer and internal recipients</dd></div></dl>
        </aside>
    </section>

    <section class="tor-tracking-card">
        <header class="tor-tracking-header"><div><p class="tor-eyebrow">Signer activity</p><h2>Invitation tracking</h2><p>Monitor each signer from delivery through completion.</p></div><span class="tor-badge tor-badge-draft">{{ $invitations->count() }} total</span></header>
        <div class="tor-table-wrap"><table class="tor-table tor-tracking-table"><thead><tr><th>Email</th><th>Status</th><th>Sent</th><th>Delivered</th><th>Clicked</th><th>Signed</th><th class="tor-action-column">Action</th></tr></thead><tbody>
        @forelse($invitations as $invitation)
            <tr><td><span class="tor-invitation-email">{{ $invitation->email }}</span></td><td><span class="tor-badge {{ $invitation->status === 'signed' ? 'tor-badge-complete' : ($invitation->status === 'clicked' ? 'tor-badge-active' : 'tor-badge-draft') }}">{{ ucfirst($invitation->status) }}</span></td><td>{{ $invitation->sent_at?->format('d M Y, H:i') ?? '—' }}</td><td>{{ $invitation->delivered_at?->format('d M Y, H:i') ?? '—' }}</td><td>{{ $invitation->clicked_at?->format('d M Y, H:i') ?? '—' }}</td><td>{{ $invitation->signed_at?->format('d M Y, H:i') ?? '—' }}</td><td class="tor-action-column">@if(! $invitation->isComplete())<form method="POST" action="{{ cp_route('tor-flows.send', $flow) }}">@csrf<input type="hidden" name="invitation" value="{{ $invitation->id }}"><button class="tor-table-action">Resend</button></form>@else<span class="tor-complete-label">Complete</span>@endif</td></tr>
        @empty
            <tr><td colspan="7"><div class="tor-table-empty"><strong>No signers imported yet</strong><span>Import authorised email addresses above to begin sending invitations.</span></div></td></tr>
        @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
