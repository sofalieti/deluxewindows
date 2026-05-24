<?php

namespace App\Services\Webflow;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WebflowClient
{
    public function __construct(
        private readonly ?string $token = null,
    ) {
    }

    public function listSites(): array
    {
        $response = $this->request()->get('/sites')->throw()->json();

        return $response['sites'] ?? [];
    }

    public function listPages(string $siteId): array
    {
        return $this->paginate("/sites/{$siteId}/pages", 'pages');
    }

    public function getPageDom(string $pageId): array
    {
        $response = $this->request()
            ->get("/pages/{$pageId}/dom", ['limit' => 100, 'offset' => 0]);

        if ($response->failed()) {
            return [];
        }

        $payload = $response->json();
        $nodes = $payload['nodes'] ?? [];
        $pagination = $payload['pagination'] ?? null;

        if (! is_array($pagination)) {
            return $nodes;
        }

        $total = (int) ($pagination['total'] ?? count($nodes));
        $offset = (int) ($pagination['offset'] ?? 0);
        $limit = max((int) ($pagination['limit'] ?? 100), 1);

        while (($offset + $limit) < $total) {
            $offset += $limit;
            $part = $this->request()
                ->get("/pages/{$pageId}/dom", ['limit' => $limit, 'offset' => $offset]);

            if ($part->failed()) {
                break;
            }

            $partPayload = $part->json();
            $nodes = array_merge($nodes, $partPayload['nodes'] ?? []);
            $limit = max((int) (($partPayload['pagination']['limit'] ?? $limit)), 1);
        }

        return $nodes;
    }

    public function listCollections(string $siteId): array
    {
        $response = $this->request()->get("/sites/{$siteId}/collections")->throw()->json();

        return $response['collections'] ?? [];
    }

    public function getCollection(string $collectionId): array
    {
        return $this->request()->get("/collections/{$collectionId}")->throw()->json();
    }

    public function listCollectionItems(string $collectionId): array
    {
        return $this->paginate("/collections/{$collectionId}/items", 'items');
    }

    private function paginate(string $path, string $key): array
    {
        $all = [];
        $offset = 0;
        $limit = 100;

        while (true) {
            $response = $this->request()
                ->get($path, ['limit' => $limit, 'offset' => $offset])
                ->throw()
                ->json();

            $chunk = $response[$key] ?? [];
            $all = array_merge($all, $chunk);

            $pagination = $response['pagination'] ?? null;
            if (! is_array($pagination)) {
                break;
            }

            $total = (int) ($pagination['total'] ?? count($all));
            $limit = max((int) ($pagination['limit'] ?? $limit), 1);
            $offset = (int) ($pagination['offset'] ?? $offset);

            if (($offset + $limit) >= $total) {
                break;
            }

            $offset += $limit;
        }

        return $all;
    }

    private function request(): PendingRequest
    {
        $token = $this->token ?: config('webflow.api_token');
        if (! $token) {
            throw new RuntimeException('Webflow token is missing. Set WEBFLOW_API_TOKEN or pass --token.');
        }

        return Http::baseUrl((string) config('webflow.api_base_url'))
            ->withToken($token)
            ->acceptJson()
            ->retry(3, 250);
    }
}
