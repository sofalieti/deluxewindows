<?php

declare(strict_types=1);

namespace App\Services\Webflow;

use App\Support\WebflowCollectionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Finds image references in the database whose local file is missing and
 * re-fetches a fresh URL from the Webflow API (matched by stable fileId),
 * downloads it into public/webflow-assets/images and rewrites the reference.
 */
class WebflowMissingImageRefetchService
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'ico', 'tif', 'tiff'];

    private const CDN_HOST_NEEDLES = ['website-files.com', 'webflow.com'];

    private const PUBLIC_DIR = 'webflow-assets/images';

    public function __construct(
        private readonly WebflowClient $client,
    ) {
    }

    /**
     * @param  callable(string):void|null  $log
     * @return array<string,mixed>
     */
    public function run(?string $onlyCollection, bool $dryRun, ?callable $log = null): array
    {
        $log ??= static function (): void {};

        $slugToCollectionId = $this->collectionIdMap();
        $hasToken = (string) config('webflow.api_token') !== '';

        $stats = [
            'missing' => 0,
            'downloaded_direct' => 0,
            'refetched' => 0,
            'would_download' => 0,
            'would_refetch' => 0,
            'unresolved' => 0,
            'rows_updated' => 0,
            'by_collection' => [],
        ];

        foreach (WebflowCollectionRegistry::all() as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            $table = (string) ($collection['table'] ?? '');

            if ($slug === '' || $table === '') {
                continue;
            }
            if ($onlyCollection !== null && $onlyCollection !== $slug) {
                continue;
            }

            $collectionId = $slugToCollectionId[$slug] ?? null;
            $this->processTable($table, $slug, $collectionId, $hasToken, $dryRun, $stats, $log);
        }

        return $stats;
    }

    private function processTable(
        string $table,
        string $slug,
        ?string $collectionId,
        bool $hasToken,
        bool $dryRun,
        array &$stats,
        callable $log
    ): void {
        $rows = DB::table($table)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return;
        }

        // First pass: are there any missing references at all in this table?
        $missingExists = false;
        foreach ($rows as $rowObject) {
            foreach ($this->collectRefs((array) $rowObject) as $ref) {
                if (! $this->localFileExists($ref)) {
                    $missingExists = true;
                    break 2;
                }
            }
        }

        if (! $missingExists) {
            return;
        }

        $log("→ {$slug}: found missing images, resolving...");

        // Fetch fresh items from Webflow (itemId => fieldData), only when needed.
        $freshMap = [];
        if ($collectionId !== null && $hasToken) {
            try {
                foreach ($this->client->listCollectionItems($collectionId) as $item) {
                    $id = (string) ($item['id'] ?? '');
                    if ($id !== '') {
                        $freshMap[$id] = $item['fieldData'] ?? [];
                    }
                }
            } catch (\Throwable $e) {
                $log("   ! Webflow API error for {$slug}: ".$e->getMessage());
            }
        } elseif ($collectionId === null) {
            $log("   ! No Webflow collection id for {$slug} (manifest mismatch). Direct download only.");
        } elseif (! $hasToken) {
            $log('   ! WEBFLOW_API_TOKEN is missing. Direct download only.');
        }

        $collectionUpdated = 0;

        foreach ($rows as $rowObject) {
            $row = (array) $rowObject;
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $webflowItemId = (string) ($row['webflow_item_id'] ?? '');
            $replacements = [];

            foreach ($this->collectRefs($row) as $ref) {
                if ($this->localFileExists($ref)) {
                    continue;
                }

                $stats['missing']++;
                $newLocal = $this->resolveMissing($ref, $webflowItemId, $freshMap, $dryRun, $stats, $log);

                if ($newLocal !== null && $newLocal !== $ref) {
                    $replacements[$ref] = $newLocal;
                }
            }

            if ($replacements !== [] && ! $dryRun) {
                $updates = $this->applyReplacements($row, $replacements);
                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $id)->update($updates);
                    $stats['rows_updated']++;
                    $collectionUpdated++;
                }
            }
        }

        $stats['by_collection'][$slug] = [
            'rows_updated' => $collectionUpdated,
        ];
    }

    /**
     * Try a direct CDN download first, then fall back to re-fetching a fresh
     * URL from the Webflow API matched by fileId.
     */
    private function resolveMissing(
        string $ref,
        string $webflowItemId,
        array $freshMap,
        bool $dryRun,
        array &$stats,
        callable $log
    ): ?string {
        $basename = $this->basenameOf($ref);
        if ($basename === '') {
            $stats['unresolved']++;

            return null;
        }

        // 1. If the reference is a live CDN URL, just download it directly.
        if ($this->isHttp($ref) && $this->isCdnImage($ref)) {
            if ($dryRun) {
                $stats['would_download']++;

                return $this->localUrl($basename);
            }
            if ($this->download($ref, $basename)) {
                $stats['downloaded_direct']++;
                $log("   downloaded: {$basename}");

                return $this->localUrl($basename);
            }
            $log("   CDN dead, refetching from Webflow: {$basename}");
        }

        // 2. Re-fetch a fresh URL from the Webflow API by fileId.
        $fileId = $this->fileIdFromBasename($basename);
        $fresh = $freshMap[$webflowItemId] ?? null;

        if ($fileId !== '' && is_array($fresh)) {
            $freshUrls = $this->extractFreshImageUrls($fresh);
            $url = $freshUrls[$fileId] ?? null;

            if ($url !== null) {
                $freshBasename = $this->basenameOf($url);
                if ($freshBasename !== '') {
                    if ($dryRun) {
                        $stats['would_refetch']++;

                        return $this->localUrl($freshBasename);
                    }
                    if ($this->download($url, $freshBasename)) {
                        $stats['refetched']++;
                        $log("   refetched: {$freshBasename}");

                        return $this->localUrl($freshBasename);
                    }
                }
            }
        }

        $stats['unresolved']++;
        $log("   UNRESOLVED: {$basename}");

        return null;
    }

    // ----- Reference collection -------------------------------------------------

    /**
     * @return list<string>
     */
    private function collectRefs(array $row): array
    {
        $refs = [];

        foreach ($row as $column => $value) {
            if (! is_string($column) || $column === '' || $column === 'id') {
                continue;
            }
            if (in_array($column, ['created_at', 'updated_at', 'webflow_item_id'], true)) {
                continue;
            }
            if (! is_string($value) || $value === '') {
                continue;
            }

            $this->collectRefsFromColumn($value, $refs);
        }

        return array_values(array_unique($refs));
    }

    /**
     * @param  list<string>  $refs
     */
    private function collectRefsFromColumn(string $value, array &$refs): void
    {
        $trimmed = ltrim($value);
        $looksJson = $trimmed !== '' && in_array($trimmed[0], ['{', '[', '"'], true);

        if ($looksJson) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->collectRefsFromValue($decoded, $refs);

                return;
            }
        }

        $this->findRefsInString($value, $refs);
    }

    /**
     * @param  list<string>  $refs
     */
    private function collectRefsFromValue(mixed $value, array &$refs): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectRefsFromValue($item, $refs);
            }

            return;
        }

        if (is_string($value) && $value !== '') {
            $this->findRefsInString($value, $refs);
        }
    }

    /**
     * @param  list<string>  $refs
     */
    private function findRefsInString(string $value, array &$refs): void
    {
        if (preg_match_all('~https?://[^\s"\'<>(),\\\\]+~i', $value, $m)) {
            foreach ($m[0] as $url) {
                if ($this->isCdnImage($url)) {
                    $refs[] = $url;
                }
            }
        }

        if (preg_match_all('~/'.preg_quote(self::PUBLIC_DIR, '~').'/[^\s"\'<>(),\\\\]+~i', $value, $m2)) {
            foreach ($m2[0] as $path) {
                if ($this->hasImageExtension($path)) {
                    $refs[] = $path;
                }
            }
        }
    }

    // ----- Replacement ----------------------------------------------------------

    /**
     * @param  array<string,string>  $replacements
     * @return array<string,mixed>
     */
    private function applyReplacements(array $row, array $replacements): array
    {
        $updates = [];

        foreach ($row as $column => $value) {
            if (! is_string($column) || $column === '' || $column === 'id') {
                continue;
            }
            if (in_array($column, ['created_at', 'updated_at', 'webflow_item_id'], true)) {
                continue;
            }
            if (! is_string($value) || $value === '') {
                continue;
            }

            [$newValue, $changed] = $this->replaceInColumn($value, $replacements);
            if ($changed) {
                $updates[$column] = $newValue;
            }
        }

        return $updates;
    }

    /**
     * @param  array<string,string>  $replacements
     * @return array{0:string,1:bool}
     */
    private function replaceInColumn(string $value, array $replacements): array
    {
        $trimmed = ltrim($value);
        $looksJson = $trimmed !== '' && in_array($trimmed[0], ['{', '[', '"'], true);

        if ($looksJson) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                [$newDecoded, $changed] = $this->replaceInValue($decoded, $replacements);
                if (! $changed) {
                    return [$value, false];
                }
                $encoded = json_encode($newDecoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return $encoded === false ? [$value, false] : [$encoded, true];
            }
        }

        $new = strtr($value, $replacements);

        return [$new, $new !== $value];
    }

    /**
     * @param  array<string,string>  $replacements
     * @return array{0:mixed,1:bool}
     */
    private function replaceInValue(mixed $value, array $replacements): array
    {
        if (is_array($value)) {
            $changed = false;
            $result = [];
            foreach ($value as $key => $item) {
                [$newItem, $itemChanged] = $this->replaceInValue($item, $replacements);
                $result[$key] = $newItem;
                $changed = $changed || $itemChanged;
            }

            return [$result, $changed];
        }

        if (is_string($value) && $value !== '') {
            $new = strtr($value, $replacements);

            return [$new, $new !== $value];
        }

        return [$value, false];
    }

    // ----- Webflow fresh URLs ---------------------------------------------------

    /**
     * Walk fresh Webflow fieldData and map every image fileId to its URL.
     *
     * @return array<string,string>
     */
    private function extractFreshImageUrls(array $fieldData): array
    {
        $map = [];
        $this->walkFreshValue($fieldData, $map);

        return $map;
    }

    /**
     * @param  array<string,string>  $map
     */
    private function walkFreshValue(mixed $value, array &$map): void
    {
        if (is_array($value)) {
            $fileId = $value['fileId'] ?? null;
            $url = $value['url'] ?? null;

            if (is_string($fileId) && $fileId !== '' && is_string($url) && $this->isImageUrl($url)) {
                $map[$fileId] = $url;
                // Continue walking in case of nested structures, but this leaf is captured.
            }

            foreach ($value as $item) {
                if (is_array($item) || is_string($item)) {
                    $this->walkFreshValue($item, $map);
                }
            }

            return;
        }

        if (is_string($value) && $value !== '') {
            if (preg_match_all('~https?://[^\s"\'<>(),\\\\]+~i', $value, $m)) {
                foreach ($m[0] as $url) {
                    if (! $this->isCdnImage($url)) {
                        continue;
                    }
                    $fid = $this->fileIdFromBasename($this->basenameOf($url));
                    if ($fid !== '') {
                        $map[$fid] = $url;
                    }
                }
            }
        }
    }

    // ----- Download / file helpers ----------------------------------------------

    private function download(string $url, string $basename): bool
    {
        $absolute = public_path(self::PUBLIC_DIR.'/'.$basename);
        if (File::exists($absolute)) {
            return true;
        }

        $dir = dirname($absolute);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        try {
            $response = Http::timeout(60)->retry(3, 500)->get($url);
            if (! $response->successful() || $response->body() === '') {
                return false;
            }

            File::put($absolute, $response->body());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function localFileExists(string $ref): bool
    {
        $basename = $this->basenameOf($ref);
        if ($basename === '') {
            return true; // can't resolve a basename; don't treat as missing
        }

        return File::exists(public_path(self::PUBLIC_DIR.'/'.$basename));
    }

    private function localUrl(string $basename): string
    {
        return '/'.self::PUBLIC_DIR.'/'.$basename;
    }

    // ----- Small utilities ------------------------------------------------------

    private function basenameOf(string $ref): string
    {
        $path = parse_url($ref, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $ref;
        }

        return basename($path);
    }

    private function fileIdFromBasename(string $basename): string
    {
        $name = $basename;
        $underscore = strpos($name, '_');
        if ($underscore !== false) {
            return substr($name, 0, $underscore);
        }

        return pathinfo($name, PATHINFO_FILENAME);
    }

    private function isHttp(string $ref): bool
    {
        return (bool) preg_match('~^https?://~i', $ref);
    }

    private function isCdnImage(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return false;
        }

        $isCdn = false;
        foreach (self::CDN_HOST_NEEDLES as $needle) {
            if (str_contains($host, $needle)) {
                $isCdn = true;
                break;
            }
        }

        return $isCdn && $this->isImageUrl($url);
    }

    private function isImageUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $this->hasImageExtension($path);
    }

    private function hasImageExtension(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * @return array<string,string> slug => Webflow collection id
     */
    private function collectionIdMap(): array
    {
        $disk = Storage::disk((string) config('webflow.export_disk', 'webflow_repo'));
        $root = trim((string) config('webflow.export_root', 'current'), '/');
        $manifestPath = $root.'/manifest.json';

        if (! $disk->exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) $disk->get($manifestPath), true);
        if (! is_array($manifest)) {
            return [];
        }

        $map = [];
        foreach (($manifest['collections'] ?? []) as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            $id = (string) ($collection['id'] ?? '');
            if ($slug !== '' && $id !== '') {
                $map[$slug] = $id;
            }
        }

        return $map;
    }
}
