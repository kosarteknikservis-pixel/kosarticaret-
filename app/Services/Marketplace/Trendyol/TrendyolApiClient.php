<?php

namespace App\Services\Marketplace\Trendyol;

use App\Models\MarketplaceChannel;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class TrendyolApiClient
{
    private ?MarketplaceChannel $channel = null;

    private string $supplierId = '';

    private string $apiKey = '';

    private string $apiSecret = '';

    public function forChannel(MarketplaceChannel $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;
        $clone->supplierId = trim((string) data_get($channel->credentials, 'supplier_id', ''));
        $clone->apiKey = trim((string) data_get($channel->credentials, 'api_key', ''));
        $clone->apiSecret = trim((string) data_get($channel->credentials, 'api_secret', ''));

        if ($clone->supplierId === '' || $clone->apiKey === '' || $clone->apiSecret === '') {
            throw new InvalidArgumentException('Trendyol API bilgileri eksik.');
        }

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, query: $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->request('post', $path, body: $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        return $this->get($this->productPath(), ['page' => 0, 'size' => 1]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createProducts(array $payload): array
    {
        return $this->post($this->productPath(), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updatePriceAndInventory(array $payload): array
    {
        return $this->post($this->inventoryPath(), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchOrders(int $page, int $size, ?int $startDate = null, ?int $endDate = null): array
    {
        $query = [
            'page' => $page,
            'size' => $size,
            'orderByField' => 'PackageLastModifiedDate',
            'orderByDirection' => 'DESC',
        ];

        if ($startDate !== null) {
            $query['startDate'] = $startDate;
        }

        if ($endDate !== null) {
            $query['endDate'] = $endDate;
        }

        return $this->get($this->orderPath(), $query);
    }

    private function productPath(): string
    {
        return $this->integrationPrefix().'/product/sellers/'.$this->supplierId.'/products';
    }

    private function inventoryPath(): string
    {
        return $this->integrationPrefix().'/inventory/sellers/'.$this->supplierId.'/products/price-and-inventory';
    }

    private function orderPath(): string
    {
        return $this->integrationPrefix().'/order/sellers/'.$this->supplierId.'/orders';
    }

    private function integrationPrefix(): string
    {
        return (string) config('marketplace.trendyol.integration_prefix', '/integration');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('marketplace.trendyol.base_url', 'https://api.trendyol.com'), '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $query = [], array $body = []): array
    {
        $started = microtime(true);

        /** @var Response $response */
        $request = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode($this->apiKey.':'.$this->apiSecret),
            'User-Agent' => $this->supplierId.' - SelfIntegration',
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->acceptJson();

        $response = $method === 'get'
            ? $request->get($this->baseUrl().$path, $query)
            : $request->asJson()->post($this->baseUrl().$path, $body);

        $json = $response->json();

        if (! $response->successful()) {
            $message = is_array($json)
                ? (string) ($json['message'] ?? $json['errorMessage'] ?? $json['errors'][0]['message'] ?? 'Trendyol API hatası')
                : 'Trendyol API hatası';

            throw new TrendyolApiException($message, $response->status(), is_array($json) ? $json : null);
        }

        return is_array($json) ? $json : ['raw' => $json, 'duration_ms' => (int) round((microtime(true) - $started) * 1000)];
    }
}
