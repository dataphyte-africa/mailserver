@component('emails.tor.layout', ['organisation' => $flow->organisation])
    <p style="margin:0 0 18px;">Hello,</p>
    <p style="margin:0 0 18px;">The {{ $final ? 'final updated' : 'current' }} signer register for <strong>{{ $flow->title }}</strong> is ready to download.</p>
    <p style="margin:0 0 22px;">It contains {{ $signerCount }} signer {{ $signerCount === 1 ? 'record' : 'records' }}, including names, contact details, signing status, unique links, and signed-copy links where available.</p>
    <p style="margin:0 0 22px;"><a href="{{ $downloadUrl }}" style="display:inline-block;padding:12px 20px;background:#183a70;border-radius:6px;color:#ffffff;text-decoration:none;font-weight:bold;">Log in to download the signer register</a></p>
    <p style="margin:0 0 18px;color:#52627b;font-size:14px;">This protected link requires an account with access to {{ $flow->organisation->name }} before the CSV is downloaded.</p>
    @if($final)
        <p style="margin:0;color:#52627b;font-size:14px;">This is the final register after the TOR closed. Each completed signer has an original-copy and organisation-copy download link.</p>
    @else
        <p style="margin:0;color:#52627b;font-size:14px;">Send another current register at any time to receive the latest completion updates.</p>
    @endif
@endcomponent
