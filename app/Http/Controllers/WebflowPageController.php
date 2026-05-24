<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WebflowPageController extends Controller
{
    public function home()
    {
        return $this->renderByPath('/');
    }

    public function show(string $webflowPath)
    {
        return $this->renderByPath('/'.$webflowPath);
    }

    private function renderByPath(string $path)
    {
        $pagesFile = storage_path('app/'.trim((string) config('webflow.export_root', 'webflow-export/current'), '/').'/site/pages.json');
        if (! File::exists($pagesFile)) {
            return view('welcome');
        }

        $payload = json_decode((string) File::get($pagesFile), true);
        $pages = $payload['pages'] ?? [];

        $normalizedPath = '/'.trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        $page = collect($pages)->first(function (array $item) use ($normalizedPath) {
            $publishedPath = '/'.trim((string) ($item['publishedPath'] ?? '/'), '/');

            return rtrim($publishedPath, '/') === rtrim($normalizedPath, '/');
        });

        if (! $page) {
            abort(404);
        }

        $view = $this->pageViewName($page);
        if (! view()->exists($view)) {
            abort(404);
        }

        $items = [];
        $collectionId = $page['collectionId'] ?? null;
        if (is_string($collectionId) && $collectionId !== '') {
            $manifestPath = storage_path('app/'.trim((string) config('webflow.export_root', 'webflow-export/current'), '/').'/manifest.json');
            if (File::exists($manifestPath)) {
                $manifest = json_decode((string) File::get($manifestPath), true);
                $collection = collect($manifest['collections'] ?? [])->firstWhere('id', $collectionId);
                if ($collection) {
                    $table = 'wf_'.Str::snake((string) ($collection['slug'] ?? ''));
                    if ($table !== 'wf_' && \Schema::hasTable($table)) {
                        $items = DB::table($table)->limit(50)->get()->map(fn ($row) => (array) $row)->all();
                        foreach ($items as &$item) {
                            $item['field_data'] = is_string($item['field_data'] ?? null)
                                ? (json_decode($item['field_data'], true) ?: [])
                                : ($item['field_data'] ?? []);
                        }
                    }
                }
            }
        }

        return view($view, [
            'page' => $page,
            'items' => $items,
        ]);
    }

    private function pageViewName(array $page): string
    {
        $publishedPath = trim((string) ($page['publishedPath'] ?? ''), '/');
        $base = $publishedPath === '' ? 'home' : str_replace('/', '.', Str::slug($publishedPath, '-'));
        $safe = str_replace('-', '_', $base);

        return "webflow.pages.{$safe}";
    }
}
