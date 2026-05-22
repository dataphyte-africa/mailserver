@php
    $productName = 'Marina & Maitama';
    $hideCollectionHeader = true;
    $lead = $rssLeadItem ?? null;
    $related = collect($relatedRssItems ?? [])->take(4);
    $recommended = collect($recommendedRssItems ?? [])->take(4);
    $introHtml = trim((string) ($introContent ?? ''));
    $marinaHtml = trim((string) ($marinaContent ?? ''));
    $maitamaHtml = trim((string) ($maitamaContent ?? ''));
    $entryHeadline = $entryTitle ?? $subject ?? '';
    $primaryCtaUrl = $lead['url'] ?? null;
    $stylePerspectiveHtml = function (string $html, string $headingColor, string $bodyColor) {
        if ($html === '') {
            return '';
        }

        $html = preg_replace(
            '/<h5\b[^>]*>(.*?)<\/h5>/is',
            '<h5 style="margin:0 0 16px;font-family:Georgia,\'Times New Roman\',serif;font-size:20px;line-height:1.12;color:' . $headingColor . ';font-weight:700;letter-spacing:-0.03em;">$1</h5>',
            $html,
            1
        );

        return preg_replace(
            '/<p\b([^>]*)>/i',
            '<p$1 style="margin:0 0 16px;font-family:Georgia,\'Times New Roman\',serif;font-size:19px;line-height:1.78;color:' . $bodyColor . ';font-style:italic;">',
            $html
        );
    };
    $marinaStyledHtml = $stylePerspectiveHtml($marinaHtml, '#155f9f', '#36527b');
    $maitamaStyledHtml = $stylePerspectiveHtml($maitamaHtml, '#0a7a82', '#335d63');
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
        <td style="padding:22px 32px 24px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#6b7280;">
                {{ $sentDate }}
            </p>
        </td>
    </tr>

    @if($introHtml !== '')
    <tr>
        <td style="padding:0 32px 24px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;">
            {!! $introHtml !!}
        </td>
    </tr>
    @endif

    @if(!empty($highlightStat) || !empty($highlightStatLabel))
    <tr>
        <td style="padding:0 32px 28px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:800;letter-spacing:2.2px;text-transform:uppercase;color:#6b7280;">
                What the Data Says
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#eef4fb;border:1px solid #d6e2f1;">
                <tr>
                    <td style="padding:18px 20px 16px;">
                        @if(!empty($highlightStat))
                            <p style="margin:0 0 10px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:42px;line-height:0.92;color:#0f4c81;font-weight:800;">
                                {{ $highlightStat }}
                            </p>
                        @endif
                        @if(!empty($highlightStatLabel))
                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#35528a;">
                                {{ $highlightStatLabel }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    @if($marinaHtml !== '' || $maitamaHtml !== '')
    <tr>
        <td style="padding:8px 32px 34px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="padding:0 0 18px;">
                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:800;letter-spacing:2.2px;text-transform:uppercase;color:#6b7280;">
                            The Dual Business &amp; Policy Sneak Peek
                        </p>
                    </td>
                </tr>
                @if($marinaHtml !== '')
                <tr>
                    <td style="padding:0 0 18px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f3f8fe;">
                            <tr>
                                <td style="padding:22px 24px 20px;border-left:7px solid #155f9f;">
                                    <div style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.7;color:#1f2937;">
                                        {!! $marinaStyledHtml !!}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif

                @if($maitamaHtml !== '')
                <tr>
                    <td style="padding:0;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f1f9f8;">
                            <tr>
                                <td style="padding:22px 24px 20px;border-left:7px solid #0a7a82;">
                                    <div style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.7;color:#1f2937;">
                                        {!! $maitamaStyledHtml !!}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
    @endif

    @if($primaryCtaUrl)
    <tr>
        <td style="padding:0 32px 30px;text-align:center;">
            <a href="{{ $primaryCtaUrl }}" style="display:inline-block;padding:13px 24px;background:#0d1b2a;color:#ffffff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;text-decoration:none;border-radius:3px;">
                Read the full analysis
            </a>
            <p style="margin:12px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.55;color:#6b7280;">
                See how policy choices and market flows shape everyday outcomes.
            </p>
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
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#6b7280;">
                                                {{ $item['primary_taxonomy_title'] }}
                                            </p>
                                        @endif
                                        <a href="{{ $item['url'] }}" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#0d1b2a;text-decoration:none;font-weight:700;">
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
                                        <a href="{{ $item['url'] }}" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#0d1b2a;text-decoration:none;font-weight:700;">
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
