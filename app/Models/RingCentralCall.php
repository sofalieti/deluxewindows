<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\StoresUtcTimestamps;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class RingCentralCall extends Model
{
    use AsSource;
    use Filterable;
    use StoresUtcTimestamps;

    protected $table = 'ringcentral_calls';

    protected $fillable = [
        'ringcentral_call_id',
        'session_id',
        'telephony_session_id',
        'direction',
        'action',
        'result',
        'started_at',
        'duration',
        'business_phone',
        'from_phone',
        'from_name',
        'to_phone',
        'to_name',
        'external_phone',
        'raw',
        'synced_at',
    ];

    protected $allowedFilters = [
        'direction' => Where::class,
        'result' => Like::class,
        'external_phone' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'started_at',
        'duration',
        'direction',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'raw' => 'array',
        ];
    }

    /**
     * Stored as UTC wall-clock; exposed as UTC Carbon.
     */
    protected function startedAt(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?CarbonImmutable => $this->asUtcImmutable($value),
            set: fn (mixed $value): ?string => $this->utcDatabaseString($value),
        );
    }

    protected function syncedAt(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?CarbonImmutable => $this->asUtcImmutable($value),
            set: fn (mixed $value): ?string => $this->utcDatabaseString($value),
        );
    }

    public function startedAtPacific(): ?CarbonImmutable
    {
        return $this->started_at?->setTimezone('America/Los_Angeles');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNotExists(function ($subquery): void {
            $subquery->selectRaw('1')
                ->from('ringcentral_excluded_numbers')
                ->whereNull('restored_at')
                ->whereColumn('ringcentral_excluded_numbers.phone', 'ringcentral_calls.external_phone');
        });
    }

    public function durationLabel(): string
    {
        $seconds = max(0, (int) $this->duration);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
