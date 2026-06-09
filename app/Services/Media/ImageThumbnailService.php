<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageThumbnailService
{
    /**
     * @return array{width: int, height: int|null, fit: string}
     */
    public function presetDimensions(string $preset): array
    {
        $def = $this->presetConfig($preset);

        return [
            'width'  => $def['width'],
            'height' => $def['height'],
            'fit'    => $def['fit'],
        ];
    }

    /**
     * @return array{width: int, height: int|null, fit: string}
     */
    private function presetConfig(string $preset): array
    {
        $presets = config('media.presets', []);
        $def = $presets[$preset] ?? $presets['card'] ?? ['width' => 640, 'height' => null, 'fit' => 'contain'];

        return [
            'width'  => (int) ($def['width'] ?? 640),
            'height' => isset($def['height']) ? (int) $def['height'] : null,
            'fit'    => (string) ($def['fit'] ?? 'contain'),
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

        if (! $this->isEnabled()) {
            return $source;
        }

        if ($this->shouldPassthrough($source)) {
            return $source;
        }

        if (is_string($presetOrWidth) && ! is_numeric($presetOrWidth)) {
            $dims = $this->presetConfig($presetOrWidth);
            $maxWidth = $dims['width'];
            $maxHeight = $height ?? $dims['height'];
            $fit = $dims['fit'];
        } else {
            $maxWidth = (int) $presetOrWidth;
            $maxHeight = $height;
            $fit = ($maxHeight !== null && $height !== null) ? 'cover' : 'contain';
        }

        $absolutePath = $this->resolveSourcePath($source);
        if ($absolutePath === null || ! is_file($absolutePath)) {
            return $source;
        }

        $cachePath = $this->buildCachePath($absolutePath, $maxWidth, $maxHeight, $fit);
        $disk = Storage::disk(config('media.disk', 'public'));

        if ($disk->exists($cachePath)) {
            $publicUrl = $this->publicUrl($cachePath);

            return $this->isWebAccessible($cachePath) ? $publicUrl : $source;
        }

        try {
            $generated = $this->generate($absolutePath, $maxWidth, $maxHeight, $fit);
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

        $src1x = $this->url($source, $w, $h);

        if ($src1x === '' || $src1x === $source || ! $this->isEnabled()) {
            return [
                'src'    => $src1x !== '' ? $src1x : trim($source),
                'srcset' => null,
                'sizes'  => null,
            ];
        }

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
        if (preg_match('~^https?://~i', $source)) {
            $path = parse_url($source, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $basename = basename(rawurldecode($path));
                if ($basename !== '') {
                    $mirrored = public_path('webflow-assets/images/'.$basename);
                    if (is_file($mirrored)) {
                        return $mirrored;
                    }
                }

                return $this->resolveSourcePath($path);
            }

            return null;
        }

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

    private function buildCachePath(string $absolutePath, int $maxWidth, ?int $maxHeight, string $fit = 'contain'): string
    {
        $mtime = (string) @filemtime($absolutePath);
        $hash = substr(hash('sha256', $absolutePath.'|'.$mtime.'|'.$maxWidth.'|'.($maxHeight ?? 0).'|'.$fit), 0, 32);
        $format = config('media.format', 'webp');

        return trim(config('media.directory', 'thumbnails'), '/').'/'.$hash.'_'.$maxWidth.'w.'.($maxHeight ? $maxHeight.'h.' : '').$format;
    }

    private function generate(string $absolutePath, int $maxWidth, ?int $maxHeight, string $fit = 'contain'): ?string
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

        $useCover = $fit === 'cover' && $maxHeight !== null;

        if ($useCover) {
            if ($origW === $maxWidth && $origH === $maxHeight) {
                imagedestroy($image);

                return null;
            }

            $canvas = $this->coverCrop($image, $origW, $origH, $maxWidth, $maxHeight);
        } else {
            $skipWithin = (int) config('media.skip_within_px', 8);
            $needsResize = $origW > ($maxWidth + $skipWithin)
                || ($maxHeight !== null && $origH > ($maxHeight + $skipWithin));

            if (! $needsResize) {
                imagedestroy($image);

                return null;
            }

            [$newW, $newH] = $this->fitDimensions($origW, $origH, $maxWidth, $maxHeight);
            $canvas = $this->resizeCanvas($image, $origW, $origH, $newW, $newH);
        }

        imagedestroy($image);

        if ($canvas === null) {
            return null;
        }

        ob_start();
        $quality = (int) config('media.quality', 82);
        $ok = imagewebp($canvas, null, $quality);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return ($ok && is_string($binary) && $binary !== '') ? $binary : null;
    }

    /**
     * @return \GdImage|null
     */
    private function resizeCanvas(mixed $image, int $origW, int $origH, int $newW, int $newH): mixed
    {
        $canvas = imagecreatetruecolor($newW, $newH);
        if ($canvas === false) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        return $canvas;
    }

    /**
     * Center-crop to an exact target aspect ratio (CSS object-fit: cover).
     *
     * @return \GdImage|null
     */
    private function coverCrop(mixed $image, int $origW, int $origH, int $targetW, int $targetH): mixed
    {
        $scale = max($targetW / $origW, $targetH / $origH);
        $scaledW = max(1, (int) round($origW * $scale));
        $scaledH = max(1, (int) round($origH * $scale));

        $scaled = imagecreatetruecolor($scaledW, $scaledH);
        if ($scaled === false) {
            return null;
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefill($scaled, 0, 0, $transparent);

        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $scaledW, $scaledH, $origW, $origH);

        $srcX = max(0, (int) round(($scaledW - $targetW) / 2));
        $srcY = max(0, (int) round(($scaledH - $targetH) / 2));

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($canvas === false) {
            imagedestroy($scaled);

            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, $transparent);

        imagecopy($canvas, $scaled, 0, 0, $srcX, $srcY, $targetW, $targetH);
        imagedestroy($scaled);

        return $canvas;
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
