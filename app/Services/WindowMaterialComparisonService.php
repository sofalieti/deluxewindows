<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WindowMaterialComparisonService
{
    private const EXPECTED_MATERIAL_SLUGS = [
        'aluminum-clad-windows',
        'aluminum-windows',
        'fiberglass-windows',
        'steel-windows',
        'vinyl-windows',
        'wood-clad-windows',
        'wood-windows',
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
            $materials = $data['materials'];

            if (! isset($materials[$slug])) {
                return null;
            }

            $peerSlugs = $materials[$slug]['default_peers'];

            return [
                'current_slug' => $slug,
                'current' => $materials[$slug],
                'peer_slugs' => $peerSlugs,
                'peers' => array_map(
                    static fn (string $peerSlug): array => $materials[$peerSlug],
                    $peerSlugs
                ),
                'materials' => $materials,
                'criteria' => $data['criteria'],
                'disclaimer' => $data['disclaimer'],
                'sources' => $data['sources'],
            ];
        } catch (\Throwable $exception) {
            Log::warning('Window material comparison data could not be loaded', [
                'slug' => $slug,
                'file' => $this->path(),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{valid: bool, materials: int, criteria: int, errors: list<string>}
     */
    public function validate(): array
    {
        try {
            $data = $this->load(force: true);

            return [
                'valid' => true,
                'materials' => count($data['materials']),
                'criteria' => count($data['criteria']),
                'errors' => [],
            ];
        } catch (\Throwable $exception) {
            return [
                'valid' => false,
                'materials' => 0,
                'criteria' => 0,
                'errors' => [$exception->getMessage()],
            ];
        }
    }

    public function path(): string
    {
        return database_path('data/window-material-comparisons.json');
    }

    /**
     * @return array<string, mixed>
     */
    private function load(bool $force = false): array
    {
        $path = $this->path();

        if (! File::exists($path)) {
            throw new InvalidArgumentException("Comparison dataset is missing: {$path}");
        }

        $mtime = File::lastModified($path);
        if (! $force && $this->cache !== null && $this->cache['mtime'] === $mtime) {
            return $this->cache['data'];
        }

        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Comparison dataset root must be an object.');
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
            throw new InvalidArgumentException('Unsupported comparison dataset version.');
        }

        $this->assertNonEmptyString($data['disclaimer'] ?? null, 'disclaimer');

        $sources = $data['sources'] ?? null;
        if (! is_array($sources) || $sources === []) {
            throw new InvalidArgumentException('At least one comparison source is required.');
        }
        foreach ($sources as $index => $source) {
            if (! is_array($source)) {
                throw new InvalidArgumentException("sources.{$index} must be an object.");
            }
            $this->assertNonEmptyString($source['label'] ?? null, "sources.{$index}.label");
            $this->assertHttpUrl($source['url'] ?? null, "sources.{$index}.url");
        }

        $criteria = $data['criteria'] ?? null;
        if (! is_array($criteria) || $criteria === []) {
            throw new InvalidArgumentException('Comparison criteria are required.');
        }

        $criterionKeys = [];
        foreach ($criteria as $index => $criterion) {
            if (! is_array($criterion)) {
                throw new InvalidArgumentException("criteria.{$index} must be an object.");
            }

            $key = $this->assertNonEmptyString($criterion['key'] ?? null, "criteria.{$index}.key");
            if (isset($criterionKeys[$key])) {
                throw new InvalidArgumentException("Duplicate comparison criterion: {$key}");
            }
            $criterionKeys[$key] = true;

            foreach (['label', 'description', 'low_label', 'high_label'] as $field) {
                $this->assertNonEmptyString(
                    $criterion[$field] ?? null,
                    "criteria.{$index}.{$field}"
                );
            }
        }

        $materials = $data['materials'] ?? null;
        if (! is_array($materials)) {
            throw new InvalidArgumentException('Comparison materials must be an object.');
        }

        $actualSlugs = array_keys($materials);
        sort($actualSlugs);
        if ($actualSlugs !== self::EXPECTED_MATERIAL_SLUGS) {
            throw new InvalidArgumentException(
                'Comparison materials must contain exactly the seven public window material slugs.'
            );
        }

        foreach ($materials as $slug => $material) {
            if (! is_array($material)) {
                throw new InvalidArgumentException("materials.{$slug} must be an object.");
            }

            foreach (['name', 'short_name', 'tagline', 'best_for'] as $field) {
                $this->assertNonEmptyString(
                    $material[$field] ?? null,
                    "materials.{$slug}.{$field}"
                );
            }

            $url = $this->assertNonEmptyString(
                $material['url'] ?? null,
                "materials.{$slug}.url"
            );
            if ($url !== "/windows/{$slug}") {
                throw new InvalidArgumentException(
                    "materials.{$slug}.url must be /windows/{$slug}."
                );
            }

            $peers = $material['default_peers'] ?? null;
            if (! is_array($peers) || count($peers) !== 2) {
                throw new InvalidArgumentException(
                    "materials.{$slug}.default_peers must contain exactly two slugs."
                );
            }
            if (count(array_unique($peers)) !== 2 || in_array($slug, $peers, true)) {
                throw new InvalidArgumentException(
                    "materials.{$slug}.default_peers must be unique and exclude itself."
                );
            }
            foreach ($peers as $peer) {
                if (! is_string($peer) || ! array_key_exists($peer, $materials)) {
                    throw new InvalidArgumentException(
                        "materials.{$slug}.default_peers contains an unknown material."
                    );
                }
            }

            $this->assertTextList($material['pros'] ?? null, "materials.{$slug}.pros");
            $this->assertTextList($material['cons'] ?? null, "materials.{$slug}.cons");

            $values = $material['values'] ?? null;
            if (! is_array($values) || array_keys($values) !== array_keys($criterionKeys)) {
                throw new InvalidArgumentException(
                    "materials.{$slug}.values must follow the complete criterion order."
                );
            }

            foreach ($values as $criterionKey => $value) {
                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        "materials.{$slug}.values.{$criterionKey} must be an object."
                    );
                }

                $rating = $value['rating'] ?? null;
                if (! is_int($rating) || $rating < 1 || $rating > 5) {
                    throw new InvalidArgumentException(
                        "materials.{$slug}.values.{$criterionKey}.rating must be 1–5."
                    );
                }

                $this->assertNonEmptyString(
                    $value['label'] ?? null,
                    "materials.{$slug}.values.{$criterionKey}.label"
                );
                $this->assertNonEmptyString(
                    $value['detail'] ?? null,
                    "materials.{$slug}.values.{$criterionKey}.detail"
                );
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

    private function assertHttpUrl(mixed $value, string $path): void
    {
        $url = $this->assertNonEmptyString($value, $path);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'https://')) {
            throw new InvalidArgumentException("{$path} must be a valid HTTPS URL.");
        }
    }

    private function assertTextList(mixed $value, string $path): void
    {
        if (! is_array($value) || count($value) < 3 || count($value) > 5) {
            throw new InvalidArgumentException("{$path} must contain between three and five items.");
        }

        foreach ($value as $index => $item) {
            $this->assertNonEmptyString($item, "{$path}.{$index}");
        }
    }
}
