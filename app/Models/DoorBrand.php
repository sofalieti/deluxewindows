<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoorBrand extends Model
{
    protected $table = 'door_brands';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'doors_title',
        'faq',
    ];

    protected function casts(): array
    {
        return [
            'faq' => 'array',
        ];
    }

    /**
     * Normalized FAQ list: array of ['question' => string, 'answer' => string].
     */
    public function faqItems(): array
    {
        $items = is_array($this->faq) ? $this->faq : [];

        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }
                $question = trim((string) ($item['question'] ?? ''));
                $answer = trim((string) ($item['answer'] ?? ''));

                return $question !== '' && $answer !== ''
                    ? ['question' => $question, 'answer' => $answer]
                    : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
