<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ClassifiesTrafficSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class SiteVisit extends Model
{
    use AsSource;
    use ClassifiesTrafficSource;
    use Filterable;

    protected $fillable = [
        'page_url',
        'landing_page',
        'first_landing_page',
        'referrer',
        'first_referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referral_partner_id',
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
        'gbraid',
        'wbraid',
        'fbclid',
        'msclkid',
        'first_gclid',
        'first_gbraid',
        'first_wbraid',
        'first_fbclid',
        'first_msclkid',
        'geo_location',
        'ip_address',
        'user_agent',
        'screen_width',
        'screen_height',
        'visitor_id',
        'session_id',
        'pageviews',
        'engaged_seconds',
        'max_scroll',
        'events_count',
        'rage_clicks',
        'last_seen_at',
        'is_bot',
        'traffic_source',
        'meta',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'utm_source' => Like::class,
        'utm_campaign' => Like::class,
        'utm_city' => Like::class,
        'traffic_source' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'created_at',
        'utm_source',
        'utm_campaign',
        'utm_city',
        'traffic_source',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'screen_width' => 'integer',
            'screen_height' => 'integer',
            'pageviews' => 'integer',
            'engaged_seconds' => 'integer',
            'max_scroll' => 'integer',
            'events_count' => 'integer',
            'rage_clicks' => 'integer',
            'is_bot' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function engagedLabel(): string
    {
        $seconds = max(0, (int) $this->engaged_seconds);
        if ($seconds < 60) {
            return $seconds.'s';
        }

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }

    /**
     * @param  Builder<SiteVisit>  $query
     * @return Builder<SiteVisit>
     */
    public function scopeConverted(Builder $query): Builder
    {
        return $query->whereNotNull('visitor_id')->where(function (Builder $inner): void {
            $inner->whereIn('visitor_id', function ($sub): void {
                $sub->select('visitor_id')->from('leads')->whereNotNull('visitor_id');
            })->orWhereIn('visitor_id', function ($sub): void {
                $sub->select('visitor_id')->from('phone_clicks')->whereNotNull('visitor_id');
            });
        });
    }

    /**
     * @param  Builder<SiteVisit>  $query
     * @return Builder<SiteVisit>
     */
    public function scopeNotConverted(Builder $query): Builder
    {
        return $query->where(function (Builder $outer): void {
            $outer->whereNull('visitor_id')->orWhere(function (Builder $inner): void {
                $inner->whereNotNull('visitor_id')
                    ->whereNotIn('visitor_id', function ($sub): void {
                        $sub->select('visitor_id')->from('leads')->whereNotNull('visitor_id');
                    })
                    ->whereNotIn('visitor_id', function ($sub): void {
                        $sub->select('visitor_id')->from('phone_clicks')->whereNotNull('visitor_id');
                    });
            });
        });
    }

    protected static function booted(): void
    {
        static::saving(function (SiteVisit $visit): void {
            $visit->traffic_source = $visit->trafficSourceKey();
        });
    }

    public function referralPartner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class, 'referral_partner_id');
    }
}
