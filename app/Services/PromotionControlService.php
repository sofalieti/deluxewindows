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

    public function get(): PromotionControl
    {
        if (! Schema::hasTable('promotion_controls')) {
            return new PromotionControl([
                'scope' => 'default',
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
    public function windowTypePricing(string $slug): ?array
    {
        return $this->lookupPricing($this->get()->window_type_prices, $slug);
    }

    /**
     * @return array{base: string, final: string}|null
     */
    public function seriesPricing(string $slug): ?array
    {
        return $this->lookupPricing($this->get()->series_prices, $slug);
    }

    /**
     * @return array{base: string, final: string}|null
     */
    public function brandPricing(string $slug): ?array
    {
        return $this->lookupPricing($this->get()->brand_prices, $slug);
    }

    /**
     * @param  array<string, mixed>|null  $map
     * @return array{base: string, final: string}|null
     */
    private function lookupPricing(?array $map, string $slug): ?array
    {
        if (! is_array($map) || $slug === '') {
            return null;
        }

        $item = $map[$slug] ?? null;
        if (! is_array($item)) {
            return null;
        }

        $base = trim((string) ($item['base'] ?? ''));
        $final = trim((string) ($item['final'] ?? ''));

        if ($base === '' || $final === '') {
            return null;
        }

        return ['base' => $base, 'final' => $final];
    }

    public function priceHtml(string $base, string $final, string $suffix = 'per window'): string
    {
        $base = e($this->normalizeMoney($base));
        $final = e($this->normalizeMoney($final));
        $suffix = e($suffix);
        $discount = e($this->globalDiscountLabel());

        return "<h3><strong>{$discount} Limited-Time Savings</strong></h3>"
            ."<p><strong>Regular price:</strong> <s>{$base}</s></p>"
            ."<p><strong>Your price:</strong> {$final}<sup>*</sup> <em>{$suffix}</em></p>";
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

