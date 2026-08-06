<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Maps a Bay Area service area to the local phone number we advertise there.
 *
 * The mapping lives in JSON datasets rather than on the Webflow city record so
 * that a Webflow re-import cannot wipe it. Cities without a region fall back
 * to the general site number.
 *
 * Numeric utm_city values come from ad platforms and use different id spaces:
 * Google fills {loc_physical_ms}, Bing fills {loc_physical}. Each has its own
 * map; the traffic source picks which one to consult first.
 */
final class ServiceAreaRegions
{
    public const GEO_GOOGLE = 'google';

    public const GEO_BING = 'bing';

    /** @var array{regions: array<string, array<string, string>>, cities: array<string, array<string, string|null>>}|null */
    private ?array $data = null;

    /** @var array<int, string>|null */
    private ?array $googleGeoTargets = null;

    /** @var array<int, string>|null */
    private ?array $bingGeoTargets = null;

    /**
     * @return array{key: string, label: string, phone_display: string, phone_tel: string}|null
     */
    public function forCitySlug(?string $slug): ?array
    {
        $city = $this->city($slug);
        $key = $city['region'] ?? null;

        return is_string($key) ? $this->region($key) : null;
    }

    /**
     * @return array{key: string, label: string, phone_display: string, phone_tel: string}|null
     */
    public function forCityName(?string $name): ?array
    {
        return $this->forCitySlug($name === null ? null : Str::slug($name));
    }

    /**
     * Resolve a raw utm_city value: a city slug/name, or a numeric geo id from
     * Google ({loc_physical_ms}) or Bing ({loc_physical}).
     *
     * @param  self::GEO_GOOGLE|self::GEO_BING|null  $platform  preferred id space; null tries both
     * @return array{slug: string, name: string, region: array{key: string, label: string, phone_display: string, phone_tel: string}|null}|null
     */
    public function resolveUtmCity(?string $value, ?string $platform = null): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! ctype_digit($value)) {
            return $this->cityBySlug(Str::slug($value));
        }

        $id = (int) $value;
        $order = $this->geoLookupOrder($platform);

        foreach ($order as $provider) {
            $slug = $this->geoTargets($provider)[$id] ?? null;
            if ($slug !== null) {
                return $this->cityBySlug($slug);
            }
        }

        return null;
    }

    /**
     * Human readable form of a raw utm_city value for the admin screens.
     */
    public function utmCityLabel(?string $value, ?string $platform = null): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        $city = $this->resolveUtmCity($value, $platform);

        return $city === null
            ? $value.' (unmatched)'
            : $city['name'].' — '.$value;
    }

    /**
     * Pick google / bing from common attribution fields (utm_source, click ids).
     *
     * @param  array{utm_source?: mixed, gclid?: mixed, msclkid?: mixed, first_utm_source?: mixed, first_gclid?: mixed, first_msclkid?: mixed}  $touch
     * @return self::GEO_GOOGLE|self::GEO_BING|null
     */
    public function platformFromAttribution(array $touch): ?string
    {
        $msclkid = trim((string) ($touch['msclkid'] ?? $touch['first_msclkid'] ?? ''));
        if ($msclkid !== '') {
            return self::GEO_BING;
        }

        $gclid = trim((string) ($touch['gclid'] ?? $touch['first_gclid'] ?? ''));
        if ($gclid !== '') {
            return self::GEO_GOOGLE;
        }

        $source = strtolower(trim((string) ($touch['utm_source'] ?? $touch['first_utm_source'] ?? '')));
        if ($source === '') {
            return null;
        }

        if (
            $source === 'bing'
            || $source === 'msn'
            || str_contains($source, 'bing')
            || str_contains($source, 'microsoft')
        ) {
            return self::GEO_BING;
        }

        if (
            $source === 'google'
            || $source === 'adwords'
            || str_contains($source, 'google')
        ) {
            return self::GEO_GOOGLE;
        }

        return null;
    }

    /**
     * @return array{slug: string, name: string, region: array{key: string, label: string, phone_display: string, phone_tel: string}|null}|null
     */
    public function cityBySlug(?string $slug): ?array
    {
        $slug = $this->normalizeSlug($slug);
        $city = $this->city($slug);

        if ($slug === null || $city === null) {
            return null;
        }

        $key = $city['region'] ?? null;

        return [
            'slug' => $slug,
            'name' => (string) ($city['name'] ?? ''),
            'region' => is_string($key) ? $this->region($key) : null,
        ];
    }

    /**
     * @return array{key: string, label: string, phone_display: string, phone_tel: string}|null
     */
    public function region(string $key): ?array
    {
        $region = $this->data()['regions'][$key] ?? null;
        if (! is_array($region)) {
            return null;
        }

        return [
            'key' => $key,
            'label' => (string) ($region['label'] ?? ''),
            'phone_display' => (string) ($region['phone_display'] ?? ''),
            'phone_tel' => (string) ($region['phone_tel'] ?? ''),
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function cities(): array
    {
        return $this->data()['cities'];
    }

    /**
     * @param  self::GEO_GOOGLE|self::GEO_BING  $platform
     * @return array<int, string>
     */
    public function geoTargets(string $platform = self::GEO_GOOGLE): array
    {
        if ($platform === self::GEO_BING) {
            return $this->bingGeoTargets ??= $this->readGeoMap(database_path('data/bing-geo-targets.json'));
        }

        return $this->googleGeoTargets ??= $this->readGeoMap(database_path('data/google-geo-targets.json'));
    }

    /**
     * @return array<int, string>
     */
    private function readGeoMap(string $path): array
    {
        $decoded = $this->readJson($path);
        $map = [];
        foreach (is_array($decoded) ? $decoded : [] as $id => $slug) {
            if (is_string($slug) && $slug !== '') {
                $map[(int) $id] = $slug;
            }
        }

        return $map;
    }

    /**
     * @return list<self::GEO_GOOGLE|self::GEO_BING>
     */
    private function geoLookupOrder(?string $platform): array
    {
        return match ($platform) {
            self::GEO_BING => [self::GEO_BING, self::GEO_GOOGLE],
            self::GEO_GOOGLE => [self::GEO_GOOGLE, self::GEO_BING],
            default => [self::GEO_GOOGLE, self::GEO_BING],
        };
    }

    /**
     * @return array<string, string|null>|null
     */
    private function city(?string $slug): ?array
    {
        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            return null;
        }

        $city = $this->data()['cities'][$slug] ?? null;

        return is_array($city) ? $city : null;
    }

    private function normalizeSlug(?string $slug): ?string
    {
        $slug = strtolower(trim((string) $slug));

        return $slug === '' ? null : $slug;
    }

    /**
     * @return array{regions: array<string, array<string, string>>, cities: array<string, array<string, string|null>>}
     */
    private function data(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $decoded = $this->readJson(database_path('data/service-area-regions.json'));

        return $this->data = [
            'regions' => is_array($decoded['regions'] ?? null) ? $decoded['regions'] : [],
            'cities' => is_array($decoded['cities'] ?? null) ? $decoded['cities'] : [],
        ];
    }

    private function readJson(string $path): mixed
    {
        return is_file($path)
            ? json_decode((string) file_get_contents($path), true)
            : null;
    }
}
