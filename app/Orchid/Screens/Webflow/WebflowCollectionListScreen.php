<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Orchid\Layouts\Webflow\WebflowCollectionSearchLayout;
use App\Services\PromotionSettingsService;
use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowItemOrder;
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

        $query = DB::table((string) $meta['table']);

        $search = Str::of((string) request()->query('search', ''))->trim()->toString();
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where('field_data->name', 'like', $like);
        }

        $rows = $query->get()->map(function ($row) {
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

        $items = WebflowItemOrder::sort($rows)->values()->map(function (Repository $item, int $index) {
            // Show 1..N position in the sorted list (matches visual order).
            $item['order'] = $index + 1;
            $item['relation_summary'] = $this->relationSummary($item);

            return $item;
        });

        return [
            'collection' => $meta,
            'items' => $items,
            'collectionSlug' => $this->collectionSlug,
            'reorderEnabled' => $search === '',
            'reorderUrl' => route('platform.webflow.collection', [
                'collection' => $this->collectionSlug,
                'method' => 'reorder',
            ]),
        ];
    }

    public function name(): ?string
    {
        return ($this->collectionMeta['title'] ?? 'Webflow Collection').' Items';
    }

    public function description(): ?string
    {
        return 'Drag rows to change order. Order saves automatically and is used on the website.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.webflow.manage',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Search')
                ->icon('bs.search')
                ->method('applySearch'),

            Link::make('Clear search')
                ->icon('bs.x-circle')
                ->route('platform.webflow.collection', ['collection' => $this->collectionSlug])
                ->canSee(request()->filled('search')),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.webflow-collection-reorder-assets'),
            WebflowCollectionSearchLayout::class,
            Layout::view('admin.webflow-collection-list'),
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

    public function reorder(string $collection, Request $request)
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404);
        }

        $itemIds = $request->input('item_ids', $request->input('items', []));
        if (! is_array($itemIds)) {
            $itemIds = [];
        }

        // Support Orchid-style [{id, sortOrder}, ...] payloads as well.
        if ($itemIds !== [] && is_array($itemIds[0] ?? null)) {
            usort($itemIds, static fn ($a, $b) => ((int) ($a['sortOrder'] ?? 0)) <=> ((int) ($b['sortOrder'] ?? 0)));
            $itemIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $itemIds);
        }

        if ($itemIds === []) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Nothing to save.'], 422);
            }

            Toast::warning('Nothing to save.');

            return redirect()->route('platform.webflow.collection', ['collection' => $collection]);
        }

        $updated = WebflowItemOrder::saveOrder((string) $meta['table'], $itemIds);

        if ($collection === 'coupons') {
            app(PromotionSettingsService::class)->forgetCache();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'updated' => $updated,
                'message' => $updated > 0 ? 'Order saved.' : 'Nothing to save.',
            ]);
        }

        Toast::info($updated > 0 ? 'Order saved.' : 'Nothing to save.');

        return redirect()->route('platform.webflow.collection', ['collection' => $collection]);
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
