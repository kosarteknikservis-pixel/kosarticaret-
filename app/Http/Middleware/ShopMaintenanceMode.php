<?php

namespace App\Http\Middleware;

use App\Support\ShopMaintenance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopMaintenanceMode
{
    /** @var list<string> */
    private const BYPASS_ROUTE_PREFIXES = [
        'admin.',
        'storage.public',
        'favicon',
        'sitemap',
        'robots',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! ShopMaintenance::isEnabled() || $this->shouldBypass($request)) {
            return $next($request);
        }

        return response()
            ->view('shop.maintenance', [
                'title' => ShopMaintenance::title(),
                'message' => ShopMaintenance::message(),
            ], 503)
            ->header('Retry-After', '3600');
    }

    private function shouldBypass(Request $request): bool
    {
        if ($request->is('yonetim', 'yonetim/*')) {
            return true;
        }

        if ($request->is('odeme/paytr/callback', 'odeme/iyzico/callback', 'up')) {
            return true;
        }

        foreach (self::BYPASS_ROUTE_PREFIXES as $prefix) {
            if ($request->routeIs($prefix.'*') || $request->routeIs($prefix)) {
                return true;
            }
        }

        return $request->user()?->isAdmin() === true;
    }
}
