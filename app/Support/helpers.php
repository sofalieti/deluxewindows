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

if (! function_exists('webflow_image_url')) {
    /**
     * Resolve Webflow CDN or legacy image paths to a working local URL when possible.
     */
    function webflow_image_url(?string $source): string
    {
        if ($source === null || ($source = trim($source)) === '') {
            return '';
        }

        $alsideLogo = '6915b29da8bcdcb16ec593b6_alside-logo.svg';

        if (str_contains($source, '6915b29da8bcdcb16ec593b6')) {
            $local = public_path('webflow-assets/images/'.$alsideLogo);
            if (is_file($local)) {
                return '/webflow-assets/images/'.$alsideLogo;
            }
        }

        $path = parse_url($source, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return $source;
        }

        $basename = basename($path);
        if ($basename === '') {
            return $source;
        }

        $decodedBasename = rawurldecode($basename);
        $candidates = array_unique([
            $basename,
            $decodedBasename,
            str_replace(' ', '%20', $decodedBasename),
        ]);

        foreach ($candidates as $name) {
            $file = public_path('webflow-assets/images/'.$name);
            if (is_file($file)) {
                return '/webflow-assets/images/'.$name;
            }
        }

        return $source;
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
        return app(PromotionControlService::class)->globalPromotionName();
    }
}

if (! function_exists('promotion_percent_label')) {
    function promotion_percent_label(): string
    {
        return app(PromotionControlService::class)->globalDiscountLabel();
    }
}

if (! function_exists('promotion_home_html')) {
    function promotion_home_html(): string
    {
        return app(PromotionControlService::class)->homePriceHtml();
    }
}

if (! function_exists('promotion_hero_mobile_price_tag_html')) {
    function promotion_hero_mobile_price_tag_html(
        ?string $heroPricingHtml = null,
        bool $isCollection = false,
        bool $isWindowType = false,
    ): string {
        $service = app(PromotionControlService::class);

        if ($heroPricingHtml !== null && trim($heroPricingHtml) !== '') {
            return $service->resolveHeroMobilePriceTagHtml($heroPricingHtml, $isCollection, $isWindowType);
        }

        return $service->defaultHeroMobilePriceTagHtml();
    }
}
