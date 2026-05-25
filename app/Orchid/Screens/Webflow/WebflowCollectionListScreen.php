<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class WebflowCollectionListScreen extends Screen
{
    protected string $collectionSlug = '';

    protected array $collectionMeta = [];

    public function query(string $collection): iterable
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        $this->collectionSlug = $meta['slug'];
        $this->collectionMeta = $meta;

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404, 'Collection table not found: '.$meta['table']);
        }

        $model = $meta['model'];

        return [
            'collection' => $meta,
            'items' => $model::query()
                ->orderByDesc('id')
                ->paginate(30),
        ];
    }

    public function name(): ?string
    {
        return ($this->collectionMeta['title'] ?? 'Webflow Collection').' Items';
    }

    public function description(): ?string
    {
        return 'Edit imported Webflow collection entries.';
    }

    public function permission(): ?iterable
    {
        return [];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('items', [
                TD::make('id')->sort(),

                TD::make('name', 'Name')
                    ->render(fn ($item) => $this->safeText(data_get($item->field_data, 'name', '-'))),

                TD::make('slug', 'Slug')
                    ->render(fn ($item) => $this->safeText(data_get($item->field_data, 'slug', '-'))),

                TD::make('webflow_item_id', 'Webflow ID'),

                TD::make('updated_at', 'Updated')->sort(),

                TD::make('Actions')
                    ->render(fn ($item) => Link::make('Edit')
                        ->icon('bs.pencil')
                        ->route('platform.webflow.collection.edit', [
                            'collection' => $this->collectionSlug,
                            'item' => $item->id,
                        ])),
            ]),
        ];
    }

    private function safeText(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '-';
        }

        $string = (string) $value;

        // Prevent malformed byte sequences from breaking Orchid table rendering.
        $clean = @mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $clean = is_string($clean) ? $clean : $string;

        return Str::limit($clean, 120);
    }
}

