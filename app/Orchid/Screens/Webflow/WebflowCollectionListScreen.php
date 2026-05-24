<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
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
        return null;
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

                TD::make('field_data.name', 'Name')
                    ->render(fn ($item) => data_get($item->field_data, 'name', '-')),

                TD::make('field_data.slug', 'Slug')
                    ->render(fn ($item) => data_get($item->field_data, 'slug', '-')),

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
}

