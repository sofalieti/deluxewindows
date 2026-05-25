<?php

namespace App\Models\Webflow;

use App\Models\Webflow\Concerns\ResolvesWebflowReferences;
use Illuminate\Database\Eloquent\Model;

class WindowReplacementWebflowItem extends Model
{
    use ResolvesWebflowReferences;

    protected $table = 'wf_window_replacement';

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
