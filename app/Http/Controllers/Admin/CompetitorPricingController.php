<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScanGoogleMarketPriceJob;
use App\Models\CompetitorOffer;
use App\Models\CompetitorPriceRule;
use App\Models\MarketPriceScan;
use App\Models\Product;
use App\Services\Pricing\CompetitorPricingService;
use App\Services\Pricing\DataForSeoClient;
use App\Services\Pricing\GoogleShoppingMarketScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitorPricingController extends Controller
{
    public function __construct(
        private CompetitorPricingService $pricing,
        private GoogleShoppingMarketScanner $scanner,
        private DataForSeoClient $dataForSeo,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $offers = CompetitorOffer::query()
            ->with(['product:id,name,slug,sku,price,compare_at_price,is_active'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('competitor_name', 'like', '%'.$q.'%')
                        ->orWhere('competitor_url', 'like', '%'.$q.'%')
                        ->orWhereHas('product', function ($p) use ($q) {
                            $p->where('name', 'like', '%'.$q.'%')
                                ->orWhere('sku', 'like', '%'.$q.'%');
                        });
                });
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $rule = CompetitorPriceRule::activeRule();
        $rows = $offers->getCollection()->map(function (CompetitorOffer $offer) use ($rule) {
            $product = $offer->product;
            $suggestion = $product
                ? $this->pricing->suggestionForProduct($product->loadMissing(['competitorOffers', 'marketPriceScan']), $rule)
                : null;

            return compact('offer', 'product', 'suggestion');
        });

        return view('admin.competitor-pricing.index', [
            'offers' => $offers,
            'rows' => $rows,
            'rule' => $rule,
            'q' => $q,
            'stats' => [
                'total' => CompetitorOffer::query()->count(),
                'approved' => CompetitorOffer::query()->where('match_status', CompetitorOffer::STATUS_APPROVED)->count(),
                'pending' => CompetitorOffer::query()->where('match_status', CompetitorOffer::STATUS_PENDING)->count(),
                'market_pending' => MarketPriceScan::query()->where('status', MarketPriceScan::STATUS_PENDING)->count(),
            ],
            'dataforseoReady' => $this->dataForSeo->configured(),
        ]);
    }

    public function market(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');

        $products = Product::query()
            ->where('is_active', true)
            ->with('marketPriceScan')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('sku', 'like', '%'.$q.'%')
                        ->orWhere('barcode', 'like', '%'.$q.'%');
                });
            })
            ->when($filter === 'pending', fn ($query) => $query->whereHas('marketPriceScan', fn ($s) => $s->where('status', MarketPriceScan::STATUS_PENDING)))
            ->when($filter === 'approved', fn ($query) => $query->whereHas('marketPriceScan', fn ($s) => $s->where('status', MarketPriceScan::STATUS_APPROVED)))
            ->when($filter === 'no_results', fn ($query) => $query->whereHas('marketPriceScan', fn ($s) => $s->where('status', MarketPriceScan::STATUS_NO_RESULTS)))
            ->when($filter === 'missing', fn ($query) => $query->whereDoesntHave('marketPriceScan'))
            ->when($filter === 'expensive', function ($query) {
                $query->whereHas('marketPriceScan', function ($s) {
                    $s->whereNotNull('google_min_price')
                        ->whereColumn('products.price', '>', 'market_price_scans.google_min_price');
                });
            })
            ->orderByDesc(
                MarketPriceScan::query()
                    ->select('last_scanned_at')
                    ->whereColumn('market_price_scans.product_id', 'products.id')
                    ->limit(1)
            )
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $rule = CompetitorPriceRule::activeRule();
        $rows = $products->getCollection()->map(function (Product $product) use ($rule) {
            $scan = $product->marketPriceScan;
            $suggestion = $this->pricing->suggestionForProduct(
                $product->loadMissing(['competitorOffers', 'marketPriceScan']),
                $rule
            );

            return compact('product', 'scan', 'suggestion');
        });

        return view('admin.competitor-pricing.market', [
            'products' => $products,
            'rows' => $rows,
            'rule' => $rule,
            'q' => $q,
            'filter' => $filter,
            'dataforseoReady' => $this->dataForSeo->configured(),
            'stats' => [
                'scanned' => MarketPriceScan::query()->count(),
                'pending' => MarketPriceScan::query()->where('status', MarketPriceScan::STATUS_PENDING)->count(),
                'approved' => MarketPriceScan::query()->where('status', MarketPriceScan::STATUS_APPROVED)->count(),
                'no_results' => MarketPriceScan::query()->where('status', MarketPriceScan::STATUS_NO_RESULTS)->count(),
                'missing' => Product::query()->where('is_active', true)->whereDoesntHave('marketPriceScan')->count(),
                'queued' => \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('payload', 'like', '%ScanGoogleMarketPriceJob%')
                    ->count(),
                'failed' => \Illuminate\Support\Facades\DB::table('failed_jobs')
                    ->where('payload', 'like', '%ScanGoogleMarketPriceJob%')
                    ->count(),
            ],
        ]);
    }

    public function scanProduct(Product $product): RedirectResponse
    {
        if (! $this->dataForSeo->configured()) {
            return back()->with('error', 'DataForSEO kimlik bilgileri eksik. Sunucuya DATAFORSEO_USERNAME / PASSWORD ekleyin.');
        }

        $scan = $this->scanner->scanProduct($product);

        if ($scan->status === MarketPriceScan::STATUS_ERROR) {
            return back()->with('error', $scan->last_error ?: 'Tarama başarısız.');
        }

        if ($scan->status === MarketPriceScan::STATUS_NO_RESULTS) {
            return back()->with(
                'success',
                'Tarama tamam: güvenilir teklif yok. '.$scan->last_error
            );
        }

        return back()->with(
            'success',
            'Google tarandı: '.$scan->offer_count.' teklif · referans '.number_format((float) ($scan->competitivePrice() ?? $scan->google_min_price), 2, ',', '.').' ₺'
        );
    }

    public function scanBatch(Request $request): RedirectResponse
    {
        if (! $this->dataForSeo->configured()) {
            return back()->with('error', 'DataForSEO kimlik bilgileri eksik.');
        }

        if ($request->input('limit') === '' || $request->input('limit') === null) {
            $request->merge(['limit' => null]);
        }

        $data = $request->validate([
            'mode' => ['required', 'in:missing,stale,all,no_results'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $mode = $data['mode'];
        $limit = isset($data['limit']) ? (int) $data['limit'] : null;

        $query = Product::query()->where('is_active', true)->orderBy('id');

        if ($mode === 'missing') {
            $query->whereDoesntHave('marketPriceScan');
        } elseif ($mode === 'no_results') {
            $query->whereHas('marketPriceScan', fn ($s) => $s->where('status', MarketPriceScan::STATUS_NO_RESULTS));
        } elseif ($mode === 'stale') {
            $query->where(function ($q) {
                $q->whereDoesntHave('marketPriceScan')
                    ->orWhereHas('marketPriceScan', function ($scan) {
                        $scan->where(function ($inner) {
                            $inner->whereNull('last_scanned_at')
                                ->orWhere('last_scanned_at', '<', now()->subDays(7))
                                ->orWhere('status', MarketPriceScan::STATUS_NO_RESULTS);
                        });
                    });
            });
        }

        if ($limit) {
            $query->limit($limit);
        }

        $productIds = $query->pluck('id');
        if ($productIds->isEmpty()) {
            return back()->with('error', 'Bu filtrede taranacak ürün yok.');
        }

        foreach ($productIds as $productId) {
            ScanGoogleMarketPriceJob::dispatch((int) $productId);
        }

        $count = $productIds->count();
        $modeLabel = match ($mode) {
            'all' => 'tüm aktif',
            'stale' => 'eksik/eski/sonuçsuz',
            'no_results' => 'sonuçsuz (yeniden)',
            default => 'taranmamış',
        };

        return back()->with(
            'success',
            "{$count} ürün ({$modeLabel}) kuyruğa alındı. Sunucu dakikada işler; sayfayı yenileyerek ilerlemeyi izleyin. Otomatik fiyat uygulanmaz."
        );
    }

    public function approveMarket(MarketPriceScan $scan): RedirectResponse
    {
        if ($scan->google_min_price === null || (float) $scan->google_min_price <= 0) {
            return back()->with('error', 'Onaylanacak Google fiyatı yok.');
        }

        $scan->update(['status' => MarketPriceScan::STATUS_APPROVED, 'last_error' => null]);

        return back()->with('success', 'Google piyasa fiyatı onaylandı. Öneriye dahil.');
    }

    public function rejectMarket(MarketPriceScan $scan): RedirectResponse
    {
        $scan->update(['status' => MarketPriceScan::STATUS_REJECTED]);

        return back()->with('success', 'Google piyasa sonucu reddedildi.');
    }

    public function applyMarket(MarketPriceScan $scan): RedirectResponse
    {
        $product = $scan->product;
        if (! $product) {
            return back()->with('error', 'Ürün bulunamadı.');
        }

        if (! $scan->isApproved()) {
            return back()->with('error', 'Önce Google sonucunu onaylayın.');
        }

        $rule = CompetitorPriceRule::activeRule();
        $suggestion = $this->pricing->suggestionForProduct(
            $product->load(['competitorOffers', 'marketPriceScan']),
            $rule
        );

        if (! $suggestion['can_apply'] || $suggestion['suggested'] === null) {
            return back()->with('error', $suggestion['reason'] ?: 'Uygulanacak öneri yok.');
        }

        $this->pricing->applySuggestion($product, (float) $suggestion['suggested'], $rule);

        return back()->with(
            'success',
            'Fiyat güncellendi: '.number_format((float) $suggestion['suggested'], 2, ',', '.').' ₺'
        );
    }

    public function create(Request $request): View
    {
        $productId = $request->integer('product_id') ?: null;
        $product = $productId ? Product::query()->find($productId) : null;

        return view('admin.competitor-pricing.form', [
            'offer' => new CompetitorOffer([
                'match_status' => CompetitorOffer::STATUS_PENDING,
                'active' => true,
                'product_id' => $product?->id,
            ]),
            'product' => $product,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'sku', 'price']),
            'rule' => CompetitorPriceRule::activeRule(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $offer = CompetitorOffer::query()->create($data);

        if ($request->boolean('fetch_now')) {
            $this->pricing->refreshOffer($offer);
        }

        return redirect()
            ->route('admin.competitor-pricing.index')
            ->with('success', 'Rakip teklif eklendi.');
    }

    public function edit(CompetitorOffer $offer): View
    {
        $offer->load('product');

        return view('admin.competitor-pricing.form', [
            'offer' => $offer,
            'product' => $offer->product,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'sku', 'price']),
            'rule' => CompetitorPriceRule::activeRule(),
            'suggestion' => $offer->product
                ? $this->pricing->suggestionForProduct($offer->product->load(['competitorOffers', 'marketPriceScan']))
                : null,
        ]);
    }

    public function update(Request $request, CompetitorOffer $offer): RedirectResponse
    {
        $offer->update($this->validated($request));

        if ($request->boolean('fetch_now')) {
            $this->pricing->refreshOffer($offer->fresh());
        }

        return redirect()
            ->route('admin.competitor-pricing.edit', $offer)
            ->with('success', 'Rakip teklif güncellendi.');
    }

    public function destroy(CompetitorOffer $offer): RedirectResponse
    {
        $offer->delete();

        return redirect()
            ->route('admin.competitor-pricing.index')
            ->with('success', 'Rakip teklif silindi.');
    }

    public function fetch(CompetitorOffer $offer): RedirectResponse
    {
        $offer = $this->pricing->refreshOffer($offer);

        return back()->with(
            $offer->last_error ? 'error' : 'success',
            $offer->last_error ?: ('Fiyat güncellendi: '.number_format((float) $offer->last_price, 2, ',', '.').' ₺')
        );
    }

    public function approve(CompetitorOffer $offer): RedirectResponse
    {
        $offer->update(['match_status' => CompetitorOffer::STATUS_APPROVED]);

        return back()->with('success', 'Eşleşme onaylandı. Artık fiyat önerisine dahil.');
    }

    public function reject(CompetitorOffer $offer): RedirectResponse
    {
        $offer->update(['match_status' => CompetitorOffer::STATUS_REJECTED]);

        return back()->with('success', 'Eşleşme reddedildi.');
    }

    public function apply(CompetitorOffer $offer): RedirectResponse
    {
        $product = $offer->product;
        if (! $product) {
            return back()->with('error', 'Ürün bulunamadı.');
        }

        if ($offer->match_status !== CompetitorOffer::STATUS_APPROVED) {
            return back()->with('error', 'Önce eşleşmeyi onaylayın.');
        }

        $rule = CompetitorPriceRule::activeRule();
        $suggestion = $this->pricing->suggestionForProduct(
            $product->load(['competitorOffers', 'marketPriceScan']),
            $rule
        );

        if (! $suggestion['can_apply'] || $suggestion['suggested'] === null) {
            return back()->with('error', $suggestion['reason'] ?: 'Uygulanacak öneri yok.');
        }

        $this->pricing->applySuggestion($product, (float) $suggestion['suggested'], $rule);

        return back()->with(
            'success',
            'Fiyat güncellendi: '.number_format((float) $suggestion['suggested'], 2, ',', '.').' ₺'
        );
    }

    public function settings(): View
    {
        return view('admin.competitor-pricing.settings', [
            'rule' => CompetitorPriceRule::activeRule(),
            'dataforseoReady' => $this->dataForSeo->configured(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'undercut_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $rule = CompetitorPriceRule::activeRule();
        $rule->update([
            'name' => $data['name'] ?: $rule->name,
            'undercut_percent' => $data['undercut_percent'],
            'min_price' => $data['min_price'],
            'keep_compare_at' => $request->boolean('keep_compare_at', true),
            'auto_apply' => false,
            'active' => true,
        ]);

        return redirect()
            ->route('admin.competitor-pricing.settings')
            ->with('success', 'Rekabet kuralları kaydedildi.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'competitor_name' => ['required', 'string', 'max:120'],
            'competitor_url' => ['required', 'url', 'max:2000'],
            'match_status' => ['required', 'in:pending,approved,rejected'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $data['active'] = $request->boolean('active', true);
        $data['currency'] = 'TRY';

        return $data;
    }
}
