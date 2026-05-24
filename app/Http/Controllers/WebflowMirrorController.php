<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class WebflowMirrorController extends Controller
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
        $normalized = $this->normalizePath($path);
        $htmlPath = $this->cachedHtmlPath($normalized);

        if (! File::exists($htmlPath)) {
            $fetched = $this->fetchAndCache($normalized, $htmlPath);
            if (! $fetched) {
                abort(404);
            }
        }

        $html = (string) File::get($htmlPath);
        $html = $this->rewriteInternalLinks($html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function rewriteInternalLinks(string $html): string
    {
        $replacements = [
            'https://www.deluxewindows.com' => '',
            'http://www.deluxewindows.com' => '',
            'https://deluxewindows.com' => '',
            'http://deluxewindows.com' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    private function normalizePath(string $path): string
    {
        $trimmed = '/'.trim($path, '/');
        return $trimmed === '/' ? '/' : rtrim($trimmed, '/');
    }

    private function cachedHtmlPath(string $normalizedPath): string
    {
        $root = storage_path('app/'.trim((string) config('webflow.export_root', 'webflow-export/current'), '/').'/html-cache');
        if ($normalizedPath === '/') {
            return $root.DIRECTORY_SEPARATOR.'index.html';
        }

        $segments = explode('/', trim($normalizedPath, '/'));
        $safeSegments = array_map(fn (string $segment) => preg_replace('/[^a-zA-Z0-9\\-_]/', '-', $segment), $segments);

        return $root.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $safeSegments).DIRECTORY_SEPARATOR.'index.html';
    }

    private function fetchAndCache(string $normalizedPath, string $htmlPath): bool
    {
        $url = 'https://www.deluxewindows.com'.$normalizedPath;
        $response = Http::timeout(25)
            ->retry(2, 200)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get($url);

        if (! $response->ok()) {
            return false;
        }

        $body = (string) $response->body();
        if (! str_contains(strtolower($body), '<html')) {
            return false;
        }

        File::ensureDirectoryExists(dirname($htmlPath));
        File::put($htmlPath, $body);

        return true;
    }
}
