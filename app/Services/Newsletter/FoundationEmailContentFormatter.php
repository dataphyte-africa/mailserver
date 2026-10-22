<?php

namespace App\Services\Newsletter;

class FoundationEmailContentFormatter
{
    public function format(string $content): string
    {
        $content = preg_replace(
            '/<ul\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<ul$1style="margin:0 0 18px;padding-left:22px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;color:#1f2937;"$3>',
            $content,
        );
        $content = preg_replace(
            '/<ol\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<ol$1style="margin:0 0 18px;padding-left:22px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.45;color:#1f2937;"$3>',
            $content,
        );
        $content = preg_replace(
            '/<li\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<li$1style="margin:0 0 4px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.38;color:#1f2937;"$3>',
            $content,
        );
        $content = preg_replace(
            '/<p\b([^>]*)style="([^"]*)"([^>]*)>/i',
            '<p$1style="margin:0 0 12px;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.7;color:#1f2937;"$3>',
            $content,
        );
        $content = preg_replace(
            '/<li([^>]*)>\s*<p\b([^>]*)style="[^"]*"([^>]*)>/i',
            '<li$1><p$2style="margin:0;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.38;color:#1f2937;"$3>',
            $content,
        );

        return preg_replace(
            '/<li([^>]*)>\s*<p([^>]*)>/i',
            '<li$1><p$2 style="margin:0;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.28;color:#1f2937;">',
            $content,
        );
    }
}
