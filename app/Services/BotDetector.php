<?php

declare(strict_types=1);

namespace App\Services;

class BotDetector
{
    /**
     * Known crawler tokens. A bare "bot" suffix is intentionally not used:
     * phone brands such as Cubot would be dropped from behavior tracking.
     */
    private const PATTERN = '/googlebot|adsbot|mediapartners-google|bingbot|duckduckbot|baiduspider|yandexbot|sogou|exabot|facebot|facebookexternalhit|ia_archiver|semrush|ahrefs|mj12bot|dotbot|petalbot|bytespider|gptbot|claudebot|amazonbot|applebot|headlesschrome|headless|lighthouse|wget\/|curl\/|python-requests|scrapy|httpclient|crawler|spider|slurp|bingpreview/i';

    public function isBot(?string $userAgent): bool
    {
        $userAgent = trim((string) $userAgent);
        if ($userAgent === '') {
            return false;
        }

        return preg_match(self::PATTERN, $userAgent) === 1;
    }
}
