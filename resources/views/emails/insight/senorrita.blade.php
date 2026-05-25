@php
    $productName = 'SenorRita';
    $hideCollectionHeader = true;
    $lead = $rssLeadItem ?? null;
    $related = collect($relatedRssItems ?? [])->take(4);
    $recommended = collect($recommendedRssItems ?? [])->take(4);
    $tocItems = collect($tableOfContentsItems ?? [])->take(6);
    $insightItems = collect($insightBlockItems ?? [])->take(2);
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
        <td style="padding:20px 32px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#7b8794;">
                {{ $formattedSentDate }}
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:0 32px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.78;color:#1f2937;">
            {!! $content !!}
        </td>
    </tr>

    @if(!empty($highlightStat) || !empty($highlightStatLabel))
    <tr>
        <td style="padding:0 32px 40px;">
            <p style="margin:0 0 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#7b8794;">
                What the data says
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:linear-gradient(135deg,#fff4f9 0%,#f4fbff 100%);border:1px solid #eadfeb;">
                <tr>
                    <td style="padding:18px 20px;">
                        @if(!empty($highlightStat))
                            <p style="margin:0 0 8px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:42px;line-height:0.95;color:#e37da7;font-weight:800;">
                                {{ $highlightStat }}
                            </p>
                        @endif
                        @if(!empty($highlightStatLabel))
                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#37628a;">
                                {{ $highlightStatLabel }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    @if($insightItems->isNotEmpty())
    <tr>
        <td style="padding:0 32px 40px;">
            <p style="margin:0 0 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#7b8794;">
                Key insights
            </p>
            @foreach($insightItems as $index => $item)
                @php
                    $isPink = $index % 2 === 0;
                    $bg = $isPink ? '#fcf6fa' : '#f4faff';
                    $border = $isPink ? '#e37da7' : '#5eaee8';
                    $titleColor = $isPink ? '#9d476f' : '#2b6f9f';
                    $bodyColor = $isPink ? '#5d4a55' : '#4b5d6d';
                @endphp
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:{{ $bg }};border-left:5px solid {{ $border }};margin:0 0 14px;">
                    <tr>
                        <td style="padding:16px 18px 14px;">
                            <h3 style="margin:0 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:1.2;color:{{ $titleColor }};font-weight:700;">
                                {{ $item['title'] ?? '' }}
                            </h3>
                            @if(!empty($item['description']))
                                <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.65;color:{{ $bodyColor }};">
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

    @if($primaryCtaUrl)
    <tr>
        <td style="padding:0 32px 40px;text-align:center;">
            <a href="{{ $primaryCtaUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:13px 24px;background:#12202f;color:#ffffff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;text-decoration:none;border-radius:4px;">
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
        <td style="padding:0 32px 40px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#7b8794;">
               Related Newsletter Issues
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
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#e37da7;">
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
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#7b8794;">
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
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#5eaee8;">
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
