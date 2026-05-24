<?php

namespace App\Services\Webflow;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebflowSyncService
{
    public function __construct(
        private readonly WebflowClient $client,
    ) {
    }

    public function sync(string $siteId, bool $withDom = true): array
    {
        $disk = Storage::disk((string) config('webflow.export_disk', 'webflow_repo'));
        $root = trim((string) config('webflow.export_root', 'current'), '/');

        $pages = $this->client->listPages($siteId);
        $collections = $this->client->listCollections($siteId);

        $disk->deleteDirectory($root);
        $disk->makeDirectory($root.'/site');
        $disk->makeDirectory($root.'/collections');

        $pagesForExport = [];
        foreach ($pages as $page) {
            $pageDom = [];
            if ($withDom) {
                $pageDom = $this->client->getPageDom((string) $page['id']);
            }

            $page['domNodes'] = $pageDom;
            $pagesForExport[] = $page;
        }

        $disk->put(
            $root.'/site/pages.json',
            json_encode(['pages' => $pagesForExport], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $collectionSummaries = [];
        foreach ($collections as $collection) {
            $collectionId = (string) $collection['id'];
            $safeSlug = $this->safeSlug((string) ($collection['slug'] ?? $collectionId), $collectionId);

            $collectionDir = $root.'/collections/'.$safeSlug;
            $disk->makeDirectory($collectionDir);

            $schema = $this->client->getCollection($collectionId);
            $items = $this->client->listCollectionItems($collectionId);

            $disk->put(
                $collectionDir.'/schema.json',
                json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
            $disk->put(
                $collectionDir.'/items.json',
                json_encode(['items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            $collectionSummaries[] = [
                'id' => $collectionId,
                'slug' => $safeSlug,
                'displayName' => $collection['displayName'] ?? null,
                'itemsCount' => count($items),
                'fieldsCount' => count($schema['fields'] ?? []),
            ];
        }

        $manifest = [
            'siteId' => $siteId,
            'generatedAt' => now()->toIso8601String(),
            'pagesCount' => count($pagesForExport),
            'collectionsCount' => count($collectionSummaries),
            'collections' => $collectionSummaries,
        ];

        $disk->put(
            $root.'/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return [
            'root' => $root,
            'manifest' => $manifest,
            'pages' => $pagesForExport,
            'collections' => $collectionSummaries,
        ];
    }

    private function safeSlug(string $slug, string $fallbackId): string
    {
        $safe = Str::slug($slug);

        return $safe !== '' ? $safe : 'collection-'.$fallbackId;
    }
}
