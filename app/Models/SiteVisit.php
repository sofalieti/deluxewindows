<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ClassifiesTrafficSource;
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
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SiteVisit $visit): void {
            $visit->traffic_source = $visit->trafficSourceKey();
        });
    }
}
