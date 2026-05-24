<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class CountyHubPagesWebflowItem extends Model
{
    protected $table = 'wf_county_hub_pages';

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
