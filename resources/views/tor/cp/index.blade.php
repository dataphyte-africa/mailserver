@extends('statamic::layout')
@section('title', 'TOR Signing')
@section('content')
@include('tor.cp._styles')
<div class="tor-shell">
    <header class="tor-page-header">
        <div><p class="tor-eyebrow">Company documents</p><h1 class="tor-page-title">TOR Signing</h1><p class="tor-page-intro">Create a Terms of Reference, invite approved signers, and retain a verified signed copy.</p></div>
        <a class="tor-primary-button" href="{{ cp_route('tor-flows.create') }}">Create TOR flow <span aria-hidden="true">→</span></a>
    </header>
    <section class="tor-overview"><div class="tor-overview-grid"><div><h2>A simple, tracked signing process</h2><p>Every TOR remains tied to its company, invitation activity, and completed document.</p><div class="tor-steps"><div class="tor-step"><strong>1. Create TOR</strong>Author the agreement and recipients.</div><div class="tor-step"><strong>2. Invite signers</strong>Send each approved person a unique link.</div><div class="tor-step"><strong>3. Receive copies</strong>Track completion and retain the PDF.</div></div></div><div class="tor-status-summary"><div class="tor-stat"><span class="tor-stat-value">{{ $ongoingCount }}</span><span class="tor-stat-label">Ongoing flows</span></div><div class="tor-stat"><span class="tor-stat-value">{{ $completedCount }}</span><span class="tor-stat-label">Completed flows</span></div></div></div></section>
    <div class="tor-list-header"><div><h2>Your TOR flows</h2><p>Monitor the current signing state and open a flow to manage its signers.</p></div></div>
    <section class="tor-table-wrap">
        @if($flows->isNotEmpty())
            <table class="tor-table"><thead><tr><th>TOR title</th><th>Company</th><th>Status</th><th>Signing progress</th><th></th></tr></thead><tbody>
                @foreach($flows as $flow)
                    @php($completed = $flow->invitations_count > 0 && $flow->invitations_count === $flow->signed_invitations_count)
                    <tr><td><span class="tor-table-title">{{ $flow->title }}</span><span class="tor-table-meta">Created {{ $flow->created_at?->format('d M Y') }}</span></td><td>{{ $flow->organisation->name }}</td><td><span class="tor-badge {{ $completed ? 'tor-badge-complete' : ($flow->status === 'active' ? 'tor-badge-active' : 'tor-badge-draft') }}">{{ $completed ? 'Completed' : ($flow->status === 'active' ? 'Ongoing' : ($flow->status === 'closed' ? 'Closed' : 'Draft')) }}</span></td><td>{{ $flow->signed_invitations_count }} of {{ $flow->invitations_count }} signed</td><td><a class="tor-secondary-button" href="{{ cp_route('tor-flows.show', $flow) }}">Manage</a></td></tr>
                @endforeach
            </tbody></table>
        @else
            <div class="tor-empty"><h3>No TOR flows yet</h3><p>Start your first company TOR to invite signers and receive a completed PDF.</p><a class="tor-primary-button" href="{{ cp_route('tor-flows.create') }}">Create TOR flow</a></div>
        @endif
    </section>
</div>
@endsection
