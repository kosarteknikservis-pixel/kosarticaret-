<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Jobs\Marketplace\ImportTrendyolOrdersJob;
use App\Jobs\Marketplace\PublishTrendyolListingJob;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\ProductListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceListingController extends Controller
{
    public function index(Request $request): View
    {
        $channelKey = $request->query('channel', 'trendyol');
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('q', ''));

        $listings = MarketplaceListing::query()
            ->with(['product:id,name,sku,barcode,price,stock,marketplace_enabled,is_active'])
            ->where('channel_key', $channelKey)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $readyProducts = Product::query()
            ->where('is_active', true)
            ->where('marketplace_enabled', true)
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            }))
            ->whereDoesntHave('marketplaceListings', fn ($q) => $q->where('channel_key', $channelKey)->whereIn('status', ['pending', 'published']))
            ->latest()
            ->limit(30)
            ->get(['id', 'name', 'sku', 'barcode', 'price', 'stock']);

        return view('admin.marketplace.listings.index', [
            'channels' => MarketplaceChannel::query()->orderBy('sort_order')->get(),
            'channelKey' => $channelKey,
            'listings' => $listings,
            'readyProducts' => $readyProducts,
            'statuses' => config('marketplace.listing_statuses', []),
            'filters' => [
                'status' => $status,
                'q' => $search,
            ],
        ]);
    }

    public function publish(Request $request, ProductListingService $listingService): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('queue', true)) {
            PublishTrendyolListingJob::dispatch((int) $data['product_id'], $data['channel_key']);

            return back()->with('success', 'Ürün Trendyol gönderim kuyruğuna eklendi.');
        }

        try {
            $result = $listingService->publish((int) $data['product_id'], $data['channel_key']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['publish' => $e->getMessage()]);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function bulkPublish(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'channel_key' => ['required', 'string', 'exists:marketplace_channels,key'],
        ]);

        foreach ($data['product_ids'] as $productId) {
            PublishTrendyolListingJob::dispatch((int) $productId, $data['channel_key']);
        }

        return back()->with('success', count($data['product_ids']).' ürün gönderim kuyruğuna eklendi.');
    }

    public function retry(MarketplaceListing $listing): RedirectResponse
    {
        PublishTrendyolListingJob::dispatch($listing->product_id, $listing->channel_key);

        return back()->with('success', 'Listeleme yeniden gönderim kuyruğuna eklendi.');
    }
}
