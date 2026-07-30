<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class PhoneClick extends Model
{
    use AsSource;
    use Filterable;

    protected $fillable = [
        'phone',
        'page_url',
        'landing_page',
        'referrer',
        'source_label',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'matchtype',
        'device',
        'creative',
        'gclid',
        'fbclid',
        'msclkid',
        'geo_location',
        'ip_address',
        'user_agent',
        'meta',
        'google_sheet_sent_at',
        'google_sheet_sent_by',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'utm_source' => Like::class,
        'utm_campaign' => Like::class,
        'phone' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'created_at',
        'utm_source',
        'utm_campaign',
        'google_sheet_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'google_sheet_sent_at' => 'datetime',
        ];
    }

    public function googleSheetSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'google_sheet_sent_by');
    }

    public function wasSentToGoogleSheet(): bool
    {
        return $this->google_sheet_sent_at !== null;
    }

    public function metaValue(string $key, string $default = ''): string
    {
        $value = data_get($this->meta, $key);
        if ($value === null || is_array($value)) {
            return $default;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $default;
    }

    /**
     * @return list<string>
     */
    public function utmSummaryParts(): array
    {
        $map = [
            'utm_source' => 'src',
            'utm_medium' => 'med',
            'utm_campaign' => 'cmp',
            'utm_content' => 'content',
            'utm_term' => 'term',
            'creative' => 'creative',
            'gclid' => 'gclid',
            'fbclid' => 'fbclid',
            'msclkid' => 'msclkid',
        ];

        $parts = [];
        foreach ($map as $field => $label) {
            $value = trim((string) ($this->{$field} ?? ''));
            if ($value !== '') {
                $parts[] = $label.': '.$value;
            }
        }

        return $parts;
    }
}
