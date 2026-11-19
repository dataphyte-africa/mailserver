@php
    $isSignerCopy = $copyType === 'signer';
    $copyKey = $isSignerCopy ? 'signer_completion' : 'organisation_completion';
    $customCopy = $invitation->flow->email_copy[$copyKey] ?? null;
    $renderedCopy = $customCopy ? strtr($customCopy, [
        ':full_name' => $invitation->signer_name,
        ':organisation_name' => $invitation->flow->organisation->name,
        ':tor_title' => $invitation->flow->title,
    ]) : null;
@endphp
@component('emails.tor.layout', ['organisation' => $invitation->flow->organisation])
    @if($isSignerCopy)
        <p style="margin:0 0 18px;">Dear {{ $invitation->signer_name }},</p>
        @if($renderedCopy)<p style="margin:0 0 22px;">{!! nl2br(e($renderedCopy)) !!}</p>@else<p style="margin:0 0 22px;">You have just completed the signing of <strong>{{ $invitation->flow->title }}</strong>.</p>@endif
        <p style="margin:0 0 22px;"><a href="{{ $downloadUrl }}" style="display:inline-block;padding:12px 20px;background:#183a70;border-radius:6px;color:#ffffff;text-decoration:none;font-weight:bold;">Download your original signed copy</a></p>
        <p style="margin:0;color:#52627b;font-size:14px;">Your original signed copy is also attached. Verification code: {{ $invitation->verification_code }}</p>
    @else
        <p style="margin:0 0 18px;"><strong>Alert: {{ $invitation->flow->title }}</strong></p>
        @if($renderedCopy)<p style="margin:0 0 22px;">{!! nl2br(e($renderedCopy)) !!}</p>@else<p style="margin:0 0 22px;"><strong>{{ $invitation->signer_name }}</strong> has just completed the signing of the Terms of Reference.</p>@endif
        <p style="margin:0 0 22px;"><a href="{{ $downloadUrl }}" style="display:inline-block;padding:12px 20px;background:#183a70;border-radius:6px;color:#ffffff;text-decoration:none;font-weight:bold;">Download the organisation copy</a></p>
        <p style="margin:0;color:#52627b;font-size:14px;">This organisation copy is also attached. Verification code: {{ $invitation->verification_code }}</p>
    @endif
@endcomponent
