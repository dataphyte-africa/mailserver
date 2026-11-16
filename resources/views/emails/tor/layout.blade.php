@php
    $firstOffice = $organisation->publicAddresses()[0] ?? [];
    $companyAddress = $firstOffice['address'] ?? null;
    $logoUrl = $organisation->logo_path ? asset('storage/'.ltrim($organisation->logo_path, '/')) : null;
    $socialLinks = array_filter($organisation->social_links ?? []);
@endphp
<!doctype html>
<html lang="en">
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#17233c;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6fb;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #dce4f1;border-radius:12px;overflow:hidden;">
            <tr><td style="padding:26px 32px 22px;border-bottom:1px solid #dce4f1;">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $organisation->name }}" style="display:block;max-width:190px;max-height:52px;border:0;">
                @else
                    <strong style="font-size:20px;color:#17233c;">{{ $organisation->name }}</strong>
                @endif
            </td></tr>
            <tr><td style="padding:30px 32px;font-size:16px;line-height:1.6;">
                {{ $slot }}
            </td></tr>
            <tr><td style="padding:22px 32px;background:#f8faff;border-top:1px solid #dce4f1;font-size:13px;line-height:1.55;color:#52627b;">
                <strong style="color:#17233c;">{{ $organisation->name }}</strong>
                @if($companyAddress)<br>{!! nl2br(e($companyAddress)) !!}@endif
                @if($socialLinks)
                    <p style="margin:14px 0 0;">
                        @foreach($socialLinks as $platform => $url)
                            <a href="{{ $url }}" style="color:#2857a4;text-decoration:none;margin-right:12px;">{{ ucfirst($platform) }}</a>
                        @endforeach
                    </p>
                @endif
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
