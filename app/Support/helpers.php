<?php

declare(strict_types=1);

use App\Services\Media\ImageThumbnailService;
use App\Services\PromotionControlService;
use App\Services\PromotionSettingsService;

if (! function_exists('site_phone_display')) {
    function site_phone_display(): string
    {
        return '(650) 461-4446';
    }
}

if (! function_exists('site_phone_tel')) {
    function site_phone_tel(): string
    {
        return '+16504614446';
    }
}

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

if (! function_exists('promotion_date')) {
    /** Formats: us-short, us-short-no-year, long */
    function promotion_date(string $format = 'us-short'): string
    {
        $date = app(PromotionControlService::class)->endDate();
        if ($date !== null) {
            return app(PromotionSettingsService::class)->format($date, $format);
        }

        return app(PromotionSettingsService::class)->formatGlobal($format);
    }
}

if (! function_exists('promotion_name')) {
    function promotion_name(): string
    {
        return app(PromotionSettingsService::class)->promotionName();
    }
}

if (! function_exists('promotion_percent_label')) {
    function promotion_percent_label(): string
    {
        return app(PromotionControlService::class)->globalDiscountLabel();
    }
}
