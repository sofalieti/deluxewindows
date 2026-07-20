<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Webflow;

use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowReferenceRegistry;
use App\Services\PromotionSettingsService;
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
    /** @var array<string, string> */
    private const GLOBAL_SETTINGS_PROMOTION_FIELDS = [
        'promotion-name' => 'Promotion name',
        'start-date' => 'Start date',
        'end-date' => 'End date',
    ];

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
        $fieldData = $this->ensureMaterialCustomHeroField($fieldData);
        $this->fieldData = $fieldData;
        $this->referenceFields = WebflowReferenceRegistry::forModel($meta['model']);
        $this->relationOptions = $this->buildRelationOptions($this->referenceFields);
        $this->referencePreview = $this->buildReferencePreview($entity, $meta['model']);

        return [
            'collection' => $meta,
            'entity' => [
                'id' => $entity->getKey(),
                'webflow_item_id' => $entity->webflow_item_id,
                'webflow_cms_locale_id' => $entity->webflow_cms_locale_id,
                'is_archived' => (bool) $entity->is_archived,
                'is_draft' => (bool) $entity->is_draft,
            ],
            'referencePreview' => $this->referencePreview,
        ];
    }

    /**
     * @param  array<string, mixed>  $fieldData
     * @return array<string, mixed>
     */
    private function ensureMaterialCustomHeroField(array $fieldData): array
    {
        if (! in_array($this->collectionSlug, ['windows', 'doors'], true)) {
            return $fieldData;
        }

        if (array_key_exists('custom-hero-image', $fieldData)) {
            return $fieldData;
        }

        $fieldData['custom-hero-image'] = [
            'fileId' => null,
            'url' => '',
            'alt' => null,
        ];

        return $fieldData;
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
        $layouts = [
            Layout::view('admin.webflow-image-upload-script'),

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
        ];

        if ($this->collectionSlug === 'global-settings') {
            $layouts[] = Layout::block(
                Layout::rows($this->buildGlobalSettingsPromotionEditors())
            )
                ->title('Site-wide promotion')
                ->description('Promotion name and dates for offers across the site. The name is stored only — it is not shown on pages yet.');
        }

        $layouts[] = Layout::tabs([
                'Main Data' => Layout::rows($this->buildFieldDataEditorsByCategory('main')),
                'Relations' => Layout::rows(array_merge(
                    $this->buildReferenceInputEditors(),
                    $this->buildReferencePreviewEditors()
                )),
            ]);

        return $layouts;
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

        // Always apply individual field edits from the form tabs first.
        $submittedFields = $request->input('fieldData', []);
        if (is_array($submittedFields)) {
            foreach ($submittedFields as $key => $value) {
                if ($this->isMetadataField((string) $key)) {
                    continue;
                }
                $existing = $fieldData[$key] ?? null;
                $fieldData[$key] = $this->hydrateFieldValue($value, $existing);
            }
        }
        $fieldData = $this->applyRelationInputs($request, $fieldData, $meta['model']);

        $entity->webflow_cms_locale_id = $request->input('entity.webflow_cms_locale_id');
        $entity->is_archived = (bool) $request->boolean('entity.is_archived');
        $entity->is_draft = (bool) $request->boolean('entity.is_draft');
        $entity->field_data = $fieldData;

        if ($collection === 'global-settings') {
            $this->syncGlobalSettingsColumns($entity, $fieldData);
        }

        $entity->save();

        if (in_array($collection, ['global-settings', 'coupons'], true)) {
            app(PromotionSettingsService::class)->forgetCache();
        }

        Toast::info('Webflow item was saved.');

        return redirect()->route('platform.webflow.collection', ['collection' => $collection]);
    }

    /**
     * @return Field[]
     */
    private function buildGlobalSettingsPromotionEditors(): array
    {
        $fields = [];

        foreach (self::GLOBAL_SETTINGS_PROMOTION_FIELDS as $slug => $title) {
            $value = (string) ($this->fieldData[$slug] ?? '');
            $name = $this->buildFieldInputName($slug);

            if ($slug === 'promotion-name') {
                $fields[] = Input::make($name)
                    ->title($title)
                    ->value($value)
                    ->help('Title of the current promotion. Saved for future use — not displayed on the site yet.');
                continue;
            }

            $fields[] = Input::make($name)
                ->title($title)
                ->value($value)
                ->help('Format: M/D/YY or M/D/YYYY');
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $fieldData
     */
    private function syncGlobalSettingsColumns(Model $entity, array $fieldData): void
    {
        if (! Schema::hasColumn($entity->getTable(), 'wf_promotion_name')) {
            return;
        }

        $entity->wf_promotion_name = $this->nullableTrimmedString($fieldData['promotion-name'] ?? null);
        $entity->wf_start_date = $this->nullableTrimmedString($fieldData['start-date'] ?? null);
        $entity->wf_end_date = $this->nullableTrimmedString($fieldData['end-date'] ?? null);
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return Field[]
     */
    private function buildFieldDataEditorsByCategory(string $category): array
    {
        if ($this->fieldData === []) {
            return [];
        }

        $fieldDataEntries = $this->orderedFieldDataEntries($this->fieldData, $category);
        $fields = [];
        foreach ($fieldDataEntries as $key => $value) {
            if (array_key_exists((string) $key, $this->referenceFields)) {
                continue;
            }

            if ($this->isMetadataField((string) $key)) {
                continue;
            }

            if (
                $this->collectionSlug === 'global-settings'
                && array_key_exists((string) $key, self::GLOBAL_SETTINGS_PROMOTION_FIELDS)
            ) {
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

            // Single image object: array with a 'url' key (not a numeric-indexed list)
            if (is_array($value) && array_key_exists('url', $value) && ! array_key_exists(0, $value)) {
                $fields = array_merge($fields, $this->buildSingleImageFields($name, $title, (string) $key, $value));
                continue;
            }

            // Multi-image: numeric array where the first element is an image object
            if (is_array($value) && ! empty($value) && is_array($value[0] ?? null) && array_key_exists('url', $value[0])) {
                $fields = array_merge($fields, $this->buildMultiImageFields($name, $title, (string) $key, $value));
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

    /**
     * Keep important fields pinned to the top of their section.
     *
     * @param  array<string, mixed>  $fieldData
     * @return array<string, mixed>
     */
    private function orderedFieldDataEntries(array $fieldData, string $category): array
    {
        if (! in_array($this->collectionSlug, ['windows', 'doors'], true) || $category !== 'main') {
            return $fieldData;
        }

        if (! array_key_exists('custom-hero-image', $fieldData)) {
            return $fieldData;
        }

        $ordered = ['custom-hero-image' => $fieldData['custom-hero-image']];
        foreach ($fieldData as $key => $value) {
            if ($key === 'custom-hero-image') {
                continue;
            }
            $ordered[$key] = $value;
        }

        return $ordered;
    }

    private function hydrateFieldValue(mixed $value, mixed $existing): mixed
    {
        if (is_bool($existing)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        // Single image object: when the user edits just the URL string, merge it back.
        if (is_array($existing) && array_key_exists('url', $existing) && ! array_key_exists(0, $existing) && is_string($value)) {
            if (trim($value) === '') {
                $cleared = $existing;
                $cleared['url'] = '';
                if (array_key_exists('fileId', $cleared)) {
                    $cleared['fileId'] = null;
                }

                return $cleared;
            }

            return array_merge($existing, ['url' => $value]);
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
                    ->value($this->normalizeRelationId($currentValue))
                    ->help('Select one related item.');
                continue;
            }

            if ($type === 'multi_reference') {
                $selected = is_array($currentValue)
                    ? array_values(array_filter(array_map(
                        fn ($id) => $this->normalizeRelationId($id),
                        $currentValue
                    )))
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

    /**
     * @return Field[]
     */
    private function buildSingleImageFields(string $inputName, string $title, string $fieldKey, array $value): array
    {
        $imageUrl = is_string($value['url'] ?? null) ? $value['url'] : '';
        $safeKey = preg_replace('/[^a-zA-Z0-9]/', '_', $fieldKey) ?: 'image';
        $escUrl = htmlspecialchars($imageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escField = htmlspecialchars($fieldKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escSafe = htmlspecialchars($safeKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hasImage = $imageUrl !== '';

        $previewHtml = '<div id="wf-preview-wrapper-'.$escSafe.'" style="margin-bottom:10px'.($hasImage ? '' : ';display:none').'">'
            .'<img id="wf-preview-'.$escSafe.'" src="'.$escUrl.'" '
            .'style="max-width:300px;max-height:180px;object-fit:contain;border:1px solid #dee2e6;'
            .'border-radius:6px;padding:4px;background:#f8f9fa;display:'.($hasImage ? 'block' : 'none').'" '
            .'onerror="this.style.display=\'none\'">'
            .'</div>';

        $uploadHtml = '<div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">'
            .'<input type="file" id="wf-file-'.$escSafe.'" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" '
            .'style="display:none" class="wf-image-action" data-wf-action="upload" '
            .'data-field-key="'.$escField.'" data-safe-key="'.$escSafe.'">'
            .'<button type="button" id="wf-btn-'.$escSafe.'" '
            .'class="btn btn-sm btn-outline-secondary wf-image-action" data-wf-action="select" '
            .'data-safe-key="'.$escSafe.'">'
            .'📁 Upload image'
            .'</button>'
            .'<button type="button" id="wf-del-'.$escSafe.'" '
            .'class="btn btn-sm btn-outline-danger wf-image-action" data-wf-action="clear" '
            .'data-field-key="'.$escField.'" data-safe-key="'.$escSafe.'"'
            .($hasImage ? '' : ' style="display:none"').'>'
            .'🗑 Delete image'
            .'</button>'
            .'</div>';

        return [
            Input::make($inputName)
                ->title($title)
                ->value($imageUrl)
                ->help($previewHtml.$uploadHtml.'<small class="text-muted d-block mt-1">Edit URL, upload a new file, or delete the image. Save the item to apply.</small>'),
        ];
    }

    /**
     * @return Field[]
     */
    private function buildMultiImageFields(string $inputName, string $title, string $fieldKey, array $value): array
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9]/', '_', $fieldKey) ?: 'gallery';
        $escField = htmlspecialchars($fieldKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escSafe = htmlspecialchars($safeKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $previewHtml = '<div id="wf-multi-preview-'.$escSafe.'" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">';

        foreach (array_values($value) as $index => $img) {
            if (! is_array($img) || ! is_string($img['url'] ?? null) || $img['url'] === '') {
                continue;
            }
            $esc = htmlspecialchars($img['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $previewHtml .= '<div class="wf-multi-thumb" data-index="'.$index.'" style="position:relative;width:90px;height:68px;">'
                .'<img src="'.$esc.'" '
                .'style="width:90px;height:68px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;display:block;" '
                .'onerror="this.parentElement.style.display=\'none\'">'
                .'<button type="button" title="Delete image" class="wf-image-action" '
                .'data-wf-action="remove-multi" data-field-key="'.$escField.'" data-safe-key="'.$escSafe.'" data-index="'.$index.'" '
                .'style="position:absolute;top:2px;right:2px;width:22px;height:22px;padding:0;border:none;'
                .'border-radius:50%;background:rgba(185,28,28,.92);color:#fff;font-size:14px;line-height:22px;'
                .'cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25);z-index:2">×</button>'
                .'</div>';
        }

        $previewHtml .= '</div>';

        $encoded = json_encode(array_values($value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            TextArea::make($inputName)
                ->title($title)
                ->rows(5)
                ->value(is_string($encoded) ? $encoded : '[]')
                ->help($previewHtml.'<small class="text-muted">Click × on a thumbnail to remove it, or edit the JSON <code>url</code> values. Save the item to apply.</small>'),
        ];
    }

    private function fieldDataCategory(string $fieldSlug): string
    {
        $slug = Str::lower($fieldSlug);

        if (
            str_contains($slug, 'schema')
            || str_contains($slug, 'json-ld')
            || str_contains($slug, 'jsonld')
            || str_contains($slug, 'structured-data')
            || str_contains($slug, 'structured_data')
            || str_contains($slug, 'ld-json')
        ) {
            return 'schemas';
        }

        if (
            str_starts_with($slug, 'opengraph-')
            || str_starts_with($slug, 'open-graph-')
            || str_starts_with($slug, 'og-')
        ) {
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

    private function isMetadataField(string $fieldSlug): bool
    {
        $slug = Str::lower($fieldSlug);

        return $this->fieldDataCategory($slug) !== 'main'
            || str_contains($slug, 'faq')
            || str_contains($slug, 'frequently-asked');
    }

    private function normalizeRelationId(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if ($trimmed[0] === '"' && str_ends_with($trimmed, '"')) {
            $decoded = json_decode($trimmed, true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $trimmed;
    }
}

