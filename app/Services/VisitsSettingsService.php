<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VisitsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class VisitsSettingsService
{
    private const CACHE_KEY = 'visits.settings.default';

    public function get(): VisitsSetting
    {
        if (! Schema::hasTable('visits_settings')) {
            return new VisitsSetting([
                'scope' => 'default',
                'enabled' => false,
            ]);
        }

        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return VisitsSetting::query()->firstOrCreate(
                ['scope' => 'default'],
                ['enabled' => false]
            );
        });
    }

    public function enabled(): bool
    {
        return (bool) $this->get()->enabled;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): VisitsSetting
    {
        $setting = $this->get();
        $setting->fill($data);
        $setting->save();
        $this->forgetCache();

        return $setting->fresh() ?? $setting;
    }
}
