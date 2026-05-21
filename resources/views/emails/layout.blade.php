<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? '' }}</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; background-color: #efe9df; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .email-shell { padding: 0 !important; }
            .stack-column, .stack-column-center { display: block !important; width: 100% !important; max-width: 100% !important; }
            .stack-column-center { text-align: center !important; }
            .hide-mobile { display: none !important; max-height: 0; overflow: hidden; }
            .hero-image img { height: auto !important; max-width: 100% !important; }
            .content-padding { padding: 20px !important; }
            .mobile-tight { padding-left: 20px !important; padding-right: 20px !important; }
            .mobile-center { text-align: center !important; }
            .story-image-cell { display: block !important; width: 100% !important; padding: 0 0 14px !important; }
            .story-copy-cell { display: block !important; width: 100% !important; }
            .story-image { width: 100% !important; max-width: 100% !important; height: auto !important; }
            .story-image-cell-fixed { width: 96px !important; padding-left: 0 !important; }
            .story-copy-cell-fixed { padding-left: 8px !important; }
            .story-image-fixed { width: 84px !important; max-width: 84px !important; height: 84px !important; }
            .office-column { padding-left: 0 !important; padding-right: 0 !important; }
            .policy-copy,
            .policy-copy p { font-size: 16px !important; line-height: 1.65 !important; }
            .policy-kicker { font-size: 10px !important; letter-spacing: 1.1px !important; }
            .policy-meta { font-size: 12px !important; line-height: 1.5 !important; }
            .policy-lead-excerpt { font-size: 14px !important; line-height: 1.55 !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#efe9df;">

    {{-- Preheader (hidden in inbox preview) --}}
    @if(!empty($preheader))
    <div style="display:none;font-size:1px;color:#f4f4f4;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
        {{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    @endif

    {{-- Email wrapper --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#efe9df;">
        <tr>
            <td class="email-shell" style="padding:18px 10px;">

                {{-- Email container --}}
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="640"
                       class="email-container"
                       style="margin:0 auto;background-color:#fffdf9;overflow:hidden;">

                    {{-- ============================================================ --}}
                    {{-- HEADER BAND                                                   --}}
                    {{-- Upper: collection logo (editor-controlled via GlobalSet)     --}}
                    {{-- Lower: product/template nameplate (hardcoded per template)   --}}
                    {{-- ============================================================ --}}
                    @if(empty($hideCollectionHeader) && !empty($collectionLogo))
                        <tr>
                            <td style="background-color:{{ $headerColor ?? '#1a1a2e' }};padding:0;">
                                <img src="{{ $collectionLogo }}"
                                     alt="{{ $fromName ?? config('app.name') }}"
                                     style="width:100%;height:auto;display:block;border:0;">
                            </td>
                        </tr>
                    @elseif(empty($hideCollectionHeader) && $__env->hasSection('nameplate'))
                        <tr>
                            <td class="mobile-tight" style="background-color:{{ $headerColor ?? '#1a1a2e' }};padding:22px 32px 18px;text-align:center;">
                                @yield('nameplate')
                            </td>
                        </tr>
                    @endif
                    {{-- /nameplate --}}

                    {{-- ============================================================ --}}
                    {{-- MAIN CONTENT                                                  --}}
                    {{-- ============================================================ --}}
                    @yield('content')

                    {{-- ============================================================ --}}
                    {{-- FOOTER                                                        --}}
                    {{-- ============================================================ --}}
                    @includeIf($footerPartial ?? 'emails.partials.shared.footer-base')

                </table>
                {{-- /Email container --}}

            </td>
        </tr>
    </table>
    {{-- /Email wrapper --}}

</body>
</html>
