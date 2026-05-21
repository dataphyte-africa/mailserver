@php
    $productName = 'Data Dive';
    $lead = $rssLeadItem ?? null;
    $secondary = collect($rssSecondaryItems ?? [])->take(4);
    $related = collect($relatedRssItems ?? [])->take(4);
    $recommended = collect($recommendedRssItems ?? [])->take(4);
@endphp

@extends('emails.layout')

@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:11px;font-weight:700;letter-spacing:2.6px;
                 text-transform:uppercase;color:rgba(255,255,255,0.72);">
        {{ $productName }}
    </span>
@endsection

@section('content')

    @if(!empty($heroImageUrl))
    <tr>
        <td style="padding:0;">
            <img src="{{ $heroImageUrl }}" alt="" width="600"
                 style="width:100%;max-width:600px;height:auto;display:block;">
        </td>
    </tr>
    @endif

    <tr>
        <td style="padding:28px 32px 10px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                Dataphyte Insight
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:0 32px 10px;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:31px;line-height:1.18;color:#0d1b2a;">
                {{ $subject }}
            </h1>
        </td>
    </tr>

    <tr>
        <td style="padding:0 32px 24px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.6;color:#6b7280;">
                {{ $sentDate ?? now()->format('F j, Y') }}
                @if(!empty($author))
                    &nbsp;&middot;&nbsp; {{ $author }}
                @endif
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:0 32px 28px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;">
            {!! $content !!}
        </td>
    </tr>

    @if($lead)
    <tr>
        <td style="padding:0 32px 28px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #d4d9e2;border-bottom:1px solid #d4d9e2;">
                <tr>
                    <td style="padding:24px 0;">
                        <p style="margin:0 0 10px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                            Lead Story
                        </p>
                        @if(!empty($lead['image_url']))
                            <img src="{{ $lead['image_url'] }}" alt="" width="536" style="width:100%;max-width:536px;height:auto;display:block;margin:0 0 16px;">
                        @endif
                        <h2 style="margin:0 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.2;color:#0d1b2a;">
                            <a href="{{ $lead['url'] }}" style="color:#0d1b2a;text-decoration:none;">{{ $lead['title'] }}</a>
                        </h2>
                        @if(!empty($lead['excerpt']))
                            <p style="margin:0 0 16px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.7;color:#374151;">
                                {{ $lead['excerpt'] }}
                            </p>
                        @endif
                        <a href="{{ $lead['url'] }}" style="display:inline-block;padding:12px 22px;background:#0d1b2a;color:#ffffff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;text-decoration:none;border-radius:2px;">
                            Read Story
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    @if($secondary->isNotEmpty())
    <tr>
        <td style="padding:0 32px 28px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                More from {{ $productName }}
            </p>
            @foreach($secondary as $item)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:18px 0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td valign="top" style="padding-right:16px;">
                                        <h3 style="margin:0 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.22;color:#0d1b2a;">
                                            <a href="{{ $item['url'] }}" style="color:#0d1b2a;text-decoration:none;">{{ $item['title'] }}</a>
                                        </h3>
                                        @if(!empty($item['primary_taxonomy_title']))
                                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#6b7280;">
                                                {{ $item['primary_taxonomy_title'] }}
                                            </p>
                                        @endif
                                    </td>
                                    @if(!empty($item['image_url']))
                                        <td valign="top" width="120">
                                            <img src="{{ $item['image_url'] }}" alt="" width="120" style="width:120px;height:120px;object-fit:cover;display:block;">
                                        </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endforeach
        </td>
    </tr>
    @endif

    @if($related->isNotEmpty())
    <tr>
        <td style="padding:0 32px 24px;">
            <p style="margin:0 0 14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#6b7280;">
                Related Across Dataphyte Newsletters
            </p>
            @foreach($related as $item)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:14px 0;">
                            <a href="{{ $item['url'] }}" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#0d1b2a;text-decoration:none;font-weight:700;">
                                {{ $item['title'] }}
                            </a>
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
                Recommended Reads on Socio-Economic Issues
            </p>
            @foreach($recommended as $item)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:14px 0;">
                            <a href="{{ $item['url'] }}" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#0d1b2a;text-decoration:none;font-weight:700;">
                                {{ $item['title'] }}
                            </a>
                        </td>
                    </tr>
                </table>
            @endforeach
        </td>
    </tr>
    @endif

@endsection
