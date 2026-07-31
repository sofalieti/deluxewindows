<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Carbon\CarbonImmutable;

trait StoresUtcTimestamps
{
    protected function asUtcImmutable(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value->utc();
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->utc();
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw) === 1) {
                return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $raw, 'UTC');
            }

            return CarbonImmutable::parse($raw)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function utcDatabaseString(mixed $value): ?string
    {
        $utc = $this->asUtcImmutable($value);

        return $utc?->format('Y-m-d H:i:s');
    }

    public function readUtcAttribute(string $column): ?CarbonImmutable
    {
        return $this->asUtcImmutable($this->getRawOriginal($column));
    }

    public function formatInPacific(string $column, string $format = 'M d, Y h:i A'): string
    {
        $utc = $this->readUtcAttribute($column);
        if ($utc === null) {
            return '—';
        }

        return $utc->setTimezone('America/Los_Angeles')->format($format).' PT';
    }
}
