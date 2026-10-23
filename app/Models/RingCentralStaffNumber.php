<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

class RingCentralStaffNumber extends Model
{
    use AsSource;

    protected $table = 'ringcentral_staff_numbers';

    protected $fillable = [
        'phone',
        'name',
        'marked_at',
        'marked_by_user_id',
        'restored_at',
        'restored_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by_user_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(RingCentralCall::class, 'external_phone', 'phone');
    }
}
