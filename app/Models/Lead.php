<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ClassifiesTrafficSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class Lead extends Model
{
    use AsSource;
    use ClassifiesTrafficSource;
    use Filterable;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_APPOINTMENT = 'appointment';

    public const STATUS_QUOTED = 'quoted';

    public const STATUS_WON = 'won';

    public const STATUS_SOLD = 'sold';

    public const STATUS_LOST = 'lost';

    public const STATUS_SPAM = 'spam';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_APPOINTMENT => 'Appointment',
        self::STATUS_QUOTED => 'Quoted',
        self::STATUS_WON => 'Won',
        self::STATUS_SOLD => 'Sold',
        self::STATUS_LOST => 'Lost',
        self::STATUS_SPAM => 'Spam',
    ];

    protected $fillable = [
        'contact_id',
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
        'contact_id' => Where::class,
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

        static::saving(function (Lead $lead): void {
            $lead->normalized_email = Contact::normalizeEmail($lead->email);
            $lead->normalized_phone = Contact::normalizePhone($lead->phone);
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LeadComment::class)->latest();
    }

    public function changes(): HasMany
    {
        return $this->hasMany(LeadChange::class)->latest('created_at');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /**
     * CSS modifier for status badges (new, contacted, …).
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'new',
            self::STATUS_CONTACTED => 'contacted',
            self::STATUS_APPOINTMENT => 'appointment',
            self::STATUS_QUOTED => 'quoted',
            self::STATUS_WON => 'won',
            self::STATUS_SOLD => 'sold',
            self::STATUS_LOST => 'lost',
            self::STATUS_SPAM => 'spam',
            default => 'new',
        };
    }

    public function isSpam(): bool
    {
        return $this->status === self::STATUS_SPAM;
    }

    /**
     * RingCentral calls for the linked contact, or this lead's phone.
     *
     * @return Collection<int, RingCentralCall>
     */
    public function ringCentralCallsForPhone(): Collection
    {
        if ($this->contact_id) {
            $contact = $this->relationLoaded('contact')
                ? $this->contact
                : $this->contact()->first();

            if ($contact !== null) {
                return $contact->ringCentralCallsForPhone();
            }
        }

        return (new Contact(['phone' => $this->phone]))->ringCentralCallsForPhone();
    }

    /**
     * Read a scalar value from the JSON meta column.
     */
    public function metaValue(string $key, string $default = ''): string
    {
        $value = data_get($this->meta, $key);
        if ($value === null || is_array($value)) {
            return $default;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $default;
    }

    /**
     * All campaign / click-id attribution fields for admin display.
     *
     * @return array<string, string>
     */
    public function utmFields(): array
    {
        return [
            'utm_source' => trim((string) ($this->utm_source ?? '')),
            'utm_medium' => trim((string) ($this->utm_medium ?? '')),
            'utm_campaign' => trim((string) ($this->utm_campaign ?? '')),
            'utm_content' => $this->metaValue('utm_content'),
            'utm_term' => $this->metaValue('utm_term'),
            'matchtype' => $this->metaValue('matchtype'),
            'device' => $this->metaValue('device'),
            'creative' => $this->metaValue('creative'),
            'gclid' => $this->metaValue('gclid'),
            'fbclid' => $this->metaValue('fbclid'),
            'msclkid' => $this->metaValue('msclkid'),
            'landing_page' => $this->metaValue('landing_page'),
            'referrer' => $this->metaValue('referrer'),
            'form_id' => $this->metaValue('form_id'),
            'geo_location' => $this->metaValue('geo_location'),
        ];
    }

    /**
     * Non-empty UTM / attribution fields as "label: value" lines.
     *
     * @return list<string>
     */
    public function utmSummaryParts(): array
    {
        $labels = [
            'utm_source' => 'src',
            'utm_medium' => 'med',
            'utm_campaign' => 'cmp',
            'utm_content' => 'content',
            'utm_term' => 'term',
            'matchtype' => 'match',
            'device' => 'device',
            'creative' => 'creative',
            'gclid' => 'gclid',
            'fbclid' => 'fbclid',
            'msclkid' => 'msclkid',
        ];

        $parts = [];
        foreach ($labels as $key => $label) {
            $value = $this->utmFields()[$key] ?? '';
            if ($value !== '') {
                $parts[] = $label.': '.$value;
            }
        }

        return $parts;
    }

}
