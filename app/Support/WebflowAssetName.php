<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Single source of truth for how a Webflow image URL maps to a local file
 * name. Names are sanitized so they never contain spaces or percent-encoding,
 * which web servers decode before the filesystem lookup (causing 404s).
 */
final class WebflowAssetName
{
    /**
     * Sanitized local basename for a CDN URL, local URL or raw basename.
     * "684d..._Frame%2039.avif" -> "684d..._Frame-39.avif".
     */
    public static function basename(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }

        $path = parse_url($source, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $source;
        }

        $basename = basename($path);
        if ($basename === '') {
            return '';
        }

        $decoded = rawurldecode($basename);

        $ext = strtolower(pathinfo($decoded, PATHINFO_EXTENSION));
        $name = pathinfo($decoded, PATHINFO_FILENAME);

        $name = preg_replace('~[^A-Za-z0-9._-]+~', '-', $name) ?? '';
        $name = trim($name, '-._');
        if ($name === '') {
            $name = 'image';
        }

        $ext = preg_replace('~[^A-Za-z0-9]+~', '', $ext) ?? '';

        return $ext !== '' ? $name.'.'.$ext : $name;
    }

    /**
     * Internal URL for a source, e.g. "/webflow-assets/images/<sanitized>".
     */
    public static function localUrl(string $source): string
    {
        $basename = self::basename($source);

        return $basename === '' ? '' : '/webflow-assets/images/'.$basename;
    }
}
