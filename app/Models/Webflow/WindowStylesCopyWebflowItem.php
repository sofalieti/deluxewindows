<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class WindowStylesCopyWebflowItem extends Model
{
    protected $table = 'wf_window_styles_copy';

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
