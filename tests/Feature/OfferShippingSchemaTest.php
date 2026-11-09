<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferShippingSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        SiteSetting::set('free_shipping_min', '1000');
        SiteSetting::set('shipping_methods_json', json_encode([
            ['id' => 'standart', 'name' => 'Standart Kargo', 'fee' => 89.90, 'active' => true],
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_offer_shipping_is_free_above_threshold(): void
    {
        $product = Product::query()->create([
            'name' => 'Kargo Esik Ustu',
            'slug' => 'kargo-esik-ustu',
            'sku' => 'SHIP-1500',
            'price' => 1500,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->assertSame('0.00', Seo::offerShippingDetails($product)['shippingRate']['value']);
    }

    public function test_offer_shipping_uses_panel_fee_below_threshold(): void
    {
        $product = Product::query()->create([
            'name' => 'Kargo Esik Alti',
            'slug' => 'kargo-esik-alti',
            'sku' => 'SHIP-500',
            'price' => 500,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->assertSame('89.90', Seo::offerShippingDetails($product)['shippingRate']['value']);
    }

    public function test_merchant_feed_shipping_price_matches_offer_schema(): void
    {
        Product::query()->create([
            'name' => 'Kargo Feed Urun',
            'slug' => 'kargo-feed-urun',
            'sku' => 'SHIP-FEED-500',
            'price' => 500,
            'stock' => 5,
            'is_active' => true,
            'image' => 'products/kargo-feed-urun.jpg',
        ]);

        $xml = $this->get('/urun-feed.xml')->assertOk()->streamedContent();

        $this->assertStringContainsString('<g:price>89.90 TRY</g:price>', $xml);
    }
}
