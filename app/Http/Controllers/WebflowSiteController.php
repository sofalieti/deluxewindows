<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebflowSiteController extends Controller
{
    public function home()
    {
        return $this->serve('/');
    }

    public function catchAll(string $path = '')
    {
        return $this->serve('/'.ltrim($path, '/'));
    }

    private function serve(string $path)
    {
        $normalizedPath = $this->normalizePath($path);
        $segments = $this->segments($normalizedPath);
        $collections = $this->collectionMeta();

        if ($segments !== []) {
            $collectionSlug = $segments[0];
            if ($this->hasCollectionModel($collectionSlug)) {
                $meta = $collections[$collectionSlug] ?? [
                    'slug' => $collectionSlug,
                    'displayName' => Str::headline(str_replace('-', ' ', $collectionSlug)),
                ];

                if (count($segments) === 1) {
                    return $this->renderCollectionIndex($collectionSlug, $meta);
                }

                $itemSlug = $segments[count($segments) - 1];
                return $this->renderCollectionItem($collectionSlug, $itemSlug, $meta);
            }
        }

        $viewName = $this->resolveStaticViewName($normalizedPath);
        if ($viewName !== null && view()->exists($viewName)) {
            return view($viewName);
        }

        abort(404);
    }

    private function resolveStaticViewName(string $normalizedPath): ?string
    {
        $mirrorView = $this->mirrorViewNameFromPath($normalizedPath);
        if (view()->exists($mirrorView)) {
            return $mirrorView;
        }

        $view = $this->pageViewNameFromPath($normalizedPath);

        return view()->exists($view) ? $view : null;
    }

    private function renderCollectionIndex(string $collectionSlug, array $meta)
    {
        $modelClass = $this->modelClassFromSlug($collectionSlug);
        if (! class_exists($modelClass)) {
            abort(404);
        }

        $items = $modelClass::query()
            ->orderByDesc('id')
            ->limit(120)
            ->get();

        $itemsData = $items->map(function ($item) {
            $data = $item->toArray();
            $data['field_data'] = is_array($item->field_data ?? null) ? $item->field_data : [];
            return $data;
        })->all();

        $view = "webflow.collections.{$collectionSlug}.index";
        if (! view()->exists($view)) {
            $view = 'webflow.collections.generic.index';
        }

        return view($view, [
            'collectionSlug' => $collectionSlug,
            'collection' => $meta,
            'items' => $itemsData,
        ]);
    }

    private function renderCollectionItem(string $collectionSlug, string $itemSlug, array $meta)
    {
        $modelClass = $this->modelClassFromSlug($collectionSlug);
        if (! class_exists($modelClass)) {
            abort(404);
        }

        $item = $modelClass::query()
            ->where('field_data->slug', $itemSlug)
            ->orWhere('webflow_item_id', $itemSlug)
            ->first();

        if (! $item) {
            // Fallback for DBs where JSON path queries are unreliable.
            $item = $modelClass::query()
                ->orderByDesc('id')
                ->get()
                ->first(function ($row) use ($itemSlug) {
                    $fieldData = $row->field_data;
                    if (! is_array($fieldData)) {
                        return false;
                    }

                    return (string) ($fieldData['slug'] ?? '') === $itemSlug;
                });
        }

        if (! $item) {
            $importItem = $this->findImportItem($collectionSlug, $itemSlug);
            if (is_array($importItem)) {
                $fieldData = $importItem['fieldData'] ?? [];
                $view = "webflow.collections.{$collectionSlug}.show";
                if (! view()->exists($view)) {
                    $view = 'webflow.collections.generic.show';
                }

                return view($view, [
                    'collectionSlug' => $collectionSlug,
                    'collection' => $meta,
                    'itemSlug' => $itemSlug,
                    'item' => $importItem,
                    'fieldData' => is_array($fieldData) ? $fieldData : [],
                ]);
            }

            abort(404);
        }

        $fieldData = is_array($item->field_data ?? null) ? $item->field_data : [];
        $view = "webflow.collections.{$collectionSlug}.show";
        if (! view()->exists($view)) {
            $view = 'webflow.collections.generic.show';
        }

        return view($view, [
            'collectionSlug' => $collectionSlug,
            'collection' => $meta,
            'itemSlug' => $itemSlug,
            'item' => $item->toArray(),
            'fieldData' => $fieldData,
        ]);
    }

    private function normalizePath(string $path): string
    {
        $trimmed = '/'.trim($path, '/');
        return $trimmed === '/' ? '/' : rtrim($trimmed, '/');
    }

    private function segments(string $normalizedPath): array
    {
        if ($normalizedPath === '/') {
            return [];
        }

        return array_values(array_filter(explode('/', trim($normalizedPath, '/'))));
    }

    private function collectionMeta(): array
    {
        $manifestPath = $this->exportPath('manifest.json');
        if (! File::exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        $collections = $manifest['collections'] ?? [];

        $map = [];
        foreach ($collections as $collection) {
            $slug = $collection['slug'] ?? null;
            if (is_string($slug) && $slug !== '') {
                $map[$slug] = $collection;
            }
        }

        return $map;
    }

    private function modelClassFromSlug(string $slug): string
    {
        return 'App\\Models\\Webflow\\'.Str::studly($slug).'WebflowItem';
    }

    private function hasCollectionModel(string $slug): bool
    {
        return class_exists($this->modelClassFromSlug($slug));
    }

    private function findImportItem(string $collectionSlug, string $itemSlug): ?array
    {
        $path = $this->exportPath("imports/{$collectionSlug}.json");
        if (! File::exists($path)) {
            return null;
        }

        $payload = json_decode((string) File::get($path), true);
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            $slug = (string) ($item['fieldData']['slug'] ?? '');
            if ($slug === $itemSlug) {
                return is_array($item) ? $item : null;
            }
        }

        return null;
    }

    private function pageViewNameFromPath(string $normalizedPath): string
    {
        if ($normalizedPath === '/') {
            return 'webflow.pages.home';
        }

        $segments = array_values(array_filter(explode('/', trim($normalizedPath, '/'))));
        $safe = array_map(fn (string $segment) => str_replace('-', '_', $segment), $segments);

        return 'webflow.pages.'.implode('.', $safe);
    }

    private function mirrorViewNameFromPath(string $normalizedPath): string
    {
        if ($normalizedPath === '/') {
            return 'webflow.mirror.home';
        }

        $segments = array_values(array_filter(explode('/', trim($normalizedPath, '/'))));

        return 'webflow.mirror.'.implode('.', $segments);
    }

    private function exportPath(string $relative = ''): string
    {
        $root = trim((string) config('webflow.export_root', 'current'), '/');
        $disk = Storage::disk((string) config('webflow.export_disk', 'webflow_repo'));
        $base = rtrim($disk->path($root), DIRECTORY_SEPARATOR);
        $relative = trim($relative, '/');

        if ($relative === '') {
            return $base;
        }

        return $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
