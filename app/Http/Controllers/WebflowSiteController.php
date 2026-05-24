<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
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
            if (array_key_exists($collectionSlug, $collections)) {
                if (count($segments) === 1) {
                    return $this->renderCollectionIndex($collectionSlug, $collections[$collectionSlug]);
                }

                $itemSlug = $segments[count($segments) - 1];
                return $this->renderCollectionItem($collectionSlug, $itemSlug, $collections[$collectionSlug]);
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
        $manifestPath = storage_path('app/'.trim((string) config('webflow.export_root', 'webflow-export/current'), '/').'/mirror-routes.json');
        if (! File::exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        $routeMap = $manifest['routeMap'] ?? [];
        $view = $routeMap[$normalizedPath] ?? null;

        return is_string($view) && $view !== '' ? $view : null;
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
        $manifestPath = storage_path('app/'.trim((string) config('webflow.export_root', 'webflow-export/current'), '/').'/manifest.json');
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
}
