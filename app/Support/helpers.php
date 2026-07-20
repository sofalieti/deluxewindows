<?php

declare(strict_types=1);

use App\Services\Media\ImageThumbnailService;
use App\Services\PromotionControlService;
use App\Services\PromotionSettingsService;

if (! function_exists('site_phone_display')) {
    function site_phone_display(): string
    {
        try {
            return app(PromotionControlService::class)->phoneDisplay();
        } catch (\Throwable) {
            return PromotionControlService::DEFAULT_PHONE_DISPLAY;
        }
    }
}

if (! function_exists('site_phone_tel')) {
    function site_phone_tel(): string
    {
        try {
            return app(PromotionControlService::class)->phoneTel();
        } catch (\Throwable) {
            return PromotionControlService::DEFAULT_PHONE_TEL;
        }
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
            \App\Support\WebflowAssetName::basename($source),
        ]);

        foreach ($candidates as $name) {
            $file = public_path('webflow-assets/images/'.$name);
            if (is_file($file)) {
                return '/webflow-assets/images/'.$name;
            }
        }

        // Last resort: sanitized WebflowAssetName path even if basename variants failed above.
        $sanitized = \App\Support\WebflowAssetName::localUrl($source);
        if ($sanitized !== '') {
            $path = ltrim((string) parse_url($sanitized, PHP_URL_PATH), '/');
            if ($path !== '' && is_file(public_path($path))) {
                return $sanitized;
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

if (! function_exists('promotion_category')) {
    function promotion_category(): string
    {
        $path = trim(request()->path(), '/');

        if (preg_match('/^(doors(?:\/|$)|door-brands(?:\/|$)|door-types(?:\/|$))/', $path) === 1) {
            return 'doors';
        }

        if (preg_match('/^(windows(?:\/|$)|brands(?:\/|$)|brand-collections(?:\/|$)|window-type(?:\/|$)|window-replacement(?:\/|$))/', $path) === 1) {
            return 'windows';
        }

        return 'general';
    }
}

if (! function_exists('site_css_bundle_url')) {
    /**
     * Concatenate several public CSS files into a single hash-named bundle
     * so the page loads one stylesheet instead of many render-blocking ones.
     * The bundle is rebuilt automatically whenever a source file changes.
     *
     * @param  list<string>  $publicPaths  Paths relative to public/ (all url() refs must be absolute)
     */
    function site_css_bundle_url(array $publicPaths, string $name = 'site'): string
    {
        $sources = [];
        $latest = 0;

        foreach ($publicPaths as $relative) {
            $file = public_path($relative);
            if (is_file($file)) {
                $sources[] = $file;
                $latest = max($latest, (int) filemtime($file));
            }
        }

        if ($sources === []) {
            return '';
        }

        $version = substr(md5(implode('|', $publicPaths).'|'.$latest), 0, 12);
        $bundleDir = public_path('build/css');
        $bundleFile = $bundleDir.'/'.$name.'-'.$version.'.css';

        if (! is_file($bundleFile)) {
            if (! is_dir($bundleDir)) {
                @mkdir($bundleDir, 0755, true);
            }

            $css = '';
            foreach ($sources as $file) {
                $css .= '/* '.basename($file)." */\n".file_get_contents($file)."\n";
            }

            $tmp = $bundleFile.'.'.getmypid().'.tmp';
            file_put_contents($tmp, $css);
            if (! @rename($tmp, $bundleFile)) {
                @unlink($tmp);
            }

            foreach (glob($bundleDir.'/'.$name.'-*.css') ?: [] as $old) {
                if ($old !== $bundleFile) {
                    @unlink($old);
                }
            }
        }

        return '/build/css/'.$name.'-'.$version.'.css';
    }
}

if (! function_exists('promotion_home_html')) {
    function promotion_home_html(?string $category = null): string
    {
        return app(PromotionControlService::class)->homePriceHtml($category ?? promotion_category());
    }
}

if (! function_exists('promotion_hero_mobile_price_tag_html')) {
    function promotion_hero_mobile_price_tag_html(
        ?string $heroPricingHtml = null,
        bool $isCollection = false,
        bool $isWindowType = false,
        ?array $pricing = null,
        string $suffix = 'per window installed',
    ): string {
        return app(PromotionControlService::class)->resolveHeroMobilePriceTagHtml(
            $heroPricingHtml,
            $isCollection,
            $isWindowType,
            $pricing,
            $suffix,
        );
    }
}
