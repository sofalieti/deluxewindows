<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\WebflowAssetName;
use App\Support\WebflowCollectionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Fixes already-downloaded images whose filenames contain percent-encoding or
 * spaces (e.g. "Frame%2039.avif"). Such files 404 in the browser because the
 * web server decodes the URL before the filesystem lookup.
 *
 * Renames the files to a clean, encoding-free name and rewrites every database
 * reference (CDN URL or local URL, in plain and JSON columns) to the new
 * /webflow-assets/images/<clean> link. Runs fully offline.
 */
class WebflowNormalizeImageNamesCommand extends Command
{
    protected $signature = 'webflow:normalize-image-names
        {--collection= : Process only one collection slug}
        {--dry-run : Only report what would change, do not rename or write}';

    protected $description = 'Rename local image files with %20/spaces to clean names and rewrite DB references to internal links.';

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'ico', 'tif', 'tiff'];

    private const CDN_HOST_NEEDLES = ['website-files.com', 'webflow.com'];

    private const PUBLIC_DIR = 'webflow-assets/images';

    private int $renamedFiles = 0;

    private int $removedDuplicates = 0;

    private int $updatedRows = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN — nothing will be renamed or written.');
        }

        $this->renameMessyFiles($dryRun);

        $onlyCollection = $this->stringOption('collection');
        foreach (WebflowCollectionRegistry::all() as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            $table = (string) ($collection['table'] ?? '');
            if ($slug === '' || $table === '') {
                continue;
            }
            if ($onlyCollection !== null && $onlyCollection !== $slug) {
                continue;
            }
            $this->rewriteTable($table, $slug, $dryRun);
        }

        $this->newLine();
        $this->info('Done.');
        $this->line('Files renamed:      '.$this->renamedFiles);
        $this->line('Duplicates removed: '.$this->removedDuplicates);
        $this->line('Rows updated:       '.$this->updatedRows);

        return self::SUCCESS;
    }

    // ----- Step A: rename files on disk -----------------------------------------

    private function renameMessyFiles(bool $dryRun): void
    {
        $dir = public_path(self::PUBLIC_DIR);
        if (! File::isDirectory($dir)) {
            return;
        }

        $this->line('→ Scanning local image files...');

        foreach (File::files($dir) as $file) {
            $current = $file->getFilename();
            $clean = WebflowAssetName::basename($current);

            if ($clean === '' || $clean === $current) {
                continue;
            }

            $target = $dir.DIRECTORY_SEPARATOR.$clean;

            if (File::exists($target)) {
                $this->line("   duplicate, removing messy copy: {$current}");
                if (! $dryRun) {
                    File::delete($file->getPathname());
                }
                $this->removedDuplicates++;

                continue;
            }

            $this->line("   rename: {$current} -> {$clean}");
            if (! $dryRun) {
                File::move($file->getPathname(), $target);
            }
            $this->renamedFiles++;
        }
    }

    // ----- Step B: rewrite DB references ----------------------------------------

    private function rewriteTable(string $table, string $slug, bool $dryRun): void
    {
        $this->line("→ {$slug} ({$table})");

        DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $dryRun): void {
            foreach ($rows as $rowObject) {
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

                    [$newValue, $changed] = $this->processColumnValue($value);
                    if ($changed) {
                        $updates[$column] = $newValue;
                    }
                }

                if ($updates !== [] && ! $dryRun) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $id)->update($updates);
                }
                if ($updates !== []) {
                    $this->updatedRows++;
                }
            }
        });
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function processColumnValue(string $raw): array
    {
        $trimmed = ltrim($raw);
        $looksJson = $trimmed !== '' && in_array($trimmed[0], ['{', '[', '"'], true);

        if ($looksJson) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                [$newDecoded, $changed] = $this->walk($decoded);
                if (! $changed) {
                    return [$raw, false];
                }
                $encoded = json_encode($newDecoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return [$encoded === false ? $raw : $encoded, $encoded !== false];
            }
        }

        return $this->replaceInString($raw);
    }

    /**
     * @return array{0:mixed,1:bool}
     */
    private function walk(mixed $value): array
    {
        if (is_array($value)) {
            $changed = false;
            $result = [];
            foreach ($value as $key => $item) {
                [$newItem, $itemChanged] = $this->walk($item);
                $result[$key] = $newItem;
                $changed = $changed || $itemChanged;
            }

            return [$result, $changed];
        }

        if (is_string($value) && $value !== '') {
            return $this->replaceInString($value);
        }

        return [$value, false];
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function replaceInString(string $value): array
    {
        $changed = false;
        $updated = $value;

        // CDN image URLs -> clean local URL (only when the file is already local).
        if (preg_match_all('~https?://[^\s"\'<>(),\\\\]+~i', $value, $m)) {
            foreach (array_unique($m[0]) as $url) {
                if (! $this->isCdnImage($url)) {
                    continue;
                }
                $localUrl = $this->localUrlIfExists($url);
                if ($localUrl !== null && $localUrl !== $url) {
                    $updated = str_replace($url, $localUrl, $updated);
                    $changed = true;
                }
            }
        }

        // Local image URLs with %20/spaces/messy names -> clean local URL.
        if (preg_match_all('~/'.preg_quote(self::PUBLIC_DIR, '~').'/[^\s"\'<>(),\\\\]+~i', $updated, $m2)) {
            foreach (array_unique($m2[0]) as $path) {
                if (! $this->hasImageExtension($path)) {
                    continue;
                }
                $localUrl = $this->localUrlIfExists($path);
                if ($localUrl !== null && $localUrl !== $path) {
                    $updated = str_replace($path, $localUrl, $updated);
                    $changed = true;
                }
            }
        }

        return [$updated, $changed];
    }

    /**
     * Clean local URL for a reference, but only if the clean file exists on disk.
     */
    private function localUrlIfExists(string $source): ?string
    {
        $basename = WebflowAssetName::basename($source);
        if ($basename === '') {
            return null;
        }
        if (! File::exists(public_path(self::PUBLIC_DIR.'/'.$basename))) {
            return null;
        }

        return '/'.self::PUBLIC_DIR.'/'.$basename;
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

        return $isCdn && $this->hasImageExtension((string) parse_url($url, PHP_URL_PATH));
    }

    private function hasImageExtension(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, self::IMAGE_EXTENSIONS, true);
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
