<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class RingCentralCall extends Model
{
    use AsSource;
    use Filterable;

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
            'started_at' => 'datetime',
            'duration' => 'integer',
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
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
