<?php

declare(strict_types=1);

namespace App\Models\Webflow\Concerns;

use App\Support\WebflowReferenceRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait ResolvesWebflowReferences
{
    public function webflowReferenceFields(): array
    {
        return WebflowReferenceRegistry::forModel($this);
    }

    public function webflowReference(string $fieldSlug): ?Model
    {
        $meta = $this->webflowReferenceFields()[$fieldSlug] ?? null;
        if (! is_array($meta) || ($meta['type'] ?? null) !== 'reference') {
            return null;
        }

        $targetModel = $meta['target_model'] ?? null;
        if (! is_string($targetModel) || ! class_exists($targetModel)) {
            return null;
        }

        $targetWebflowId = $this->extractReferenceId($fieldSlug);
        if ($targetWebflowId === null) {
            return null;
        }

        return $targetModel::query()->where('webflow_item_id', $targetWebflowId)->first();
    }

    public function webflowReferences(string $fieldSlug): EloquentCollection
    {
        $meta = $this->webflowReferenceFields()[$fieldSlug] ?? null;
        if (! is_array($meta) || ($meta['type'] ?? null) !== 'multi_reference') {
            return new EloquentCollection();
        }

        $targetModel = $meta['target_model'] ?? null;
        if (! is_string($targetModel) || ! class_exists($targetModel)) {
            return new EloquentCollection();
        }

        $targetIds = $this->extractMultiReferenceIds($fieldSlug);
        if ($targetIds === []) {
            return new EloquentCollection();
        }

        $items = $targetModel::query()
            ->whereIn('webflow_item_id', $targetIds)
            ->get()
            ->keyBy('webflow_item_id');

        $ordered = [];
        foreach ($targetIds as $id) {
            $model = $items->get($id);
            if ($model !== null) {
                $ordered[] = $model;
            }
        }

        return new EloquentCollection($ordered);
    }

    public function webflowRelated(string $fieldSlug): Model|EloquentCollection|null
    {
        $meta = $this->webflowReferenceFields()[$fieldSlug] ?? null;
        if (! is_array($meta)) {
            return null;
        }

        return ($meta['type'] ?? null) === 'reference'
            ? $this->webflowReference($fieldSlug)
            : $this->webflowReferences($fieldSlug);
    }

    private function extractReferenceId(string $fieldSlug): ?string
    {
        $value = $this->fieldDataValue($fieldSlug);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    private function extractMultiReferenceIds(string $fieldSlug): array
    {
        $value = $this->fieldDataValue($fieldSlug);
        if (! is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $ids[] = $candidate;
            }
        }

        return array_values(array_unique($ids));
    }

    private function fieldDataValue(string $fieldSlug): mixed
    {
        $fieldData = $this->getAttribute('field_data');
        if (! is_array($fieldData)) {
            return null;
        }

        return array_key_exists($fieldSlug, $fieldData) ? $fieldData[$fieldSlug] : null;
    }
}

