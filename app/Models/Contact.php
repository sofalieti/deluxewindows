<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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

    public function leadComments(): HasManyThrough
    {
        return $this->hasManyThrough(LeadComment::class, Lead::class);
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
