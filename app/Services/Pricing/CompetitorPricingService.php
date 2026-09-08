<?php

namespace App\Services\Pricing;

use App\Models\CompetitorOffer;
use App\Models\CompetitorPriceRule;
use App\Models\MarketPriceScan;
use App\Models\Product;
use Illuminate\Support\Collection;

class CompetitorPricingService
{
    public function __construct(private CompetitorPriceFetcher $fetcher) {}

    public function refreshOffer(CompetitorOffer $offer): CompetitorOffer
    {
        $result = $this->fetcher->fetch($offer->competitor_url);

        $offer->fill([
            'competitor_title' => $result['title'] ?: $offer->competitor_title,
            'last_price' => $result['ok'] ? $result['price'] : $offer->last_price,
            'last_fetched_at' => now(),
            'last_error' => $result['ok'] ? null : $result['error'],
        ])->save();

        return $offer->fresh();
    }

    /**
     * @return array{
     *     our_price: float,
     *     competitor_min: ?float,
     *     suggested: ?string,
     *     difference_percent: ?float,
     *     can_apply: bool,
     *     reason: ?string
     * }
     */
    public function suggestionForProduct(Product $product, ?CompetitorPriceRule $rule = null): array
    {
        $rule ??= CompetitorPriceRule::activeRule();
        $our = round((float) $product->price, 2);

        /** @var Collection<int, CompetitorOffer> $offers */
        $offers = $product->relationLoaded('competitorOffers')
            ? $product->competitorOffers
            : $product->competitorOffers()->get();

        $approvedPrices = $offers
            ->where('active', true)
            ->where('match_status', CompetitorOffer::STATUS_APPROVED)
            ->pluck('last_price')
            ->filter(fn ($p) => $p !== null && (float) $p > 0)
            ->map(fn ($p) => round((float) $p, 2));

        $scan = $product->relationLoaded('marketPriceScan')
            ? $product->marketPriceScan
            : $product->marketPriceScan()->first();

        if ($scan instanceof MarketPriceScan && $scan->isApproved()) {
            $approvedPrices = $approvedPrices->push(round((float) $scan->google_min_price, 2));
        }

        if ($approvedPrices->isEmpty()) {
            return [
                'our_price' => $our,
                'competitor_min' => null,
                'suggested' => null,
                'difference_percent' => null,
                'can_apply' => false,
                'reason' => 'Onaylı Google piyasa taraması veya rakip teklifi yok.',
            ];
        }

        $minCompetitor = (float) $approvedPrices->min();
        $undercut = max(0, (float) $rule->undercut_percent);
        $suggested = round($minCompetitor * (1 - ($undercut / 100)), 2);

        if ($rule->min_price !== null && $suggested < (float) $rule->min_price) {
            $suggested = round((float) $rule->min_price, 2);
        }

        $diff = $minCompetitor > 0
            ? round((($our - $minCompetitor) / $minCompetitor) * 100, 2)
            : null;

        if ($suggested >= $our) {
            return [
                'our_price' => $our,
                'competitor_min' => $minCompetitor,
                'suggested' => number_format($suggested, 2, '.', ''),
                'difference_percent' => $diff,
                'can_apply' => false,
                'reason' => 'Önerilen fiyat mevcut fiyattan düşük değil (zaten rekabetçi veya tabana takıldı).',
            ];
        }

        return [
            'our_price' => $our,
            'competitor_min' => $minCompetitor,
            'suggested' => number_format($suggested, 2, '.', ''),
            'difference_percent' => $diff,
            'can_apply' => true,
            'reason' => null,
        ];
    }

    public function applySuggestion(Product $product, float $newPrice, ?CompetitorPriceRule $rule = null): Product
    {
        $rule ??= CompetitorPriceRule::activeRule();
        $newPrice = round($newPrice, 2);
        $current = round((float) $product->price, 2);

        if ($newPrice <= 0) {
            throw new \InvalidArgumentException('Geçersiz fiyat.');
        }

        if ($rule->min_price !== null && $newPrice < (float) $rule->min_price) {
            throw new \InvalidArgumentException('Fiyat tabanın altında olamaz.');
        }

        if ($rule->keep_compare_at && $current > $newPrice) {
            $existingCompare = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;
            $product->compare_at_price = max($current, $existingCompare ?? 0) ?: $current;
        }

        $product->price = $newPrice;
        $product->save();

        return $product->fresh();
    }
}
