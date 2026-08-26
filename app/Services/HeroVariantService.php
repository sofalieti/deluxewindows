<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HeroSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Site-wide hero block version, editable on the admin dashboard.
 *
 * The stored value replaces the HERO_VARIANT config default for every page;
 * a visitor's ?hero= cookie still wins so both versions stay previewable.
 */
class HeroVariantService
{
    private const CACHE_KEY = 'hero.settings.default';

    private const SCOPE = 'default';

    /** @return list<string> */
    public function allowed(): array
    {
        /** @var list<string> $variants */
        $variants = (array) config('hero.variants', ['old', 'new']);

        return $variants;
    }

    public function configDefault(): string
    {
        $variant = (string) config('hero.variant', 'new');

        return in_array($variant, $this->allowed(), true) ? $variant : 'new';
    }

    /** Stored site-wide variant, or the config default when nothing is stored yet. */
    public function variant(): string
    {
        $stored = Cache::remember(self::CACHE_KEY, now()->addHour(), function (): ?string {
            if (! Schema::hasTable('hero_settings')) {
                return null;
            }

            $variant = HeroSetting::query()->where('scope', self::SCOPE)->value('variant');

            return is_string($variant) ? $variant : null;
        });

        return in_array((string) $stored, $this->allowed(), true)
            ? (string) $stored
            : $this->configDefault();
    }

    public function update(string $variant): string
    {
        if (! in_array($variant, $this->allowed(), true)) {
            $variant = $this->configDefault();
        }

        HeroSetting::query()->updateOrCreate(
            ['scope' => self::SCOPE],
            ['variant' => $variant],
        );

        $this->forgetCache();

        return $variant;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
