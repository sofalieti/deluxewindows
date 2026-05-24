<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

        $cms = $this->resolveCmsItem($normalizedPath);
        if ($cms !== null) {
            return view('webflow.cms.item', $cms);
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

    private function resolveCmsItem(string $normalizedPath): ?array
    {
        if ($normalizedPath === '/') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($normalizedPath, '/'))));
        if (count($segments) < 2) {
            return null;
        }

        $collectionSlug = $segments[0];
        $itemSlug = $segments[count($segments) - 1];
        $table = 'wf_'.str_replace('-', '_', $collectionSlug);

        if (! \Schema::hasTable($table)) {
            return null;
        }

        $row = DB::table($table)
            ->where('field_data->slug', $itemSlug)
            ->first();

        if (! $row) {
            return null;
        }

        $item = (array) $row;
        $fieldData = $item['field_data'] ?? [];
        if (is_string($fieldData)) {
            $decoded = json_decode($fieldData, true);
            if (is_array($decoded)) {
                $fieldData = $decoded;
            }
        }

        return [
            'collectionSlug' => $collectionSlug,
            'itemSlug' => $itemSlug,
            'item' => $item,
            'fieldData' => is_array($fieldData) ? $fieldData : [],
        ];
    }

    private function normalizePath(string $path): string
    {
        $trimmed = '/'.trim($path, '/');
        return $trimmed === '/' ? '/' : rtrim($trimmed, '/');
    }
}
