<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class MailboxMessage extends Model
{
    use AsSource;
    use Filterable;

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * @var array<string, string>
     */
    protected $allowedSorts = [
        'id',
        'sent_at',
        'subject',
        'from_email',
        'direction',
        'created_at',
    ];

    protected $fillable = [
        'direction',
        'folder',
        'imap_uid',
        'message_id',
        'in_reply_to',
        'subject',
        'from_email',
        'from_name',
        'to',
        'cc',
        'participant_emails',
        'sent_at',
        'snippet',
        'body_text',
        'body_html',
        'has_attachments',
        'raw_headers',
        'is_read_local',
    ];

    protected function casts(): array
    {
        return [
            'imap_uid' => 'integer',
            'sent_at' => 'datetime',
            'has_attachments' => 'boolean',
            'is_read_local' => 'boolean',
            'participant_emails' => 'array',
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MailboxAttachment::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<MailboxMessage>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MailboxMessage>
     */
    public function scopeForParticipant($query, ?string $email)
    {
        $normalized = Contact::normalizeEmail($email);
        if ($normalized === null) {
            return $query->whereRaw('0 = 1');
        }

        $like = '%'.addcslashes($normalized, '%_\\').'%';

        return $query->where(function ($inner) use ($normalized, $like): void {
            $inner->whereJsonContains('participant_emails', $normalized)
                ->orWhereRaw('LOWER(from_email) = ?', [$normalized])
                ->orWhere('to', 'like', $like)
                ->orWhere('cc', 'like', $like);
        });
    }
}
