<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $products = Product::query()->orderBy('stock')->get(['id', 'slug', 'name', 'stock']);
        $successfulOrders = Order::query()->where('payment_status', 'basarili');
        $monthlyRevenue = (float) (clone $successfulOrders)->where('created_at', '>=', $monthStart)->sum('total');
        $todayRevenue = (float) (clone $successfulOrders)->where('created_at', '>=', $today)->sum('total');
        $ordersToday = Order::query()->where('created_at', '>=', $today)->count();
        $averageOrder = (float) (clone $successfulOrders)->avg('total');
        $ordersThisMonth = Order::query()->where('created_at', '>=', $monthStart)->count();
        $ordersLastMonth = Order::query()
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();
        $orderGrowth = $ordersLastMonth > 0
            ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100)
            : ($ordersThisMonth > 0 ? 100 : 0);
        $unreadContactMessages = ContactMessage::query()->whereNull('read_at')->count();
        $pendingReviews = ProductReview::query()->where('approved', false)->count();

        $salesCharts = $this->salesCharts();
        $salesSeries = collect($salesCharts['month']['points']);
        $maxRevenue = max(1, (float) $salesSeries->max('revenue'));
        $statusBreakdown = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                return [
                    'status' => $row->status,
                    'label' => OrderStatus::label($row->status),
                    'total' => (int) $row->total,
                ];
            });

        $statusMax = max(1, (int) $statusBreakdown->max('total'));
        $topProducts = OrderItem::query()
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as sold_qty'), DB::raw('SUM(line_total) as revenue'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('sold_qty')
            ->take(5)
            ->get();

        $riskItems = collect([
            [
                'label' => 'Stokta olmayan ürün',
                'count' => $products->where('stock', 0)->count(),
                'tone' => 'danger',
                'route' => route('admin.products.index'),
            ],
            [
                'label' => 'Onay bekleyen yorum',
                'count' => $pendingReviews,
                'tone' => 'warning',
                'route' => route('admin.reviews.index'),
            ],
            [
                'label' => 'Okunmamış iletişim',
                'count' => $unreadContactMessages,
                'tone' => 'info',
                'route' => route('admin.contact-messages.index'),
            ],
        ])->filter(fn ($item) => $item['count'] > 0)->values();

        return view('admin.dashboard', [
            'productCount' => $products->count(),
            'orderCount' => Order::query()->count(),
            'lowStock' => $products->filter(fn ($p) => $p->stock > 0 && $p->stock <= 3),
            'outOfStock' => $products->filter(fn ($p) => $p->stock === 0),
            'recentOrders' => Order::query()->latest()->take(8)->get(),
            'pendingReviews' => $pendingReviews,
            'unreadContactMessages' => $unreadContactMessages,
            'monthlyRevenue' => $monthlyRevenue,
            'todayRevenue' => $todayRevenue,
            'ordersToday' => $ordersToday,
            'averageOrder' => $averageOrder,
            'ordersThisMonth' => $ordersThisMonth,
            'orderGrowth' => $orderGrowth,
            'salesSeries' => $salesSeries,
            'salesCharts' => $salesCharts,
            'maxRevenue' => $maxRevenue,
            'statusBreakdown' => $statusBreakdown,
            'statusMax' => $statusMax,
            'topProducts' => $topProducts,
            'riskItems' => $riskItems,
        ]);
    }

    /** @return array<string, array{label: string, points: array<int, array{label: string, revenue: float, orders: int}>}> */
    private function salesCharts(): array
    {
        $start = now()->subYear()->startOfDay();
        $orders = Order::query()
            ->where('payment_status', 'basarili')
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'total']);

        return [
            'week' => [
                'label' => 'Haftalık',
                'points' => $this->dailySalesPoints($orders, 6, 'D d.m'),
            ],
            'month' => [
                'label' => 'Aylık',
                'points' => $this->dailySalesPoints($orders, 29, 'd.m'),
            ],
            'six_months' => [
                'label' => '6 Aylık',
                'points' => $this->monthlySalesPoints($orders, 5),
            ],
            'year' => [
                'label' => 'Yıllık',
                'points' => $this->monthlySalesPoints($orders, 11),
            ],
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Order> $orders
     * @return array<int, array{label: string, revenue: float, orders: int}>
     */
    private function dailySalesPoints($orders, int $daysBack, string $format): array
    {
        $grouped = $orders->groupBy(fn (Order $order) => $order->created_at->format('Y-m-d'));

        return collect(range($daysBack, 0))->map(function (int $daysAgo) use ($grouped, $format) {
            $date = now()->subDays($daysAgo);
            $items = $grouped->get($date->format('Y-m-d'), collect());

            return [
                'label' => $date->translatedFormat($format),
                'revenue' => (float) $items->sum('total'),
                'orders' => $items->count(),
            ];
        })->values()->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Order> $orders
     * @return array<int, array{label: string, revenue: float, orders: int}>
     */
    private function monthlySalesPoints($orders, int $monthsBack): array
    {
        $grouped = $orders->groupBy(fn (Order $order) => $order->created_at->format('Y-m'));

        return collect(range($monthsBack, 0))->map(function (int $monthsAgo) use ($grouped) {
            $date = now()->subMonthsNoOverflow($monthsAgo);
            $items = $grouped->get($date->format('Y-m'), collect());

            return [
                'label' => $date->translatedFormat('M Y'),
                'revenue' => (float) $items->sum('total'),
                'orders' => $items->count(),
            ];
        })->values()->all();
    }
}
