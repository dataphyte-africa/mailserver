@extends('emails.layout')

@section('content')
<tr>
    <td class="content-padding" style="padding:40px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2937;">
                    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;font-weight:700;color:#111827;">
                        {{ $headline }}
                    </h1>

                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#374151;">
                        {{ filled($subscriberFirstName ?? null) ? 'Hello ' . $subscriberFirstName . ',' : 'Hello,' }}
                    </p>

                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#374151;">
                        {!! nl2br(e($bodyCopy)) !!}
                    </p>

                    @if(!empty($privacyUrl))
                    <p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#6b7280;">
                        Review our
                        <a href="{{ $privacyUrl }}" style="color:#374151;text-decoration:underline;">privacy policy</a>.
                    </p>
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection
