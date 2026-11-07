<?php

namespace App\Services\Newsletter;

class FoundationEmailContentFormatter
{
    public function format(string $content, bool $compactHeadings = false): string
    {
        if ($compactHeadings) {
            $content = preg_replace_callback('/<h3\b[^>]*>.*?<\/h3>/is', static function (array $match): string {
                $heading = preg_replace('/(<h3\b[^>]*style="[^"]*?)font-size:\s*\d+px/i', '$1font-size:16px', $match[0]);
                $heading = preg_replace('/(<h3\b[^>]*style="[^"]*)"/i', '$1;font-weight:600;"', $heading, 1);

                return preg_replace('/(<strong\b[^>]*style="[^"]*?)font-weight:\s*\d+/i', '$1font-weight:600', $heading);
            }, $content);
        }

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
