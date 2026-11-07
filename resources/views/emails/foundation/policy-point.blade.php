@extends('emails.layout')

@if(empty($heroImageUrl))
    @section('nameplate')
        <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:10px;font-weight:700;letter-spacing:2.6px;text-transform:uppercase;color:rgba(255,255,255,0.62);">Policy Point</span>
    @endsection
@endif

@section('content')
    @if(empty($heroImageUrl))
        <tr>
            <td class="mobile-tight" style="padding:0 32px 16px;background-color:{{ $headerColor ?? '#1b4332' }};">
                <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;line-height:1.5;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,0.72);">Policy insights from Dataphyte Foundation</p>
            </td>
        </tr>
    @endif

    @if(!empty($heroImageUrl))
        <tr>
            <td>
                @if(!empty($heroImageLinkUrl))<a href="{{ $heroImageLinkUrl }}">@endif
                <img src="{{ $heroImageUrl }}" alt="" style="width:100%;height:auto;display:block;border:0;">
                @if(!empty($heroImageLinkUrl))</a>@endif
            </td>
        </tr>
    @endif

    <tr>
        <td class="content-padding" style="padding:30px 32px 18px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;line-height:1.5;letter-spacing:1.2px;text-transform:uppercase;color:#7c8596;">{{ $sentDate ?? now()->format('F j, Y') }}</p>
        </td>
    </tr>

    @if(!empty($content))
        <tr>
            <td class="content-padding" style="padding:0 32px 26px;">
                <div class="policy-copy" style="font-family:Georgia,'Times New Roman',serif;font-size:18px;line-height:1.75;color:#31353d;">{!! $content !!}</div>
            </td>
        </tr>
    @endif

    @if(!empty($rssLeadItem))
        <tr>
            <td style="padding:0 0 28px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td class="mobile-tight" style="padding:0 32px 12px;">
                            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;line-height:1.5;letter-spacing:1.4px;text-transform:uppercase;color:#7c8596;">Lead story</p>
                        </td>
                    </tr>
                    @if(!empty($rssLeadItem['image_url']))
                        <tr><td style="padding:0 0 18px;"><img src="{{ $rssLeadItem['image_url'] }}" alt="" style="width:100%;height:auto;display:block;border:0;"></td></tr>
                    @endif
                    <tr>
                        <td class="mobile-tight" style="padding:0 32px;">
                            @if(!empty($rssLeadItem['primary_taxonomy_title']))
                                <p style="margin:0 0 8px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;text-transform:uppercase;color:#7c8596;">{{ $rssLeadItem['primary_taxonomy_title'] }}</p>
                            @endif
                            <h2 style="margin:0 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:1.2;color:#111827;"><a href="{{ $rssLeadItem['url'] }}" style="color:#111827;text-decoration:none;">{{ $rssLeadItem['title'] }}</a></h2>
                            @if(!empty($rssLeadItem['excerpt']))
                                <p style="margin:0 0 18px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#4b5563;">{{ $rssLeadItem['excerpt'] }}</p>
                            @endif
                            @if(!empty($rssLeadItem['author']))
                                <p style="margin:0 0 18px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;color:#6b7280;">{{ $rssLeadItem['author'] }}</p>
                            @endif
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td style="background-color:{{ $headerColor ?? '#1b4332' }};"><a href="{{ $rssLeadItem['url'] }}" style="display:block;padding:10px 22px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Read story</a></td></tr></table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    @if(!empty($rssSecondaryItems))
        <tr>
            <td class="content-padding" style="padding:0 32px 36px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr><td style="padding:0 0 16px;"><p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:1.3px;text-transform:uppercase;color:#7c8596;">More from Policy Point</p></td></tr>
                    @foreach($rssSecondaryItems as $item)
                        <tr>
                            <td style="padding:{{ $loop->first ? '0' : '18px' }} 0 18px;{{ $loop->last ? '' : 'border-bottom:1px solid #d9d1c6;' }}">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"><tr>
                                    @if(!empty($item['image_url']))
                                        <td class="story-image-cell-fixed" width="164" valign="top" style="padding:0 0 0 16px;"><img class="story-image-fixed" src="{{ $item['image_url'] }}" alt="" style="width:128px;height:128px;display:block;border:0;object-fit:cover;"></td>
                                    @endif
                                    <td class="story-copy-cell-fixed" valign="top" style="padding:0 0 0 16px;">
                                        @if(!empty($item['primary_taxonomy']['title']))
                                            <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;text-transform:uppercase;color:#7c8596;">{{ $item['primary_taxonomy']['title'] }}</p>
                                        @endif
                                        <h2 style="margin:0 0 6px;font-family:Georgia,'Times New Roman',serif;font-size:14px;line-height:1.28;"><a href="{{ $item['url'] }}" style="color:#111827;text-decoration:none;">{{ $item['title'] }}</a></h2>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    @endif

    @php
        $policyCtaText = $foundationCtaText ?? $ctaText ?? null;
        $policyCtaUrl = $foundationCtaUrl ?? $ctaUrl ?? null;
    @endphp
    @if(!empty($policyCtaText) && !empty($policyCtaUrl))
        <tr>
            <td class="content-padding" style="padding:0 32px 40px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #d9d1c6;"><tr><td style="padding:26px 0 0;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td style="background-color:{{ $headerColor ?? '#1b4332' }};"><a href="{{ $policyCtaUrl }}" style="display:block;padding:12px 18px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;font-weight:700;color:#ffffff;text-decoration:none;">{{ $policyCtaText }}</a></td></tr></table>
                </td></tr></table>
            </td>
        </tr>
    @endif
@endsection
