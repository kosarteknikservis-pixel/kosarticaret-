<?php

namespace App\Support;

use App\Models\SiteSetting;

class PageSpeedAuditUrl
{
    public static function base(): ?string
    {
        foreach (self::candidates() as $candidate) {
            if (self::isPublic($candidate)) {
                return rtrim($candidate, '/');
            }
        }

        return null;
    }

    public static function isConfigured(): bool
    {
        return self::base() !== null;
    }

    public static function isPublic(?string $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $parts = parse_url(trim($url));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'], true)) {
            return false;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! self::isPrivateIp($host);
        }

        return true;
    }

    public static function toPath(string $path): ?string
    {
        $base = self::base();
        if ($base === null) {
            return null;
        }

        $path = '/'.ltrim($path, '/');

        return $path === '/' ? $base.'/' : $base.$path;
    }

    /**
     * @param  mixed  $parameters
     */
    public static function route(string $name, mixed $parameters = []): ?string
    {
        return self::toPath(route($name, $parameters, absolute: false));
    }

    /** @return list<string> */
    private static function candidates(): array
    {
        return array_values(array_filter([
            trim((string) SiteSetting::get('pagespeed_audit_base_url', '')),
            trim((string) config('kosar.pagespeed.audit_base_url', '')),
            trim((string) config('kosar.url', '')),
            trim((string) config('app.url', '')),
        ]));
    }

    private static function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
