<?php

declare(strict_types=1);

namespace App\Services\Webflow;

use App\Support\WebflowCollectionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WebflowImageLocalizerService
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'tif', 'tiff'];

    /**
     * @return array{processed_rows:int, updated_rows:int, downloaded_files:int, by_collection:array<string,array{processed:int,updated:int}>}
     */
    public function localize(?string $onlyCollectionSlug = null, bool $dryRun = false): array
    {
        $processedRows = 0;
        $updatedRows = 0;
        $downloadedFiles = 0;
        $byCollection = [];
        $urlCache = [];

        foreach (WebflowCollectionRegistry::all() as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            $table = (string) ($collection['table'] ?? '');

            if ($slug === '' || $table === '') {
                continue;
            }

            if ($onlyCollectionSlug !== null && $onlyCollectionSlug !== $slug) {
                continue;
            }

            $processedForCollection = 0;
            $updatedForCollection = 0;

            DB::table($table)
                ->orderBy('id')
                ->chunkById(100, function ($rows) use (
                    &$processedRows,
                    &$updatedRows,
                    &$downloadedFiles,
                    &$processedForCollection,
                    &$updatedForCollection,
                    &$urlCache,
                    $table,
                    $slug,
                    $dryRun
                ): void {
                    foreach ($rows as $rowObject) {
                        $processedRows++;
                        $processedForCollection++;
                        $row = (array) $rowObject;
                        $id = (int) ($row['id'] ?? 0);
                        if ($id <= 0) {
                            continue;
                        }

                        $updates = [];
                        foreach ($row as $column => $value) {
                            if (! is_string($column) || $column === '' || $column === 'id') {
                                continue;
                            }

                            if (in_array($column, ['created_at', 'updated_at'], true)) {
                                continue;
                            }

                            [$newValue, $changed, $newDownloads] = $this->localizeValue($value, $slug, $urlCache, $dryRun);
                            if ($changed) {
                                $updates[$column] = $newValue;
                                $downloadedFiles += $newDownloads;
                            }
                        }

                        if ($updates !== []) {
                            $updatedRows++;
                            $updatedForCollection++;

                            if (! $dryRun) {
                                $updates['updated_at'] = now();
                                DB::table($table)->where('id', $id)->update($updates);
                            }
                        }
                    }
                });

            $byCollection[$slug] = [
                'processed' => $processedForCollection,
                'updated' => $updatedForCollection,
            ];
        }

        return [
            'processed_rows' => $processedRows,
            'updated_rows' => $updatedRows,
            'downloaded_files' => $downloadedFiles,
            'by_collection' => $byCollection,
        ];
    }

    /**
     * @param array<string,string> $urlCache
     * @return array{0:mixed,1:bool,2:int}
     */
    private function localizeValue(mixed $value, string $collectionSlug, array &$urlCache, bool $dryRun): array
    {
        if (is_array($value)) {
            $changed = false;
            $downloads = 0;
            $result = [];

            foreach ($value as $key => $item) {
                [$newItem, $itemChanged, $itemDownloads] = $this->localizeValue($item, $collectionSlug, $urlCache, $dryRun);
                $result[$key] = $newItem;
                $changed = $changed || $itemChanged;
                $downloads += $itemDownloads;
            }

            return [$result, $changed, $downloads];
        }

        if (is_object($value)) {
            $arrayValue = (array) $value;
            [$newArray, $changed, $downloads] = $this->localizeValue($arrayValue, $collectionSlug, $urlCache, $dryRun);

            return [$newArray, $changed, $downloads];
        }

        if (! is_string($value) || $value === '') {
            return [$value, false, 0];
        }

        return $this->replaceImageUrlsInString($value, $collectionSlug, $urlCache, $dryRun);
    }

    /**
     * @param array<string,string> $urlCache
     * @return array{0:string,1:bool,2:int}
     */
    private function replaceImageUrlsInString(string $value, string $collectionSlug, array &$urlCache, bool $dryRun): array
    {
        $changed = false;
        $downloads = 0;
        $updated = $value;

        preg_match_all('~https?://[^\s"\'<>()]+~i', $value, $matches);
        $urls = array_values(array_unique($matches[0] ?? []));
        if ($urls === []) {
            return [$value, false, 0];
        }

        foreach ($urls as $url) {
            if (! $this->isImageUrl($url)) {
                continue;
            }

            if (isset($urlCache[$url])) {
                $localUrl = $urlCache[$url];
                $updated = str_replace($url, $localUrl, $updated);
                $changed = true;
                continue;
            }

            $localized = $this->downloadAndStoreImage($url, $collectionSlug, $dryRun);
            if ($localized === null) {
                continue;
            }

            $urlCache[$url] = $localized['url'];
            $updated = str_replace($url, $localized['url'], $updated);
            $changed = true;
            $downloads += $localized['downloaded'] ? 1 : 0;
        }

        return [$updated, $changed, $downloads];
    }

    private function isImageUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return false;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * @return array{url:string,downloaded:bool}|null
     */
    private function downloadAndStoreImage(string $url, string $collectionSlug, bool $dryRun): ?array
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === '' || ! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        $filename = sha1($url).'.'.$ext;
        $relativePath = 'webflow-media/'.$collectionSlug.'/'.$filename;
        $localUrl = Storage::disk('public')->url($relativePath);

        if (Storage::disk('public')->exists($relativePath)) {
            return ['url' => $localUrl, 'downloaded' => false];
        }

        if ($dryRun) {
            return ['url' => $localUrl, 'downloaded' => true];
        }

        try {
            $response = Http::timeout(40)->retry(2, 300)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if ($body === '') {
                return null;
            }

            Storage::disk('public')->put($relativePath, $body);

            return ['url' => $localUrl, 'downloaded' => true];
        } catch (\Throwable) {
            return null;
        }
    }
}

