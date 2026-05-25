<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WebflowCollectionEditScreen extends Screen
{
    protected string $collectionSlug = '';

    protected array $collectionMeta = [];

    public function query(string $collection, int $item): iterable
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        $this->collectionSlug = $meta['slug'];
        $this->collectionMeta = $meta;

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404, 'Collection table not found: '.$meta['table']);
        }

        $model = $meta['model'];
        $entity = $model::query()->findOrFail($item);
        $fieldData = is_array($entity->field_data) ? $entity->field_data : [];

        return [
            'collection' => $meta,
            'entity' => $entity->toArray(),
            'fieldDataJson' => json_encode($fieldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    public function name(): ?string
    {
        return 'Edit '.($this->collectionMeta['title'] ?? 'Webflow Item');
    }

    public function description(): ?string
    {
        return 'Update item fields imported from Webflow.';
    }

    public function permission(): ?iterable
    {
        return [];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Save')
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('entity.id')
                    ->title('Local ID')
                    ->readonly(),

                Input::make('entity.webflow_item_id')
                    ->title('Webflow Item ID')
                    ->readonly(),

                Input::make('entity.webflow_cms_locale_id')
                    ->title('CMS Locale ID'),

                Switcher::make('entity.is_archived')
                    ->title('Archived'),

                Switcher::make('entity.is_draft')
                    ->title('Draft'),

                TextArea::make('fieldDataJson')
                    ->title('Field Data JSON')
                    ->rows(24)
                    ->help('Edit JSON payload from Webflow item fields.'),
            ]),
        ];
    }

    public function save(string $collection, int $item, Request $request)
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);
        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404, 'Collection table not found: '.$meta['table']);
        }

        $model = $meta['model'];
        $entity = $model::query()->findOrFail($item);

        $json = (string) $request->input('fieldDataJson', '{}');
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            Toast::error('Field Data JSON is invalid.');

            return redirect()->back();
        }

        $entity->webflow_cms_locale_id = $request->input('entity.webflow_cms_locale_id');
        $entity->is_archived = (bool) $request->boolean('entity.is_archived');
        $entity->is_draft = (bool) $request->boolean('entity.is_draft');
        $entity->field_data = $decoded;
        $entity->save();

        Toast::info('Webflow item was saved.');

        return redirect()->route('platform.webflow.collection', ['collection' => $collection]);
    }
}

