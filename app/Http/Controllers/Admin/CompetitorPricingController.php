<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitorOffer;
use App\Models\CompetitorPriceRule;
use App\Models\Product;
use App\Services\Pricing\CompetitorPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitorPricingController extends Controller
{
    public function __construct(private CompetitorPricingService $pricing) {}

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
                ? $this->pricing->suggestionForProduct($product->loadMissing('competitorOffers'), $rule)
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
            ],
        ]);
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
                ? $this->pricing->suggestionForProduct($offer->product->load('competitorOffers'))
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
        $suggestion = $this->pricing->suggestionForProduct($product->load('competitorOffers'), $rule);

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
