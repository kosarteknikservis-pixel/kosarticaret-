<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Jobs\Marketplace\ImportTrendyolOrdersJob;
use App\Models\MarketplaceChannel;
use App\Models\Order;
use App\Services\Marketplace\Trendyol\TrendyolOrderImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketplaceOrderSyncController extends Controller
{
    public function index(): View
    {
        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();

        $recentMarketplaceOrders = Order::query()
            ->where('sales_channel', 'trendyol')
            ->latest()
            ->limit(15)
            ->get(['id', 'order_number', 'customer_name', 'total', 'status', 'external_order_id', 'created_at']);

        return view('admin.marketplace.orders.index', [
            'channel' => $channel,
            'recentOrders' => $recentMarketplaceOrders,
            'totalTrendyolOrders' => Order::query()->where('sales_channel', 'trendyol')->count(),
        ]);
    }

    public function import(Request $request, TrendyolOrderImporter $importer): RedirectResponse
    {
        if ($request->boolean('queue', true)) {
            ImportTrendyolOrdersJob::dispatch();

            return back()->with('success', 'Trendyol sipariş import işi kuyruğa eklendi.');
        }

        try {
            $result = $importer->import();
        } catch (\Throwable $e) {
            return back()->withErrors(['import' => $e->getMessage()]);
        }

        return back()->with('success', sprintf(
            'Import tamamlandı: %d yeni, %d güncellendi, %d atlandı.',
            $result['imported'],
            $result['updated'],
            $result['skipped'],
        ));
    }
}
