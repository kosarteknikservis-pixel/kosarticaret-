<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsVisitor;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->startOfDay();
        $activeSince = now()->subMinutes(5);
        $period = $request->query('period', 'today');
        $periods = $this->periods();
        if (! array_key_exists($period, $periods)) {
            $period = 'today';
        }
        $periodStart = $periods[$period]['start'];
        $periodLabel = $periods[$period]['label'];

        $events = AnalyticsEvent::query();

        $topViewedProducts = AnalyticsEvent::query()
            ->where('event_type', 'product_view')
            ->where('occurred_at', '>=', $periodStart)
            ->whereNotNull('product_id')
            ->join('products', 'products.id', '=', 'analytics_events.product_id')
            ->select('products.id', 'products.name', 'products.slug', DB::raw('COUNT(*) as views'))
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('views')
            ->take(8)
            ->get();

        $topCartProducts = AnalyticsEvent::query()
            ->where('event_type', 'cart_add')
            ->where('occurred_at', '>=', $periodStart)
            ->whereNotNull('product_id')
            ->join('products', 'products.id', '=', 'analytics_events.product_id')
            ->select('products.id', 'products.name', 'products.slug', DB::raw('COUNT(*) as cart_adds'))
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('cart_adds')
            ->take(8)
            ->get();

        $sourceOrders = Order::query()
            ->where('created_at', '>=', $periodStart)
            ->selectRaw("COALESCE(NULLIF(order_source, ''), 'direct') as source, COUNT(*) as orders, SUM(total) as revenue")
            ->groupBy('source')
            ->orderByDesc('orders')
            ->take(8)
            ->get();

        $sourceVisitors = AnalyticsVisitor::query()
            ->where('first_seen_at', '>=', $periodStart)
            ->selectRaw("COALESCE(NULLIF(utm_source, ''), 'direct') as source, COUNT(*) as visitors")
            ->groupBy('source')
            ->pluck('visitors', 'source');

        $sourcePerformance = $sourceOrders->map(function ($source) use ($sourceVisitors) {
            $visitors = (int) ($sourceVisitors[$source->source] ?? 0);

            return [
                'source' => $source->source,
                'visitors' => $visitors,
                'orders' => (int) $source->orders,
                'revenue' => (float) $source->revenue,
                'conversion' => $visitors > 0 ? round(((int) $source->orders / $visitors) * 100, 1) : null,
            ];
        });

        $productViews = AnalyticsEvent::query()
            ->where('event_type', 'product_view')
            ->where('occurred_at', '>=', $periodStart)
            ->whereNotNull('product_id')
            ->select('product_id', DB::raw('COUNT(*) as views'))
            ->groupBy('product_id');

        $productCartAdds = AnalyticsEvent::query()
            ->where('event_type', 'cart_add')
            ->where('occurred_at', '>=', $periodStart)
            ->whereNotNull('product_id')
            ->select('product_id', DB::raw('COUNT(*) as cart_adds'))
            ->groupBy('product_id');

        $productConversions = DB::query()
            ->fromSub($productViews, 'views')
            ->leftJoinSub($productCartAdds, 'cart_adds', 'cart_adds.product_id', '=', 'views.product_id')
            ->join('products', 'products.id', '=', 'views.product_id')
            ->select(
                'products.name',
                'products.slug',
                'views.views',
                DB::raw('COALESCE(cart_adds.cart_adds, 0) as cart_adds'),
            )
            ->orderByDesc('views.views')
            ->take(8)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'slug' => $row->slug,
                'views' => (int) $row->views,
                'cart_adds' => (int) $row->cart_adds,
                'rate' => (int) $row->views > 0 ? round(((int) $row->cart_adds / (int) $row->views) * 100, 1) : 0,
            ]);

        $abandonedCarts = AbandonedCart::query()
            ->with('visitor')
            ->whereIn('status', ['active', 'checkout'])
            ->where('item_count', '>', 0)
            ->where('last_activity_at', '>=', $periodStart)
            ->latest('last_activity_at')
            ->take(12)
            ->get();

        $recentEvents = AnalyticsEvent::query()
            ->with(['visitor:id,device_type,last_url,last_seen_at', 'product:id,name,slug', 'order:id,order_number,total'])
            ->latest('occurred_at')
            ->take(80)
            ->get()
            ->map(function (AnalyticsEvent $event) {
                $event->display_label = $this->eventLabel($event->event_type);

                return $event;
            });

        $recentVisitorSummaries = $this->visitorSummaries($recentEvents);

        $activeVisitorList = AnalyticsVisitor::query()
            ->where('last_seen_at', '>=', $activeSince)
            ->latest('last_seen_at')
            ->take(12)
            ->get(['id', 'device_type', 'utm_source', 'last_url', 'last_seen_at']);

        return view('admin.analytics.index', [
            'activeVisitors' => AnalyticsVisitor::query()->where('last_seen_at', '>=', $activeSince)->count(),
            'todayVisitors' => AnalyticsVisitor::query()->where('first_seen_at', '>=', $today)->count(),
            'periodVisitors' => AnalyticsVisitor::query()->where('first_seen_at', '>=', $periodStart)->count(),
            'todayPageViews' => (clone $events)->where('event_type', 'page_view')->where('occurred_at', '>=', $today)->count(),
            'periodPageViews' => AnalyticsEvent::query()->where('event_type', 'page_view')->where('occurred_at', '>=', $periodStart)->count(),
            'periodProductViews' => AnalyticsEvent::query()->where('event_type', 'product_view')->where('occurred_at', '>=', $periodStart)->count(),
            'periodCartAdds' => AnalyticsEvent::query()->where('event_type', 'cart_add')->where('occurred_at', '>=', $periodStart)->count(),
            'checkoutStarts' => AnalyticsEvent::query()->where('event_type', 'checkout_started')->where('occurred_at', '>=', $periodStart)->count(),
            'ordersThisPeriod' => Order::query()->where('created_at', '>=', $periodStart)->count(),
            'abandonedCartCount' => AbandonedCart::query()
                ->whereIn('status', ['active', 'checkout'])
                ->where('item_count', '>', 0)
                ->where('last_activity_at', '>=', $periodStart)
                ->count(),
            'topViewedProducts' => $topViewedProducts,
            'topCartProducts' => $topCartProducts,
            'sourceOrders' => $sourceOrders,
            'sourcePerformance' => $sourcePerformance,
            'productConversions' => $productConversions,
            'abandonedCarts' => $abandonedCarts,
            'recentEvents' => $recentEvents,
            'recentVisitorSummaries' => $recentVisitorSummaries,
            'activeVisitorList' => $activeVisitorList,
            'period' => $period,
            'periods' => $periods,
            'periodLabel' => $periodLabel,
        ]);
    }

    public function showVisitor(AnalyticsVisitor $visitor): View
    {
        $orders = Order::query()
            ->where('analytics_visitor_id', $visitor->id)
            ->latest()
            ->get();

        $carts = AbandonedCart::query()
            ->where('visitor_id', $visitor->id)
            ->latest('last_activity_at')
            ->get();

        return view('admin.analytics.visitor', [
            'visitor' => $visitor,
            'orders' => $orders,
            'carts' => $carts,
        ]);
    }

    /**
     * @return array<string, array{label: string, start: \Illuminate\Support\Carbon}>
     */
    private function periods(): array
    {
        return [
            'today' => ['label' => 'Günlük', 'start' => now()->startOfDay()],
            'week' => ['label' => 'Haftalık', 'start' => now()->subDays(6)->startOfDay()],
            'month' => ['label' => 'Aylık', 'start' => now()->startOfMonth()],
            'year' => ['label' => 'Yıllık', 'start' => now()->startOfYear()],
        ];
    }

    private function visitorSummaries(Collection $events): Collection
    {
        return $events
            ->filter(fn (AnalyticsEvent $event) => $event->visitor_id)
            ->groupBy('visitor_id')
            ->map(function (Collection $visitorEvents) {
                /** @var AnalyticsEvent $latest */
                $latest = $visitorEvents->sortByDesc('occurred_at')->first();
                $cartAdds = $visitorEvents->where('event_type', 'cart_add')->count();
                $productViews = $visitorEvents->where('event_type', 'product_view')->count();
                $checkoutStarted = $visitorEvents->contains('event_type', 'checkout_started');
                $orderCreated = $visitorEvents->contains('event_type', 'order_created');

                return [
                    'visitor' => $latest->visitor,
                    'latest' => $latest,
                    'last_label' => $this->eventLabel($latest->event_type),
                    'cart_adds' => $cartAdds,
                    'product_views' => $productViews,
                    'checkout_started' => $checkoutStarted,
                    'order_created' => $orderCreated,
                    'summary' => collect([
                        $productViews > 0 ? $productViews.' ürün görüntüleme' : null,
                        $cartAdds > 0 ? $cartAdds.' sepete ekleme' : null,
                        $checkoutStarted ? 'ödeme adımına geçti' : null,
                        $orderCreated ? 'sipariş oluşturdu' : null,
                    ])->filter()->implode(' · '),
                ];
            })
            ->sortByDesc(fn (array $summary) => $summary['latest']->occurred_at)
            ->take(12)
            ->values();
    }

    private function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'page_view' => 'Sayfa görüntüleme',
            'product_view' => 'Ürün görüntüleme',
            'cart_add' => 'Sepete ekleme',
            'cart_update' => 'Sepet güncelleme',
            'cart_remove' => 'Sepetten çıkarma',
            'checkout_started' => 'Ödeme adımına geçti',
            'order_created' => 'Sipariş oluşturuldu',
            default => str_replace('_', ' ', $eventType),
        };
    }
}
