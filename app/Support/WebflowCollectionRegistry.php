<?php

declare(strict_types=1);

namespace App\Support;

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
            $title = Str::title(str_replace('-', ' ', $slug));

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

