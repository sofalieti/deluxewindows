<?php

declare(strict_types=1);

use App\Services\Media\ImageThumbnailService;

if (! function_exists('thumbnail_url')) {
    /**
     * Return a cached, resized image URL when the source is larger than the preset.
     *
     * @param  string|int  $presetOrWidth  Preset name (e.g. "card") or max width in pixels
     */
    function thumbnail_url(?string $source, string|int $presetOrWidth = 'card', ?int $height = null): string
    {
        return app(ImageThumbnailService::class)->url($source, $presetOrWidth, $height);
    }
}

if (! function_exists('thumbnail_responsive')) {
    /**
     * @return array{src: string, srcset: string|null, sizes: string|null}
     */
    function thumbnail_responsive(
        ?string $source,
        string|int $presetOrWidth = 'card',
        ?int $displayWidth = null,
        ?int $height = null,
        ?string $sizes = null,
    ): array {
        return app(ImageThumbnailService::class)->responsive($source, $presetOrWidth, $displayWidth, $height, $sizes);
    }
}
