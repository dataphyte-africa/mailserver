@extends('emails.layout')

@php
    $hideCollectionHeader = true;
@endphp

@section('content')
    @php
        $brandColor = $headerColor ?? ($newsletterSettings['academy_brand_color'] ?? '#0f766e');
        $ctaText = filled($ctaText ?? null) ? trim((string) $ctaText) : 'Explore Dataphyte Academy';
        $ctaUrl = filled($ctaUrl ?? null) ? trim((string) $ctaUrl) : 'https://academy.dataphyte.com';
        $datalabContent = preg_replace(
            '/<p\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<p$1style="margin:0 0 12px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.7;color:#1f2937;"$3>',
            $content ?? ''
        );
        $datalabContent = preg_replace(
            '/<ul\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<ul$1style="margin:0 0 18px;padding-left:22px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;color:#1f2937;"$3>',
            $datalabContent
        );
        $datalabContent = preg_replace(
            '/<ol\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<ol$1style="margin:0 0 18px;padding-left:22px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;color:#1f2937;"$3>',
            $datalabContent
        );
        $datalabContent = preg_replace(
            '/<li\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<li$1style="margin:0 0 4px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.38;color:#1f2937;"$3>',
            $datalabContent
        );
        $datalabContent = preg_replace(
            '/<h3\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<h3$1style="margin:0 0 12px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;font-weight:700;color:#111827;"$3>',
            $datalabContent
        );
        $datalabContent = preg_replace(
            '/<h2\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<h2$1style="margin:0 0 14px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:18px;line-height:1.4;font-weight:700;color:#111827;"$3>',
            $datalabContent
        );
    @endphp

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

    <tr>
        <td class="content-padding"
            style="padding:32px 40px 24px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.75;color:#333333;">
            {!! $datalabContent !!}
        </td>
    </tr>

    <tr>
        <td style="padding:0 40px 40px;text-align:center;">
            <a href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer"
               style="display:inline-block;background-color:{{ $brandColor }};color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                {{ $ctaText }} &rarr;
            </a>
        </td>
    </tr>
@endsection
