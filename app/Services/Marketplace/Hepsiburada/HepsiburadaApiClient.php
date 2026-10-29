<?php

namespace App\Services\Marketplace\Hepsiburada;

use App\Models\MarketplaceChannel;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HepsiburadaApiClient
{
    private ?MarketplaceChannel $channel = null;

    private string $merchantId = '';

    private string $username = '';

    private string $password = '';

    public function forChannel(MarketplaceChannel $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;
        $clone->merchantId = trim((string) data_get($channel->credentials, 'merchant_id', ''));
        $clone->username = trim((string) data_get($channel->credentials, 'username', ''));
        $clone->password = trim((string) data_get($channel->credentials, 'password', ''));

        if ($clone->merchantId === '' || $clone->username === '' || $clone->password === '') {
            throw new InvalidArgumentException('Hepsiburada API bilgileri eksik (merchant_id, username, password).');
        }

        return $clone;
    }

    public function merchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        return $this->getCategories(0, 1);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategories(int $page = 0, int $size = 2000): array
    {
        return $this->get('/product/api/categories/get-all-categories', [
            'leaf' => 'true',
            'status' => 'ACTIVE',
            'available' => 'true',
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Leaf kategoriler içinde ada göre arama (sunucu tarafı filtre yoksa yerel filtre).
     *
     * @return list<array{categoryId: int, name: string, paths: string}>
     */
    public function searchLeafCategories(string $query, int $limit = 25): array
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return [];
        }

        $response = $this->getCategories(0, 2000);
        $rows = $response['data'] ?? [];
        if (! is_array($rows)) {
            return [];
        }

        $matches = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = (string) ($row['name'] ?? '');
            $paths = is_array($row['paths'] ?? null)
                ? implode(' > ', array_map('strval', $row['paths']))
                : (string) ($row['paths'] ?? '');
            $haystack = mb_strtolower($name.' '.$paths);

            if (! str_contains($haystack, $needle)) {
                continue;
            }

            $categoryId = (int) ($row['categoryId'] ?? $row['id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }

            $matches[] = [
                'categoryId' => $categoryId,
                'name' => $name,
                'paths' => $paths,
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategoryAttributes(int $categoryId): array
    {
        return $this->get('/product/api/categories/'.$categoryId.'/attributes', [
            'version' => 2,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return array<string, mixed>
     */
    public function importProducts(array $products): array
    {
        if ($products === []) {
            throw new InvalidArgumentException('Hepsiburada import: ürün listesi boş.');
        }

        foreach ($products as $index => $product) {
            if (! is_array($product) || empty($product['categoryId'])) {
                throw new InvalidArgumentException('Hepsiburada import: satır '.$index.' categoryId zorunlu.');
            }
            $sku = data_get($product, 'attributes.merchantSku');
            if (! is_string($sku) || trim($sku) === '') {
                throw new InvalidArgumentException('Hepsiburada import: satır '.$index.' merchantSku zorunlu.');
            }
            if (empty($product['merchant'])) {
                $products[$index]['merchant'] = $this->merchantId;
            }
        }

        $json = json_encode(array_values($products), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new InvalidArgumentException('Hepsiburada import: JSON üretilemedi.');
        }

        return $this->postMultipart('/product/api/products/import', 'file', $json, 'integrator.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductStatus(string $trackingId): array
    {
        if (trim($trackingId) === '') {
            throw new InvalidArgumentException('trackingId zorunlu.');
        }

        return $this->get('/product/api/products/status/'.rawurlencode($trackingId));
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, query: $query);
    }

    /**
     * @return array<string, mixed>
     */
    private function postMultipart(string $path, string $field, string $contents, string $filename): array
    {
        $this->assertReady();

        /** @var Response $response */
        $response = Http::withBasicAuth($this->merchantId, $this->password)
            ->withHeaders([
                'User-Agent' => $this->username,
                'Accept' => 'application/json',
            ])
            ->timeout(60)
            ->attach($field, $contents, $filename)
            ->post($this->baseUrl().$path);

        return $this->decodeResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $query = [], array $body = []): array
    {
        $this->assertReady();

        /** @var Response $response */
        $request = Http::withBasicAuth($this->merchantId, $this->password)
            ->withHeaders([
                'User-Agent' => $this->username,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(45)
            ->acceptJson();

        $response = $method === 'get'
            ? $request->get($this->baseUrl().$path, $query)
            : $request->asJson()->post($this->baseUrl().$path, $body);

        return $this->decodeResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        $json = $response->json();
        $payload = is_array($json) ? $json : ['raw' => $json];

        if (! $response->successful()) {
            throw new HepsiburadaApiException(
                $this->extractErrorMessage($payload, 'Hepsiburada API hatası'),
                $response->status(),
                $payload,
            );
        }

        if (array_key_exists('success', $payload) && $payload['success'] === false) {
            $code = (int) ($payload['code'] ?? 0);
            // Bazı boş-liste kodları soft fail; çağıran yorumlar.
            if (! in_array($code, [4008, 4014], true)) {
                throw new HepsiburadaApiException(
                    $this->extractErrorMessage($payload, 'Hepsiburada işlem başarısız'),
                    $response->status(),
                    $payload,
                );
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractErrorMessage(array $payload, string $fallback): string
    {
        $message = $payload['message'] ?? $payload['errorMessage'] ?? null;
        if (is_string($message) && trim($message) !== '') {
            return $message;
        }

        if (isset($payload['code'])) {
            return $fallback.' (kod '.(string) $payload['code'].')';
        }

        return $fallback;
    }

    private function assertReady(): void
    {
        if ($this->merchantId === '' || $this->username === '' || $this->password === '') {
            throw new InvalidArgumentException('Hepsiburada API client kanal ile başlatılmadı.');
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('marketplace.hepsiburada.mpop_base_url', 'https://mpop.hepsiburada.com'), '/');
    }
}
