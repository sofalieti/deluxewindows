<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Orchid\Layouts\Webflow\WebflowCollectionSearchLayout;
use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowReferenceRegistry;
use Illuminate\Http\RedirectResponse;
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

        $query = DB::table((string) $meta['table'])->orderByDesc('id');

        $search = Str::of((string) request()->query('search', ''))->trim()->toString();
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where('field_data->name', 'like', $like);
        }

        $items = $query
            ->paginate(30)
            ->withQueryString()
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
        $actions = [
            Button::make('Search')
                ->icon('bs.search')
                ->method('applySearch'),

            Link::make('Clear search')
                ->icon('bs.x-circle')
                ->route('platform.webflow.collection', ['collection' => $this->collectionSlug])
                ->canSee(request()->filled('search')),

            Link::make('Export JSON')
                ->icon('bs.download')
                ->route('platform.webflow.export', ['collection' => $this->collectionSlug]),
        ];

        return $actions;
    }

    public function layout(): iterable
    {
        return [
            WebflowCollectionSearchLayout::class,

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

                TD::make('status', 'Status')
                    ->render(function ($item) {
                        $isDraft = (bool) $this->value($item, 'is_draft', false);
                        return $isDraft
                            ? '<span class="badge bg-secondary">Disabled</span>'
                            : '<span class="badge bg-success">Enabled</span>';
                    }),

                TD::make('Actions')
                    ->render(function ($item) {
                        $id      = $this->value($item, 'id');
                        $isDraft = (bool) $this->value($item, 'is_draft', false);

                        $edit = Link::make('Edit')
                            ->icon('bs.pencil')
                            ->route('platform.webflow.collection.edit', [
                                'collection' => $this->collectionSlug,
                                'item'       => $id,
                            ])
                            ->render();

                        $toggle = Button::make($isDraft ? 'Enable' : 'Disable')
                            ->icon($isDraft ? 'bs.eye' : 'bs.eye-slash')
                            ->method('toggleDraft')
                            ->parameters(['item_id' => $id])
                            ->render();

                        $delete = Button::make('Delete')
                            ->icon('bs.trash')
                            ->confirm('Delete this item? This action cannot be undone.')
                            ->method('delete')
                            ->parameters(['item_id' => $id])
                            ->render();

                        return $edit.' '.$toggle.' '.$delete;
                    }),
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

    public function applySearch(string $collection, Request $request): RedirectResponse
    {
        $search = Str::of((string) $request->input('search', ''))->trim()->toString();

        return redirect()->route('platform.webflow.collection', array_filter([
            'collection' => $collection,
            'search' => $search !== '' ? $search : null,
        ]));
    }

    public function toggleDraft(string $collection, Request $request): void
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404);
        }

        $itemId = (int) $request->input('item_id', 0);
        if ($itemId > 0) {
            $row = DB::table((string) $meta['table'])->where('id', $itemId)->first();
            if ($row !== null) {
                $current = (bool) ($row->is_draft ?? false);
                DB::table((string) $meta['table'])
                    ->where('id', $itemId)
                    ->update(['is_draft' => ! $current]);
                Toast::info($current ? 'Item enabled.' : 'Item disabled.');
            }
        }
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

