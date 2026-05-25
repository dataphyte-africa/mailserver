@php
    $productName = 'Pocket Science';
    $pocketPrimary = '#136097';
    $pocketSecondary = '#357088';
    $pocketPanelBg = '#357088';
    $pocketLabelTint = '#d7e8f1';
    $hideCollectionHeader = true;
    $lead = $rssLeadItem ?? null;
    $secondary = collect($rssSecondaryItems ?? [])->take(4);
    $related = collect($relatedRssItems ?? [])->take(4);
    $recommended = collect($recommendedRssItems ?? [])->take(4);
    $pocketIntelligence = collect($pocketIntelligenceItems ?? [])->values();
    $pocketIntelligenceVisible = $pocketIntelligence->take(2);
    $formattedSentDate = $sentDate
        ? \Illuminate\Support\Carbon::parse($sentDate)->format('l, F j, Y')
        : '';
@endphp

@extends('emails.layout')

@section('content')

    @if(!empty($heroImageUrl))
    <tr>
        <td style="padding:0;">
            <img src="{{ $heroImageUrl }}" alt=""
                 style="width:100%;height:auto;display:block;border:0;">
        </td>
    </tr>
    @endif

    @if($formattedSentDate !== '')
    <tr>
        <td style="padding:20px 32px 16px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#7b8794;">
                {{ $formattedSentDate }}
            </p>
        </td>
    </tr>
    @endif

    <tr>
        <td style="padding:20px 32px 28px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;">
            {!! $content !!}
        </td>
    </tr>

    @if($pocketIntelligenceVisible->isNotEmpty())
    <tr>
        <td style="padding:0 32px 28px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:{{ $pocketPanelBg }};border-radius:10px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 24px 10px;position:relative;">
                        <div style="position:absolute;top:16px;right:18px;width:62px;height:62px;border-radius:62px;background:rgba(255,255,255,0.10);color:rgba(255,255,255,0.28);font-family:Georgia,'Times New Roman',serif;font-size:44px;line-height:62px;text-align:center;">i</div>
                        <h2 style="margin:0 0 4px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:18px;line-height:1.1;color:#ffffff;font-weight:700;position:relative;z-index:1;padding-right:72px;">
                            {{ $pocketIntelligenceTitle ?: 'Pocket Intelligence' }}
                        </h2>
                        @if(!empty($pocketIntelligenceSubtitle))
                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.45;color:#dbe9f6;position:relative;z-index:1;padding-right:72px;">
                                {{ $pocketIntelligenceSubtitle }}
                            </p>
                        @endif
                    </td>
                </tr>
                @foreach($pocketIntelligenceVisible as $item)
                <tr>
                    <td style="padding:0 16px 10px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:rgba(255,255,255,0.12);border-radius:8px;">
                            <tr>
                                <td valign="top" style="padding:16px 18px 16px;position:relative;">
                                    @if(!empty($item['number']))
                                        <p style="margin:0;position:absolute;top:10px;right:14px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:34px;font-weight:700;line-height:1;color:rgba(207,224,240,0.22);">
                                            {{ $item['number'] }}
                                        </p>
                                    @endif
                                    <div style="position:relative;z-index:1;">
                                        <h3 style="margin:0 0 10px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.2;color:#ffffff;font-weight:700;padding-right:52px;">
                                            {{ $item['title'] ?? '' }}
                                        </h3>
                                        @if(!empty($item['description']))
                                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#eaf2f9;max-width:100%;">
                                                {{ $item['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endforeach
                @if($lead && $pocketIntelligenceVisible->isNotEmpty())
                <tr>
                    <td style="padding:4px 16px 20px;">
                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.55;color:#dbe9f6;">
                                    <a href="{{ $lead['url'] }}" target="_blank" style="color:#ffffff;text-decoration:underline;font-weight:700;">
                                        Continue Reading
                                    </a>
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


    @if($lead)
    <tr>
        <td style="padding:0 32px 40px;text-align:center;">
            <a href="{{ $lead['url'] }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:13px 24px;background:{{ $pocketPrimary }};color:#ffffff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;text-decoration:none;border-radius:3px;">
                Continue Reading
            </a>
            @if(!empty($lead['title']))
                <p style="margin:10px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.45;color:#6b7280;">
                    <span style="color:#1f2937;font-weight:600;">{{ $lead['title'] }}</span>
                </p>
            @endif
            @if(!empty($lead['author']))
                <p style="margin:3px 0 0;color:#6b7280;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.4;">
                    By {{ $lead['author'] }}
                </p>
            @endif
            <div style="height:1px;max-height:1px;line-height:1px;font-size:1px;background:#d9e1ea;width:100%;margin:14px 0 0;">&nbsp;</div>
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
                                        <h3 style="margin:0 0 8px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:20px;line-height:1.18;color:#0d1b2a;font-weight:800;">
                                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" style="color:#0d1b2a;text-decoration:none;">{{ $item['title'] }}</a>
                                        </h3>
                                        @if(!empty($item['primary_taxonomy_title']))
                                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:{{ $pocketSecondary }};">
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
        <td style="padding:18px 32px 24px;">
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
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:{{ $pocketSecondary }};">
                                                {{ $item['primary_taxonomy_title'] }}
                                            </p>
                                        @endif
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#0d1b2a;text-decoration:none;font-weight:700;">
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
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:{{ $pocketSecondary }};">
                                                {{ $item['primary_taxonomy_title'] }}
                                            </p>
                                        @endif
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#0d1b2a;text-decoration:none;font-weight:700;">
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
