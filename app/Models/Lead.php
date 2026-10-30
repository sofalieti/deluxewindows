<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ClassifiesTrafficSource;
use App\Services\TrafficSourceVisibility;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public const STATUS_UNQUALIFIED_BELOW_MIN = 'unqualified_below_min';

    public const STATUS_NON_SALES_INQUIRY = 'non_sales_inquiry';

    public const STATUS_TRANSFERED_TO_FELIX = 'transfered_to_felix';

    public const STATUS_SPAM = 'spam';

    /**
     * @var array<string, string>
     */
    public const OPENING_COUNTS = [
        '1-3' => '1–3',
        '4-10' => '4–10',
        '11+' => '11+',
    ];

    /**
     * @var array<string, string>
     */
    public const WINDOW_COUNTS = [
        '1' => '1',
        '2' => '2',
        '3-5' => '3–5',
        '6-10' => '6–10',
        '11plus' => '11+',
        'unsure' => 'Not sure',
    ];

    /**
     * @var array<string, string>
     */
    public const OPENING_KINDS = [
        'windows' => 'Windows',
        'doors' => 'Doors',
        'both' => 'Windows & doors',
    ];

    /**
     * Staff-confirmed count; the visitor's own WINDOW_COUNTS answer is only a hint.
     *
     * @var array<string, string>
     */
    public const QUALIFICATION_WINDOW_COUNTS = [
        '1-2' => '1–2',
        '3-4' => '3–4',
        '5-9' => '5–9',
        '10plus' => '10+',
        'unknown' => 'Not sure yet',
    ];

    /**
     * @var array<string, string>
     */
    public const QUALIFICATION_PROJECT_TYPES = [
        'replacement' => 'Window replacement',
        'new_construction' => 'New construction',
        'doors_only' => 'Doors only',
    ];

    /**
     * @var array<string, string>
     */
    public const QUALIFICATION_PROPERTY_TYPES = [
        'house' => 'Private house',
        'apartment' => 'Apartment',
        'commercial' => 'Commercial',
    ];

    public const BING_QUALIFIED_PENDING = 'pending';

    public const BING_QUALIFIED_SENT = 'sent';

    public const BING_QUALIFIED_FAILED = 'failed';

    public const BING_QUALIFIED_SKIPPED = 'skipped';

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
        self::STATUS_UNQUALIFIED_BELOW_MIN => 'Unqualified: below minimum requirement',
        self::STATUS_NON_SALES_INQUIRY => 'Non-Sales Inquiry',
        self::STATUS_TRANSFERED_TO_FELIX => 'Transfered to Felix',
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
        'referral_partner_id',
        'ip_address',
        'user_agent',
        'screen_width',
        'screen_height',
        'visitor_id',
        'session_id',
        'meta',
        'status',
        'assigned_to',
        'assigned_at',
        'qualification_window_count',
        'qualification_project_type',
        'qualification_property_type',
        'qualification_zip',
        'qualification_decision_maker',
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
            'assigned_at' => 'datetime',
            'qualification_decision_maker' => 'boolean',
            'bing_qualified_at' => 'datetime',
            'bing_qualified_sent_at' => 'datetime',
            'bing_qualified_attempts' => 'integer',
            'screen_width' => 'integer',
            'screen_height' => 'integer',
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
            $lead->traffic_source = $lead->trafficSourceKey();
        });

        static::saved(function (Lead $lead): void {
            if ($lead->wasRecentlyCreated || $lead->wasChanged(['phone', 'status'])) {
                app(\App\Services\RingCentralContactBinder::class)->rebindLead($lead);
            }
        });

        static::created(function (Lead $lead): void {
            self::queueListSheetSync($lead);
        });

        static::updated(function (Lead $lead): void {
            if ($lead->wasChanged('status')) {
                self::queueListSheetSync($lead);
            }
        });
    }

    private static function queueListSheetSync(self $lead): void
    {
        if (! config('services.google_drive.leads_list_sync') || $lead->id === null) {
            return;
        }

        \App\Jobs\SyncLeadToListSheet::dispatch($lead->id)->afterCommit();
    }

    /**
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeMatchingSearch(Builder $query): Builder
    {
        return app(\App\Services\CrmSmartSearch::class)->apply(
            $query,
            ['full_name', 'email', 'phone', 'city', 'message'],
            ['phone', 'normalized_phone'],
        );
    }

    /**
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeVisibleTo(Builder $query, ?Authenticatable $user): Builder
    {
        return app(TrafficSourceVisibility::class)
            ->constrain($query, $user, TrafficSourceVisibility::SECTION_LEADS);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function referralPartner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class, 'referral_partner_id');
    }

    public function referralReward(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReferralReward::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(CrmTask::class, 'subject')->latest();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(CrmNote::class, 'subject')->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LeadComment::class)->latest();
    }

    public function latestComment(): HasOne
    {
        return $this->hasOne(LeadComment::class)->latestOfMany();
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
            self::STATUS_UNQUALIFIED_BELOW_MIN => 'unqualified_below_min',
            self::STATUS_NON_SALES_INQUIRY => 'non_sales_inquiry',
            self::STATUS_TRANSFERED_TO_FELIX => 'transfered_to_felix',
            self::STATUS_SPAM => 'spam',
            default => 'new',
        };
    }

    public function isSpam(): bool
    {
        return $this->status === self::STATUS_SPAM;
    }

    /**
     * Still New and nobody has left a comment.
     *
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_NEW)
            ->whereDoesntHave('comments');
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
    public static function openingKindFromUrl(?string $url): string
    {
        $path = trim((string) parse_url((string) $url, PHP_URL_PATH), '/');

        if (preg_match('/^(doors|door-brands|door-types)(\/|$)/', $path) === 1) {
            return 'doors';
        }

        if (preg_match('/^(windows|brands|brand-collections|window-type|window-replacement)(\/|$)/', $path) === 1) {
            return 'windows';
        }

        return 'both';
    }

    public function openingSummary(): string
    {
        $windowCount = $this->metaValue('window_count');
        if (array_key_exists($windowCount, self::WINDOW_COUNTS)) {
            $countLabel = self::WINDOW_COUNTS[$windowCount];
        } else {
            $legacyCount = $this->metaValue('opening_count');
            if (! array_key_exists($legacyCount, self::OPENING_COUNTS)) {
                return '';
            }
            $countLabel = self::OPENING_COUNTS[$legacyCount];
        }

        $kind = $this->metaValue('opening_kind');
        $kindLabel = self::OPENING_KINDS[$kind] ?? self::OPENING_KINDS['both'];

        return $kindLabel.' · '.$countLabel;
    }

    /**
     * Click id captured with the lead itself, or with the phone click it was created from.
     */
    public function resolvedMsclkid(): ?string
    {
        foreach (['msclkid', 'first_touch.msclkid', 'first_msclkid'] as $key) {
            $value = $this->metaValue($key);
            if ($value !== '') {
                return $value;
            }
        }

        $phoneClickId = (int) $this->metaValue('phone_click_id', '0');
        if ($phoneClickId > 0) {
            return PhoneClick::query()->find($phoneClickId)?->resolvedMsclkid();
        }

        return null;
    }

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
            'gbraid' => $this->metaValue('gbraid'),
            'wbraid' => $this->metaValue('wbraid'),
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
            'gbraid' => 'gbraid',
            'wbraid' => 'wbraid',
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
