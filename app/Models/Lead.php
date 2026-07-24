<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class Lead extends Model
{
    use AsSource;
    use Filterable;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUOTED = 'quoted';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    public const STATUS_SPAM = 'spam';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_QUOTED => 'Quoted',
        self::STATUS_WON => 'Won',
        self::STATUS_LOST => 'Lost',
        self::STATUS_SPAM => 'Spam',
    ];

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'city',
        'message',
        'page_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip_address',
        'user_agent',
        'meta',
        'status',
    ];

    /**
     * The attributes for which you can use filters in the url,
     * e.g. /admin/leads?filter[id]=123 (used to deep-link a single
     * lead from the "new lead" email straight to its admin record).
     *
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'full_name' => Like::class,
        'email' => Like::class,
        'phone' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'created_at',
        'status',
        'full_name',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if ($lead->status === null || $lead->status === '') {
                $lead->status = self::STATUS_NEW;
            }
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LeadComment::class)->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function isSpam(): bool
    {
        return $this->status === self::STATUS_SPAM;
    }
}
