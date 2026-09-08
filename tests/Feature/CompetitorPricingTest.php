<?php

namespace Tests\Feature;

use App\Models\CompetitorOffer;
use App\Models\CompetitorPriceRule;
use App\Models\MarketPriceScan;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\CompetitorPriceFetcher;
use App\Services\Pricing\CompetitorPricingService;
use App\Services\Pricing\GoogleShoppingMarketScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompetitorPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        $admin = User::query()->where('is_admin', true)->first();
        $this->assertNotNull($admin);

        return $admin;
    }

    private function product(float $price = 14313): Product
    {
        return Product::query()->create([
            'name' => 'Test Vantilatör',
            'slug' => 'test-vantilator-'.uniqid(),
            'sku' => 'TV-'.uniqid(),
            'price' => $price,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function suggestion_undercuts_approved_competitor_min(): void
    {
        $product = $this->product(14313);
        CompetitorOffer::query()->create([
            'product_id' => $product->id,
            'competitor_name' => 'Rakip A',
            'competitor_url' => 'https://example.com/a',
            'match_status' => CompetitorOffer::STATUS_APPROVED,
            'last_price' => 14301,
            'active' => true,
        ]);
        CompetitorOffer::query()->create([
            'product_id' => $product->id,
            'competitor_name' => 'Rakip B',
            'competitor_url' => 'https://example.com/b',
            'match_status' => CompetitorOffer::STATUS_APPROVED,
            'last_price' => 14100,
            'active' => true,
        ]);

        $rule = CompetitorPriceRule::query()->create([
            'name' => 'Test',
            'undercut_percent' => 2,
            'keep_compare_at' => true,
            'auto_apply' => false,
            'active' => true,
        ]);

        $suggestion = app(CompetitorPricingService::class)
            ->suggestionForProduct($product->load('competitorOffers'), $rule);

        $this->assertSame(14100.0, $suggestion['competitor_min']);
        $this->assertSame('13818.00', $suggestion['suggested']); // 14100 * 0.98
        $this->assertTrue($suggestion['can_apply']);
    }

    #[Test]
    public function pending_offers_are_ignored_in_suggestion(): void
    {
        $product = $this->product(15000);
        CompetitorOffer::query()->create([
            'product_id' => $product->id,
            'competitor_name' => 'Bekleyen',
            'competitor_url' => 'https://example.com/p',
            'match_status' => CompetitorOffer::STATUS_PENDING,
            'last_price' => 10000,
            'active' => true,
        ]);

        $suggestion = app(CompetitorPricingService::class)
            ->suggestionForProduct($product->load('competitorOffers'));

        $this->assertFalse($suggestion['can_apply']);
        $this->assertNull($suggestion['suggested']);
    }

    #[Test]
    public function apply_sets_compare_at_and_new_price(): void
    {
        $product = $this->product(14313);
        $rule = CompetitorPriceRule::query()->create([
            'name' => 'Test',
            'undercut_percent' => 2,
            'keep_compare_at' => true,
            'auto_apply' => false,
            'active' => true,
        ]);

        app(CompetitorPricingService::class)->applySuggestion($product, 13818, $rule);
        $product->refresh();

        $this->assertSame('13818.00', (string) $product->price);
        $this->assertSame('14313.00', (string) $product->compare_at_price);
    }

    #[Test]
    public function admin_can_create_approve_and_apply_offer(): void
    {
        $product = $this->product(14313);

        $this->mock(CompetitorPriceFetcher::class, function ($mock) {
            $mock->shouldReceive('fetch')->andReturn([
                'ok' => true,
                'price' => 14100.0,
                'title' => 'Rakip Ürün',
                'error' => null,
            ]);
        });

        $this->actingAs($this->admin())
            ->post(route('admin.competitor-pricing.store'), [
                'product_id' => $product->id,
                'competitor_name' => 'Rakip X',
                'competitor_url' => 'https://example.com/x',
                'match_status' => 'pending',
                'active' => '1',
                'fetch_now' => '1',
            ])
            ->assertRedirect(route('admin.competitor-pricing.index'))
            ->assertSessionHas('success');

        $offer = CompetitorOffer::query()->first();
        $this->assertNotNull($offer);
        $this->assertSame('14100.00', (string) $offer->last_price);

        $this->actingAs($this->admin())
            ->post(route('admin.competitor-pricing.approve', $offer))
            ->assertRedirect();

        CompetitorPriceRule::activeRule()->update(['undercut_percent' => 2]);

        $this->actingAs($this->admin())
            ->post(route('admin.competitor-pricing.apply', $offer->fresh()))
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('13818.00', (string) $product->price);
    }

    #[Test]
    public function fetcher_parses_json_ld_price(): void
    {
        $html = '<html><head><title>Test</title>
            <script type="application/ld+json">{"@type":"Product","name":"X","offers":{"@type":"Offer","price":"14.100,50","priceCurrency":"TRY"}}</script>
            </head><body></body></html>';

        $fetcher = new CompetitorPriceFetcher;
        $ref = new \ReflectionClass($fetcher);
        $method = $ref->getMethod('extractPrice');
        $method->setAccessible(true);
        $price = $method->invoke($fetcher, $html);

        $this->assertSame(14100.5, $price);
    }

    #[Test]
    public function settings_page_saves_undercut_rule(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.competitor-pricing.settings.update'), [
                'name' => 'Varsayılan',
                'undercut_percent' => 2.5,
                'min_price' => 100,
                'keep_compare_at' => '1',
            ])
            ->assertRedirect(route('admin.competitor-pricing.settings'))
            ->assertSessionHas('success');

        $rule = CompetitorPriceRule::activeRule();
        $this->assertSame('2.50', (string) $rule->undercut_percent);
        $this->assertFalse((bool) $rule->auto_apply);
    }

    #[Test]
    public function approved_google_scan_feeds_suggestion(): void
    {
        $product = $this->product(15000);
        MarketPriceScan::query()->create([
            'product_id' => $product->id,
            'search_query' => $product->name,
            'status' => MarketPriceScan::STATUS_APPROVED,
            'google_min_price' => 14100,
            'google_median_price' => 14500,
            'offer_count' => 2,
            'offers' => [],
            'last_scanned_at' => now(),
        ]);

        $rule = CompetitorPriceRule::query()->create([
            'name' => 'Test',
            'undercut_percent' => 2,
            'keep_compare_at' => true,
            'auto_apply' => false,
            'active' => true,
        ]);

        $suggestion = app(CompetitorPricingService::class)
            ->suggestionForProduct($product->load('marketPriceScan'), $rule);

        $this->assertSame(14100.0, $suggestion['competitor_min']);
        $this->assertSame('13818.00', $suggestion['suggested']);
        $this->assertTrue($suggestion['can_apply']);
    }

    #[Test]
    public function google_scanner_stores_filtered_offers_from_api(): void
    {
        config([
            'services.dataforseo.login' => 'test-user',
            'services.dataforseo.password' => 'test-pass',
            'services.dataforseo.poll_interval' => 0,
            'services.dataforseo.poll_timeout' => 5,
            'services.dataforseo.min_match_score' => 0.2,
            'services.dataforseo.price_band_min' => 0.3,
            'services.dataforseo.price_band_max' => 3,
        ]);

        Http::fake([
            'api.dataforseo.com/v3/merchant/google/products/task_post' => Http::response([
                'status_code' => 20000,
                'tasks' => [[
                    'id' => 'task-1',
                    'status_code' => 20100,
                    'status_message' => 'Task Created.',
                ]],
            ], 200),
            'api.dataforseo.com/v3/merchant/google/products/task_get/advanced/task-1' => Http::response([
                'status_code' => 20000,
                'tasks' => [[
                    'status_code' => 20000,
                    'result' => [[
                        'items' => [
                            [
                                'type' => 'google_shopping_serp',
                                'title' => 'Test Vantilatör Endüstriyel 50 cm',
                                'price' => 14000,
                                'currency' => 'TRY',
                                'seller' => 'Rakip A',
                                'shopping_url' => 'https://google.com/x',
                                'product_id' => '111',
                            ],
                            [
                                'type' => 'google_shopping_serp',
                                'title' => 'Tamamen alakasız mutfak robotu',
                                'price' => 500,
                                'currency' => 'TRY',
                                'seller' => 'X',
                            ],
                            [
                                'type' => 'google_shopping_serp',
                                'title' => 'Test Vantilatör Koşar',
                                'price' => 13900,
                                'currency' => 'TRY',
                                'seller' => 'Koşar Ticaret',
                            ],
                        ],
                    ]],
                ]],
            ], 200),
        ]);

        $product = $this->product(14313);
        $product->name = 'Test Vantilatör Endüstriyel';
        $product->save();

        $scan = app(GoogleShoppingMarketScanner::class)->scanProduct($product);

        $this->assertSame(MarketPriceScan::STATUS_PENDING, $scan->status);
        $this->assertSame('14000.00', (string) $scan->google_min_price);
        $this->assertGreaterThanOrEqual(1, $scan->offer_count);
        $this->assertFalse(collect($scan->offers)->contains(fn ($o) => str_contains(mb_strtolower($o['seller'] ?? ''), 'koşar')));
    }

    #[Test]
    public function admin_can_approve_and_apply_google_scan(): void
    {
        $product = $this->product(15000);
        $scan = MarketPriceScan::query()->create([
            'product_id' => $product->id,
            'search_query' => $product->name,
            'status' => MarketPriceScan::STATUS_PENDING,
            'google_min_price' => 14100,
            'offer_count' => 1,
            'offers' => [['title' => 'X', 'price' => 14100, 'seller' => 'A', 'score' => 0.5]],
            'last_scanned_at' => now(),
        ]);

        CompetitorPriceRule::activeRule()->update(['undercut_percent' => 2]);

        $this->actingAs($this->admin())
            ->post(route('admin.competitor-pricing.market.approve', $scan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->admin())
            ->post(route('admin.competitor-pricing.market.apply', $scan->fresh()))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('13818.00', (string) $product->fresh()->price);
    }

    #[Test]
    public function market_page_loads_for_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.competitor-pricing.market'))
            ->assertOk();
    }
}
