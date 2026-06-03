<?php

namespace App\Services;

use App\Models\AbandonedCart;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsVisitor;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AnalyticsTracker
{
    private static ?bool $available = null;

    public function trackPageView(Request $request): void
    {
        $this->track('page_view', $request);
    }

    public function trackProductView(Request $request, Product $product): void
    {
        $this->track('product_view', $request, $product);
    }

    public function trackCartAction(Request $request, string $eventType, Product $product, int $quantity): void
    {
        $this->track($eventType, $request, $product, [
            'quantity' => $quantity,
            'price' => (float) $product->price,
        ]);
    }

    public function trackCheckoutStarted(Request $request, CartService $cart): void
    {
        $this->track('checkout_started', $request, metadata: [
            'item_count' => $cart->count(),
            'subtotal' => $cart->subtotal(),
        ]);

        $this->syncCart($request, $cart, 'checkout');
    }

    public function trackHeartbeat(Request $request, ?string $currentUrl = null): void
    {
        if (! $this->available() || $this->isAdminRequest($request) || $this->isBot((string) $request->userAgent())) {
            return;
        }

        try {
            $visitor = $this->visitor($request);
            $url = Str::limit($currentUrl ?: $request->headers->get('referer') ?: $request->fullUrl(), 1000, '');

            $visitor->forceFill([
                'last_url' => $url,
                'last_seen_at' => now(),
            ])->save();

            AnalyticsEvent::query()->create([
                'visitor_id' => $visitor->id,
                'user_id' => $request->user()?->id,
                'event_type' => 'visitor_heartbeat',
                'url' => $url,
                'referrer' => $request->headers->get('referer'),
                'metadata' => null,
                'occurred_at' => now(),
            ]);
        } catch (Throwable) {
            // Active visitor pings must never affect storefront performance.
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCheckoutContact(Request $request, CartService $cart, array $data): void
    {
        if (! $this->available()) {
            return;
        }

        try {
            $visitor = $this->visitor($request);

            $this->syncCart($request, $cart, 'checkout');

            $cartSnapshot = AbandonedCart::query()
                ->where('visitor_id', $visitor->id)
                ->whereIn('status', ['active', 'checkout'])
                ->latest('last_activity_at')
                ->first();

            $cartSnapshot?->update([
                'customer_name' => trim(($data['ad'] ?? '').' '.($data['soyad'] ?? '')) ?: null,
                'email' => $data['eposta'] ?? null,
                'phone' => $data['telefon'] ?? null,
                'last_activity_at' => now(),
            ]);
        } catch (Throwable) {
            // Checkout must never fail because analytics contact enrichment failed.
        }
    }

    public function syncCart(Request $request, CartService $cart, string $status = 'active'): void
    {
        if (! $this->available()) {
            return;
        }

        try {
            $visitor = $this->visitor($request);
            $lines = collect($cart->lines());

            if ($lines->isEmpty()) {
                AbandonedCart::query()
                    ->where('visitor_id', $visitor->id)
                    ->whereIn('status', ['active', 'checkout'])
                    ->update([
                        'status' => 'emptied',
                        'item_count' => 0,
                        'subtotal' => 0,
                        'last_activity_at' => now(),
                    ]);

                return;
            }

            $snapshot = [
                'visitor_id' => $visitor->id,
                'user_id' => $request->user()?->id,
                'item_count' => $cart->count(),
                'subtotal' => $cart->subtotal(),
                'status' => $status,
                'items' => $lines->map(fn (array $line) => [
                    'product_id' => $line['product']->id,
                    'name' => $line['product']->name,
                    'slug' => $line['product']->slug,
                    'quantity' => $line['quantity'],
                    'price' => (float) $line['product']->price,
                    'line_total' => $line['line_total'],
                ])->values()->all(),
                'last_activity_at' => now(),
            ];

            if ($status === 'checkout') {
                $snapshot['started_checkout_at'] = now();
            }

            $existing = AbandonedCart::query()
                ->where('visitor_id', $visitor->id)
                ->whereIn('status', ['active', 'checkout'])
                ->latest('last_activity_at')
                ->first();

            $existing
                ? $existing->update($snapshot)
                : AbandonedCart::query()->create($snapshot);
        } catch (Throwable) {
            // Analytics must never block storefront shopping.
        }
    }

    public function attachOrder(Request $request, Order $order): void
    {
        if (! $this->available()) {
            return;
        }

        try {
            $visitor = $this->visitor($request);

            $order->update([
                'analytics_visitor_id' => $visitor->id,
                'order_source' => $visitor->utm_source ?: $this->sourceFromReferrer($visitor->referrer),
                'order_medium' => $visitor->utm_medium,
                'order_campaign' => $visitor->utm_campaign,
                'landing_url' => $visitor->landing_url,
                'referrer_url' => $visitor->referrer,
            ]);

            AbandonedCart::query()
                ->where('visitor_id', $visitor->id)
                ->whereIn('status', ['active', 'checkout'])
                ->update([
                    'status' => 'converted',
                    'converted_order_id' => $order->id,
                    'last_activity_at' => now(),
                ]);

            $this->track('order_created', $request, order: $order, metadata: [
                'total' => (float) $order->total,
                'payment_method' => $order->payment_method,
                'source' => $order->order_source,
            ]);
        } catch (Throwable) {
            // Order creation must not fail because of analytics.
        }
    }

    public function track(
        string $eventType,
        Request $request,
        ?Product $product = null,
        array $metadata = [],
        ?Order $order = null,
    ): void {
        if (! $this->available()) {
            return;
        }

        try {
            $visitor = $this->visitor($request);

            AnalyticsEvent::query()->create([
                'visitor_id' => $visitor->id,
                'user_id' => $request->user()?->id,
                'product_id' => $product?->id,
                'order_id' => $order?->id,
                'event_type' => $eventType,
                'url' => $request->fullUrl(),
                'referrer' => $request->headers->get('referer'),
                'metadata' => $metadata ?: null,
                'occurred_at' => now(),
            ]);
        } catch (Throwable) {
            // Keep analytics best-effort and invisible to customers.
        }
    }

    public function visitor(Request $request): AnalyticsVisitor
    {
        $id = $request->session()->get('analytics_visitor_id');
        if (! $id) {
            $id = (string) Str::uuid();
            $request->session()->put('analytics_visitor_id', $id);
        }

        $visitor = AnalyticsVisitor::query()->firstOrNew(['id' => $id]);
        $firstSeen = $visitor->exists ? $visitor->first_seen_at : now();

        $visitor->fill([
            'user_id' => $request->user()?->id,
            'ip_hash' => $this->ipHash($request),
            'device_type' => $this->deviceType((string) $request->userAgent()),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'utm_source' => $visitor->utm_source ?: $request->query('utm_source'),
            'utm_medium' => $visitor->utm_medium ?: $request->query('utm_medium'),
            'utm_campaign' => $visitor->utm_campaign ?: $request->query('utm_campaign'),
            'referrer' => $visitor->referrer ?: $request->headers->get('referer'),
            'landing_url' => $visitor->landing_url ?: $request->fullUrl(),
            'last_url' => $request->fullUrl(),
            'first_seen_at' => $firstSeen,
            'last_seen_at' => now(),
        ])->save();

        return $visitor;
    }

    public function shouldTrackRequest(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return false;
        }

        if ($this->isAdminRequest($request)) {
            return false;
        }

        if ($request->is('yonetim', 'yonetim/*', 'admin', 'admin/*', 'storage/*', 'sitemap.xml', 'robots.txt')) {
            return false;
        }

        return ! $this->isBot((string) $request->userAgent());
    }

    private function available(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        try {
            return self::$available = Schema::hasTable('analytics_visitors')
                && Schema::hasTable('analytics_events')
                && Schema::hasTable('abandoned_carts');
        } catch (Throwable) {
            return self::$available = false;
        }
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }

    private function deviceType(string $userAgent): string
    {
        $agent = Str::lower($userAgent);

        return str_contains($agent, 'tablet') || str_contains($agent, 'ipad')
            ? 'tablet'
            : (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone') ? 'mobile' : 'desktop');
    }

    private function isBot(string $userAgent): bool
    {
        $agent = Str::lower($userAgent);

        return str_contains($agent, 'bot')
            || str_contains($agent, 'crawler')
            || str_contains($agent, 'spider')
            || str_contains($agent, 'preview')
            || str_contains($agent, 'headless')
            || str_contains($agent, 'lighthouse')
            || str_contains($agent, 'pagespeed')
            || str_contains($agent, 'uptime')
            || str_contains($agent, 'monitor');
    }

    private function isAdminRequest(Request $request): bool
    {
        return (bool) $request->user()?->is_admin;
    }

    private function sourceFromReferrer(?string $referrer): string
    {
        if (! $referrer) {
            return 'direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST) ?: '';

        return Str::limit(str_replace('www.', '', $host), 80, '');
    }
}
