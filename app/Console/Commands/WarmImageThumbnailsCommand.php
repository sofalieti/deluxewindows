<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Media\ImageThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WarmImageThumbnailsCommand extends Command
{
    protected $signature = 'media:warm-thumbnails
                            {--path=webflow-assets/images : Directory under public/ to scan}
                            {--presets=* : Preset names (default: all from config)}';

    protected $description = 'Pre-generate WebP thumbnails for local raster images';

    public function handle(ImageThumbnailService $thumbnails): int
    {
        $relative = trim((string) $this->option('path'), '/');
        $directory = public_path($relative);

        if (! is_dir($directory)) {
            $this->error("Directory not found: {$directory}");

            return self::FAILURE;
        }

        $presets = $this->option('presets');
        if ($presets === [] || $presets === null) {
            $presets = array_keys(config('media.presets', []));
        }

        $files = File::allFiles($directory);
        $generated = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'], true)) {
                continue;
            }

            $publicPath = '/'.$relative.'/'.$file->getRelativePathname();
            $publicPath = str_replace('\\', '/', $publicPath);

            foreach ($presets as $preset) {
                $before = $thumbnails->url($publicPath, (string) $preset);
                if ($before !== $publicPath) {
                    $generated++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->info("Thumbnail warm-up finished. Generated or cached: {$generated}, skipped (already small/SVG): {$skipped}.");

        return self::SUCCESS;
    }
}
