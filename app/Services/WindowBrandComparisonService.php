<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WindowBrandComparisonService
{
    private const EXPECTED_BRAND_SLUGS = [
        'all-weather-architectural-aluminum',
        'alside',
        'andersen',
        'anlin',
        'italwindows',
        'jeld-wen',
        'marvin',
        'milgard',
        'ply-gem',
        'simonton',
        'western-window-systems',
    ];

    /** @var array{mtime: int, data: array<string, mixed>}|null */
    private ?array $cache = null;

    /**
     * @return array<string, mixed>|null
     */
    public function forSlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));

        try {
            $data = $this->load();
            $brands = $data['brands'];

            if (! isset($brands[$slug])) {
                return null;
            }

            $peerSlugs = $brands[$slug]['default_peers'];

            return [
                'current_slug' => $slug,
                'current' => $brands[$slug],
                'peer_slugs' => $peerSlugs,
                'peers' => array_map(
                    static fn (string $peerSlug): array => $brands[$peerSlug],
                    $peerSlugs
                ),
                'brands' => $brands,
                'criteria' => $data['criteria'],
                'disclaimer' => $data['disclaimer'],
                'verified_at' => $data['verified_at'],
            ];
        } catch (\Throwable $exception) {
            Log::warning('Window brand comparison data could not be loaded', [
                'slug' => $slug,
                'file' => $this->path(),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{valid: bool, brands: int, criteria: int, errors: list<string>}
     */
    public function validate(): array
    {
        try {
            $data = $this->load(force: true);

            return [
                'valid' => true,
                'brands' => count($data['brands']),
                'criteria' => count($data['criteria']),
                'errors' => [],
            ];
        } catch (\Throwable $exception) {
            return [
                'valid' => false,
                'brands' => 0,
                'criteria' => 0,
                'errors' => [$exception->getMessage()],
            ];
        }
    }

    public function path(): string
    {
        return database_path('data/window-brand-comparisons.json');
    }

    /**
     * @return array<string, mixed>
     */
    private function load(bool $force = false): array
    {
        $path = $this->path();

        if (! File::exists($path)) {
            throw new InvalidArgumentException("Brand comparison dataset is missing: {$path}");
        }

        $mtime = File::lastModified($path);
        if (! $force && $this->cache !== null && $this->cache['mtime'] === $mtime) {
            return $this->cache['data'];
        }

        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Brand comparison dataset root must be an object.');
        }

        $this->assertValid($decoded);
        $this->cache = ['mtime' => $mtime, 'data' => $decoded];

        return $decoded;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertValid(array $data): void
    {
        if (($data['version'] ?? null) !== 1) {
            throw new InvalidArgumentException('Unsupported brand comparison dataset version.');
        }

        $this->assertNonEmptyString($data['verified_at'] ?? null, 'verified_at');
        $this->assertNonEmptyString($data['disclaimer'] ?? null, 'disclaimer');

        $criteria = $data['criteria'] ?? null;
        if (! is_array($criteria) || count($criteria) !== 9) {
            throw new InvalidArgumentException('Brand comparison must define exactly nine criteria.');
        }

        $criterionTypes = [];
        foreach ($criteria as $index => $criterion) {
            if (! is_array($criterion)) {
                throw new InvalidArgumentException("criteria.{$index} must be an object.");
            }

            $key = $this->assertNonEmptyString($criterion['key'] ?? null, "criteria.{$index}.key");
            $type = $criterion['type'] ?? null;
            if (! in_array($type, ['score', 'text'], true)) {
                throw new InvalidArgumentException("criteria.{$index}.type must be score or text.");
            }
            if (isset($criterionTypes[$key])) {
                throw new InvalidArgumentException("Duplicate brand comparison criterion: {$key}");
            }

            $criterionTypes[$key] = $type;
            $this->assertNonEmptyString($criterion['label'] ?? null, "criteria.{$index}.label");
            $this->assertNonEmptyString(
                $criterion['description'] ?? null,
                "criteria.{$index}.description"
            );
        }

        $brands = $data['brands'] ?? null;
        if (! is_array($brands)) {
            throw new InvalidArgumentException('Brand comparison brands must be an object.');
        }

        $actualSlugs = array_keys($brands);
        sort($actualSlugs);
        if ($actualSlugs !== self::EXPECTED_BRAND_SLUGS) {
            throw new InvalidArgumentException(
                'Brand comparison must contain exactly the eleven public window brands.'
            );
        }

        foreach ($brands as $slug => $brand) {
            if (! is_array($brand)) {
                throw new InvalidArgumentException("brands.{$slug} must be an object.");
            }

            foreach (['name', 'tagline', 'best_for'] as $field) {
                $this->assertNonEmptyString($brand[$field] ?? null, "brands.{$slug}.{$field}");
            }

            $url = $this->assertNonEmptyString($brand['url'] ?? null, "brands.{$slug}.url");
            if ($url !== "/brands/{$slug}") {
                throw new InvalidArgumentException("brands.{$slug}.url must be /brands/{$slug}.");
            }

            $peers = $brand['default_peers'] ?? null;
            if (! is_array($peers) || count($peers) !== 2) {
                throw new InvalidArgumentException(
                    "brands.{$slug}.default_peers must contain exactly two brands."
                );
            }
            if (count(array_unique($peers)) !== 2 || in_array($slug, $peers, true)) {
                throw new InvalidArgumentException(
                    "brands.{$slug}.default_peers must be unique and exclude itself."
                );
            }
            foreach ($peers as $peer) {
                if (! is_string($peer) || ! array_key_exists($peer, $brands)) {
                    throw new InvalidArgumentException(
                        "brands.{$slug}.default_peers contains an unknown brand."
                    );
                }
            }

            $this->assertTextList($brand['pros'] ?? null, "brands.{$slug}.pros");
            $this->assertTextList(
                $brand['considerations'] ?? null,
                "brands.{$slug}.considerations"
            );

            $sources = $brand['sources'] ?? null;
            if (! is_array($sources) || $sources === []) {
                throw new InvalidArgumentException("brands.{$slug}.sources cannot be empty.");
            }
            foreach ($sources as $index => $source) {
                if (! is_array($source)) {
                    throw new InvalidArgumentException(
                        "brands.{$slug}.sources.{$index} must be an object."
                    );
                }
                $this->assertNonEmptyString(
                    $source['label'] ?? null,
                    "brands.{$slug}.sources.{$index}.label"
                );
                $this->assertHttpsUrl(
                    $source['url'] ?? null,
                    "brands.{$slug}.sources.{$index}.url"
                );
            }

            $values = $brand['values'] ?? null;
            if (! is_array($values) || array_keys($values) !== array_keys($criterionTypes)) {
                throw new InvalidArgumentException(
                    "brands.{$slug}.values must follow the complete criterion order."
                );
            }

            foreach ($values as $criterionKey => $value) {
                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        "brands.{$slug}.values.{$criterionKey} must be an object."
                    );
                }

                $this->assertNonEmptyString(
                    $value['label'] ?? null,
                    "brands.{$slug}.values.{$criterionKey}.label"
                );
                $this->assertNonEmptyString(
                    $value['detail'] ?? null,
                    "brands.{$slug}.values.{$criterionKey}.detail"
                );

                if ($criterionTypes[$criterionKey] === 'score') {
                    $rating = $value['rating'] ?? null;
                    if (! is_int($rating) || $rating < 1 || $rating > 5) {
                        throw new InvalidArgumentException(
                            "brands.{$slug}.values.{$criterionKey}.rating must be 1–5."
                        );
                    }
                } elseif (array_key_exists('rating', $value)) {
                    throw new InvalidArgumentException(
                        "brands.{$slug}.values.{$criterionKey} must not rate a factual criterion."
                    );
                }
            }
        }
    }

    private function assertNonEmptyString(mixed $value, string $path): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$path} must be a non-empty string.");
        }

        return trim($value);
    }

    private function assertHttpsUrl(mixed $value, string $path): void
    {
        $url = $this->assertNonEmptyString($value, $path);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'https://')) {
            throw new InvalidArgumentException("{$path} must be a valid HTTPS URL.");
        }
    }

    private function assertTextList(mixed $value, string $path): void
    {
        if (! is_array($value) || count($value) < 3 || count($value) > 4) {
            throw new InvalidArgumentException("{$path} must contain three or four items.");
        }

        foreach ($value as $index => $item) {
            $this->assertNonEmptyString($item, "{$path}.{$index}");
        }
    }
}
