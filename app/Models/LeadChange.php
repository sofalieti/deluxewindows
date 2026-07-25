<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class LeadChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        Lead $lead,
        string $field,
        ?string $oldValue,
        ?string $newValue,
        string $summary,
        ?int $userId = null,
    ): self {
        return self::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $userId ?? Auth::id(),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'summary' => $summary,
            'created_at' => now(),
        ]);
    }

    public static function recordStatusChange(Lead $lead, string $from, string $to, ?int $userId = null): ?self
    {
        if ($from === $to) {
            return null;
        }

        $fromLabel = Lead::STATUSES[$from] ?? $from;
        $toLabel = Lead::STATUSES[$to] ?? $to;

        return self::record(
            $lead,
            'status',
            $from,
            $to,
            'Status: '.$fromLabel.' → '.$toLabel,
            $userId,
        );
    }
}
