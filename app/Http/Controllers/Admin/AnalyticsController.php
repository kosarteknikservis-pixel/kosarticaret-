<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsVisitor;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $activeSince = now()->subMinutes(5);
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $events = AnalyticsEvent::query();

        $topViewedProducts = AnalyticsEvent::query()
            ->where('event_type', 'product_view')
            ->where('occurred_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('product_id')
            ->join('products', 'products.id', '=', 'analytics_events.product_id')
            ->select('products.id', 'products.name', 'products.slug', DB::raw('COUNT(*) as views'))
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('views')
            ->take(8)
            ->get();

        $topCartProducts = AnalyticsEvent::query()
            ->where('event_type', 'cart_add')
            ->where('occurred_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('product_id')
            ->join('products', 'products.id', '=', 'analytics_events.product_id')
            ->select('products.id', 'products.name', 'products.slug', DB::raw('COUNT(*) as cart_adds'))
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('cart_adds')
            ->take(8)
            ->get();

        $sourceOrders = Order::query()
            ->where('created_at', '>=', $monthStart)
            ->selectRaw("COALESCE(NULLIF(order_source, ''), 'direct') as source, COUNT(*) as orders, SUM(total) as revenue")
            ->groupBy('source')
            ->orderByDesc('orders')
            ->take(8)
            ->get();

        $sourceVisitors = AnalyticsVisitor::query()
            ->where('first_seen_at', '>=', $monthStart)
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
            ->where('occurred_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('product_id')
            ->select('product_id', DB::raw('COUNT(*) as views'))
            ->groupBy('product_id');

        $productCartAdds = AnalyticsEvent::query()
            ->where('event_type', 'cart_add')
            ->where('occurred_at', '>=', $thirtyDaysAgo)
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
            ->latest('last_activity_at')
            ->take(12)
            ->get();

        $recentEvents = AnalyticsEvent::query()
            ->with(['visitor:id,device_type,last_url,last_seen_at', 'product:id,name,slug', 'order:id,order_number,total'])
            ->latest('occurred_at')
            ->take(30)
            ->get();

        $activeVisitorList = AnalyticsVisitor::query()
            ->where('last_seen_at', '>=', $activeSince)
            ->latest('last_seen_at')
            ->take(12)
            ->get(['id', 'device_type', 'utm_source', 'last_url', 'last_seen_at']);

        return view('admin.analytics.index', [
            'activeVisitors' => AnalyticsVisitor::query()->where('last_seen_at', '>=', $activeSince)->count(),
            'todayVisitors' => AnalyticsVisitor::query()->where('first_seen_at', '>=', $today)->count(),
            'monthVisitors' => AnalyticsVisitor::query()->where('first_seen_at', '>=', $monthStart)->count(),
            'todayPageViews' => (clone $events)->where('event_type', 'page_view')->where('occurred_at', '>=', $today)->count(),
            'monthPageViews' => AnalyticsEvent::query()->where('event_type', 'page_view')->where('occurred_at', '>=', $monthStart)->count(),
            'todayProductViews' => AnalyticsEvent::query()->where('event_type', 'product_view')->where('occurred_at', '>=', $today)->count(),
            'todayCartAdds' => AnalyticsEvent::query()->where('event_type', 'cart_add')->where('occurred_at', '>=', $today)->count(),
            'checkoutStarts' => AnalyticsEvent::query()->where('event_type', 'checkout_started')->where('occurred_at', '>=', $monthStart)->count(),
            'ordersThisMonth' => Order::query()->where('created_at', '>=', $monthStart)->count(),
            'abandonedCartCount' => AbandonedCart::query()->whereIn('status', ['active', 'checkout'])->where('item_count', '>', 0)->count(),
            'topViewedProducts' => $topViewedProducts,
            'topCartProducts' => $topCartProducts,
            'sourceOrders' => $sourceOrders,
            'sourcePerformance' => $sourcePerformance,
            'productConversions' => $productConversions,
            'abandonedCarts' => $abandonedCarts,
            'recentEvents' => $recentEvents,
            'activeVisitorList' => $activeVisitorList,
        ]);
    }

    public function showVisitor(AnalyticsVisitor $visitor): View
    {
        $visitor->load(['events' => fn ($query) => $query
            ->with(['product:id,name,slug', 'order:id,order_number,total'])
            ->latest('occurred_at')
            ->take(120)]);

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
            'timeline' => $this->timeline($visitor->events),
        ]);
    }

    private function timeline(Collection $events): Collection
    {
        return $events->map(function (AnalyticsEvent $event) {
            return [
                'label' => match ($event->event_type) {
                    'page_view' => 'Sayfa görüntüleme',
                    'product_view' => 'Ürün görüntüleme',
                    'cart_add' => 'Sepete ekleme',
                    'cart_update' => 'Sepet güncelleme',
                    'cart_remove' => 'Sepetten çıkarma',
                    'checkout_started' => 'Checkout başlangıcı',
                    'order_created' => 'Sipariş oluşturuldu',
                    default => str_replace('_', ' ', $event->event_type),
                },
                'event' => $event,
            ];
        });
    }
}
