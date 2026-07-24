<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

class Lead extends Model
{
    use Filterable;

    protected $fillable = [
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
        'full_name' => Like::class,
        'email' => Like::class,
        'phone' => Like::class,
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
