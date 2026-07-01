<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleIndexingClient
{
    private const TOKEN_CACHE_KEY = 'seo.google_indexing.access_token';

    /** @return array{ok: bool, status?: int, error?: string, skipped?: bool} */
    public function submit(string $url, string $type = 'URL_UPDATED'): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => true, 'skipped' => true];
        }

        $token = $this->accessToken();
        if ($token === null) {
            return ['ok' => false, 'error' => 'Google Indexing access token alınamadı.'];
        }

        try {
            $response = Http::timeout(20)
                ->withToken($token)
                ->acceptJson()
                ->post('https://indexing.googleapis.com/v3/urlNotifications:publish', [
                    'url' => $url,
                    'type' => $type,
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'status' => $response->status()];
            }

            return [
                'ok' => false,
                'status' => $response->status(),
                'error' => Str::limit($response->body(), 500),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function isEnabled(): bool
    {
        if (! config('seo.indexing.google_enabled')) {
            return false;
        }

        $path = (string) config('seo.indexing.google_credentials');

        return $path !== '' && is_readable($path);
    }

    private function accessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3300, function (): ?string {
            $credentials = $this->credentials();
            if ($credentials === null) {
                return null;
            }

            $jwt = $this->buildJwt($credentials);
            if ($jwt === null) {
                return null;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('access_token');
        });
    }

    /** @return array<string, mixed>|null */
    private function credentials(): ?array
    {
        $path = (string) config('seo.indexing.google_credentials');
        if ($path === '' || ! is_readable($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    /** @param  array<string, mixed>  $credentials */
    private function buildJwt(array $credentials): ?string
    {
        $clientEmail = (string) ($credentials['client_email'] ?? '');
        $privateKey = (string) ($credentials['private_key'] ?? '');
        if ($clientEmail === '' || $privateKey === '') {
            return null;
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time(),
            'exp' => time() + 3600,
        ], JSON_THROW_ON_ERROR));

        $input = $header.'.'.$claims;
        $signature = '';
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            return null;
        }

        openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256);

        return $input.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
