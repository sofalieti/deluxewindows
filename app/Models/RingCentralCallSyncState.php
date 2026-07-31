<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RingCentralCallSyncState extends Model
{
    protected $table = 'ringcentral_call_sync_states';

    protected $fillable = [
        'business_phone',
        'started_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
