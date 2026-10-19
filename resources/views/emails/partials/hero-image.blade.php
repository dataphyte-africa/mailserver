@php
    $heroHref = filled($heroImageLinkUrl ?? null) ? trim((string) $heroImageLinkUrl) : null;
    $heroAlt = $heroImageAlt ?? '';
    $heroWidth = $heroImageWidth ?? null;
    $heroImgStyle = $heroImageStyle ?? 'width:100%;height:auto;display:block;border:0;';
    $heroTdClass = $heroImageTdClass ?? null;
    $heroTdStyle = $heroImageTdStyle ?? 'padding:0;';
@endphp

@if(!empty($heroImageUrl))
<tr>
    <td @if($heroTdClass) class="{{ $heroTdClass }}" @endif style="{{ $heroTdStyle }}">
        @if($heroHref)
            <a href="{{ $heroHref }}" target="_blank" rel="noopener noreferrer" style="display:block;text-decoration:none;border:0;">
        @endif
        <img src="{{ $heroImageUrl }}"
             alt="{{ $heroAlt }}"
             @if($heroWidth) width="{{ $heroWidth }}" @endif
             style="{{ $heroImgStyle }}">
        @if($heroHref)
            </a>
        @endif
    </td>
</tr>
@endif
