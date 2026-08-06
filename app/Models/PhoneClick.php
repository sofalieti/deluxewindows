<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ClassifiesTrafficSource;
use App\Services\TrafficSourceVisibility;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class PhoneClick extends Model
{
    use AsSource;
    use ClassifiesTrafficSource;
    use Filterable;

    public const RINGCENTRAL_NOT_CHECKED = 'not_checked';

    public const RINGCENTRAL_PENDING = 'pending';

    public const RINGCENTRAL_FOUND = 'found';

    public const RINGCENTRAL_NO_CALL = 'no_call';

    public const RINGCENTRAL_ERROR = 'error';

    protected $fillable = [
        'phone',
        'page_url',
        'landing_page',
        'first_landing_page',
        'referrer',
        'first_referrer',
        'source_label',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'utm_city',
        'utm_redirect',
        'first_utm_source',
        'first_utm_medium',
        'first_utm_campaign',
        'first_utm_content',
        'first_utm_term',
        'first_utm_city',
        'matchtype',
        'device',
        'creative',
        'gclid',
        'fbclid',
        'msclkid',
        'first_gclid',
        'first_fbclid',
        'first_msclkid',
        'geo_location',
        'ip_address',
        'user_agent',
        'meta',
        'is_spam',
        'spam_marked_at',
        'ringcentral_status',
        'ringcentral_checked_at',
        'ringcentral_attempts',
        'ringcentral_call_id',
        'ringcentral_recording_id',
        'ringcentral_session_id',
        'ringcentral_result',
        'ringcentral_direction',
        'ringcentral_call_started_at',
        'ringcentral_duration',
        'ringcentral_from_phone',
        'ringcentral_to_phone',
        'ringcentral_error',
        'google_sheet_sent_at',
        'google_sheet_sent_by',
        'google_ads_conversion_sent_at',
        'google_ads_conversion_error',
        'bing_ads_conversion_sent_at',
        'bing_ads_conversion_error',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'utm_source' => Like::class,
        'utm_campaign' => Like::class,
        'phone' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'created_at',
        'utm_source',
        'utm_campaign',
        'ringcentral_status',
        'google_sheet_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_spam' => 'boolean',
            'spam_marked_at' => 'datetime',
            'ringcentral_checked_at' => 'datetime',
            'ringcentral_call_started_at' => 'datetime',
            'ringcentral_attempts' => 'integer',
            'ringcentral_duration' => 'integer',
            'google_sheet_sent_at' => 'datetime',
            'google_ads_conversion_sent_at' => 'datetime',
            'bing_ads_conversion_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PhoneClick $click): void {
            $click->traffic_source = $click->trafficSourceKey();
        });
    }

    /**
     * @param  Builder<PhoneClick>  $query
     * @return Builder<PhoneClick>
     */
    public function scopeVisibleTo(Builder $query, ?Authenticatable $user): Builder
    {
        return app(TrafficSourceVisibility::class)
            ->constrain($query, $user, TrafficSourceVisibility::SECTION_PHONE_CLICKS);
    }

    public function isSpam(): bool
    {
        return (bool) ($this->attributes['is_spam'] ?? false);
    }

    public function scopeNotSpam(Builder $query): Builder
    {
        if (! $this->spamColumnReady()) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.is_spam', false);
    }

    public function scopeOnlySpam(Builder $query): Builder
    {
        if (! $this->spamColumnReady()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where($query->getModel()->getTable().'.is_spam', true);
    }

    public function markAsSpam(): void
    {
        if (! $this->spamColumnReady()) {
            throw new \RuntimeException('Run php artisan migrate — column phone_clicks.is_spam is missing.');
        }

        $this->forceFill([
            'is_spam' => true,
            'spam_marked_at' => now(),
        ])->save();
    }

    public function restoreFromSpam(): void
    {
        if (! $this->spamColumnReady()) {
            throw new \RuntimeException('Run php artisan migrate — column phone_clicks.is_spam is missing.');
        }

        $this->forceFill([
            'is_spam' => false,
            'spam_marked_at' => null,
        ])->save();
    }

    private function spamColumnReady(): bool
    {
        static $ready = null;

        if ($ready === null) {
            $ready = Schema::hasColumn($this->getTable(), 'is_spam');
        }

        return $ready;
    }

    public function googleSheetSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'google_sheet_sent_by');
    }

    public function wasSentToGoogleSheet(): bool
    {
        return $this->google_sheet_sent_at !== null;
    }

    public function hasFinalRingCentralStatus(): bool
    {
        return in_array($this->ringcentral_status, [
            self::RINGCENTRAL_FOUND,
            self::RINGCENTRAL_NO_CALL,
        ], true);
    }

    public function resolvedRecordingId(): ?string
    {
        $id = trim((string) ($this->ringcentral_recording_id ?? ''));
        if ($id !== '') {
            return $id;
        }

        $callId = trim((string) ($this->ringcentral_call_id ?? ''));
        if ($callId === '' || ! Schema::hasTable('ringcentral_calls')) {
            return null;
        }

        $call = RingCentralCall::query()
            ->where('ringcentral_call_id', $callId)
            ->first();

        return $call?->resolvedRecordingId();
    }

    public function hasRecording(): bool
    {
        return $this->resolvedRecordingId() !== null;
    }

    public function recordingUrl(): ?string
    {
        if (! $this->hasRecording() || ! $this->exists) {
            return null;
        }

        return route('platform.phone-clicks.recording', $this);
    }

    public function ringCentralCall(): ?RingCentralCall
    {
        $callId = trim((string) ($this->ringcentral_call_id ?? ''));
        if ($callId === '' || ! Schema::hasTable('ringcentral_calls')) {
            return null;
        }

        return RingCentralCall::query()
            ->where('ringcentral_call_id', $callId)
            ->first();
    }

    /**
     * External (client) phone from the matched RingCentral call.
     */
    public function ringCentralClientPhone(): ?string
    {
        if ($this->ringcentral_status !== self::RINGCENTRAL_FOUND) {
            return null;
        }

        $direction = ucfirst(strtolower(trim((string) ($this->ringcentral_direction ?? ''))));
        $from = trim((string) ($this->ringcentral_from_phone ?? ''));
        $to = trim((string) ($this->ringcentral_to_phone ?? ''));

        $client = $direction === 'Outbound' ? $to : $from;
        if ($client === '') {
            $client = $from !== '' ? $from : $to;
        }

        return $client !== '' ? $client : null;
    }

    /**
     * Last-touch click id wins; first touch is the fallback for multi-visit journeys.
     */
    public function resolvedGclid(): ?string
    {
        return $this->firstFilled(['gclid', 'first_gclid']);
    }

    public function resolvedMsclkid(): ?string
    {
        return $this->firstFilled(['msclkid', 'first_msclkid']);
    }

    /**
     * Timestamp reported to the ad platforms as the conversion moment.
     */
    public function offlineConversionTime(): \Illuminate\Support\Carbon
    {
        return $this->ringcentral_call_started_at
            ? $this->ringcentral_call_started_at->copy()
            : ($this->created_at?->copy() ?? now());
    }

    public function offlineConversionOrderId(): string
    {
        return 'phone-click-'.$this->id;
    }

    /**
     * @param  list<string>  $fields
     */
    private function firstFilled(array $fields): ?string
    {
        foreach ($fields as $field) {
            $value = trim((string) ($this->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function ringCentralDurationLabel(): string
    {
        $seconds = max(0, (int) $this->ringcentral_duration);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
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
     * @return list<string>
     */
    public function utmSummaryParts(): array
    {
        $map = [
            'utm_source' => 'src',
            'utm_medium' => 'med',
            'utm_campaign' => 'cmp',
            'utm_content' => 'content',
            'utm_term' => 'term',
            'creative' => 'creative',
            'gclid' => 'gclid',
            'fbclid' => 'fbclid',
            'msclkid' => 'msclkid',
        ];

        $parts = [];
        foreach ($map as $field => $label) {
            $value = trim((string) ($this->{$field} ?? ''));
            if ($value !== '') {
                $parts[] = $label.': '.$value;
            }
        }

        return $parts;
    }
}
