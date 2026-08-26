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
     * @param  list<string|null>|iterable<int, string|null>  $emails
     * @return \Illuminate\Database\Eloquent\Builder<MailboxMessage>
     */
    public function scopeForParticipants($query, iterable $emails)
    {
        $normalized = [];
        foreach ($emails as $email) {
            $key = Contact::normalizeEmail($email);
            if ($key !== null) {
                $normalized[$key] = true;
            }
        }
        $keys = array_keys($normalized);
        if ($keys === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function ($outer) use ($keys): void {
            foreach ($keys as $index => $email) {
                $like = '%'.addcslashes($email, '%_\\').'%';
                $clause = function ($inner) use ($email, $like): void {
                    $inner->whereJsonContains('participant_emails', $email)
                        ->orWhereRaw('LOWER(from_email) = ?', [$email])
                        ->orWhere('to', 'like', $like)
                        ->orWhere('cc', 'like', $like);
                };
                if ($index === 0) {
                    $outer->where($clause);
                } else {
                    $outer->orWhere($clause);
                }
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<MailboxMessage>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MailboxMessage>
     */
    public function scopeForParticipant($query, ?string $email)
    {
        return $query->forParticipants([$email]);
    }
}
