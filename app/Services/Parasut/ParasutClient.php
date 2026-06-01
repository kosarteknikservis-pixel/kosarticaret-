<?php

namespace App\Services\Parasut;

use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ParasutClient
{
    private string $baseUrl = 'https://api.parasut.com';

    public function authenticateWithPassword(): void
    {
        $basePayload = [
            'grant_type' => 'password',
            'client_id' => $this->setting('parasut_client_id'),
            'client_secret' => $this->setting('parasut_client_secret'),
            'username' => $this->setting('parasut_username'),
            'password' => $this->setting('parasut_password'),
        ];

        $redirectUri = $this->setting('parasut_redirect_uri');
        $payloads = $redirectUri !== ''
            ? [array_merge($basePayload, ['redirect_uri' => $redirectUri])]
            : [
                $basePayload,
                array_merge($basePayload, ['redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob']),
                array_merge($basePayload, ['redirect_uri' => route('admin.integrations.parasut.callback')]),
            ];

        $lastError = null;
        foreach ($payloads as $payload) {
            $response = Http::asForm()->post($this->baseUrl.'/oauth/token', $payload);

            if ($response->successful()) {
                $this->storeToken($response->json());

                return;
            }

            $lastError = $response->json();
        }

        throw new \RuntimeException($this->errorMessage($lastError, 'Token alınamadı.'));
    }

    public function post(string $endpoint, array $payload): array
    {
        $response = $this->request()->post($this->apiUrl($endpoint), $payload);

        if ($response->status() === 401 && $this->setting('parasut_refresh_token') !== '') {
            $this->refreshToken();
            $response = $this->request()->post($this->apiUrl($endpoint), $payload);
        }

        if ($response->failed()) {
            throw new \RuntimeException($this->errorMessage($response->json(), 'Paraşüt API isteği başarısız.'));
        }

        return $response->json();
    }

    public function isConfigured(): bool
    {
        return $this->setting('parasut_enabled') === '1'
            && $this->setting('parasut_client_id') !== ''
            && $this->setting('parasut_client_secret') !== ''
            && $this->setting('parasut_company_id') !== ''
            && $this->setting('parasut_access_token') !== '';
    }

    private function refreshToken(): void
    {
        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->setting('parasut_refresh_token'),
            'client_id' => $this->setting('parasut_client_id'),
            'client_secret' => $this->setting('parasut_client_secret'),
        ];

        $redirectUri = $this->setting('parasut_redirect_uri');
        if ($redirectUri !== '') {
            $payload['redirect_uri'] = $redirectUri;
        }

        $response = Http::asForm()->post($this->baseUrl.'/oauth/token', $payload);

        if ($response->failed()) {
            throw new \RuntimeException($this->errorMessage($response->json(), 'Paraşüt token yenilenemedi.'));
        }

        $this->storeToken($response->json());
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Paraşüt entegrasyonu bağlı değil veya eksik yapılandırılmış.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($this->setting('parasut_access_token'));
    }

    private function apiUrl(string $endpoint): string
    {
        return $this->baseUrl.'/v4/'.$this->setting('parasut_company_id').'/'.ltrim($endpoint, '/');
    }

    private function storeToken(array $payload): void
    {
        SiteSetting::set('parasut_access_token', (string) ($payload['access_token'] ?? ''));
        SiteSetting::set('parasut_refresh_token', (string) ($payload['refresh_token'] ?? $this->setting('parasut_refresh_token')));
        SiteSetting::set('parasut_token_expires_at', (string) now()->addSeconds((int) ($payload['expires_in'] ?? 7200))->timestamp);
    }

    private function setting(string $key): string
    {
        return trim((string) SiteSetting::get($key, ''));
    }

    private function errorMessage(?array $payload, string $fallback): string
    {
        $error = $payload['error_description'] ?? $payload['error'] ?? null;

        if (isset($payload['errors'][0]['detail'])) {
            $error = $payload['errors'][0]['detail'];
        } elseif (isset($payload['errors'][0]['title'])) {
            $error = $payload['errors'][0]['title'];
        }

        if ($error === 'invalid_grant' || str_contains((string) $error, 'authorization grant')) {
            return $fallback.' Paraşüt kullanıcı adı/şifre, Client ID/Secret veya Paraşüt uygulamasındaki Redirect URI ayarı uyuşmuyor olabilir. Paraşüt uygulamasındaki redirect değeri varsa paneldeki Redirect URI alanına birebir yazın.';
        }

        return $error ? $fallback.' '.$error : $fallback;
    }
}
