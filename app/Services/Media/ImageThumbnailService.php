<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageThumbnailService
{
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
     * @param  string|int|null  $tuningFrom  Preset whose quality and sharpen settings to reuse
     */
    public function url(?string $source, string|int $presetOrWidth = 'card', ?int $height = null, string|int|null $tuningFrom = null): string
    {
        if ($source === null || trim($source) === '') {
            return '';
        }

        $source = trim($source);
        $source = webflow_image_url($source);

        if (! $this->isEnabled()) {
            return $source;
        }

        if ($this->shouldPassthrough($source)) {
            return $source;
        }

        $tuning = $this->tuning($tuningFrom ?? $presetOrWidth);

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

        $cachePath = $this->buildCachePath($absolutePath, $maxWidth, $maxHeight, $tuning);
        $disk = Storage::disk(config('media.disk', 'public'));

        if ($disk->exists($cachePath)) {
            $publicUrl = $this->publicUrl($cachePath);

            return $this->isWebAccessible($cachePath) ? $publicUrl : $source;
        }

        try {
            $generated = $this->generate($absolutePath, $maxWidth, $maxHeight, $tuning);
            if ($generated === null) {
                return $source;
            }

            $disk->put($cachePath, $generated);
            $publicUrl = $this->publicUrl($cachePath);

            return $this->isWebAccessible($cachePath) ? $publicUrl : $source;
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

        $src1x = $this->url($source, $w, $h, $presetOrWidth);

        if ($src1x === '' || $src1x === $source || ! $this->isEnabled()) {
            return [
                'src'    => $src1x !== '' ? $src1x : trim($source),
                'srcset' => null,
                'sizes'  => null,
            ];
        }

        $src2x = $this->url($source, $w * 2, $h, $presetOrWidth);

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

    private function isEnabled(): bool
    {
        if (! config('media.enabled', true)) {
            return false;
        }

        return extension_loaded('gd') && function_exists('imagewebp');
    }

    private function shouldPassthrough(string $source): bool
    {
        $path = parse_url($source, PHP_URL_PATH) ?? $source;
        $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return $ext === 'svg' || $ext === '';
    }

    private function resolveSourcePath(string $source): ?string
    {
        $source = explode('#', explode('?', $source, 2)[0], 2)[0];

        if (preg_match('~^https?://~i', $source)) {
            $path = parse_url($source, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                return $this->resolveSourcePath(rawurldecode($path));
            }

            return null;
        }

        $source = rawurldecode($source);

        if (str_starts_with($source, '/storage/')) {
            $relative = ltrim(substr($source, strlen('/storage/')), '/');

            return storage_path('app/public/'.$relative);
        }

        if (str_starts_with($source, '/')) {
            $public = public_path(ltrim($source, '/'));

            return is_file($public) ? $public : null;
        }

        return null;
    }

    private function publicUrl(string $cachePath): string
    {
        return '/storage/'.ltrim(str_replace('\\', '/', $cachePath), '/');
    }

    private function isWebAccessible(string $cachePath): bool
    {
        $disk = Storage::disk(config('media.disk', 'public'));
        if (! $disk->exists($cachePath)) {
            return false;
        }

        $publicFile = public_path('storage/'.ltrim(str_replace('\\', '/', $cachePath), '/'));

        return is_file($publicFile);
    }

    private function buildCachePath(string $absolutePath, int $maxWidth, ?int $maxHeight, array $tuning): string
    {
        $mtime = (string) @filemtime($absolutePath);
        $hash = substr(hash('sha256', $absolutePath.'|'.$mtime.'|'.$maxWidth.'|'.($maxHeight ?? 0).'|'.$tuning['quality'].'|'.($tuning['sharpen'] ? '1' : '0')), 0, 32);
        $format = config('media.format', 'webp');

        return trim(config('media.directory', 'thumbnails'), '/').'/'.$hash.'_'.$maxWidth.'w.'.($maxHeight ? $maxHeight.'h.' : '').$format;
    }

    /**
     * @param  array{quality: int, sharpen: bool}  $tuning
     */
    private function generate(string $absolutePath, int $maxWidth, ?int $maxHeight, array $tuning): ?string
    {
        $this->ensureMemory();

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
        imagealphablending($canvas, true);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($image);

        if ($tuning['sharpen']) {
            imageconvolution($canvas, [
                [0, -1, 0],
                [-1, 16, -1],
                [0, -1, 0],
            ], 12, 0);
        }

        ob_start();
        $ok = imagewebp($canvas, null, $tuning['quality']);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return ($ok && is_string($binary) && $binary !== '') ? $binary : null;
    }

    /**
     * @return array{quality: int, sharpen: bool}
     */
    private function tuning(string|int|null $presetOrWidth): array
    {
        $quality = (int) config('media.quality', 82);
        $sharpen = false;

        if (is_string($presetOrWidth) && ! is_numeric($presetOrWidth)) {
            $def = config('media.presets.'.$presetOrWidth, []);
            if (isset($def['quality'])) {
                $quality = (int) $def['quality'];
            }
            $sharpen = (bool) ($def['sharpen'] ?? false);
        }

        return [
            'quality' => max(1, min(100, $quality)),
            'sharpen' => $sharpen,
        ];
    }

    private function ensureMemory(): void
    {
        $limit = ini_get('memory_limit');
        if ($limit === false || $limit === '-1') {
            return;
        }

        $bytes = match (strtolower(substr($limit, -1))) {
            'g' => (int) $limit * 1024 * 1024 * 1024,
            'm' => (int) $limit * 1024 * 1024,
            'k' => (int) $limit * 1024,
            default => (int) $limit,
        };

        if ($bytes > 0 && $bytes < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
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
