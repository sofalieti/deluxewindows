<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\StoresUtcTimestamps;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class RingCentralCall extends Model
{
    use AsSource;
    use Filterable;
    use StoresUtcTimestamps;

    protected $table = 'ringcentral_calls';

    public const TRANSCRIPT_PENDING = 'pending';

    public const TRANSCRIPT_PROCESSING = 'processing';

    public const TRANSCRIPT_COMPLETED = 'completed';

    public const TRANSCRIPT_FAILED = 'failed';

    public const TRANSCRIPT_SKIPPED = 'skipped';

    protected $fillable = [
        'ringcentral_call_id',
        'session_id',
        'telephony_session_id',
        'direction',
        'action',
        'result',
        'started_at',
        'duration',
        'business_phone',
        'from_phone',
        'from_name',
        'to_phone',
        'to_name',
        'external_phone',
        'recording_id',
        'contact_id',
        'transcript_status',
        'transcript_queued_at',
        'transcript_processed_at',
        'transcript',
        'transcript_summary',
        'transcript_error',
        'transcript_meta',
        'raw',
        'synced_at',
    ];

    protected $allowedFilters = [
        'direction' => Where::class,
        'result' => Like::class,
        'external_phone' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'started_at',
        'duration',
        'direction',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'raw' => 'array',
            'transcript_summary' => 'array',
            'transcript_meta' => 'array',
            'transcript_queued_at' => 'datetime',
            'transcript_processed_at' => 'datetime',
        ];
    }

    /**
     * Stored as UTC wall-clock; exposed as UTC Carbon.
     */
    protected function startedAt(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?CarbonImmutable => $this->asUtcImmutable($value),
            set: fn (mixed $value): ?string => $this->utcDatabaseString($value),
        );
    }

    protected function syncedAt(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?CarbonImmutable => $this->asUtcImmutable($value),
            set: fn (mixed $value): ?string => $this->utcDatabaseString($value),
        );
    }

    public function startedAtPacific(): ?CarbonImmutable
    {
        return $this->started_at?->setTimezone('America/Los_Angeles');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNotExists(function ($subquery): void {
            $subquery->selectRaw('1')
                ->from('ringcentral_excluded_numbers')
                ->whereNull('restored_at')
                ->whereColumn('ringcentral_excluded_numbers.phone', 'ringcentral_calls.external_phone');
        });
    }

    /**
     * @param  list<string>  $monitoredPhones  Normalized E.164 business numbers
     */
    public function scopeOnMonitoredLines(Builder $query, array $monitoredPhones): Builder
    {
        $phones = array_values(array_filter($monitoredPhones));
        if ($phones === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $inner) use ($phones): void {
            $inner->whereIn('business_phone', $phones);
            foreach ($phones as $phone) {
                $last10 = substr(preg_replace('/\D+/', '', $phone) ?? '', -10);
                if (strlen($last10) === 10) {
                    $inner->orWhere('business_phone', 'like', '%'.$last10);
                }
            }
        });
    }

    /**
     * @param  list<string>  $monitoredPhones  Normalized E.164 business numbers
     */
    public function scopeOnOtherLines(Builder $query, array $monitoredPhones): Builder
    {
        $phones = array_values(array_filter($monitoredPhones));
        if ($phones === []) {
            return $query;
        }

        return $query->whereNot(function (Builder $inner) use ($phones): void {
            $inner->onMonitoredLines($phones);
        });
    }

    public function durationLabel(): string
    {
        $seconds = max(0, (int) $this->duration);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function resolvedRecordingId(): ?string
    {
        $id = trim((string) ($this->recording_id ?? ''));
        if ($id !== '') {
            return $id;
        }

        $raw = is_array($this->raw) ? $this->raw : [];
        $fromRaw = trim((string) data_get($raw, 'recording.id', ''));
        if ($fromRaw !== '') {
            return $fromRaw;
        }

        foreach ((array) ($raw['legs'] ?? []) as $leg) {
            if (! is_array($leg)) {
                continue;
            }
            $legId = trim((string) data_get($leg, 'recording.id', ''));
            if ($legId !== '') {
                return $legId;
            }
        }

        return null;
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

        return route('platform.ringcentral-calls.recording', $this);
    }

    public function hasCompletedTranscript(): bool
    {
        return $this->transcript_status === self::TRANSCRIPT_COMPLETED
            && trim((string) ($this->transcript ?? '')) !== '';
    }

    public function transcriptOverview(): string
    {
        $summary = is_array($this->transcript_summary) ? $this->transcript_summary : [];
        $overview = trim((string) ($summary['overview'] ?? ''));

        return $overview;
    }
}
