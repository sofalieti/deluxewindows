<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionControl extends Model
{
    protected $fillable = [
        'scope',
        'global_promotion_name',
        'global_discount_percent',
        'global_end_date',
        'phone_display',
        'phone_tel',
        'window_type_prices',
        'series_prices',
        'brand_prices',
        'door_prices',
    ];

    protected function casts(): array
    {
        return [
            'global_end_date' => 'date',
            'window_type_prices' => 'array',
            'series_prices' => 'array',
            'brand_prices' => 'array',
            'door_prices' => 'array',
        ];
    }
}

