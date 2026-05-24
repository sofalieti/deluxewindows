<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class TestWebflowItem extends Model
{
    protected $table = 'wf_test';

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
