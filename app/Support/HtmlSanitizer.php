<?php

declare(strict_types=1);

namespace App\Support;

final class HtmlSanitizer
{
    public static function mailboxBody(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><br/><b><strong><i><em><u><a><ul><ol><li><h1><h2><h3><h4><blockquote><pre><code><span><div><table><thead><tbody><tr><td><th><img>';
        $clean = strip_tags($html, $allowed);

        // Drop dangerous attributes / javascript: URLs
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '$1="#"', $clean) ?? $clean;

        return $clean;
    }
}
