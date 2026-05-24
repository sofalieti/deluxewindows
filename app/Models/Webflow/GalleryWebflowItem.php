<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class GalleryWebflowItem extends Model
{
    protected $table = 'wf_gallery';

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
