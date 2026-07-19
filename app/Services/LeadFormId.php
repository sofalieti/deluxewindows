<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Human-readable Form ID for Google lead bridge (sheet column "Form ID").
 */
final class LeadFormId
{
    public static function fromUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return 'Unknown Page Form';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $url;
        }

        return self::fromPath($path);
    }

    public static function fromPath(string $path): string
    {
        $path = '/'.trim($path, '/');
        if ($path === '/') {
            return 'Home Page Form';
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $first = $segments[0] ?? '';
        $slug = $segments[1] ?? '';

        return match ($first) {
            'windows' => $slug === ''
                ? 'Windows Index Form'
                : self::title($slug, strip: 'windows').' Page Form',
            'doors' => $slug === ''
                ? 'Doors Index Form'
                : self::title($slug, strip: 'doors').' Page Form',
            'brands' => self::title($slug).' Window Form',
            'door-brands' => self::title($slug).' Door Form',
            'window-type' => self::title($slug).' Form',
            'door-types' => self::title($slug).' Form',
            'brand-collections' => self::title(preg_replace('/^brand-/', '', $slug) ?: $slug).' Collection Form',
            'window-replacement' => self::title($slug).' Window Replacement Form',
            'county-hub-pages' => self::title($slug).' County Hub Form',
            'blog' => $slug === ''
                ? 'Blog Index Form'
                : self::title($slug).' Blog Form',
            'brand' => 'Brands Catalog Form',
            'contacts' => 'Contacts Page Form',
            'about' => 'About Page Form',
            'financing' => 'Financing Page Form',
            'special-offers' => 'Special Offers Form',
            'gallery' => 'Gallery Page Form',
            'faq' => 'FAQ Page Form',
            'testimonials' => 'Testimonials Page Form',
            'glossary' => 'Glossary Page Form',
            default => self::title($slug !== '' ? $slug : $first).' Page Form',
        };
    }

    private static function title(string $slug, string $strip = ''): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return 'Page';
        }

        if ($strip !== '' && str_ends_with($slug, '-'.$strip)) {
            $slug = substr($slug, 0, -strlen($strip) - 1);
        }

        $parts = preg_split('/[-_]+/', $slug) ?: [];
        $parts = array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));

        $words = array_map(static function (string $word): string {
            return match ($word) {
                'and' => 'and',
                'vs' => 'vs',
                default => ucfirst($word),
            };
        }, $parts);

        return $words === [] ? 'Page' : implode(' ', $words);
    }
}
