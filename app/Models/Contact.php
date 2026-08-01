<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\RingCentralCallLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class)->latest();
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
