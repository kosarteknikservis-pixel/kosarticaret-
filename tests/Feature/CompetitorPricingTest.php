<?php

namespace Tests\Feature;

use App\Models\CompetitorOffer;
use App\Models\CompetitorPriceRule;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\CompetitorPriceFetcher;
use App\Services\Pricing\CompetitorPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
