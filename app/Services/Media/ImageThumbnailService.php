<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageThumbnailService
{
    private const RASTER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'];

    /**
     * @return array{width: int, height: int|null}
     */
    public function presetDimensions(string $preset): array
    {
        $presets = config('media.presets', []);
        $def = $presets[$preset] ?? $presets['card'] ?? ['width' => 640, 'height' => null];

        return [
            'width'  => (int) ($def['width'] ?? 640),
            'height' => isset($def['height']) ? (int) $def['height'] : null,
        ];
    }

    /**
     * Return optimized URL (or original if already small enough / not raster).
     *
     * @param  string|int  $presetOrWidth  Preset name or explicit max width
     */
    public function url(?string $source, string|int $presetOrWidth = 'card', ?int $height = null): string
    {
        if ($source === null || trim($source) === '') {
            return '';
        }

        $source = trim($source);

        if ($this->shouldPassthrough($source)) {
            return $source;
        }

        if (is_string($presetOrWidth) && ! is_numeric($presetOrWidth)) {
            $dims = $this->presetDimensions($presetOrWidth);
            $maxWidth = $dims['width'];
            $maxHeight = $height ?? $dims['height'];
        } else {
            $maxWidth = (int) $presetOrWidth;
            $maxHeight = $height;
        }

        $absolutePath = $this->resolveSourcePath($source);
        if ($absolutePath === null || ! is_file($absolutePath)) {
            return $source;
        }

        $cachePath = $this->buildCachePath($absolutePath, $maxWidth, $maxHeight);
        $disk = Storage::disk(config('media.disk', 'public'));

        if ($disk->exists($cachePath)) {
            return $disk->url($cachePath);
        }

        try {
            $generated = $this->generate($absolutePath, $maxWidth, $maxHeight);
            if ($generated === null) {
                return $source;
            }

            $disk->put($cachePath, $generated);

            return $disk->url($cachePath);
        } catch (Throwable) {
            return $source;
        }
    }

    /**
     * @return array{src: string, srcset: string|null, sizes: string|null}
     */
    public function responsive(
        ?string $source,
        string|int $presetOrWidth = 'card',
        ?int $displayWidth = null,
        ?int $height = null,
        ?string $sizes = null,
    ): array {
        if ($source === null || trim($source) === '') {
            return ['src' => '', 'srcset' => null, 'sizes' => null];
        }

        if (is_string($presetOrWidth) && ! is_numeric($presetOrWidth)) {
            $dims = $this->presetDimensions($presetOrWidth);
            $w = $displayWidth ?? $dims['width'];
            $h = $height ?? $dims['height'];
        } else {
            $w = $displayWidth ?? (int) $presetOrWidth;
            $h = $height;
        }

        $src1x = $this->url($source, $w, $h);
        $src2x = $this->url($source, $w * 2, $h);

        if ($src2x !== $src1x && $src2x !== $source) {
            return [
                'src'    => $src1x,
                'srcset' => "{$src1x} 1x, {$src2x} 2x",
                'sizes'  => $sizes,
            ];
        }

        return [
            'src'    => $src1x,
            'srcset' => null,
            'sizes'  => null,
        ];
    }

    private function shouldPassthrough(string $source): bool
    {
        $path = parse_url($source, PHP_URL_PATH) ?? $source;
        $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return $ext === 'svg' || $ext === '';
    }

    private function resolveSourcePath(string $source): ?string
    {
        if (str_starts_with($source, '/storage/')) {
            $relative = ltrim(substr($source, strlen('/storage/')), '/');

            return storage_path('app/public/'.$relative);
        }

        if (str_starts_with($source, '/webflow-assets/')) {
            return public_path(ltrim($source, '/'));
        }

        if (str_starts_with($source, '/')) {
            $public = public_path(ltrim($source, '/'));

            return is_file($public) ? $public : null;
        }

        if (preg_match('~^https?://~i', $source)) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $srcHost = parse_url($source, PHP_URL_HOST);
            if ($appHost && $srcHost && strcasecmp($appHost, $srcHost) === 0) {
                $path = parse_url($source, PHP_URL_PATH);
                if (is_string($path) && $path !== '') {
                    return $this->resolveSourcePath($path);
                }
            }
        }

        return null;
    }

    private function buildCachePath(string $absolutePath, int $maxWidth, ?int $maxHeight): string
    {
        $mtime = (string) @filemtime($absolutePath);
        $hash = substr(hash('sha256', $absolutePath.'|'.$mtime.'|'.$maxWidth.'|'.($maxHeight ?? 0)), 0, 32);
        $format = config('media.format', 'webp');

        return trim(config('media.directory', 'thumbnails'), '/').'/'.$hash.'_'.$maxWidth.'w.'.($maxHeight ? $maxHeight.'h.' : '').$format;
    }

    private function generate(string $absolutePath, int $maxWidth, ?int $maxHeight): ?string
    {
        $image = $this->loadImage($absolutePath);
        if ($image === null) {
            return null;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);
        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($image);

            return null;
        }

        $skipWithin = (int) config('media.skip_within_px', 8);
        $needsResize = $origW > ($maxWidth + $skipWithin)
            || ($maxHeight !== null && $origH > ($maxHeight + $skipWithin));

        if (! $needsResize) {
            imagedestroy($image);

            return null;
        }

        [$newW, $newH] = $this->fitDimensions($origW, $origH, $maxWidth, $maxHeight);

        $canvas = imagecreatetruecolor($newW, $newH);
        if ($canvas === false) {
            imagedestroy($image);

            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($image);

        ob_start();
        $quality = (int) config('media.quality', 82);
        $ok = imagewebp($canvas, null, $quality);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return ($ok && is_string($binary) && $binary !== '') ? $binary : null;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fitDimensions(int $origW, int $origH, int $maxWidth, ?int $maxHeight): array
    {
        $ratio = $origW / $origH;

        $newW = min($origW, $maxWidth);
        $newH = (int) round($newW / $ratio);

        if ($maxHeight !== null && $newH > $maxHeight) {
            $newH = $maxHeight;
            $newW = (int) round($newH * $ratio);
        }

        return [max(1, $newW), max(1, $newH)];
    }

    /**
     * @return \GdImage|null
     */
    private function loadImage(string $path): mixed
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'png' => @imagecreatefrompng($path) ?: null,
            'gif' => @imagecreatefromgif($path) ?: null,
            'webp' => @imagecreatefromwebp($path) ?: null,
            'avif' => function_exists('imagecreatefromavif') ? (@imagecreatefromavif($path) ?: null) : null,
            'bmp' => @imagecreatefrombmp($path) ?: null,
            default => null,
        };
    }
}
