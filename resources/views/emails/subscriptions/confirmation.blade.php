@extends('emails.layout')

@section('content')
<tr>
    <td class="content-padding" style="padding:40px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2937;">
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#374151;">
                        {{ filled($subscriberFirstName ?? null) ? 'Hello ' . $subscriberFirstName . ',' : 'Hello,' }}
                    </p>

                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#374151;">
                        {!! nl2br(e($bodyCopy)) !!}
                    </p>

                    @if(!empty($submissionSummary ?? []))
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 20px;border:1px solid #e5e7eb;border-collapse:collapse;">
                        <tr>
                            <td style="padding:14px 16px;background:#f9fafb;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;font-weight:700;color:#111827;">
                                Key information submitted
                            </td>
                        </tr>
                        @foreach($submissionSummary as $item)
                        <tr>
                            <td style="padding:12px 16px;border-top:1px solid #e5e7eb;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#374151;">
                                <div style="font-size:12px;line-height:1.4;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;">
                                    {{ $item['label'] }}
                                </div>
                                <div style="margin-top:4px;font-size:15px;line-height:1.6;color:#111827;">
                                    {{ $item['value'] }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </table>
                    @endif

                    @if(!empty($privacyUrl))
                    <p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#6b7280;">
                        Review our
                        <a href="{{ $privacyUrl }}" target="_blank" rel="noopener noreferrer" style="color:#374151;text-decoration:underline;">privacy policy</a>.
                    </p>
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection
