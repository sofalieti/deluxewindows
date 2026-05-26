<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowReferenceRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WebflowCollectionListScreen extends Screen
{
    protected string $collectionSlug = '';

    protected array $collectionMeta = [];

    protected array $referenceFields = [];

    public function query(string $collection): iterable
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        $this->collectionSlug = $meta['slug'];
        $this->collectionMeta = $meta;
        $this->referenceFields = WebflowReferenceRegistry::forModel($meta['model']);

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404, 'Collection table not found: '.$meta['table']);
        }

        $items = DB::table((string) $meta['table'])
            ->orderByDesc('id')
            ->paginate(30)
            ->through(function ($row) {
                $item = (array) $row;
                $fieldData = $item['field_data'] ?? null;

                if (is_string($fieldData) && $fieldData !== '') {
                    $decoded = json_decode($fieldData, true);
                    $item['field_data'] = is_array($decoded) ? $decoded : [];
                } elseif (! is_array($fieldData)) {
                    $item['field_data'] = [];
                }

                return new Repository($item);
            });

        return [
            'collection' => $meta,
            'items' => $items,
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
        return [
            Link::make('Export JSON')
                ->icon('bs.download')
                ->route('platform.webflow.export', ['collection' => $this->collectionSlug]),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('items', [
                TD::make('id')
                    ->render(fn ($item) => (string) $this->value($item, 'id', '')),

                TD::make('name', 'Name')
                    ->render(fn ($item) => $this->safeText($this->value($item, 'field_data.name', '-'))),

                TD::make('slug', 'Slug')
                    ->render(fn ($item) => $this->safeText($this->value($item, 'field_data.slug', '-'))),

                TD::make('webflow_item_id', 'Webflow ID')
                    ->render(fn ($item) => $this->safeText($this->value($item, 'webflow_item_id', '-'))),

                TD::make('updated_at', 'Updated')
                    ->render(fn ($item) => $this->safeText($this->value($item, 'updated_at', '-'))),

                TD::make('relations', 'Relations')
                    ->render(fn ($item) => $this->safeText($this->relationSummary($item))),

                TD::make('Actions')
                    ->render(fn ($item) => Link::make('Edit')
                        ->icon('bs.pencil')
                        ->route('platform.webflow.collection.edit', [
                            'collection' => $this->collectionSlug,
                            'item' => $this->value($item, 'id'),
                        ])
                        ->render()
                        .' '.
                        Button::make('Delete')
                            ->icon('bs.trash')
                            ->confirm('Delete this item? This action cannot be undone.')
                            ->method('delete')
                            ->parameters(['item_id' => $this->value($item, 'id')])
                            ->render()
                    ),
            ]),
        ];
    }

    private function value(mixed $item, string $key, mixed $default = null): mixed
    {
        if (is_object($item) && method_exists($item, 'getContent')) {
            $value = $item->getContent($key);
            return $value === null ? $default : $value;
        }

        return data_get($item, $key, $default);
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

    public function delete(string $collection, Request $request): void
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404);
        }

        $itemId = (int) $request->input('item_id', 0);
        if ($itemId > 0) {
            DB::table((string) $meta['table'])->where('id', $itemId)->delete();
            Toast::info('Item deleted.');
        }
    }

    private function relationSummary(mixed $item): string
    {
        if ($this->referenceFields === []) {
            return '-';
        }

        $parts = [];
        foreach ($this->referenceFields as $fieldSlug => $meta) {
            $value = $this->value($item, 'field_data.'.$fieldSlug);
            $relationType = (string) ($meta['type'] ?? '');

            if ($relationType === 'reference') {
                if (is_string($value) && $value !== '') {
                    $parts[] = $fieldSlug.':1';
                }
                continue;
            }

            if ($relationType === 'multi_reference' && is_array($value)) {
                $count = count(array_filter($value, fn ($id) => is_string($id) && $id !== ''));
                if ($count > 0) {
                    $parts[] = $fieldSlug.':'.$count;
                }
            }
        }

        if ($parts === []) {
            return '-';
        }

        return implode(', ', array_slice($parts, 0, 4)).(count($parts) > 4 ? ', ...' : '');
    }
}

