<?php

declare(strict_types=1);

namespace App\Services\Webflow;

use App\Models\Webflow\WebflowReferenceLink;
use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowReferenceRegistry;
use Illuminate\Database\Eloquent\Model;

class WebflowReferenceLinkSyncService
{
    public function sync(): array
    {
        $created = 0;
        $byCollection = [];

        foreach (WebflowCollectionRegistry::all() as $collection) {
            $sourceSlug = (string) ($collection['slug'] ?? '');
            $sourceModelClass = (string) ($collection['model'] ?? '');
            if ($sourceSlug === '' || $sourceModelClass === '' || ! class_exists($sourceModelClass)) {
                continue;
            }

            $fieldMap = WebflowReferenceRegistry::all()[$sourceSlug] ?? [];
            if (! is_array($fieldMap) || $fieldMap === []) {
                continue;
            }

            $sourceItems = $sourceModelClass::query()->get();
            if ($sourceItems->isEmpty()) {
                continue;
            }

            $sourceIds = [];
            $rows = [];

            foreach ($sourceItems as $sourceItem) {
                if (! $sourceItem instanceof Model) {
                    continue;
                }

                $sourceIds[] = (int) $sourceItem->getKey();
                foreach ($fieldMap as $fieldSlug => $meta) {
                    if (! is_array($meta)) {
                        continue;
                    }

                    $relationType = (string) ($meta['type'] ?? '');
                    $targetSlug = (string) ($meta['target_slug'] ?? '');
                    $targetModel = (string) ($meta['target_model'] ?? '');
                    if ($targetSlug === '' || $targetModel === '' || ! class_exists($targetModel)) {
                        continue;
                    }

                    $targetWebflowIds = $this->extractTargetIds($sourceItem, $fieldSlug, $relationType);
                    if ($targetWebflowIds === []) {
                        continue;
                    }

                    $targetLocalIds = $targetModel::query()
                        ->whereIn('webflow_item_id', $targetWebflowIds)
                        ->pluck('id', 'webflow_item_id');

                    foreach ($targetWebflowIds as $targetWebflowId) {
                        $rows[] = [
                            'source_collection_slug' => $sourceSlug,
                            'source_id' => (int) $sourceItem->getKey(),
                            'source_webflow_item_id' => (string) ($sourceItem->getAttribute('webflow_item_id') ?: ''),
                            'field_slug' => (string) $fieldSlug,
                            'relation_type' => $relationType,
                            'target_collection_slug' => $targetSlug,
                            'target_webflow_item_id' => $targetWebflowId,
                            'target_id' => $targetLocalIds[$targetWebflowId] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            if ($sourceIds !== []) {
                WebflowReferenceLink::query()
                    ->where('source_collection_slug', $sourceSlug)
                    ->whereIn('source_id', array_values(array_unique($sourceIds)))
                    ->delete();
            }

            if ($rows !== []) {
                WebflowReferenceLink::query()->insert($rows);
            }

            $created += count($rows);
            $byCollection[$sourceSlug] = count($rows);
        }

        return [
            'created' => $created,
            'by_collection' => $byCollection,
        ];
    }

    private function extractTargetIds(Model $sourceItem, string $fieldSlug, string $relationType): array
    {
        $fieldData = $sourceItem->getAttribute('field_data');
        if (! is_array($fieldData) || ! array_key_exists($fieldSlug, $fieldData)) {
            return [];
        }

        $value = $fieldData[$fieldSlug];

        if ($relationType === 'reference') {
            return is_string($value) && $value !== '' ? [$value] : [];
        }

        if ($relationType !== 'multi_reference' || ! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->values()
            ->unique()
            ->all();
    }
}

