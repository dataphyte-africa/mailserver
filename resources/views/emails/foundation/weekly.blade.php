@extends('emails.layout')

@php
    $hideCollectionHeader = true;
@endphp

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
        $weeklyContent = app(\App\Services\Newsletter\FoundationEmailContentFormatter::class)->format($content ?? '');

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
            @if(!empty($heroImageLinkUrl))<a href="{{ $heroImageLinkUrl }}">@endif
            <img src="{{ $heroImageUrl }}"
                 alt="{{ $subject }}"
                 width="600"
                 style="width:100%;height:auto;display:block;">
            @if(!empty($heroImageLinkUrl))</a>@endif
        </td>
    </tr>
    @endif

    @if(!empty($productName))
    <tr>
        <td class="content-padding" style="padding:22px 40px 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:{{ $brandColor }};">
            {{ $productName }}
        </td>
    </tr>
    @endif

    <tr>
        <td class="content-padding" style="padding:14px 40px 0;font-family:Georgia,'Times New Roman',serif;font-size:27px;font-weight:700;line-height:1.25;color:#172033;">
            {{ $subject }}
        </td>
    </tr>

    {{-- Date --}}
    <tr>
        <td class="content-padding"
            style="padding:20px 40px 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:13px;line-height:1.5;color:#7b8794;">
            {{ $displayDate }}@if(!empty($author)) &middot; {{ $author }}@endif
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
            <a href="{{ $ctaUrl ?? 'https://dataphyte.org' }}" target="_blank" rel="noopener noreferrer"
               style="display:inline-block;background-color:{{ $brandColor }};color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                {{ $ctaText ?? 'Visit Dataphyte Foundation' }} &rarr;
            </a>
        </td>
    </tr>

@endsection
