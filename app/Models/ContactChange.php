<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ContactChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contact_id',
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

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        Contact $contact,
        string $field,
        ?string $oldValue,
        ?string $newValue,
        string $summary,
        ?int $userId = null,
    ): self {
        return self::query()->create([
            'contact_id' => $contact->id,
            'user_id' => $userId ?? Auth::id(),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'summary' => $summary,
            'created_at' => now(),
        ]);
    }
}
