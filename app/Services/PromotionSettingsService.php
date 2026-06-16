<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webflow\CouponsWebflowItem;
use App\Models\Webflow\GlobalSettingsWebflowItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PromotionSettingsService
{
    public const CACHE_KEY = 'promotion.global_settings';

    /**
     * @return array{promotion_name: string, start_date: ?Carbon, end_date: ?Carbon, end_date_raw: string}
     */
    public function globalSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            $item = GlobalSettingsWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->where(function ($query) {
                    $query->where('field_data->slug', 'default')
                        ->orWhereNull('field_data->slug');
                })
                ->orderBy('id')
                ->first();

            if ($item === null) {
                $item = GlobalSettingsWebflowItem::query()
                    ->where('is_archived', false)
                    ->where('is_draft', false)
                    ->orderBy('id')
                    ->first();
            }

            $fieldData = is_array($item?->field_data) ? $item->field_data : [];

            $endRaw = trim((string) (
                $fieldData['end-date']
                ?? $item?->wf_end_date
                ?? ''
            ));

            $startRaw = trim((string) (
                $fieldData['start-date']
                ?? $item?->wf_start_date
                ?? ''
            ));

            return [
                'promotion_name' => trim((string) (
                    $fieldData['promotion-name']
                    ?? $item?->wf_promotion_name
                    ?? ''
                )),
                'start_date' => $this->parseDate($startRaw),
                'end_date' => $this->parseDate($endRaw),
                'end_date_raw' => $endRaw,
            ];
        });
    }

    public function endDate(): ?Carbon
    {
        return $this->globalSettings()['end_date'];
    }

    public function format(?Carbon $date, string $format): string
    {
        if ($date === null) {
            return '';
        }

        return match ($format) {
            'us-short' => $date->format('n/j/y'),
            'us-short-no-year' => $date->format('n/j'),
            'long' => $date->format('F j, Y'),
            default => $date->format('n/j/y'),
        };
    }

    public function formatGlobal(string $format): string
    {
        return $this->format($this->endDate(), $format);
    }

    public function promotionName(): string
    {
        return $this->globalSettings()['promotion_name'];
    }

    public function couponEndDate(CouponsWebflowItem $coupon): ?Carbon
    {
        $fieldData = is_array($coupon->field_data) ? $coupon->field_data : [];

        $iso = $fieldData['offer-expires'] ?? null;
        if (is_string($iso) && $iso !== '') {
            try {
                return Carbon::parse($iso);
            } catch (\Throwable) {
                // fall through
            }
        }

        if ($coupon->wf_offer_expires !== null) {
            return Carbon::parse($coupon->wf_offer_expires);
        }

        $text = trim((string) ($fieldData['offer-expires-text'] ?? $coupon->wf_offer_expires_text ?? ''));

        return $this->parseDate($text);
    }

    public function couponExpiresLabel(CouponsWebflowItem $coupon, string $format = 'long'): string
    {
        $date = $this->couponEndDate($coupon);
        if ($date !== null) {
            return $this->format($date, $format);
        }

        $fieldData = is_array($coupon->field_data) ? $coupon->field_data : [];
        $text = trim((string) ($fieldData['offer-expires-text'] ?? $coupon->wf_offer_expires_text ?? ''));

        return $text;
    }

    /**
     * @return \Illuminate\Support\Collection<int, CouponsWebflowItem>
     */
    public function publishedCoupons()
    {
        return Cache::remember('promotion.coupons', now()->addHour(), function () {
            return CouponsWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->orderBy('id')
                ->get();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('promotion.coupons');
    }

    public function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $value, $matches)) {
            $month = (int) $matches[1];
            $day = (int) $matches[2];
            $year = (int) $matches[3];
            if ($year < 100) {
                $year += $year >= 70 ? 1900 : 2000;
            }

            try {
                return Carbon::createFromDate($year, $month, $day)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
