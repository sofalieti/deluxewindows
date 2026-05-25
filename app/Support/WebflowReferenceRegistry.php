<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebflowReferenceRegistry
{
    private static ?array $cache = null;

    public static function forModel(Model|string $model): array
    {
        $slug = self::slugFromModel($model);
        if ($slug === null) {
            return [];
        }

        return self::all()[$slug] ?? [];
    }

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $disk = Storage::disk((string) config('webflow.export_disk', 'webflow_repo'));
        $root = trim((string) config('webflow.export_root', 'current'), '/');
        $manifestPath = $root.'/manifest.json';

        if (! $disk->exists($manifestPath)) {
            self::$cache = [];

            return self::$cache;
        }

        $manifest = json_decode((string) $disk->get($manifestPath), true);
        if (! is_array($manifest)) {
            self::$cache = [];

            return self::$cache;
        }

        $idToSlug = [];
        foreach (($manifest['collections'] ?? []) as $collection) {
            $id = (string) ($collection['id'] ?? '');
            $slug = (string) ($collection['slug'] ?? '');

            if ($id !== '' && $slug !== '') {
                $idToSlug[$id] = $slug;
            }
        }

        $result = [];
        foreach (array_values($idToSlug) as $slug) {
            $schemaPath = $root."/collections/{$slug}/schema.json";
            if (! $disk->exists($schemaPath)) {
                continue;
            }

            $schema = json_decode((string) $disk->get($schemaPath), true);
            if (! is_array($schema)) {
                continue;
            }

            $fieldMap = [];
            foreach (($schema['fields'] ?? []) as $field) {
                $type = (string) ($field['type'] ?? '');
                if (! in_array($type, ['Reference', 'MultiReference'], true)) {
                    continue;
                }

                $fieldSlug = (string) ($field['slug'] ?? '');
                if ($fieldSlug === '') {
                    continue;
                }

                $targetCollectionId = (string) data_get($field, 'validations.collectionId', '');
                $targetSlug = $idToSlug[$targetCollectionId] ?? null;
                $targetModel = is_string($targetSlug) ? self::modelFromSlug($targetSlug) : null;

                $fieldMap[$fieldSlug] = [
                    'type' => $type === 'Reference' ? 'reference' : 'multi_reference',
                    'target_collection_id' => $targetCollectionId !== '' ? $targetCollectionId : null,
                    'target_slug' => $targetSlug,
                    'target_model' => $targetModel,
                ];
            }

            if ($fieldMap !== []) {
                $result[$slug] = $fieldMap;
            }
        }

        self::$cache = $result;

        return self::$cache;
    }

    public static function modelFromSlug(string $slug): ?string
    {
        $class = 'App\\Models\\Webflow\\'.Str::studly($slug).'WebflowItem';

        return class_exists($class) ? $class : null;
    }

    public static function slugFromModel(Model|string $model): ?string
    {
        $instance = is_string($model) && class_exists($model) ? new $model() : $model;
        if (! $instance instanceof Model) {
            return null;
        }

        $table = (string) $instance->getTable();
        if (! str_starts_with($table, 'wf_')) {
            return null;
        }

        return str_replace('_', '-', substr($table, 3));
    }
}

