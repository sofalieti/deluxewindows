<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\RingCentralCallLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Screen\AsSource;

class Contact extends Model
{
    use AsSource;
    use Filterable;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'city',
        'address',
        'additional_information',
        'source_lead_id',
        'created_by_user_id',
    ];

    protected $allowedFilters = [
        'full_name' => Like::class,
        'email' => Like::class,
        'phone' => Like::class,
        'city' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'full_name',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (Contact $contact): void {
            $contact->normalized_email = self::normalizeEmail($contact->email);
            $contact->normalized_phone = self::normalizePhone($contact->phone);
        });

        static::saved(function (Contact $contact): void {
            $contact->syncPrimaryEmailRow();
        });
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class)->latest();
    }

    public function emailAddresses(): HasMany
    {
        return $this->hasMany(ContactEmail::class)
            ->orderByDesc('is_primary')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function allNormalizedEmails(): array
    {
        $set = [];

        $primary = self::normalizeEmail($this->email);
        if ($primary !== null) {
            $set[$primary] = true;
        }

        if ($this->relationLoaded('emailAddresses')) {
            $rows = $this->emailAddresses;
        } elseif ($this->exists && Schema::hasTable('contact_emails')) {
            $rows = $this->emailAddresses()->get(['normalized_email']);
        } else {
            $rows = collect();
        }

        foreach ($rows as $row) {
            $normalized = self::normalizeEmail($row->normalized_email ?? $row->email ?? null);
            if ($normalized !== null) {
                $set[$normalized] = true;
            }
        }

        return array_keys($set);
    }

    /**
     * @return list<string>
     */
    public function additionalEmailsList(): array
    {
        if (! $this->exists || ! Schema::hasTable('contact_emails')) {
            return [];
        }

        $primary = self::normalizeEmail($this->email);

        return $this->emailAddresses()
            ->where('is_primary', false)
            ->orderBy('id')
            ->get(['email', 'normalized_email'])
            ->map(function (ContactEmail $row) use ($primary): ?string {
                $normalized = self::normalizeEmail($row->normalized_email ?: $row->email);
                if ($normalized === null || $normalized === $primary) {
                    return null;
                }

                return trim((string) $row->email);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Replace non-primary emails. Primary stays on contacts.email.
     *
     * @param  list<string>|array<int, string|null>  $emails
     */
    public function syncAdditionalEmails(array $emails): void
    {
        if (! $this->exists || ! Schema::hasTable('contact_emails')) {
            return;
        }

        $primary = self::normalizeEmail($this->email);
        $wanted = [];
        foreach ($emails as $email) {
            $normalized = self::normalizeEmail($email);
            if ($normalized === null || $normalized === $primary || isset($wanted[$normalized])) {
                continue;
            }
            $wanted[$normalized] = trim((string) $email);
        }

        $this->emailAddresses()
            ->where('is_primary', false)
            ->whereNotIn('normalized_email', array_keys($wanted) ?: ['__none__'])
            ->delete();

        foreach ($wanted as $normalized => $email) {
            $this->emailAddresses()->updateOrCreate(
                [
                    'contact_id' => $this->id,
                    'normalized_email' => $normalized,
                ],
                [
                    'email' => $email,
                    'is_primary' => false,
                ],
            );
        }
    }

    public function addEmailAddress(?string $email, bool $asPrimary = false): bool
    {
        if (! $this->exists || ! Schema::hasTable('contact_emails')) {
            return false;
        }

        $normalized = self::normalizeEmail($email);
        if ($normalized === null) {
            return false;
        }

        if (in_array($normalized, $this->allNormalizedEmails(), true)) {
            return false;
        }

        if ($asPrimary || self::normalizeEmail($this->email) === null) {
            $this->email = trim((string) $email);
            $this->save();

            return true;
        }

        $this->emailAddresses()->create([
            'email' => trim((string) $email),
            'normalized_email' => $normalized,
            'is_primary' => false,
        ]);

        return true;
    }

    public function syncPrimaryEmailRow(): void
    {
        if (! $this->exists || ! Schema::hasTable('contact_emails')) {
            return;
        }

        $normalized = self::normalizeEmail($this->email);
        $this->emailAddresses()->update(['is_primary' => false]);

        if ($normalized === null) {
            return;
        }

        $this->emailAddresses()->updateOrCreate(
            [
                'contact_id' => $this->id,
                'normalized_email' => $normalized,
            ],
            [
                'email' => trim((string) $this->email),
                'is_primary' => true,
            ],
        );
    }

    public function phoneClicks(): HasMany
    {
        return $this->hasMany(PhoneClick::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class)->latest();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(CrmNote::class, 'subject')->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContactComment::class)->latest();
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ContactChange::class)->latest('created_at')->latest('id');
    }

    public function leadComments(): HasManyThrough
    {
        return $this->hasManyThrough(LeadComment::class, Lead::class);
    }

    public function ringCentralCalls(): HasMany
    {
        return $this->hasMany(RingCentralCall::class)->latest('started_at');
    }

    /**
     * RingCentral calls for this contact's phone (bound by contact_id, with phone fallback).
     *
     * @return Collection<int, RingCentralCall>
     */
    public function ringCentralCallsForPhone(?RingCentralCallLogService $ringCentral = null): Collection
    {
        if (! Schema::hasTable('ringcentral_calls')) {
            return collect();
        }

        $ringCentral ??= app(RingCentralCallLogService::class);
        $phone = trim((string) ($this->phone ?? ''));
        $normalized = self::normalizePhone($phone);
        $last10 = $normalized !== null ? substr($normalized, -10) : '';

        $query = RingCentralCall::query()->visible()->orderByDesc('started_at')->limit(200);

        if (Schema::hasColumn('ringcentral_calls', 'contact_id') && $this->exists) {
            $query->where(function ($inner) use ($last10): void {
                $inner->where('contact_id', $this->id);
                if (strlen($last10) === 10) {
                    $inner->orWhere(function ($phoneQuery) use ($last10): void {
                        $phoneQuery->whereNull('contact_id')
                            ->where(function ($match) use ($last10): void {
                                $match->where('external_phone', 'like', '%'.$last10)
                                    ->orWhere('from_phone', 'like', '%'.$last10)
                                    ->orWhere('to_phone', 'like', '%'.$last10);
                            });
                    });
                }
            });
        } elseif (strlen($last10) === 10) {
            $query->where(function ($match) use ($last10): void {
                $match->where('external_phone', 'like', '%'.$last10)
                    ->orWhere('from_phone', 'like', '%'.$last10)
                    ->orWhere('to_phone', 'like', '%'.$last10);
            });
        } else {
            return collect();
        }

        return $query->get()
            ->filter(function (RingCentralCall $call) use ($ringCentral, $phone): bool {
                if ((int) $call->contact_id === (int) $this->id) {
                    return true;
                }
                if ($phone === '') {
                    return false;
                }
                foreach ([$call->external_phone, $call->from_phone, $call->to_phone] as $candidate) {
                    if ($candidate !== null && $candidate !== '' && $ringCentral->phonesMatch($phone, (string) $candidate)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * Contact notes plus lead comments, newest first.
     *
     * @return \Illuminate\Support\Collection<int, object{
     *     type: string,
     *     body: string,
     *     created_at: \Illuminate\Support\Carbon|null,
     *     user: ?User,
     *     lead_id: ?int,
     *     lead: ?Lead
     * }>
     */
    public function timelineComments()
    {
        $contactComments = $this->comments()
            ->with('user')
            ->get()
            ->map(fn (ContactComment $comment) => (object) [
                'type' => 'contact',
                'body' => $comment->body,
                'created_at' => $comment->created_at,
                'user' => $comment->user,
                'lead_id' => null,
                'lead' => null,
            ]);

        $leadComments = $this->leadComments()
            ->with(['lead', 'user'])
            ->get()
            ->map(fn (LeadComment $comment) => (object) [
                'type' => 'lead',
                'body' => $comment->body,
                'created_at' => $comment->created_at,
                'user' => $comment->user,
                'lead_id' => $comment->lead_id,
                'lead' => $comment->lead,
            ]);

        return $contactComments
            ->concat($leadComments)
            ->sortByDesc(fn (object $comment) => optional($comment->created_at)->timestamp ?? 0)
            ->values();
    }

    public function sourceLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'source_lead_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return list<array{key: string, label: string, color: string, count: int}>
     */
    public function trafficSummary(): array
    {
        $leads = $this->relationLoaded('leads')
            ? $this->leads
            : $this->leads()->get();

        return $leads
            ->groupBy(fn (Lead $lead): string => $lead->trafficSourceKey())
            ->map(function ($sourceLeads, string $key): array {
                /** @var Lead $lead */
                $lead = $sourceLeads->first();

                return [
                    'key' => $key,
                    'label' => $lead->trafficSourceLabel(),
                    'color' => $lead->trafficSourceColor(),
                    'count' => $sourceLeads->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    public static function normalizeEmail(mixed $email): ?string
    {
        $value = strtolower(trim((string) $email));

        return $value !== '' ? $value : null;
    }

    public static function normalizePhone(mixed $phone): ?string
    {
        $value = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return $value !== '' ? $value : null;
    }
}
