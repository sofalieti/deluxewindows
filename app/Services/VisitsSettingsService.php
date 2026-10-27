<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VisitsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class VisitsSettingsService
{
    private const CACHE_PREFIX = 'visits.settings.';

    public function get(): VisitsSetting
    {
        return $this->forScope('default');
    }

    public function forScope(string $scope): VisitsSetting
    {
        if (! Schema::hasTable('visits_settings')) {
            return new VisitsSetting([
                'scope' => $scope,
                'enabled' => false,
            ]);
        }

        return Cache::remember(self::CACHE_PREFIX.$scope, now()->addHour(), function () use ($scope) {
            return VisitsSetting::query()->firstOrCreate(
                ['scope' => $scope],
                ['enabled' => false]
            );
        });
    }

    public function enabled(): bool
    {
        return (bool) $this->get()->enabled;
    }

    public function behaviorEnabled(): bool
    {
        return (bool) $this->forScope('behavior')->enabled;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_PREFIX.'default');
        Cache::forget(self::CACHE_PREFIX.'behavior');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): VisitsSetting
    {
        return $this->updateScope('default', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateScope(string $scope, array $data): VisitsSetting
    {
        $setting = $this->forScope($scope);
        $setting->fill($data);
        $setting->save();
        Cache::forget(self::CACHE_PREFIX.$scope);

        return $setting->fresh() ?? $setting;
    }
}
