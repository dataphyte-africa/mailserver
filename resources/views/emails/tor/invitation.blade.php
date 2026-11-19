@component('emails.tor.layout', ['organisation' => $invitation->flow->organisation])
    <p style="margin:0 0 18px;">Dear {{ $invitation->expected_name ?: 'Signer' }},</p>
    @php($customCopy = $invitation->flow->email_copy['invitation'] ?? null)
    @if($customCopy)
        <p style="margin:0 0 22px;">{!! nl2br(e(strtr($customCopy, [':full_name' => $invitation->expected_name ?: 'Signer', ':organisation_name' => $invitation->flow->organisation->name, ':tor_title' => $invitation->flow->title]))) !!}</p>
    @else
        <p style="margin:0 0 22px;">{{ $invitation->flow->organisation->name }} has invited you to read and sign <strong>{{ $invitation->flow->title }}</strong>.</p>
    @endif
    <p style="margin:0 0 22px;"><a href="{{ $signingUrl }}" style="display:inline-block;padding:12px 20px;background:#183a70;border-radius:6px;color:#ffffff;text-decoration:none;font-weight:bold;">Read and sign the TOR</a></p>
    <p style="margin:0;color:#52627b;font-size:14px;">This personal link remains available until you complete signing or it is revoked.</p>
@endcomponent
