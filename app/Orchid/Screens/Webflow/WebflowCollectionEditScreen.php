<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Field;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WebflowCollectionEditScreen extends Screen
{
    protected string $collectionSlug = '';

    protected array $collectionMeta = [];

    protected array $fieldData = [];

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
        $this->fieldData = $fieldData;

        return [
            'collection' => $meta,
            'entity' => $entity->toArray(),
            'fieldData' => $fieldData,
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
            ]),

            Layout::rows($this->buildFieldDataEditors()),

            Layout::rows([
                TextArea::make('fieldDataJson')
                    ->title('Field Data JSON (advanced)')
                    ->rows(16)
                    ->help('Optional: paste full JSON to override field values above.'),
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

        $fieldData = is_array($entity->field_data) ? $entity->field_data : [];
        $submittedFields = $request->input('fieldData', []);
        if (is_array($submittedFields)) {
            foreach ($submittedFields as $key => $value) {
                $existing = $fieldData[$key] ?? null;
                $fieldData[$key] = $this->hydrateFieldValue($value, $existing);
            }
        }

        $rawJson = trim((string) $request->input('fieldDataJson', ''));
        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (! is_array($decoded)) {
                Toast::error('Field Data JSON is invalid.');

                return redirect()->back();
            }

            $fieldData = $decoded;
        }

        $entity->webflow_cms_locale_id = $request->input('entity.webflow_cms_locale_id');
        $entity->is_archived = (bool) $request->boolean('entity.is_archived');
        $entity->is_draft = (bool) $request->boolean('entity.is_draft');
        $entity->field_data = $fieldData;
        $entity->save();

        Toast::info('Webflow item was saved.');

        return redirect()->route('platform.webflow.collection', ['collection' => $collection]);
    }

    /**
     * @return Field[]
     */
    private function buildFieldDataEditors(): array
    {
        if ($this->fieldData === []) {
            return [
                TextArea::make('fieldDataJson')
                    ->title('Field Data JSON')
                    ->rows(16),
            ];
        }

        $fields = [];
        foreach ($this->fieldData as $key => $value) {
            $title = Str::headline(str_replace(['---', '-'], ' ', (string) $key));
            $name = 'fieldData.'.$key;

            if (is_bool($value)) {
                $fields[] = Switcher::make($name)
                    ->title($title)
                    ->value($value);
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $fields[] = TextArea::make($name)
                    ->title($title)
                    ->rows(6)
                    ->value(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->help('JSON value');
                continue;
            }

            $stringValue = (string) ($value ?? '');
            $isLong = mb_strlen($stringValue) > 180 || str_contains($stringValue, "\n") || str_contains($stringValue, '<');

            if ($isLong) {
                $fields[] = TextArea::make($name)
                    ->title($title)
                    ->rows(4)
                    ->value($stringValue);
            } else {
                $fields[] = Input::make($name)
                    ->title($title)
                    ->value($stringValue);
            }
        }

        return $fields;
    }

    private function hydrateFieldValue(mixed $value, mixed $existing): mixed
    {
        if (is_bool($existing)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if (is_array($existing) || is_object($existing)) {
            if (! is_string($value)) {
                return $existing;
            }

            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $existing;
        }

        if (is_int($existing) || is_float($existing)) {
            return is_numeric((string) $value) ? ($value + 0) : $existing;
        }

        return $value;
    }
}

