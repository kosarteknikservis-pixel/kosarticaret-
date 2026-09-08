<?php

namespace App\Services\Pricing;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DataForSeoClient
{
    public function configured(): bool
    {
        return filled(config('services.dataforseo.login'))
            && filled(config('services.dataforseo.password'));
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    public function postMerchantProductsTask(array $task): array
    {
        $response = $this->request('POST', '/v3/merchant/google/products/task_post', [$task]);
        $taskResult = $response['tasks'][0] ?? null;

        if (! is_array($taskResult) || empty($taskResult['id'])) {
            throw new RuntimeException('DataForSEO görev oluşturulamadı.');
        }

        if (($taskResult['status_code'] ?? 0) >= 40000) {
            throw new RuntimeException((string) ($taskResult['status_message'] ?? 'DataForSEO görev hatası.'));
        }

        return $taskResult;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMerchantProductsTask(string $taskId): array
    {
        return $this->request('GET', '/v3/merchant/google/products/task_get/advanced/'.$taskId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('DataForSEO kimlik bilgileri eksik (DATAFORSEO_USERNAME / DATAFORSEO_PASSWORD).');
        }

        $login = (string) config('services.dataforseo.login');
        $password = (string) config('services.dataforseo.password');
        $base = rtrim((string) config('services.dataforseo.base_url', 'https://api.dataforseo.com'), '/');

        try {
            $pending = Http::timeout(60)
                ->withBasicAuth($login, $password)
                ->acceptJson()
                ->asJson();

            $response = strtoupper($method) === 'GET'
                ? $pending->get($base.$path)
                : $pending->post($base.$path, $payload);
        } catch (Throwable $e) {
            throw new RuntimeException('DataForSEO bağlantı hatası: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('DataForSEO HTTP '.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('DataForSEO geçersiz yanıt.');
        }

        if (($json['status_code'] ?? 0) >= 40000) {
            throw new RuntimeException((string) ($json['status_message'] ?? 'DataForSEO API hatası.'));
        }

        return $json;
    }
}
