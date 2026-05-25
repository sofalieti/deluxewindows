<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowReferenceRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
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

    protected array $referenceFields = [];

    protected array $relationOptions = [];

    protected array $referencePreview = [];

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
        $this->referenceFields = WebflowReferenceRegistry::forModel($meta['model']);
        $this->relationOptions = $this->buildRelationOptions($this->referenceFields);
        $this->referencePreview = $this->buildReferencePreview($entity, $meta['model']);

        return [
            'collection' => $meta,
            'entity' => $entity->toArray(),
            'fieldData' => $fieldData,
            'fieldDataJson' => json_encode($fieldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'referencePreview' => $this->referencePreview,
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

            Layout::tabs([
                'Main Data' => Layout::rows($this->buildFieldDataEditorsByCategory('main')),
                'Relations' => Layout::rows(array_merge(
                    $this->buildReferenceInputEditors(),
                    $this->buildReferencePreviewEditors()
                )),
                'SEO' => Layout::rows($this->buildFieldDataEditorsByCategory('seo')),
                'Schemas' => Layout::rows($this->buildFieldDataEditorsByCategory('schemas')),
                'OpenGraph' => Layout::rows($this->buildFieldDataEditorsByCategory('opengraph')),
                'JSON' => Layout::rows([
                    TextArea::make('fieldDataJson')
                        ->title('Field Data JSON (advanced)')
                        ->rows(16)
                        ->help('Optional: paste full JSON to override field values above.'),
                ]),
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

        $originalFieldData = is_array($entity->field_data) ? $entity->field_data : [];
        $fieldData = $originalFieldData;

        // Always apply individual field edits from the form tabs first.
        $submittedFields = $request->input('fieldData', []);
        if (is_array($submittedFields)) {
            foreach ($submittedFields as $key => $value) {
                $existing = $fieldData[$key] ?? null;
                $fieldData[$key] = $this->hydrateFieldValue($value, $existing);
            }
        }
        $fieldData = $this->applyRelationInputs($request, $fieldData, $meta['model']);

        // JSON textarea override: only when the user actually changed the JSON content.
        // Compare decoded arrays (not raw strings) to avoid false positives from whitespace/encoding differences.
        $rawJsonInput = $request->input('fieldDataJson', '');
        $rawJson = is_string($rawJsonInput) ? trim($rawJsonInput) : '';
        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (! is_array($decoded)) {
                Toast::error('Field Data JSON is invalid. Changes from individual fields were saved instead.');
            } elseif ($decoded !== $originalFieldData) {
                // JSON was intentionally changed by the user — let it take full precedence.
                $fieldData = $decoded;
            }
            // If $decoded === $originalFieldData the JSON textarea was not touched;
            // individual field edits already applied above remain in effect.
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
    private function buildFieldDataEditorsByCategory(string $category): array
    {
        if ($this->fieldData === []) {
            return [];
        }

        $fields = [];
        foreach ($this->fieldData as $key => $value) {
            if (array_key_exists((string) $key, $this->referenceFields)) {
                continue;
            }

            if ($this->fieldDataCategory((string) $key) !== $category) {
                continue;
            }

            $title = Str::headline(str_replace(['---', '-'], ' ', (string) $key));
            $name = $this->buildFieldInputName((string) $key);

            if (is_bool($value)) {
                $fields[] = Switcher::make($name)
                    ->title($title)
                    ->value($value);
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $fields[] = TextArea::make($name)
                    ->title($title)
                    ->rows(6)
                    ->value(is_string($encoded) ? $encoded : '{}')
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

    private function buildFieldInputName(string $key): string
    {
        return 'fieldData['.$key.']';
    }

    /**
     * @return Field[]
     */
    private function buildReferenceInputEditors(): array
    {
        if ($this->referenceFields === []) {
            return [];
        }

        $fields = [];
        foreach ($this->referenceFields as $fieldSlug => $meta) {
            $title = Str::headline(str_replace(['---', '-'], ' ', (string) $fieldSlug));
            $type = (string) ($meta['type'] ?? '');
            $options = $this->relationOptions[$fieldSlug] ?? [];
            $currentValue = $this->fieldData[$fieldSlug] ?? null;

            if ($type === 'reference') {
                $fields[] = Select::make('relationFields['.$fieldSlug.']')
                    ->title($title)
                    ->options($options)
                    ->empty('Not selected')
                    ->value(is_string($currentValue) ? $currentValue : null)
                    ->help('Select one related item.');
                continue;
            }

            if ($type === 'multi_reference') {
                $selected = is_array($currentValue)
                    ? array_values(array_filter($currentValue, fn ($id) => is_string($id) && $id !== ''))
                    : [];

                $fields[] = Select::make('relationFields['.$fieldSlug.'][]')
                    ->title($title)
                    ->options($options)
                    ->multiple()
                    ->value($selected)
                    ->help('Select one or many related items.');
            }
        }

        return $fields;
    }

    /**
     * @return Field[]
     */
    private function buildReferencePreviewEditors(): array
    {
        if ($this->referencePreview === []) {
            return [];
        }

        $fields = [];
        foreach ($this->referencePreview as $fieldSlug => $preview) {
            $title = Str::headline(str_replace(['---', '-'], ' ', (string) $fieldSlug));
            $name = 'referencePreview['.$fieldSlug.']';
            $value = is_string($preview) && $preview !== '' ? $preview : '-';

            $fields[] = TextArea::make($name)
                ->title($title.' Relation')
                ->rows(4)
                ->readonly()
                ->value($value)
                ->help('Resolved related items from imported collections.');
        }

        return $fields;
    }

    private function buildReferencePreview(Model $entity, string $modelClass): array
    {
        if (! method_exists($entity, 'webflowRelated')) {
            return [];
        }

        $referenceFields = WebflowReferenceRegistry::forModel($modelClass);
        if ($referenceFields === []) {
            return [];
        }

        $preview = [];
        foreach ($referenceFields as $fieldSlug => $meta) {
            $related = $entity->webflowRelated((string) $fieldSlug);
            $relationType = (string) ($meta['type'] ?? '');

            if ($relationType === 'reference') {
                if ($related instanceof Model) {
                    $preview[$fieldSlug] = $this->relatedLabel($related);
                } else {
                    $preview[$fieldSlug] = 'Not linked';
                }

                continue;
            }

            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                if ($related->isEmpty()) {
                    $preview[$fieldSlug] = 'No linked items';
                    continue;
                }

                $lines = [];
                foreach ($related->take(10) as $linked) {
                    if ($linked instanceof Model) {
                        $lines[] = '- '.$this->relatedLabel($linked);
                    }
                }

                if ($related->count() > 10) {
                    $lines[] = '... and '.($related->count() - 10).' more';
                }

                $preview[$fieldSlug] = implode(PHP_EOL, $lines);
            }
        }

        return $preview;
    }

    private function relatedLabel(Model $model): string
    {
        $fieldData = $model->getAttribute('field_data');
        $name = is_array($fieldData) ? ($fieldData['name'] ?? null) : null;
        $title = is_array($fieldData) ? ($fieldData['title'] ?? null) : null;
        $slug = is_array($fieldData) ? ($fieldData['slug'] ?? null) : null;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        if (is_string($title) && $title !== '') {
            return $title;
        }

        if (is_string($slug) && $slug !== '') {
            return Str::headline(str_replace('-', ' ', $slug));
        }

        return 'Untitled item';
    }

    private function buildRelationOptions(array $referenceFields): array
    {
        $options = [];

        foreach ($referenceFields as $fieldSlug => $meta) {
            $targetModel = (string) ($meta['target_model'] ?? '');
            if ($targetModel === '' || ! class_exists($targetModel)) {
                $options[$fieldSlug] = [];
                continue;
            }

            /** @var Collection<int, Model> $items */
            $items = $targetModel::query()
                ->orderBy('id')
                ->get();

            $options[$fieldSlug] = $items
                ->mapWithKeys(function (Model $item): array {
                    $webflowId = (string) ($item->getAttribute('webflow_item_id') ?? '');
                    if ($webflowId === '') {
                        return [];
                    }

                    return [$webflowId => $this->relatedLabel($item)];
                })
                ->all();
        }

        return $options;
    }

    private function applyRelationInputs(Request $request, array $fieldData, string $modelClass): array
    {
        $referenceFields = WebflowReferenceRegistry::forModel($modelClass);
        if ($referenceFields === []) {
            return $fieldData;
        }

        $input = $request->input('relationFields', []);
        if (! is_array($input)) {
            return $fieldData;
        }

        foreach ($referenceFields as $fieldSlug => $meta) {
            if (! array_key_exists($fieldSlug, $input)) {
                continue;
            }

            $value = $input[$fieldSlug];
            $type = (string) ($meta['type'] ?? '');

            if ($type === 'reference') {
                $fieldData[$fieldSlug] = is_string($value) && $value !== '' ? $value : null;
                continue;
            }

            if ($type === 'multi_reference') {
                if (! is_array($value)) {
                    $fieldData[$fieldSlug] = [];
                    continue;
                }

                $fieldData[$fieldSlug] = array_values(array_unique(array_filter(
                    $value,
                    fn ($id) => is_string($id) && $id !== ''
                )));
            }
        }

        return $fieldData;
    }

    private function fieldDataCategory(string $fieldSlug): string
    {
        $slug = Str::lower($fieldSlug);

        if (
            str_contains($slug, 'schema')
            || str_contains($slug, 'json-ld')
            || str_contains($slug, 'structured-data')
            || str_contains($slug, 'ld-json')
        ) {
            return 'schemas';
        }

        if (str_starts_with($slug, 'opengraph-') || str_starts_with($slug, 'og-')) {
            return 'opengraph';
        }

        if (
            str_starts_with($slug, 'seo-')
            || str_starts_with($slug, 'meta-')
            || str_starts_with($slug, 'twitter-')
            || str_contains($slug, 'canonical')
        ) {
            return 'seo';
        }

        return 'main';
    }
}

