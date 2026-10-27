<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class SiteEvent extends Model
{
    use MassPrunable;

    public const RETENTION_DAYS = 90;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    public const TYPES = [
        'page_view',
        'scroll_depth',
        'engaged_time',
        'click',
        'form_start',
        'form_field_focus',
        'form_submit',
        'form_abandon',
        'rage_click',
    ];

    protected $fillable = [
        'session_id',
        'visitor_id',
        'type',
        'path',
        'target',
        'value',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'value' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    public function label(): string
    {
        return match ($this->type) {
            'page_view' => 'Page view',
            'scroll_depth' => 'Scroll '.(int) $this->value.'%',
            'engaged_time' => 'Engaged '.(int) $this->value.'s',
            'click' => 'Click',
            'form_start' => 'Form started',
            'form_field_focus' => 'Field focus',
            'form_submit' => 'Form submitted',
            'form_abandon' => 'Form abandoned',
            'rage_click' => 'Rage click',
            default => (string) $this->type,
        };
    }
}
