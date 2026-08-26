<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEmail extends Model
{
    protected $fillable = [
        'contact_id',
        'email',
        'normalized_email',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContactEmail $row): void {
            $row->email = trim((string) $row->email);
            $row->normalized_email = Contact::normalizeEmail($row->email) ?? '';
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
