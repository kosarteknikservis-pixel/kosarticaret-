<?php

namespace App\Services\Marketplace\Idefix;

use App\Models\MarketplaceChannel;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class IdefixApiClient
{
    private ?MarketplaceChannel $channel = null;

    private string $vendorId = '';

    private string $apiKey = '';

    private string $apiSecret = '';

    public function forChannel(MarketplaceChannel $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;
        $clone->vendorId = trim((string) data_get($channel->credentials, 'seller_id', ''));
        $clone->apiKey = trim((string) data_get($channel->credentials, 'api_key', ''));
        $clone->apiSecret = trim((string) data_get($channel->credentials, 'api_secret', ''));

        if ($clone->vendorId === '' || $clone->apiKey === '' || $clone->apiSecret === '') {
            throw new InvalidArgumentException('Idefix API bilgileri eksik (seller_id, api_key, api_secret).');
        }

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        // Hafif auth doğrulama: marka arama (Sumak) veya kategori ağacı.
        try {
            $brand = $this->searchBrandByName('Sumak');
            if ((int) ($brand['id'] ?? 0) > 0) {
                return $brand;
            }
        } catch (IdefixApiException $e) {
            $brandError = $e->getMessage();
        }

        $categories = $this->getProductCategories();
        $items = $categories['items'] ?? $categories;
        $roots = is_array($items) ? count($items) : 0;

        return [
            'ping' => 'categories',
            'roots' => $roots,
            'brand_error' => $brandError ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchBrandByName(string $title): array
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException('Marka adı boş.');
        }

        $last = null;
        foreach (['/pim/brand/by-name', '/brand/by-name'] as $path) {
            try {
                $row = $this->get($path, ['title' => $title]);
                if ((int) ($row['id'] ?? 0) > 0) {
                    return $row;
                }
                $last = $row;
            } catch (IdefixApiException $e) {
                $last = $e;
            }
        }

        if ($last instanceof IdefixApiException) {
            throw $last;
        }

        return is_array($last) ? $last : [];
    }

    /**
     * @return array<int, mixed>|array<string, mixed>
     */
    public function getProductCategories(): array
    {
        return $this->get('/pim/product-category');
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategoryAttributes(int $categoryId): array
    {
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('categoryId zorunlu.');
        }

        return $this->get('/pim/category-attribute/'.$categoryId);
    }

    /**
     * @param  array{products: list<array<string, mixed>>}  $body
     * @return array<string, mixed>
     */
    public function createProducts(array $body): array
    {
        return $this->post('/pim/pool/'.$this->vendorId.'/create', $body);
    }

    /**
     * Mevcut barkodlu ürünü günceller (kategori/marka/fiyat düzeltmesi).
     *
     * @param  array{products: list<array<string, mixed>>}  $body
     * @return array<string, mixed>
     */
    public function updateProducts(array $body): array
    {
        $last = null;
        foreach (['/pim/pool/'.$this->vendorId.'/update', '/pim/pool/'.$this->vendorId.'/products/update'] as $path) {
            try {
                return $this->post($path, $body);
            } catch (IdefixApiException $e) {
                $last = $e;
                if ($e->status !== 404) {
                    throw $e;
                }
            }
        }

        throw $last ?? new IdefixApiException('Idefix update endpoint bulunamadı.');
    }

    /**
     * @return array<string, mixed>
     */
    public function batchResult(string $batchRequestId): array
    {
        $batchRequestId = trim($batchRequestId);
        if ($batchRequestId === '') {
            throw new InvalidArgumentException('batchRequestId zorunlu.');
        }

        $last = null;
        $attempts = [
            ['/pim/pool/'.$this->vendorId.'/batch-result/'.$batchRequestId, []],
            ['/pim/pool/'.$this->vendorId.'/batch-result', ['batchRequestId' => $batchRequestId]],
        ];
        foreach ($attempts as [$path, $query]) {
            try {
                return $this->get($path, $query);
            } catch (IdefixApiException $e) {
                $last = $e;
                if ($e->status !== 404) {
                    throw $e;
                }
            }
        }

        throw $last ?? new IdefixApiException('Idefix batch-result alınamadı.');
    }

    /**
     * @return array<string, mixed>
     */
    public function listProducts(int $page = 1, int $limit = 20, ?string $barcode = null): array
    {
        $query = [
            'page' => max(1, $page),
            'limit' => max(1, min(100, $limit)),
        ];
        if ($barcode !== null && trim($barcode) !== '') {
            $query['barcode'] = trim($barcode);
        }

        return $this->get('/pim/pool/'.$this->vendorId.'/list', $query);
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
        $token = base64_encode($this->apiKey.':'.$this->apiSecret);

        return [
            'X-API-KEY' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function url(string $path): string
    {
        $base = rtrim((string) config('marketplace.idefix.base_url', 'https://merchantapi.idefix.com'), '/');

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $path): array
    {
        $json = $response->json();
        $data = is_array($json) ? $json : ['raw' => $response->body()];

        // Liste endpointleri kökte dizi dönebilir.
        if ($response->successful()) {
            if (array_is_list($data)) {
                return ['items' => $data];
            }

            return $data;
        }

        $message = (string) (
            data_get($data, 'message')
            ?? data_get($data, 'errorMessage')
            ?? data_get($data, 'error')
            ?? data_get($data, 'title')
            ?? ''
        );
        if ($message === '') {
            $message = 'Idefix API hatası HTTP '.$response->status().' @ '.$path;
        }
        $snippet = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (is_string($snippet) && $snippet !== '' && $snippet !== '[]' && $snippet !== '{}') {
            $message .= ' | '.mb_substr($snippet, 0, 500);
        }

        throw new IdefixApiException($message, $response->status(), $data);
    }
}
