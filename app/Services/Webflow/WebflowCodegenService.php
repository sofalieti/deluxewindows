<?php

namespace App\Services\Webflow;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebflowCodegenService
{
    public function generateFromExport(string $root): array
    {
        $disk = Storage::disk((string) config('webflow.export_disk', 'local'));
        $manifest = json_decode((string) $disk->get($root.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $pagesPayload = json_decode((string) $disk->get($root.'/site/pages.json'), true, 512, JSON_THROW_ON_ERROR);

        $createdMigrations = [];
        $createdModels = [];
        $createdViews = [];
        $createdImports = [];

        foreach ($manifest['collections'] as $index => $collection) {
            $slug = (string) $collection['slug'];
            $schemaPayload = json_decode((string) $disk->get($root."/collections/{$slug}/schema.json"), true, 512, JSON_THROW_ON_ERROR);
            $itemsPayload = json_decode((string) $disk->get($root."/collections/{$slug}/items.json"), true, 512, JSON_THROW_ON_ERROR);

            $tableName = 'wf_'.Str::snake($slug);
            $fields = $schemaPayload['fields'] ?? [];
            $timestamp = now()->addSeconds($index)->format('Y_m_d_His');
            $migrationName = "{$timestamp}_create_{$tableName}_table.php";
            $migrationPath = database_path('migrations/'.$migrationName);

            if (! File::exists($migrationPath)) {
                File::put($migrationPath, $this->buildMigrationContent($tableName, $fields));
                $createdMigrations[] = $migrationPath;
            }

            $modelClass = Str::studly($slug).'WebflowItem';
            $modelPath = app_path("Models/Webflow/{$modelClass}.php");
            File::ensureDirectoryExists(dirname($modelPath));
            File::put($modelPath, $this->buildModelContent($modelClass, $tableName, $fields));
            $createdModels[] = $modelPath;

            $importPath = storage_path("app/{$root}/imports/{$slug}.json");
            File::ensureDirectoryExists(dirname($importPath));
            File::put($importPath, json_encode([
                'table' => $tableName,
                'collectionId' => $collection['id'] ?? null,
                'collectionSlug' => $slug,
                'items' => $itemsPayload['items'] ?? [],
                'flattenedFieldMap' => $this->flattenedFieldMap($fields),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $createdImports[] = $importPath;
        }

        File::ensureDirectoryExists(resource_path('views/webflow/layouts'));
        $layoutPath = resource_path('views/webflow/layouts/app.blade.php');
        File::put($layoutPath, $this->layoutTemplate());
        $createdViews[] = $layoutPath;

        foreach (($pagesPayload['pages'] ?? []) as $page) {
            $viewInfo = $this->pageViewPathFromPage($page);
            File::ensureDirectoryExists(dirname($viewInfo['path']));
            File::put($viewInfo['path'], $this->pageTemplate($page));
            $createdViews[] = $viewInfo['path'];
        }

        return [
            'migrations' => $createdMigrations,
            'models' => $createdModels,
            'views' => $createdViews,
            'imports' => $createdImports,
        ];
    }

    public function importIntoDatabase(string $root): array
    {
        $manifestPath = storage_path("app/{$root}/manifest.json");
        if (! File::exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $imported = [];

        foreach ($manifest['collections'] as $collection) {
            $slug = (string) $collection['slug'];
            $importPath = storage_path("app/{$root}/imports/{$slug}.json");
            $payload = $this->resolveImportPayload($root, $slug, $collection);
            if (! is_array($payload)) {
                continue;
            }

            $table = (string) ($payload['table'] ?? '');
            if ($table === '' || ! Schema::hasTable($table)) {
                continue;
            }

            $rows = [];
            foreach (($payload['items'] ?? []) as $item) {
                $row = [
                    'webflow_item_id' => (string) ($item['id'] ?? ''),
                    'webflow_cms_locale_id' => $item['cmsLocaleId'] ?? null,
                    'webflow_created_on' => $item['createdOn'] ?? null,
                    'webflow_updated_on' => $item['lastUpdated'] ?? null,
                    'webflow_published_on' => $item['lastPublished'] ?? null,
                    'is_archived' => (bool) ($item['isArchived'] ?? false),
                    'is_draft' => (bool) ($item['isDraft'] ?? false),
                    'field_data' => json_encode($item['fieldData'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                foreach (($payload['flattenedFieldMap'] ?? []) as $wfField => $columnName) {
                    $value = $item['fieldData'][$wfField] ?? null;
                    $row[$columnName] = $this->normalizeFieldValue($value);
                }

                if ($row['webflow_item_id'] !== '') {
                    $rows[] = $row;
                }
            }

            if ($rows !== []) {
                \DB::table($table)->upsert($rows, ['webflow_item_id']);
            }

            $imported[] = ['table' => $table, 'count' => count($rows)];
        }

        return $imported;
    }

    public function exportFromDatabase(string $root): array
    {
        $manifestPath = storage_path("app/{$root}/manifest.json");
        if (! File::exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $exported = [];

        foreach ($manifest['collections'] as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $payload = $this->resolveImportPayload($root, $slug, $collection);
            if (! is_array($payload)) {
                continue;
            }

            $table = (string) ($payload['table'] ?? '');
            if ($table === '' || ! Schema::hasTable($table)) {
                continue;
            }

            $fieldMap = $payload['flattenedFieldMap'] ?? [];
            $fieldMap = is_array($fieldMap) ? $fieldMap : [];
            $reverseMap = [];
            foreach ($fieldMap as $wfField => $columnName) {
                if (is_string($wfField) && is_string($columnName) && $columnName !== '') {
                    $reverseMap[$columnName] = $wfField;
                }
            }

            $rows = \DB::table($table)->orderBy('id')->get();
            $items = [];

            foreach ($rows as $rowObj) {
                $row = (array) $rowObj;
                $fieldData = [];
                if (isset($row['field_data']) && is_string($row['field_data']) && $row['field_data'] !== '') {
                    $decoded = json_decode($row['field_data'], true);
                    if (is_array($decoded)) {
                        $fieldData = $decoded;
                    }
                }

                foreach ($reverseMap as $columnName => $wfField) {
                    if (! array_key_exists($columnName, $row)) {
                        continue;
                    }
                    $fieldData[$wfField] = $this->decodeStoredValue($row[$columnName]);
                }

                $items[] = [
                    'id' => (string) ($row['webflow_item_id'] ?? ''),
                    'cmsLocaleId' => $row['webflow_cms_locale_id'] ?? null,
                    'createdOn' => $this->asIsoString($row['webflow_created_on'] ?? null),
                    'lastUpdated' => $this->asIsoString($row['webflow_updated_on'] ?? null),
                    'lastPublished' => $this->asIsoString($row['webflow_published_on'] ?? null),
                    'isArchived' => (bool) ($row['is_archived'] ?? false),
                    'isDraft' => (bool) ($row['is_draft'] ?? false),
                    'fieldData' => $fieldData,
                ];
            }

            $itemsOutputPath = storage_path("app/{$root}/collections/{$slug}/items.local.json");
            File::ensureDirectoryExists(dirname($itemsOutputPath));
            File::put(
                $itemsOutputPath,
                json_encode(['items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            $importPath = storage_path("app/{$root}/imports/{$slug}.json");
            File::ensureDirectoryExists(dirname($importPath));
            File::put(
                $importPath,
                json_encode([
                    'table' => $table,
                    'collectionId' => $collection['id'] ?? null,
                    'collectionSlug' => $slug,
                    'items' => $items,
                    'flattenedFieldMap' => $fieldMap,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            $exported[] = [
                'slug' => $slug,
                'table' => $table,
                'count' => count($items),
                'itemsFile' => $itemsOutputPath,
            ];
        }

        return $exported;
    }

    private function buildMigrationContent(string $tableName, array $fields): string
    {
        $columnLines = [];
        $used = [];
        $fieldMap = $this->flattenedFieldMap($fields, $used);

        foreach ($fieldMap as $fieldSlug => $column) {
            $type = $this->laravelColumnForField($fieldSlug, $fields);
            $columnLines[] = "            \$table->{$type}('{$column}')->nullable();";
        }

        $columns = implode("\n", $columnLines);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('webflow_item_id')->unique();
            \$table->string('webflow_cms_locale_id')->nullable();
            \$table->timestamp('webflow_created_on')->nullable();
            \$table->timestamp('webflow_updated_on')->nullable();
            \$table->timestamp('webflow_published_on')->nullable();
            \$table->boolean('is_archived')->default(false);
            \$table->boolean('is_draft')->default(false);
            \$table->json('field_data')->nullable();
{$columns}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    private function buildModelContent(string $modelClass, string $tableName, array $fields): string
    {
        $casts = [
            "'field_data' => 'array'",
            "'is_archived' => 'boolean'",
            "'is_draft' => 'boolean'",
        ];

        foreach ($fields as $field) {
            $type = $field['type'] ?? '';
            $slug = (string) ($field['slug'] ?? '');
            $column = $this->flattenedFieldMap([$field])[$slug] ?? null;
            if (! $column) {
                continue;
            }

            if (in_array($type, ['MultiImage', 'MultiReference', 'Image', 'File', 'Video'], true)) {
                $casts[] = "'{$column}' => 'array'";
            } elseif ($type === 'Switch') {
                $casts[] = "'{$column}' => 'boolean'";
            } elseif ($type === 'Number') {
                $casts[] = "'{$column}' => 'float'";
            }
        }

        $castsBlock = implode(",\n        ", array_unique($casts));

        return <<<PHP
<?php

namespace App\Models\Webflow;

use Illuminate\Database\Eloquent\Model;

class {$modelClass} extends Model
{
    protected \$table = '{$tableName}';

    protected \$guarded = [];

    protected function casts(): array
    {
        return [
        {$castsBlock}
        ];
    }
}
PHP;
    }

    private function flattenedFieldMap(array $fields, ?array &$usedColumns = null): array
    {
        $usedColumns = $usedColumns ?? [];
        $map = [];

        foreach ($fields as $field) {
            $slug = (string) ($field['slug'] ?? '');
            if ($slug === '' || in_array($slug, ['name', 'slug'], true)) {
                continue;
            }

            $base = 'wf_'.Str::snake($slug);
            $candidate = Str::limit($base, 52, '');
            $suffix = 1;
            while (in_array($candidate, $usedColumns, true)) {
                $candidate = Str::limit($base, 48, '').'_'.($suffix++);
            }

            $usedColumns[] = $candidate;
            $map[$slug] = $candidate;
        }

        return $map;
    }

    private function laravelColumnForField(string $fieldSlug, array $fields): string
    {
        $field = collect($fields)->firstWhere('slug', $fieldSlug);
        $type = $field['type'] ?? 'PlainText';

        return match ($type) {
            'Switch' => 'boolean',
            'Number' => 'decimal',
            'DateTime' => 'timestamp',
            'RichText' => 'longText',
            'Image', 'MultiImage', 'File', 'Video', 'Reference', 'MultiReference', 'Option' => 'json',
            default => 'text',
        };
    }

    private function normalizeFieldValue(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    private function pageViewPathFromPage(array $page): array
    {
        $publishedPath = trim((string) ($page['publishedPath'] ?? ''), '/');
        $base = $publishedPath === '' ? 'home' : str_replace('/', '.', Str::slug($publishedPath, '-'));
        $safe = str_replace('-', '_', $base);

        return [
            'name' => "webflow.pages.{$safe}",
            'path' => resource_path("views/webflow/pages/{$safe}.blade.php"),
        ];
    }

    private function pageTemplate(array $page): string
    {
        $title = addslashes((string) ($page['title'] ?? 'Webflow Page'));
        $slug = addslashes((string) ($page['slug'] ?? ''));
        $path = addslashes((string) ($page['publishedPath'] ?? '/'));
        $nodes = $page['domNodes'] ?? [];

        $nodeBlocks = '';
        foreach (array_slice($nodes, 0, 40) as $node) {
            $textHtml = $node['text']['html'] ?? null;
            if (! is_string($textHtml) || trim($textHtml) === '') {
                continue;
            }

            $escapedHtml = $this->escapeForSingleQuotedPhp($textHtml);
            $nodeBlocks .= "        <section class=\"mb-3\">{!! '{$escapedHtml}' !!}</section>\n";
        }

        if ($nodeBlocks === '') {
            $nodeBlocks = "        <section class=\"mb-3\">\n            <p class=\"text-muted\">DOM content unavailable in API response for this page.</p>\n        </section>\n";
        }

        return <<<BLADE
@extends('webflow.layouts.app')

@section('title', '{$title}')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-2">{$title}</h1>
            <p class="text-secondary mb-4">Webflow slug: <code>{$slug}</code> | Path: <code>{$path}</code></p>
        </div>
    </div>

{$nodeBlocks}

    @if(!empty(\$items))
    <section class="mt-4">
        <h2 class="h4 mb-3">Collection Items</h2>
        <div class="row g-3">
            @foreach(\$items as \$item)
                <div class="col-12 col-md-6 col-lg-4">
                    <article class="card h-100">
                        <div class="card-body">
                            <h3 class="h6">{{ data_get(\$item, 'field_data.name', data_get(\$item, 'field_data.title', 'Untitled')) }}</h3>
                            <pre class="small mb-0">{{ json_encode(\$item['field_data'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
BLADE;
    }

    private function layoutTemplate(): string
    {
        return <<<'BLADE'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Webflow Mirror')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
BLADE;
    }

    private function escapeForSingleQuotedPhp(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function resolveImportPayload(string $root, string $slug, array $collection): ?array
    {
        $importPath = storage_path("app/{$root}/imports/{$slug}.json");
        if (File::exists($importPath)) {
            return json_decode((string) File::get($importPath), true, 512, JSON_THROW_ON_ERROR);
        }

        $itemsPath = storage_path("app/{$root}/collections/{$slug}/items.json");
        $schemaPath = storage_path("app/{$root}/collections/{$slug}/schema.json");
        if (! File::exists($itemsPath) || ! File::exists($schemaPath)) {
            return null;
        }

        $itemsPayload = json_decode((string) File::get($itemsPath), true, 512, JSON_THROW_ON_ERROR);
        $schemaPayload = json_decode((string) File::get($schemaPath), true, 512, JSON_THROW_ON_ERROR);

        return [
            'table' => 'wf_'.Str::snake($slug),
            'collectionId' => $collection['id'] ?? null,
            'collectionSlug' => $slug,
            'items' => $itemsPayload['items'] ?? [],
            'flattenedFieldMap' => $this->flattenedFieldMap($schemaPayload['fields'] ?? []),
        ];
    }

    private function decodeStoredValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        if (($trimmed[0] === '{' && str_ends_with($trimmed, '}')) || ($trimmed[0] === '[' && str_ends_with($trimmed, ']'))) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    private function asIsoString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
