<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Maps a Bay Area service area to the local phone number we advertise there.
 *
 * The mapping lives in JSON datasets rather than on the Webflow city record so
 * that a Webflow re-import cannot wipe it. Cities without a region (Solano
 * County today) simply fall back to the general site number.
 */
final class ServiceAreaRegions
{
    /** @var array{regions: array<string, array<string, string>>, cities: array<string, array<string, string|null>>}|null */
    private ?array $data = null;

    /** @var array<int, string>|null Google geo criteria id => city slug */
    private ?array $geoTargets = null;

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
     * Resolve a raw utm_city value, which is either one of our slugs or the
     * numeric Google Ads geo criteria id produced by {loc_physical_ms}.
     *
     * @return array{slug: string, name: string, region: array{key: string, label: string, phone_display: string, phone_tel: string}|null}|null
     */
    public function resolveUtmCity(?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $slug = ctype_digit($value)
            ? ($this->geoTargets()[(int) $value] ?? null)
            : Str::slug($value);

        return $slug === null ? null : $this->cityBySlug($slug);
    }

    /**
     * Human readable form of a raw utm_city value for the admin screens.
     */
    public function utmCityLabel(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        $city = $this->resolveUtmCity($value);

        return $city === null
            ? $value.' (unmatched)'
            : $city['name'].' — '.$value;
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
     * @return array<int, string>
     */
    public function geoTargets(): array
    {
        if ($this->geoTargets !== null) {
            return $this->geoTargets;
        }

        $decoded = $this->readJson(database_path('data/google-geo-targets.json'));

        $map = [];
        foreach (is_array($decoded) ? $decoded : [] as $id => $slug) {
            if (is_string($slug) && $slug !== '') {
                $map[(int) $id] = $slug;
            }
        }

        return $this->geoTargets = $map;
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
