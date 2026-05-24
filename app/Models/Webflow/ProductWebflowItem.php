<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class ProductWebflowItem extends Model
{
    protected $table = 'wf_product';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'field_data' => 'array',
            'is_archived' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }
}
