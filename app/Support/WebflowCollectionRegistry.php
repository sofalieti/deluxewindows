<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebflowCollectionRegistry
{
    public static function all(): array
    {
        $modelsPath = app_path('Models/Webflow');
        if (! is_dir($modelsPath)) {
            return [];
        }

        $files = glob($modelsPath.'/*.php') ?: [];
        $collections = [];
        $displayNames = self::displayNamesBySlug();

        foreach ($files as $file) {
            $class = 'App\\Models\\Webflow\\'.basename($file, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $instance = app($class);
            if (! method_exists($instance, 'getTable')) {
                continue;
            }

            $table = (string) $instance->getTable();
            if (! str_starts_with($table, 'wf_')) {
                continue;
            }

            $slug = str_replace('_', '-', substr($table, 3));
            $title = $displayNames[$slug] ?? Str::title(str_replace('-', ' ', $slug));

            $collections[$slug] = [
                'slug' => $slug,
                'title' => $title,
                'model' => $class,
                'table' => $table,
            ];
        }

        ksort($collections);

        return array_values($collections);
    }

    private static function displayNamesBySlug(): array
    {
        $disk = Storage::disk((string) config('webflow.export_disk', 'webflow_repo'));
        $root = trim((string) config('webflow.export_root', 'current'), '/');
        $manifestPath = $root.'/manifest.json';

        if (! $disk->exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) $disk->get($manifestPath), true);
        if (! is_array($manifest)) {
            return [];
        }

        $result = [];
        foreach (($manifest['collections'] ?? []) as $collection) {
            $slug = (string) ($collection['slug'] ?? '');
            $displayName = (string) ($collection['displayName'] ?? '');
            if ($slug !== '' && $displayName !== '') {
                $result[$slug] = $displayName;
            }
        }

        return $result;
    }

    public static function find(string $slug): ?array
    {
        foreach (self::all() as $collection) {
            if ($collection['slug'] === $slug) {
                return $collection;
            }
        }

        return null;
    }
}

