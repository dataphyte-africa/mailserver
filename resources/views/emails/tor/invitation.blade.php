@component('emails.tor.layout', ['organisation' => $invitation->flow->organisation])
    <p style="margin:0 0 18px;">Dear {{ $invitation->expected_name ?: 'Signer' }},</p>
    <p style="margin:0 0 22px;">{{ $invitation->flow->organisation->name }} has invited you to read and sign <strong>{{ $invitation->flow->title }}</strong>.</p>
    <p style="margin:0 0 22px;"><a href="{{ $signingUrl }}" style="display:inline-block;padding:12px 20px;background:#183a70;border-radius:6px;color:#ffffff;text-decoration:none;font-weight:bold;">Read and sign the TOR</a></p>
    <p style="margin:0;color:#52627b;font-size:14px;">This personal link remains available until you complete signing or it is revoked.</p>
@endcomponent
