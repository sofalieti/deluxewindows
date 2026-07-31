<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class RingCentralExcludedNumber extends Model
{
    use AsSource;

    protected $table = 'ringcentral_excluded_numbers';

    protected $fillable = [
        'phone',
        'excluded_at',
        'excluded_by_user_id',
        'restored_at',
        'restored_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'excluded_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function excludedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_by_user_id');
    }

    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by_user_id');
    }

    public function hiddenCallsCount(): int
    {
        return RingCentralCall::query()->where('external_phone', $this->phone)->count();
    }
}
