<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoorBrand;
use App\Models\Webflow\DoorsWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use App\Services\Seo\PageMetadataRepository;
use App\Services\Webflow\WebflowCodegenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ContentDatasetService
{
    private const FORMAT_VERSION = 1;

    public function __construct(
        private readonly WebflowCodegenService $webflow,
        private readonly PageMetadataRepository $pageMetadata,
    ) {
    }

    /**
     * @return array{
     *     generated_at: string,
     *     webflow_collections: int,
     *     webflow_records: int,
     *     door_brands: int,
     *     page_metadata: int
     * }
     */
    public function exportAll(): array
    {
        $root = $this->webflowRoot();
        $this->assertWebflowManifestExists($root);
        $pageMetadataCount = $this->validatedPageMetadataCount();

        $webflow = $this->webflow->exportFromDatabase($root);
        $expectedCollectionCount = $this->webflowCollectionCount($root);
        if (count($webflow) !== $expectedCollectionCount) {
            throw new RuntimeException(sprintf(
                'Webflow export was incomplete: exported %d of %d collections.',
                count($webflow),
                $expectedCollectionCount
            ));
        }
        $doorBrands = $this->exportDoorBrands();
        $generatedAt = now()->toIso8601String();

        $summary = [
            'generated_at' => $generatedAt,
            'webflow_collections' => count($webflow),
            'webflow_records' => array_sum(array_column($webflow, 'count')),
            'door_brands' => count($doorBrands),
            'page_metadata' => $pageMetadataCount,
        ];

        $this->writeJson($this->doorBrandsPath(), $doorBrands);
        $this->writeJson($this->datasetManifestPath(), [
            'format_version' => self::FORMAT_VERSION,
            ...$summary,
            'files' => [
                'webflow_root' => $root,
                'door_brands' => $this->relativeProjectPath($this->doorBrandsPath()),
                'page_metadata_root' => $this->relativeProjectPath($this->pageMetadata->root()),
            ],
        ]);

        return $summary;
    }

    /**
     * @return array{
     *     imported_at: string,
     *     webflow_collections: int,
     *     webflow_records: int,
     *     door_brands: int,
     *     page_metadata: int
     * }
     */
    public function importAll(): array
    {
        $validated = $this->validateImportFiles();

        $result = DB::transaction(function () use ($validated): array {
            $webflow = $this->webflow->importIntoDatabase($this->webflowRoot());
            $this->touchMaterialUpdateDates();
            $doorBrandCount = $this->importDoorBrands($validated['door_brands']);

            return [
                'imported_at' => now()->toIso8601String(),
                'webflow_collections' => count($webflow),
                'webflow_records' => array_sum(array_column($webflow, 'count')),
                'door_brands' => $doorBrandCount,
                'page_metadata' => $validated['page_metadata'],
            ];
        });

        $this->pageMetadata->clearCache();

        return $result;
    }

    /**
     * Dataset import refreshes material (windows/doors) sitemap dates even when
     * CMS lastUpdated in the JSON is older than today.
     */
    private function touchMaterialUpdateDates(): void
    {
        $now = now();

        foreach ([WindowsWebflowItem::class, DoorsWebflowItem::class] as $modelClass) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
            $table = (new $modelClass)->getTable();
            if (! Schema::hasTable($table)) {
                continue;
            }

            $payload = ['updated_at' => $now];
            if (Schema::hasColumn($table, 'webflow_updated_on')) {
                $payload['webflow_updated_on'] = $now;
            }

            $modelClass::query()->update($payload);
        }
    }

    /**
     * @return array{
     *     ready: bool,
     *     manifest_path: string,
     *     generated_at: ?string,
     *     webflow_collections: int,
     *     webflow_records: int,
     *     door_brands: int,
     *     page_metadata: int,
     *     error: ?string
     * }
     */
    public function status(): array
    {
        if (! File::exists($this->datasetManifestPath())) {
            return [
                'ready' => false,
                'manifest_path' => $this->datasetManifestPath(),
                'generated_at' => null,
                'webflow_collections' => 0,
                'webflow_records' => 0,
                'door_brands' => 0,
                'page_metadata' => 0,
                'error' => 'Dataset manifest has not been generated.',
            ];
        }

        try {
            $manifest = $this->readJsonObject($this->datasetManifestPath());
            $this->validateImportFiles();

            return [
                'ready' => true,
                'manifest_path' => $this->datasetManifestPath(),
                'generated_at' => isset($manifest['generated_at'])
                    ? (string) $manifest['generated_at']
                    : null,
                'webflow_collections' => (int) ($manifest['webflow_collections'] ?? 0),
                'webflow_records' => (int) ($manifest['webflow_records'] ?? 0),
                'door_brands' => (int) ($manifest['door_brands'] ?? 0),
                'page_metadata' => (int) ($manifest['page_metadata'] ?? 0),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ready' => false,
                'manifest_path' => $this->datasetManifestPath(),
                'generated_at' => null,
                'webflow_collections' => 0,
                'webflow_records' => 0,
                'door_brands' => 0,
                'page_metadata' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return list<array{slug: string, name: string, doors_title: string, description: string}>
     */
    private function exportDoorBrands(): array
    {
        return DoorBrand::query()
            ->orderBy('slug')
            ->get()
            ->map(fn (DoorBrand $item): array => [
                'slug' => (string) $item->slug,
                'name' => (string) ($item->name ?? ''),
                'doors_title' => (string) ($item->doors_title ?? ''),
                'description' => (string) ($item->description ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     door_brands: list<array<string, mixed>>,
     *     page_metadata: int
     * }
     */
    private function validateImportFiles(): array
    {
        $manifest = $this->readJsonObject($this->datasetManifestPath());
        if ((int) ($manifest['format_version'] ?? 0) !== self::FORMAT_VERSION) {
            throw new RuntimeException('Unsupported content dataset format version.');
        }

        $pageMetadataCount = $this->validatedPageMetadataCount();

        $root = $this->webflowRoot();
        $webflowManifest = $this->readJsonObject($this->webflowManifestPath($root));
        $collections = $webflowManifest['collections'] ?? null;
        if (! is_array($collections)) {
            throw new RuntimeException('Webflow manifest does not contain a collections array.');
        }

        foreach ($collections as $collection) {
            $slug = is_array($collection) ? trim((string) ($collection['slug'] ?? '')) : '';
            if ($slug === '') {
                throw new RuntimeException('Webflow manifest contains a collection without a slug.');
            }
            $payload = $this->readJsonObject($this->webflowImportPath($root, $slug));
            if (! isset($payload['items']) || ! is_array($payload['items'])) {
                throw new RuntimeException("Webflow import file for {$slug} has no items array.");
            }
            $table = trim((string) ($payload['table'] ?? ''));
            if ($table === '' || ! Schema::hasTable($table)) {
                throw new RuntimeException("Database table for Webflow collection {$slug} is missing.");
            }
        }

        $doorBrands = $this->readJsonList($this->doorBrandsPath());
        foreach ($doorBrands as $index => $item) {
            if (! is_array($item) || trim((string) ($item['slug'] ?? '')) === '') {
                throw new RuntimeException("Door brand dataset row {$index} has no slug.");
            }
        }

        return [
            'door_brands' => $doorBrands,
            'page_metadata' => $pageMetadataCount,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function importDoorBrands(array $items): int
    {
        foreach ($items as $item) {
            $slug = strtolower(trim((string) $item['slug']));
            DoorBrand::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => isset($item['name']) ? (string) $item['name'] : null,
                    'doors_title' => isset($item['doors_title']) ? (string) $item['doors_title'] : null,
                    'description' => isset($item['description']) ? (string) $item['description'] : null,
                ]
            );
        }

        return count($items);
    }

    private function validatedPageMetadataCount(): int
    {
        $result = $this->pageMetadata->validateAll();
        if ($result['invalid'] !== []) {
            throw new RuntimeException(
                "Page metadata validation failed:\n".implode("\n", $result['invalid'])
            );
        }
        if ($result['valid'] === 0) {
            throw new RuntimeException('No page metadata files were found.');
        }

        return $result['valid'];
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonObject(string $path): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("Required dataset file not found: {$path}");
        }

        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException("Dataset file must contain a JSON object: {$path}");
        }

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJsonList(string $path): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("Required dataset file not found: {$path}");
        }

        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw new RuntimeException("Dataset file must contain a JSON array: {$path}");
        }

        return $decoded;
    }

    private function writeJson(string $path, array $data): void
    {
        $this->assertWritableDirectory(dirname($path));
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ).PHP_EOL;

        try {
            File::replace($path, $json);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->writeFailureMessage($path, $e), 0, $e);
        }
    }

    private function assertWritableDirectory(string $directory): void
    {
        File::ensureDirectoryExists($directory);
        if (! is_writable($directory)) {
            throw new RuntimeException($this->permissionHint($directory));
        }
    }

    private function writeFailureMessage(string $path, \Throwable $e): string
    {
        $message = $e->getMessage();
        if (stripos($message, 'Permission denied') !== false || stripos($message, 'failed to open stream') !== false) {
            return $this->permissionHint(dirname($path)).' Original error: '.$message;
        }

        return 'Failed to write '.$path.': '.$message;
    }

    private function permissionHint(string $directory): string
    {
        $webflowRoot = base_path('webflow-data');

        return "Cannot write to {$directory}: permission denied. "
            ."On the server fix ownership for the PHP user, e.g. "
            ."sudo chown -R www-data:www-data {$webflowRoot} database/data "
            ."&& sudo chmod -R ug+rwX {$webflowRoot} database/data";
    }

    private function assertWebflowManifestExists(string $root): void
    {
        if (! File::exists($this->webflowManifestPath($root))) {
            throw new RuntimeException('Webflow manifest was not found. Run the Webflow code generator first.');
        }
    }

    private function webflowCollectionCount(string $root): int
    {
        $manifest = $this->readJsonObject($this->webflowManifestPath($root));
        $collections = $manifest['collections'] ?? null;
        if (! is_array($collections)) {
            throw new RuntimeException('Webflow manifest does not contain a collections array.');
        }

        return count($collections);
    }

    private function webflowRoot(): string
    {
        return trim((string) config('webflow.export_root', 'current'), '/');
    }

    private function webflowManifestPath(string $root): string
    {
        return $this->webflowDiskPath($root.'/manifest.json');
    }

    private function webflowImportPath(string $root, string $slug): string
    {
        return $this->webflowDiskPath($root."/imports/{$slug}.json");
    }

    private function webflowDiskPath(string $relative): string
    {
        return Storage::disk((string) config('webflow.export_disk', 'webflow_repo'))
            ->path(trim($relative, '/'));
    }

    private function doorBrandsPath(): string
    {
        return database_path('data/door-brands.json');
    }

    private function datasetManifestPath(): string
    {
        return database_path('data/content-datasets.json');
    }

    private function relativeProjectPath(string $path): string
    {
        return str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR)
        );
    }
}
