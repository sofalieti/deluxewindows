<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\WebflowCollectionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Downloads every Webflow CDN image referenced in the imported collections and
 * rewrites the stored URLs to internal /webflow-assets/images/<name> links.
 *
 * Files are saved into public/webflow-assets/images keeping their original
 * Webflow basename, which is exactly what webflow_image_url() and the thumbnail
 * pipeline already resolve against.
 */
class WebflowDownloadCdnImagesCommand extends Command
{
    protected $signature = 'webflow:download-cdn-images
        {--collection= : Process only one collection slug}
        {--dry-run : Only report what would change, do not download or write}
        {--export : Re-export local Webflow JSON files after rewriting the database}';

    protected $description = 'Download Webflow CDN images locally and rewrite their URLs to internal links.';

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'ico', 'tif', 'tiff'];

    /** Hosts that we treat as Webflow asset CDNs. */
    private const CDN_HOST_NEEDLES = ['website-files.com', 'webflow.com'];

    private const PUBLIC_DIR = 'webflow-assets/images';

    /** @var array<string, string|null> Maps a remote URL to its local URL (or null if it failed). */
    private array $urlCache = [];

    private int $downloaded = 0;

    private int $skippedExisting = 0;

    private int $failed = 0;

    private int $updatedRows = 0;

    private int $processedRows = 0;

    public function handle(): int
    {
        $onlyCollection = $this->stringOption('collection');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — nothing will be downloaded or saved.');
        }

        $targetDir = public_path(self::PUBLIC_DIR);
        if (! $dryRun && ! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        foreach (WebflowCollectionRegistry::all() as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            $table = (string) ($collection['table'] ?? '');

            if ($slug === '' || $table === '') {
                continue;
            }

            if ($onlyCollection !== null && $onlyCollection !== $slug) {
                continue;
            }

            $this->processTable($table, $slug, $dryRun);
        }

        $this->newLine();
        $this->info('Done.');
        $this->line('Rows processed: '.$this->processedRows);
        $this->line('Rows updated:   '.$this->updatedRows);
        $this->line('Files downloaded: '.$this->downloaded);
        $this->line('Already local:    '.$this->skippedExisting);
        $this->line('Failed downloads: '.$this->failed);

        if (! $dryRun && (bool) $this->option('export')) {
            $this->newLine();
            $this->info('Re-exporting local Webflow JSON files...');
            $this->call('webflow:local', ['action' => 'export']);
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processTable(string $table, string $slug, bool $dryRun): void
    {
        $this->line("→ {$slug} ({$table})");

        DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $dryRun): void {
            foreach ($rows as $rowObject) {
                $this->processedRows++;
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
                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    [$newValue, $changed] = $this->processColumnValue($value, $dryRun);
                    if ($changed) {
                        $updates[$column] = $newValue;
                    }
                }

                if ($updates !== []) {
                    $this->updatedRows++;
                    if (! $dryRun) {
                        $updates['updated_at'] = now();
                        DB::table($table)->where('id', $id)->update($updates);
                    }
                }
            }
        });
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function processColumnValue(string $raw, bool $dryRun): array
    {
        $trimmed = ltrim($raw);
        $looksJson = $trimmed !== '' && in_array($trimmed[0], ['{', '[', '"'], true);

        if ($looksJson) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                [$newDecoded, $changed] = $this->walk($decoded, $dryRun);
                if (! $changed) {
                    return [$raw, false];
                }

                $encoded = json_encode($newDecoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return [$encoded === false ? $raw : $encoded, $encoded !== false];
            }
        }

        return $this->replaceInString($raw, $dryRun);
    }

    /**
     * @return array{0:mixed,1:bool}
     */
    private function walk(mixed $value, bool $dryRun): array
    {
        if (is_array($value)) {
            $changed = false;
            $result = [];
            foreach ($value as $key => $item) {
                [$newItem, $itemChanged] = $this->walk($item, $dryRun);
                $result[$key] = $newItem;
                $changed = $changed || $itemChanged;
            }

            return [$result, $changed];
        }

        if (is_string($value) && $value !== '') {
            return $this->replaceInString($value, $dryRun);
        }

        return [$value, false];
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function replaceInString(string $value, bool $dryRun): array
    {
        if (! preg_match_all('~https?://[^\s"\'<>(),\\\\]+~i', $value, $matches)) {
            return [$value, false];
        }

        $changed = false;
        $updated = $value;

        foreach (array_unique($matches[0]) as $url) {
            if (! $this->isWebflowCdnImage($url)) {
                continue;
            }

            $localUrl = $this->resolveLocalUrl($url, $dryRun);
            if ($localUrl === null) {
                continue;
            }

            $updated = str_replace($url, $localUrl, $updated);
            $changed = true;
        }

        return [$updated, $changed];
    }

    private function isWebflowCdnImage(string $url): bool
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
        if (! $isCdn) {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * Downloads the remote file (if needed) and returns the internal URL, or null on failure.
     */
    private function resolveLocalUrl(string $url, bool $dryRun): ?string
    {
        if (array_key_exists($url, $this->urlCache)) {
            return $this->urlCache[$url];
        }

        $basename = \App\Support\WebflowAssetName::basename($url);
        if ($basename === '') {
            return $this->urlCache[$url] = null;
        }

        $localUrl = '/'.self::PUBLIC_DIR.'/'.$basename;
        $absolute = public_path(self::PUBLIC_DIR.'/'.$basename);

        if (File::exists($absolute)) {
            $this->skippedExisting++;

            return $this->urlCache[$url] = $localUrl;
        }

        if ($dryRun) {
            $this->line('   would download: '.$basename);

            return $this->urlCache[$url] = $localUrl;
        }

        try {
            $response = Http::timeout(60)->retry(3, 500)->get($url);
            if (! $response->successful() || $response->body() === '') {
                $this->failed++;
                $this->warn('   failed ('.$response->status().'): '.$url);

                return $this->urlCache[$url] = null;
            }

            File::put($absolute, $response->body());
            $this->downloaded++;
            $this->line('   downloaded: '.$basename);

            return $this->urlCache[$url] = $localUrl;
        } catch (\Throwable $e) {
            $this->failed++;
            $this->warn('   error: '.$url.' — '.$e->getMessage());

            return $this->urlCache[$url] = null;
        }
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
