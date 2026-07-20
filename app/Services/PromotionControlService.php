<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PromotionControl;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PromotionControlService
{
    private const CACHE_KEY = 'promotion.control.default';
    private const DEFAULT_PROMOTION_NAME = 'Upgrade to Energy Efficient Windows and Doors for Less';
    public const DEFAULT_PHONE_DISPLAY = '(650) 461-4446';
    public const DEFAULT_PHONE_TEL = '+16504614446';

    public function get(): PromotionControl
    {
        if (! Schema::hasTable('promotion_controls')) {
            return new PromotionControl([
                'scope' => 'default',
                'global_promotion_name' => self::DEFAULT_PROMOTION_NAME,
                'global_discount_percent' => 40,
                'global_end_date' => null,
                'phone_display' => self::DEFAULT_PHONE_DISPLAY,
                'phone_tel' => self::DEFAULT_PHONE_TEL,
                'window_type_prices' => [],
                'series_prices' => [],
                'brand_prices' => [],
                'door_prices' => [],
            ]);
        }

        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return PromotionControl::query()->firstOrCreate(
                ['scope' => 'default'],
                [
                    'global_promotion_name' => self::DEFAULT_PROMOTION_NAME,
                    'global_discount_percent' => 40,
                    'window_type_prices' => [],
                    'series_prices' => [],
                    'brand_prices' => [],
                    'door_prices' => [],
                ]
            );
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function globalDiscountPercent(): int
    {
        $value = (int) ($this->get()->global_discount_percent ?? 40);

        return max(0, min(95, $value));
    }

    public function globalDiscountLabel(): string
    {
        return $this->globalDiscountPercent().'% OFF';
    }

    public function globalPromotionName(): string
    {
        $value = trim((string) ($this->get()->global_promotion_name ?? ''));

        return $value !== '' ? $value : self::DEFAULT_PROMOTION_NAME;
    }

    public function phoneDisplay(): string
    {
        $value = trim((string) ($this->get()->phone_display ?? ''));

        return $value !== '' ? $value : self::DEFAULT_PHONE_DISPLAY;
    }

    public function phoneTel(): string
    {
        $value = trim((string) ($this->get()->phone_tel ?? ''));
        if ($value !== '') {
            return $value;
        }

        return self::normalizeTel($this->phoneDisplay());
    }

    /**
     * Convert a human-readable phone number into a tel: friendly value (+1XXXXXXXXXX).
     */
    public static function normalizeTel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return self::DEFAULT_PHONE_TEL;
        }

        $hasPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return self::DEFAULT_PHONE_TEL;
        }

        if ($hasPlus) {
            return '+'.$digits;
        }

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        return '+'.$digits;
    }

    public function endDate(): ?Carbon
    {
        $date = $this->get()->global_end_date;

        if ($date instanceof Carbon) {
            return $date;
        }

        if ($date === null) {
            return null;
        }

        try {
            return Carbon::parse((string) $date);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{base: string, final: string}|null
     */
    public function windowTypePricing(?string $webflowItemId = null, ?string $slug = null): ?array
    {
        return $this->lookupPricing($this->get()->window_type_prices, $webflowItemId, $slug);
    }

    /**
     * @return array{base: string, final: string}|null
     */
    public function seriesPricing(?string $webflowItemId = null, ?string $slug = null): ?array
    {
        return $this->lookupPricing($this->get()->series_prices, $webflowItemId, $slug);
    }

    /**
     * @return array{base: string, final: string}|null
     */
    public function brandPricing(?string $webflowItemId = null, ?string $slug = null): ?array
    {
        return $this->lookupPricing($this->get()->brand_prices, $webflowItemId, $slug);
    }

    /**
     * @return array{base: string, final: string}|null
     */
    public function doorPricing(?string $webflowItemId = null, ?string $slug = null): ?array
    {
        return $this->lookupPricing($this->get()->door_prices, $webflowItemId, $slug);
    }

    /**
     * @param  array<string, mixed>|null  $map
     * @return array{base: string, final: string}|null
     */
    private function lookupPricing(?array $map, ?string $webflowItemId = null, ?string $slug = null): ?array
    {
        if (! is_array($map)) {
            return null;
        }

        $candidates = [];
        $id = trim((string) ($webflowItemId ?? ''));
        if ($id !== '') {
            $candidates[] = $id;
        }
        $legacySlug = trim((string) ($slug ?? ''));
        if ($legacySlug !== '') {
            $candidates[] = $legacySlug;
        }

        if ($candidates === []) {
            return null;
        }

        $item = null;
        foreach ($candidates as $key) {
            if (isset($map[$key]) && is_array($map[$key])) {
                $item = $map[$key];
                break;
            }
        }
        if (! is_array($item)) {
            return null;
        }

        $base = trim((string) ($item['base'] ?? ''));
        $final = trim((string) ($item['final'] ?? ''));

        if ($final === '') {
            return null;
        }

        return ['base' => $base, 'final' => $final];
    }

    /**
     * @param array{base?: string, final: string} $pricing
     */
    public function pricingHtmlFromMap(array $pricing, string $suffix = 'per window installed'): string
    {
        $base = trim((string) ($pricing['base'] ?? ''));
        $final = trim((string) ($pricing['final'] ?? ''));

        if ($final === '') {
            return $this->priceHtml('915', '$549', $suffix);
        }

        if ($base === '') {
            return $this->priceHtmlStartingFrom($final, $suffix);
        }

        return $this->priceHtml($base, $final, $suffix);
    }

    public function pricingTagHtmlFromMap(array $pricing, string $suffix = 'per window installed'): string
    {
        $base = trim((string) ($pricing['base'] ?? ''));
        $final = trim((string) ($pricing['final'] ?? ''));

        if ($final === '') {
            return $this->priceTagHtml('915', '$549', $suffix);
        }

        if ($base === '') {
            return $this->priceTagHtmlStartingFrom($final, $suffix);
        }

        return $this->priceTagHtml($base, $final, $suffix);
    }

    public function priceTagHtml(string $base, string $final, string $suffix = 'per window'): string
    {
        $base = e($this->normalizeMoney($base));
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);

        return '<div class="promo-price-tag">'
            .'<div class="promo-price-tag-line"><span class="promo-price-tag-label">Regular</span><span class="promo-price-tag-old"><s>'.$base.'</s></span></div>'
            .'<div class="promo-price-tag-line promo-price-tag-line--new"><span class="promo-price-tag-label">Now</span><span class="promo-price-tag-new">'.$final.'</span></div>'
            .'<div class="promo-price-tag-note">'.$suffix.'</div>'
            .'</div>';
    }

    public function priceTagHtmlStartingFrom(string $final, string $suffix = 'per window installed'): string
    {
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);

        return '<div class="promo-price-tag">'
            .'<div class="promo-price-tag-line promo-price-tag-line--start"><span class="promo-price-tag-label">Starting from</span><span class="promo-price-tag-start">'.$final.'</span></div>'
            .'<div class="promo-price-tag-note">'.$suffix.'</div>'
            .'</div>';
    }

    public function extractPriceTagFromPromoHtml(string $html): ?string
    {
        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $start = strpos($html, '<div class="promo-price-tag');
        if ($start !== false) {
            $openEnd = strpos($html, '>', $start);
            if ($openEnd === false) {
                return null;
            }

            $depth = 1;
            $pos = $openEnd + 1;
            $length = strlen($html);

            while ($pos < $length && $depth > 0) {
                $nextOpen = strpos($html, '<div', $pos);
                $nextClose = strpos($html, '</div>', $pos);

                if ($nextClose === false) {
                    return null;
                }

                if ($nextOpen !== false && $nextOpen < $nextClose) {
                    $depth++;
                    $pos = $nextOpen + 4;
                    continue;
                }

                $depth--;
                $pos = $nextClose + 6;

                if ($depth === 0) {
                    return substr($html, $start, $pos - $start);
                }
            }

            return null;
        }

        // Classic desktop richtext: Starting from <s>832</s> $499
        if (preg_match(
            '/Starting from\s*<s>\s*\$?\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?)\s*<\/s>\s*(\$?\s*[0-9][0-9,]*(?:\.[0-9]{1,2})?)/i',
            $html,
            $match
        ) === 1) {
            $suffix = 'per window';
            if (preg_match('/<\/div>\s*<p>\s*([^<]+?)\s*<\/p>/i', $html, $suffixMatch) === 1) {
                $suffix = trim(html_entity_decode($suffixMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            return $this->priceTagHtml($match[1], $match[2], $suffix !== '' ? $suffix : 'per window');
        }

        // Classic desktop richtext: Starting from $999 per window installed.
        if (preg_match(
            '/Starting from\s+(\$?\s*[0-9][0-9,]*(?:\.[0-9]{1,2})?)\s+([^.<]+)/i',
            strip_tags(str_replace(['<s>', '</s>'], ' ', $html)),
            $match
        ) === 1 && ! str_contains(strtolower($html), '<s>')) {
            return $this->priceTagHtmlStartingFrom($match[1], trim($match[2]));
        }

        return null;
    }

    public function resolveHeroMobilePriceTagHtml(
        ?string $heroPricingHtml = null,
        bool $isCollection = false,
        bool $isWindowType = false,
        ?array $pricing = null,
        string $suffix = 'per window installed',
    ): string {
        if (is_array($pricing) && trim((string) ($pricing['final'] ?? '')) !== '') {
            return $this->pricingTagHtmlFromMap($pricing, $suffix);
        }

        $extracted = $heroPricingHtml !== null
            ? $this->extractPriceTagFromPromoHtml($heroPricingHtml)
            : null;
        if ($extracted !== null) {
            return $extracted;
        }

        if ($isWindowType) {
            return $this->priceTagHtmlStartingFrom('$1199', $suffix);
        }

        if ($isCollection) {
            return $this->priceTagHtml('915', '$549', $suffix);
        }

        return $this->priceTagHtml('915', '$549', $suffix);
    }

    public function defaultHeroMobilePriceTagHtml(): string
    {
        return $this->priceTagHtml('915', '$549', 'per window installed');
    }

    /**
     * Desktop hero pricing (no red badge). Dual price → strikethrough richtext.
     * Mobile red badge is built separately via priceTagHtml().
     */
    public function priceHtml(string $base, string $final, string $suffix = 'per window'): string
    {
        $baseAmount = e($this->moneyAmount($base));
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);
        $headline = e($this->globalDiscountPercent().'% off for limited time');

        return '<h3><strong>'.$headline.'</strong></h3>'
            .'<div class="w-embed hero-promo-priced">Starting from <s>'.$baseAmount.'</s> '.$final.'<sup>*</sup></div>'
            .'<p>'.$suffix.'</p>'
            .'<p>‍</p>';
    }

    public function homePriceHtml(string $category = 'general'): string
    {
        $promoName = rtrim(trim($this->globalPromotionName()), '.');
        $percent = e($this->globalDiscountPercent().'%');
        $suffix = match ($category) {
            'windows' => 'Windows',
            'doors' => 'Doors',
            default => 'Windows',
        };

        return '<h2 class="display-4">Get Deluxe Windows for Less. <br>'
            .e($promoName).'. <br>'
            .$percent.'&nbsp;OFF* '.$suffix.'</h2>';
    }

    /**
     * Desktop hero pricing when only a final/"from" price exists (no red badge).
     */
    public function priceHtmlStartingFrom(string $final, string $suffix = 'per window installed'): string
    {
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);

        return '<p class="hero-promo-priced">Starting from '.$final.' '.$suffix.'.</p>'
            .'<p><strong>Special pricing available upon request!</strong>‍</p>';
    }

    private function moneyAmount(string $value): string
    {
        $normalized = $this->normalizeMoney($value);
        if (str_starts_with($normalized, '$')) {
            return substr($normalized, 1);
        }

        return $normalized;
    }

    private function normalizeMoney(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '$')) {
            return $trimmed;
        }
        if (is_numeric(str_replace(',', '', $trimmed))) {
            return '$'.$trimmed;
        }

        return $trimmed;
    }
}

