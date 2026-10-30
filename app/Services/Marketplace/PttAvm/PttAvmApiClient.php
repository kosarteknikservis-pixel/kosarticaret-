<?php

namespace App\Services\Marketplace\PttAvm;

use App\Models\MarketplaceChannel;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PttAvmApiClient
{
    private ?MarketplaceChannel $channel = null;

    private string $apiKey = '';

    private string $accessToken = '';

    public function forChannel(MarketplaceChannel $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;
        $clone->apiKey = trim((string) data_get($channel->credentials, 'api_key', ''));
        $clone->accessToken = trim((string) data_get($channel->credentials, 'access_token', ''));

        if ($clone->apiKey === '' || $clone->accessToken === '') {
            throw new InvalidArgumentException('PTT AVM API bilgileri eksik (api_key, access_token).');
        }

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        return $this->getMainCategories();
    }

    /**
     * @return array<string, mixed>
     */
    public function getMainCategories(): array
    {
        return $this->get('/api/v1/categories/main');
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategoryTree(): array
    {
        return $this->get('/api/v1/categories/category-tree');
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategory(int $categoryId): array
    {
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('categoryId zorunlu.');
        }

        return $this->get('/api/v1/categories/'.$categoryId);
    }

    /**
     * @param  array{items: list<array<string, mixed>>}  $body
     * @return array<string, mixed>
     */
    public function upsertProducts(array $body): array
    {
        return $this->post('/api/v1/products/upsert', $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function trackingResult(string $trackingId): array
    {
        $trackingId = trim($trackingId);
        if ($trackingId === '') {
            throw new InvalidArgumentException('trackingId zorunlu.');
        }

        return $this->post('/api/v1/products/tracking-result/'.$trackingId, []);
    }

    /**
     * @return array<string, mixed>
     */
    public function listProducts(?string $barcode = null, int $page = 1, int $limit = 20): array
    {
        $query = [
            'page' => max(1, $page),
            'limit' => max(1, min(100, $limit)),
        ];
        if ($barcode !== null && trim($barcode) !== '') {
            $query['barcode'] = trim($barcode);
        }

        return $this->get('/api/v1/products', $query);
    }

    /**
     * @return list<array{id: int, name: string, path: string}>
     */
    public function searchLeafCategories(string $query, int $limit = 40): array
    {
        $needle = mb_strtolower(trim($query), 'UTF-8');
        if ($needle === '') {
            return [];
        }

        $tree = $this->getCategoryTree();
        $roots = $tree['category_tree']
            ?? $tree['categories']
            ?? data_get($tree, 'data')
            ?? [];
        if (! is_array($roots)) {
            return [];
        }

        $matches = [];
        $this->walkLeaves($roots, [], function (int $id, string $name, string $path) use ($needle, $limit, &$matches): void {
            if (count($matches) >= $limit) {
                return;
            }
            $hay = mb_strtolower($name.' '.$path, 'UTF-8');
            if (! str_contains($hay, $needle)) {
                return;
            }
            $matches[] = ['id' => $id, 'name' => $name, 'path' => $path];
        });

        return $matches;
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $nodes
     * @param  list<string>  $path
     * @param  callable(int, string, string): void  $onLeaf
     */
    private function walkLeaves(array $nodes, array $path, callable $onLeaf): void
    {
        $list = array_is_list($nodes) ? $nodes : [$nodes];
        foreach ($list as $node) {
            if (! is_array($node)) {
                continue;
            }
            $id = (int) ($node['id'] ?? 0);
            $name = trim((string) ($node['name'] ?? ''));
            $children = $node['children'] ?? $node['subCategories'] ?? null;
            $nextPath = $name !== '' ? array_merge($path, [$name]) : $path;

            if (is_array($children) && $children !== []) {
                $this->walkLeaves($children, $nextPath, $onLeaf);
                continue;
            }

            if ($id > 0 && $name !== '') {
                $onLeaf($id, $name, implode(' > ', $nextPath));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $response = Http::timeout(60)
            ->withHeaders($this->headers())
            ->get($this->url($path), $query);

        return $this->decode($response, $path);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body): array
    {
        $response = Http::timeout(90)
            ->withHeaders($this->headers())
            ->post($this->url($path), $body);

        return $this->decode($response, $path);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Api-Key' => $this->apiKey,
            'Access-Token' => $this->accessToken,
            'access-token' => $this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Correlation-Id' => (string) Str::uuid(),
        ];
    }

    private function url(string $path): string
    {
        $base = rtrim((string) config('marketplace.pttavm.base_url', 'https://integration-api.pttavm.com'), '/');

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $path): array
    {
        $json = $response->json();
        $data = is_array($json) ? $json : ['raw' => $response->body()];

        if ($response->successful()) {
            if (array_is_list($data)) {
                return ['items' => $data];
            }

            return $data;
        }

        $message = (string) (
            data_get($data, 'message')
            ?? data_get($data, 'error.error_message')
            ?? data_get($data, 'error_message')
            ?? data_get($data, 'error')
            ?? data_get($data, 'title')
            ?? ''
        );
        if ($message === '') {
            $message = 'PTT AVM API hatası HTTP '.$response->status().' @ '.$path;
        }
        $snippet = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (is_string($snippet) && $snippet !== '' && $snippet !== '[]' && $snippet !== '{}') {
            $message .= ' | '.mb_substr($snippet, 0, 500);
        }

        throw new PttAvmApiException($message, $response->status(), $data);
    }
}
