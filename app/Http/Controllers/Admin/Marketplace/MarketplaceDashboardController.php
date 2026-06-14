<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceSyncLog;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceManager;
use App\Services\Marketplace\ProductReadinessChecker;
use Illuminate\View\View;

class MarketplaceDashboardController extends Controller
{
    public function __invoke(
        MarketplaceManager $manager,
        ProductReadinessChecker $readiness,
    ): View {
        $channels = $manager->channels();
        $activeProducts = Product::query()->where('is_active', true)->count();
        $marketplaceEnabled = Product::query()->where('is_active', true)->where('marketplace_enabled', true)->count();
        $withBarcode = Product::query()->where('is_active', true)->whereNotNull('barcode')->where('barcode', '!=', '')->count();

        $sample = Product::query()
            ->where('is_active', true)
            ->where('marketplace_enabled', true)
            ->with(['brand:id,name', 'categories:id,name'])
            ->latest()
            ->limit(300)
            ->get();

        $readinessSummary = $readiness->summarize($sample);

        return view('admin.marketplace.index', [
            'channels' => $channels,
            'stats' => [
                'active_products' => $activeProducts,
                'marketplace_enabled' => $marketplaceEnabled,
                'with_barcode' => $withBarcode,
                'ready' => $readinessSummary['ready'],
                'not_ready' => $readinessSummary['not_ready'],
                'listings' => MarketplaceListing::query()->count(),
                'published_listings' => MarketplaceListing::query()->where('status', 'published')->count(),
            ],
            'recentLogs' => MarketplaceSyncLog::query()->latest()->limit(12)->get(),
        ]);
    }
}
