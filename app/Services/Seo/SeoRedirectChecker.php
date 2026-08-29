<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Http;

class SeoRedirectChecker
{
    /**
     * @param  list<array{from: string, expected_path: string}>  $checks
     * @return array{checked_at: string, base_url: string, results: list<array<string, mixed>>}
     */
    public function check(string $baseUrl, array $checks, int $timeoutSeconds = 15): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $results = [];

        foreach ($checks as $check) {
            $fromPath = '/'.ltrim($check['from'], '/');
            $url = $baseUrl.$fromPath;
            $expectedPath = '/'.trim($check['expected_path'], '/');

            $trace = $this->followRedirects($url, $timeoutSeconds);
            $finalPath = parse_url($trace['final_url'], PHP_URL_PATH) ?: '';
            $finalPath = '/'.trim($finalPath, '/');
            $ok = $this->isOk($trace, $expectedPath);

            $results[] = [
                'from' => $fromPath,
                'expected_path' => $expectedPath,
                'final_url' => $trace['final_url'],
                'final_path' => $finalPath,
                'hop_count' => count($trace['hops']),
                'hops' => $trace['hops'],
                'status_code' => $trace['status_code'],
                'ok' => $ok,
                'issue' => $ok ? null : $this->describeIssue($trace, $expectedPath),
            ];
        }

        return [
            'checked_at' => now()->toIso8601String(),
            'base_url' => $baseUrl,
            'results' => $results,
        ];
    }

    /**
     * @return array{final_url: string, status_code: int, hops: list<array{status: int, location: string}>}
     */
    private function followRedirects(string $url, int $timeoutSeconds): array
    {
        $hops = [];
        $current = $url;
        $status = 0;

        for ($i = 0; $i < 10; $i++) {
            $response = Http::timeout($timeoutSeconds)
                ->withHeaders(['User-Agent' => 'KosarSeoRedirectCheck/1.0'])
                ->withoutRedirecting()
                ->get($current);

            $status = $response->status();

            if ($status >= 300 && $status < 400) {
                $location = (string) $response->header('Location');
                $hops[] = ['status' => $status, 'location' => $location];
                $current = $this->resolveLocation($current, $location);
                continue;
            }

            break;
        }

        return [
            'final_url' => $current,
            'status_code' => $status,
            'hops' => $hops,
        ];
    }

    private function resolveLocation(string $current, string $location): string
    {
        if ($location === '') {
            return $current;
        }

        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return $location;
        }

        $parts = parse_url($current);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $path = $parts['path'] ?? '/';
        $dir = str_contains($path, '/') ? substr($path, 0, (int) strrpos($path, '/')) : '';

        return "{$scheme}://{$host}{$port}{$dir}/{$location}";
    }

    /**
     * @param  array{final_url: string, status_code: int, hops: list<array{status: int, location: string}>}  $trace
     */
    private function isOk(array $trace, string $expectedPath): bool
    {
        $finalPath = '/'.trim(parse_url($trace['final_url'], PHP_URL_PATH) ?: '', '/');

        if ($finalPath !== $expectedPath || $trace['status_code'] !== 200) {
            return false;
        }

        if (count($trace['hops']) !== 1) {
            return false;
        }

        $hopStatus = (int) ($trace['hops'][0]['status'] ?? 0);

        return $hopStatus >= 301 && $hopStatus < 400;
    }

    /**
     * @param  array{final_url: string, status_code: int, hops: list<array{status: int, location: string}>}  $trace
     */
    private function describeIssue(array $trace, string $expectedPath): ?string
    {
        $finalPath = '/'.trim(parse_url($trace['final_url'], PHP_URL_PATH) ?: '', '/');

        if (count($trace['hops']) === 0 && $trace['status_code'] === 200) {
            return '301 yok — legacy URL dogrudan 200 donuyor';
        }

        if (count($trace['hops']) > 1) {
            return 'Redirect zinciri ('.count($trace['hops']).' atlama)';
        }

        if ($trace['status_code'] === 404) {
            return '404 — hedef bulunamadi';
        }

        if ($finalPath !== $expectedPath) {
            return "Yanlis hedef: {$finalPath}";
        }

        if ($trace['status_code'] >= 500) {
            return 'Sunucu hatasi: '.$trace['status_code'];
        }

        return null;
    }
}
