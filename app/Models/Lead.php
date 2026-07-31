<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function trafficSourceKey(): string
    {
        $source = strtolower(trim((string) ($this->utm_source ?? '')));
        $medium = strtolower(trim((string) ($this->utm_medium ?? '')));
        $referrerHost = $this->referrerHost();
        $isPaid = preg_match('/(?:^|[_\-\s])(cpc|ppc|paid|paidsearch|paid_social|display|sem)(?:$|[_\-\s])/i', $medium) === 1;

        if ($this->metaValue('gclid') !== '' || $source === 'adwords' || ($this->isGoogleSource($source) && $isPaid)) {
            return 'google_ads';
        }

        if ($this->metaValue('msclkid') !== '' || ($this->isBingSource($source) && $isPaid)) {
            return 'microsoft_ads';
        }

        if ($this->metaValue('fbclid') !== '' && $isPaid) {
            return 'meta_ads';
        }

        if ($this->isMetaSource($source)) {
            return $isPaid ? 'meta_ads' : 'social';
        }

        if ($isPaid) {
            return 'paid_ads';
        }

        if ($this->isGoogleSource($source) || $this->isGoogleHost($referrerHost)) {
            return 'seo_google';
        }

        if ($this->isBingSource($source) || $this->hostMatches($referrerHost, 'bing.com')) {
            return 'seo_bing';
        }

        if (
            str_contains($source, 'yahoo')
            || str_contains($source, 'duckduckgo')
            || $this->hostMatches($referrerHost, 'search.yahoo.com')
            || $this->hostMatches($referrerHost, 'duckduckgo.com')
        ) {
            return 'seo_other';
        }

        if ($medium === 'email' || str_contains($source, 'email') || str_contains($source, 'newsletter')) {
            return 'email';
        }

        if ($this->isSocialHost($referrerHost)) {
            return 'social';
        }

        if ($referrerHost !== '' && ! $this->hostMatches($referrerHost, 'deluxewindows.com')) {
            return 'referral';
        }

        if ($source !== '' && ! in_array($source, ['(direct)', 'direct'], true)) {
            return 'campaign';
        }

        return 'direct';
    }

    public function trafficSourceLabel(): string
    {
        return match ($this->trafficSourceKey()) {
            'google_ads' => 'Google Ads',
            'microsoft_ads' => 'Microsoft Ads',
            'meta_ads' => 'Meta Ads',
            'paid_ads' => 'Paid Ads',
            'seo_google' => 'SEO · Google',
            'seo_bing' => 'SEO · Bing',
            'seo_other' => 'SEO · Other',
            'social' => 'Social',
            'email' => 'Email',
            'referral' => 'Referral',
            'campaign' => 'Campaign',
            default => 'Direct',
        };
    }

    public function trafficSourceColor(): string
    {
        return match ($this->trafficSourceKey()) {
            'google_ads', 'microsoft_ads', 'meta_ads', 'paid_ads' => 'primary',
            'seo_google', 'seo_bing', 'seo_other' => 'success',
            'social', 'email' => 'warning',
            'referral', 'campaign' => 'info',
            default => 'secondary',
        };
    }

    public function trafficSourceDetail(): string
    {
        $campaign = trim((string) ($this->utm_campaign ?? ''));
        if ($campaign !== '') {
            return $campaign;
        }

        $source = trim((string) ($this->utm_source ?? ''));
        $medium = trim((string) ($this->utm_medium ?? ''));
        if ($source !== '' && ! in_array(strtolower($source), ['(direct)', 'direct'], true)) {
            return $medium !== '' && $medium !== '(none)'
                ? $source.' / '.$medium
                : $source;
        }

        if ($this->trafficSourceKey() === 'referral') {
            return $this->referrerHost();
        }

        return '';
    }

    private function referrerHost(): string
    {
        $referrer = $this->metaValue('referrer');
        if ($referrer === '') {
            return '';
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host)) {
            return '';
        }

        return preg_replace('/^www\./i', '', strtolower($host)) ?? '';
    }

    private function isGoogleSource(string $source): bool
    {
        return $source === 'google' || str_contains($source, 'googleads') || str_contains($source, 'google_ads');
    }

    private function isBingSource(string $source): bool
    {
        return $source === 'msn' || str_contains($source, 'bing') || str_contains($source, 'microsoft');
    }

    private function isMetaSource(string $source): bool
    {
        return in_array($source, ['fb', 'ig'], true)
            || str_contains($source, 'facebook')
            || str_contains($source, 'instagram')
            || str_contains($source, 'meta');
    }

    private function isGoogleHost(string $host): bool
    {
        return $host !== '' && (
            $this->hostMatches($host, 'google.com')
            || str_starts_with($host, 'google.')
            || str_contains($host, '.google.')
        );
    }

    private function isSocialHost(string $host): bool
    {
        foreach (['facebook.com', 'instagram.com', 'linkedin.com', 't.co', 'youtube.com'] as $socialHost) {
            if ($this->hostMatches($host, $socialHost)) {
                return true;
            }
        }

        return false;
    }

    private function hostMatches(string $host, string $domain): bool
    {
        return $host === $domain || str_ends_with($host, '.'.$domain);
    }
}
