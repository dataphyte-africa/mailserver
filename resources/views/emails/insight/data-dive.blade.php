@php
    $productName = 'Data Dive';
    $hideCollectionHeader = true;
    $lead = $rssLeadItem ?? null;
    $related = collect($relatedRssItems ?? [])->take(4);
    $recommended = collect($recommendedRssItems ?? [])->take(4);
    $tocItems = collect($tableOfContentsItems ?? [])->take(6);
    $findings = collect($dataPoints ?? [])->take(3);
    $entryHeadline = $entryTitle ?? $subject ?? '';
    $primaryCtaUrl = $lead['url'] ?? null;
    $ctaTitle = $lead['title'] ?? $entryHeadline;
    $ctaAuthor = $lead['author'] ?? ($author ?? '');
    $formattedSentDate = $sentDate
        ? \Illuminate\Support\Carbon::parse($sentDate)->format('l, F j, Y')
        : '';
@endphp

@extends('emails.layout')

@section('content')

    @if(!empty($heroImageUrl))
    <tr>
        <td style="padding:0;">
            <img src="{{ $heroImageUrl }}" alt="" style="width:100%;height:auto;display:block;border:0;">
        </td>
    </tr>
    @endif


    <tr>
        <td style="padding:35px 32px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#7b8794;">
                {{ $formattedSentDate }}
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:0 32px 30px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.78;color:#1f2937;">
            {!! $content !!}
        </td>
    </tr>

    @if(!empty($highlightStat) || !empty($highlightStatLabel))
    <tr>
        <td style="padding:0 32px 40px;">
            <p style="margin:0 0 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                What the data says
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f7fafc;border:1px solid #d9e2ec;">
                <tr>
                    <td style="padding:18px 20px;">
                        @if(!empty($highlightStat))
                            <p style="margin:0 0 8px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:38px;line-height:0.95;color:#0f4c81;font-weight:700;">
                                {{ $highlightStat }}
                            </p>
                        @endif
                        @if(!empty($highlightStatLabel))
                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#334e68;">
                                {{ $highlightStatLabel }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    @if($findings->isNotEmpty())
    <tr>
        <td style="padding:0 32px 34px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                Key findings
            </p>
            @foreach($findings as $item)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#ffffff;border:1px solid #d9e2ec;margin:0 0 14px;">
                    <tr>
                        <td style="padding:16px 18px 14px;">
                            <h3 style="margin:0 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:1.2;color:#0d1b2a;">
                                {{ $item['title'] ?? '' }}
                            </h3>
                            @if(!empty($item['description']))
                                <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.65;color:#486581;">
                                    {{ $item['description'] }}
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            @endforeach
        </td>
    </tr>
    @endif

    @if(!empty($accountabilityQuestion))
    <tr>
        <td style="padding:0 32px 34px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                The accountability question
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#eef4f8;border-left:5px solid #0f4c81;">
                <tr>
                    <td style="padding:18px 18px 16px;">
                        <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:18px;line-height:1.35;color:#12344d;font-style:italic;">
                            {{ $accountabilityQuestion }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    @if($primaryCtaUrl)
    <tr>
        <td style="padding:0 32px 40px;text-align:center;">
            <a href="{{ $primaryCtaUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:13px 24px;background:#0d1b2a;color:#ffffff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;text-decoration:none;border-radius:3px;">
                Continue reading
            </a>
            @if(!empty($ctaTitle))
                <p style="margin:10px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.45;color:#1f2937;">
                    <span style="font-weight:600;">{{ $ctaTitle }}</span>
                </p>
            @endif
            @if(!empty($ctaAuthor))
                <p style="margin:3px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.4;color:#7b8794;">
                    By {{ $ctaAuthor }}
                </p>
            @endif
        </td>
    </tr>
    @endif

    @if($related->isNotEmpty())
    <tr>
        <td style="padding:0 32px 24px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                Other Issues
            </p>
            @foreach($related as $item)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:16px 0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    @if(!empty($item['image_url']))
                                        <td valign="top" width="84" style="padding-right:14px;">
                                            <img src="{{ $item['image_url'] }}" alt="" width="84" style="width:84px;height:84px;object-fit:cover;display:block;">
                                        </td>
                                    @endif
                                    <td valign="top">
                                        @if(!empty($item['primary_taxonomy_title']))
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#0f4c81;">
                                                {{ $item['primary_taxonomy_title'] }}
                                            </p>
                                        @endif
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.5;color:#12202f;text-decoration:none;font-weight:700;">
                                            {{ $item['title'] }}
                                        </a>
                                        @if(!empty($item['author']))
                                            <p style="margin:8px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#6b7280;">
                                                By {{ $item['author'] }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endforeach
        </td>
    </tr>
    @endif

    @if($recommended->isNotEmpty())
    <tr>
        <td style="padding:0 32px 32px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                Read This Also
            </p>
            @foreach($recommended as $item)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:16px 0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    @if(!empty($item['image_url']))
                                        <td valign="top" width="84" style="padding-right:14px;">
                                            <img src="{{ $item['image_url'] }}" alt="" width="84" style="width:84px;height:84px;object-fit:cover;display:block;">
                                        </td>
                                    @endif
                                    <td valign="top">
                                        @if(!empty($item['primary_taxonomy_title']))
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#6b7280;">
                                                {{ $item['primary_taxonomy_title'] }}
                                            </p>
                                        @endif
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.5;color:#12202f;text-decoration:none;font-weight:700;">
                                            {{ $item['title'] }}
                                        </a>
                                        @if(!empty($item['author']))
                                            <p style="margin:8px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#6b7280;">
                                                By {{ $item['author'] }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endforeach
        </td>
    </tr>
    @endif

@endsection
