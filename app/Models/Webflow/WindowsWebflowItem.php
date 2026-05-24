<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class WindowsWebflowItem extends Model
{
    protected $table = 'wf_windows';

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
