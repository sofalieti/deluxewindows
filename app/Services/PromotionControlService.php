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

    public function get(): PromotionControl
    {
        if (! Schema::hasTable('promotion_controls')) {
            return new PromotionControl([
                'scope' => 'default',
                'global_promotion_name' => self::DEFAULT_PROMOTION_NAME,
                'global_discount_percent' => 40,
                'global_end_date' => null,
                'window_type_prices' => [],
                'series_prices' => [],
                'brand_prices' => [],
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

    public function priceHtml(string $base, string $final, string $suffix = 'per window'): string
    {
        $base = e($this->normalizeMoney($base));
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);
        $discount = e($this->globalDiscountLabel());
        $promoName = e($this->globalPromotionName());

        return '<div class="promo-offer-card">'
            .'<h3 class="promo-offer-title">'.$promoName.'</h3>'
            .'<div class="promo-offer-headline">'.$discount.'</div>'
            .'<div class="promo-offer-subtitle">Limited-time pricing</div>'
            .'<div class="promo-price-tag">'
            .'<div class="promo-price-tag-line"><span class="promo-price-tag-label">Regular</span><span class="promo-price-tag-old"><s>'.$base.'</s></span></div>'
            .'<div class="promo-price-tag-line promo-price-tag-line--new"><span class="promo-price-tag-label">Now</span><span class="promo-price-tag-new">'.$final.'</span></div>'
            .'<div class="promo-price-tag-note">'.$suffix.'</div>'
            .'</div>'
            .'</div>';
    }

    public function homePriceHtml(): string
    {
        $discount = e($this->globalDiscountLabel());
        $percent = e($this->globalDiscountPercent().'%');

        return '<div class="promo-offer-card promo-offer-card--home">'
            .'<h3 class="promo-offer-title">Get Deluxe Windows for Less</h3>'
            .'<div class="promo-offer-headline">'.$discount.'</div>'
            .'<div class="promo-offer-subtitle">Limited-time window replacement offer</div>'
            .'<div class="promo-price-tag promo-price-tag--percent">'
            .'<div class="promo-price-tag-line promo-price-tag-line--new"><span class="promo-price-tag-new">'.$percent.'</span></div>'
            .'<div class="promo-price-tag-note">OFF Windows</div>'
            .'</div>'
            .'<button type="button" class="promo-offer-card__estimate-btn" data-open-estimate-modal>Request a Free Estimate</button>'
            .'</div>';
    }

    public function priceHtmlStartingFrom(string $final, string $suffix = 'per window installed'): string
    {
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);
        $discount = e($this->globalDiscountLabel());
        $promoName = e($this->globalPromotionName());

        return '<div class="promo-offer-card">'
            .'<h3 class="promo-offer-title">'.$promoName.'</h3>'
            .'<div class="promo-offer-headline">'.$discount.'</div>'
            .'<div class="promo-offer-subtitle">Special pricing available upon request!</div>'
            .'<div class="promo-price-tag">'
            .'<div class="promo-price-tag-line promo-price-tag-line--start"><span class="promo-price-tag-label">Starting from</span><span class="promo-price-tag-start">'.$final.'</span></div>'
            .'</div>'
            .'<div class="promo-offer-note">'.$suffix.'</div>'
            .'</div>';
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

