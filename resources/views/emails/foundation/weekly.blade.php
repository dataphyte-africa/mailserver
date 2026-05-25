@extends('emails.layout')

@php
    $hideCollectionHeader = true;
@endphp

{{-- Drop a 280×40px PNG at public/assets/email/foundation-weekly.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
    </span>
@endsection

@section('content')
    @php
        $brandColor = $headerColor ?? ($newsletterSettings['foundation_brand_color'] ?? '#1b4332');
        $displayDate = $sentDate ?? now()->format('F j, Y');
        $weeklyContent = preg_replace(
            '/<ul\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<ul$1style="margin:0 0 18px;padding-left:22px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;color:#1f2937;"$3>',
            $content ?? ''
        );
        $weeklyContent = preg_replace(
            '/<ol\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<ol$1style="margin:0 0 18px;padding-left:22px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;color:#1f2937;"$3>',
            $weeklyContent
        );
        $weeklyContent = preg_replace(
            '/<li\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<li$1style="margin:0 0 4px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.38;color:#1f2937;"$3>',
            $weeklyContent
        );
        $weeklyContent = preg_replace(
            '/<p\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<p$1style="margin:0 0 12px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.7;color:#1f2937;"$3>',
            $weeklyContent
        );
        $weeklyContent = preg_replace(
            '/<li([^>]*)>\s*<p\b([^>]*)style="[^"]*"([^>]*)>/i',
            '<li$1><p$2style="margin:0;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.38;color:#1f2937;"$3>',
            $weeklyContent
        );
        $weeklyContent = preg_replace(
            '/<li([^>]*)>\s*<p([^>]*)>/i',
            '<li$1><p$2 style="margin:0;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.28;color:#1f2937;">',
            $weeklyContent
        );

        try {
            $displayDate = \Illuminate\Support\Carbon::parse($displayDate)->format('l, F j, Y');
        } catch (\Throwable $e) {
            // Keep the provided value if it cannot be parsed.
        }
    @endphp

    {{-- Hero image --}}
    @if(!empty($heroImageUrl))
    <tr>
        <td class="hero-image" style="padding:0;">
            <img src="{{ $heroImageUrl }}"
                 alt="{{ $subject }}"
                 width="600"
                 style="width:100%;height:auto;display:block;">
        </td>
    </tr>
    @endif

    {{-- Date --}}
    <tr>
        <td class="content-padding"
            style="padding:20px 40px 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:13px;line-height:1.5;color:#7b8794;">
            {{ $displayDate }}
        </td>
    </tr>


    {{-- Body --}}
    <tr>
        <td class="content-padding"
            style="padding:24px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.75;color:#333333;">
            {!! $weeklyContent !!}
        </td>
    </tr>

    {{-- CTA --}}
    <tr>
        <td style="padding:0 40px 40px;text-align:center;">
            <a href="{{ $foundationCtaUrl ?? 'https://dataphyte.org' }}" target="_blank" rel="noopener noreferrer"
               style="display:inline-block;background-color:{{ $brandColor }};color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                {{ $foundationCtaText ?? 'Visit Dataphyte Foundation' }} &rarr;
            </a>
        </td>
    </tr>

@endsection
