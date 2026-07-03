<?php

namespace App\Models\Webflow;

use App\Models\Webflow\Concerns\ResolvesWebflowReferences;
use Illuminate\Database\Eloquent\Model;

class DoorsWebflowItem extends Model
{
    use ResolvesWebflowReferences;

    public const FIELD_CUSTOM_HERO_IMAGE = 'custom-hero-image';

    protected $table = 'wf_doors';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'field_data' => 'array',
            'is_archived' => 'boolean',
            'is_draft' => 'boolean',
            'wf_custom_hero_image' => 'array',
        ];
    }

    public function customHeroImageUrl(): ?string
    {
        $value = data_get($this->field_data, self::FIELD_CUSTOM_HERO_IMAGE);

        if (is_array($value)) {
            $url = trim((string) ($value['url'] ?? ''));
            return $url !== '' ? $url : null;
        }

        if (is_string($value)) {
            $url = trim($value);
            return $url !== '' ? $url : null;
        }

        return null;
    }
}
