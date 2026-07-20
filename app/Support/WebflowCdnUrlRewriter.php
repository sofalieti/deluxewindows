<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Rewrites Webflow CDN image URLs to local /webflow-assets/images/... paths
 * when the file already exists on disk (same rules as webflow_image_url()).
 */
final class WebflowCdnUrlRewriter
{
    private const CDN_HOST_NEEDLES = ['website-files.com', 'webflow.com'];

    /**
     * @return array{0:mixed,1:bool} [rewritten value, whether anything changed]
     */
    public static function rewrite(mixed $value): array
    {
        if (is_array($value)) {
            $changed = false;
            $result = [];
            foreach ($value as $key => $item) {
                [$newItem, $itemChanged] = self::rewrite($item);
                $result[$key] = $newItem;
                $changed = $changed || $itemChanged;
            }

            return [$result, $changed];
        }

        if (! is_string($value) || $value === '') {
            return [$value, false];
        }

        return self::rewriteString($value);
    }

    /**
     * @return array{0:string,1:bool}
     */
    public static function rewriteString(string $value): array
    {
        if (! self::stringMayContainCdn($value)) {
            return [$value, false];
        }

        // Whole-string URL (typical image.url field).
        if (self::isHttpUrl($value) && self::isCdnHost($value)) {
            $local = webflow_image_url($value);
            if ($local !== '' && $local !== $value && self::isLocalAssetUrl($local)) {
                return [$local, true];
            }

            // Prefer deterministic local path when basename sanitizes to an existing file.
            $candidate = WebflowAssetName::localUrl($value);
            if ($candidate !== '' && self::localFileExists($candidate)) {
                return [$candidate, true];
            }

            return [$value, false];
        }

        // Embedded URLs inside HTML / JSON / rich text.
        if (! preg_match_all('~https?://[^\s"\'<>(),\\\\]+~i', $value, $matches)) {
            return [$value, false];
        }

        $changed = false;
        $updated = $value;

        foreach (array_unique($matches[0]) as $url) {
            if (! self::isCdnHost($url)) {
                continue;
            }

            $local = webflow_image_url($url);
            if ($local === '' || $local === $url || ! self::isLocalAssetUrl($local)) {
                $candidate = WebflowAssetName::localUrl($url);
                if ($candidate === '' || ! self::localFileExists($candidate)) {
                    continue;
                }
                $local = $candidate;
            }

            $updated = str_replace($url, $local, $updated);
            $changed = true;
        }

        return [$updated, $changed];
    }

    public static function isCdnHost(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return false;
        }

        foreach (self::CDN_HOST_NEEDLES as $needle) {
            if (str_contains($host, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function stringMayContainCdn(string $value): bool
    {
        foreach (self::CDN_HOST_NEEDLES as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function isHttpUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    private static function isLocalAssetUrl(string $url): bool
    {
        return str_starts_with($url, '/webflow-assets/')
            || str_starts_with($url, '/storage/')
            || str_starts_with($url, '/webflow-media/');
    }

    private static function localFileExists(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }

        $relative = ltrim($path, '/');
        if ($relative === '') {
            return false;
        }

        return is_file(public_path($relative));
    }
}
