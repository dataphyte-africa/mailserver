<p>Hello,</p>
<p>The {{ $final ? 'final updated' : 'current' }} signer register for <strong>{{ $flow->title }}</strong> is attached.</p>
@if($final)<p>It includes the completed signer and company copy links for every completed TOR.</p>@else<p>It includes each authorised signer’s name, email address, and unique signing link.</p>@endif
