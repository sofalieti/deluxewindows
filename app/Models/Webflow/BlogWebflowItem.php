<?php

namespace App\Models\Webflow;

use App\Models\Webflow\Concerns\ResolvesWebflowReferences;
use Illuminate\Database\Eloquent\Model;

class BlogWebflowItem extends Model
{
    use ResolvesWebflowReferences;

    protected $table = 'wf_blog';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'field_data' => 'array',
            'is_archived' => 'boolean',
            'is_draft' => 'boolean',
            'webflow_created_on' => 'datetime',
            'webflow_updated_on' => 'datetime',
            'webflow_published_on' => 'datetime',
        ];
    }
}
