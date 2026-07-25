<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MailboxAttachment extends Model
{
    protected $fillable = [
        'mailbox_message_id',
        'filename',
        'mime',
        'size',
        'disk_path',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(MailboxMessage::class, 'mailbox_message_id');
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->disk_path);
    }
}
