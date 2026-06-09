<?php

namespace App\Http\Middleware;

use App\Support\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPages
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PublicPageCache::shouldCache($request)) {
            return $next($request);
        }

        $key = PublicPageCache::key($request).':v'.PublicPageCache::versionSuffix();

        /** @var array{content: string, status: int, headers: array<string, string>}|null $cached */
        $cached = Cache::get($key);
        if ($cached !== null) {
            return response($cached['content'], $cached['status'], $cached['headers']);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && $this->isCacheableResponse($response)) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $this->cacheableHeaders($response),
            ], PublicPageCache::TTL_SECONDS);
        }

        return $response;
    }

    private function isCacheableResponse(Response $response): bool
    {
        if ($response->headers->has('Set-Cookie')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || $contentType === '';
    }

    /** @return array<string, string> */
    private function cacheableHeaders(Response $response): array
    {
        $headers = [];
        foreach (['Content-Type', 'Cache-Control'] as $name) {
            $value = $response->headers->get($name);
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
