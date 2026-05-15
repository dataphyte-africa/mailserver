@php
    $socialLinks = $footerConfig['social_links'] ?? [];
    $offices = $footerConfig['offices'] ?? [];
@endphp

<tr>
    <td style="background-color:#f6f2eb;padding:30px 32px;border-top:1px solid #e7ddd1;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="padding:0 0 18px;text-align:center;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                        <tr>
                            @foreach ([
                                'facebook' => ['label' => 'Facebook'],
                                'twitter' => ['label' => 'Twitter'],
                                'linkedin' => ['label' => 'LinkedIn'],
                                'whatsapp' => ['label' => 'WhatsApp Channel'],
                                'youtube' => ['label' => 'YouTube'],
                                'instagram' => ['label' => 'Instagram'],
                                'tiktok' => ['label' => 'TikTok'],
                            ] as $platform => $icon)
                                <td style="padding:0 6px;">
                                    <a href="{{ $socialLinks[$platform] ?? '#' }}" aria-label="{{ $icon['label'] }}" style="display:block;width:34px;height:34px;border-radius:17px;background-color:#ffffff;border:1px solid #ddd3c6;text-decoration:none;">
                                        <img src="{{ asset('assets/emails/social/' . $platform . '.png') }}" alt="{{ $icon['label'] }}" width="34" height="34" style="display:block;width:34px;height:34px;border:0;">
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="padding:0 0 14px;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#6b7280;">
                    You're receiving this because you subscribed to {{ $fromName ?? config('app.name') }}.
                </td>
            </tr>

            <tr>
                <td style="padding:0 0 22px;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#6b7280;">
                    <a href="{{ $preferencesUrl ?? '#' }}" style="color:#3d405b;text-decoration:underline;">Manage preferences</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ $unsubscribeUrl ?? '#' }}" style="color:#3d405b;text-decoration:underline;">Unsubscribe</a>
                </td>
            </tr>

            @if(!empty($offices))
                <tr>
                    <td style="padding:0;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                @foreach($offices as $office)
                                    <td class="stack-column office-column" width="{{ floor(100 / count($offices)) }}%" valign="top" style="padding:12px 10px;border-top:1px solid #e7ddd1;text-align:left;">
                                        <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;font-weight:700;color:#1f2937;">
                                            {{ $office['label'] ?? '' }}
                                        </p>
                                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#6b7280;">
                                            {{ $office['address'] ?? '' }}
                                        </p>
                                    </td>
                                @endforeach
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif

            <tr>
                <td style="padding:18px 0 0;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;line-height:1.6;color:#8a8f98;">
                    &copy; {{ date('Y') }} {{ $fromName ?? config('app.name') }}. All rights reserved.
                </td>
            </tr>
        </table>
    </td>
</tr>
