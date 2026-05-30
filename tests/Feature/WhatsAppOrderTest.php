<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\WhatsAppOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function pdp_shows_whatsapp_order_button_when_enabled(): void
    {
        SiteSetting::set('pdp_whatsapp_order_enabled', '1');
        SiteSetting::set('contact_whatsapp', '905554443000');

        $product = Product::query()->where('is_active', true)->where('stock', '>', 0)->first();
        $this->assertNotNull($product);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('shop-pdp-wa-order', false)
            ->assertSee('data-pdp-wa-order', false);
    }

    #[Test]
    public function pdp_hides_whatsapp_order_when_disabled_in_settings(): void
    {
        SiteSetting::set('pdp_whatsapp_order_enabled', '0');
        SiteSetting::set('contact_whatsapp', '905554443000');

        $product = Product::query()->where('is_active', true)->where('stock', '>', 0)->first();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('shop-pdp-wa-order', false);
    }

    #[Test]
    public function order_url_includes_product_details(): void
    {
        SiteSetting::set('contact_whatsapp', '905554443000');

        $product = Product::query()->where('is_active', true)->first();
        $url = WhatsAppOrder::orderUrl($product, 2);

        $this->assertNotNull($url);
        $this->assertStringContainsString('wa.me/905554443000', $url);
        $this->assertStringContainsString(rawurlencode($product->name), $url);
    }
}
