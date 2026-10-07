<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HavaleDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function product(float $price): Product
    {
        $category = Category::query()->create([
            'name' => 'Havale Test',
            'slug' => 'havale-test-'.str_replace('.', '-', (string) $price),
            'active' => true,
            'sort_order' => 0,
        ]);
        $product = Product::query()->create([
            'slug' => 'havale-urun-'.str_replace('.', '-', (string) $price),
            'sku' => 'HV-'.str_replace('.', '-', (string) $price),
            'name' => 'Havale İndirim Test Ürünü',
            'price' => $price,
            'stock' => 4,
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id);

        return $product;
    }

    public function test_product_page_shows_the_configured_havale_price(): void
    {
        SiteSetting::set('havale_discount_percent', '3');

        $product = $this->product(8271.37);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('shop-pdp-havale', false)
            ->assertSee('Havale ile', false)
            ->assertSee('8.023,23', false)
            ->assertSee('%3 indirim', false);
    }

    public function test_product_page_hides_havale_price_when_the_rate_is_zero(): void
    {
        SiteSetting::set('havale_discount_percent', '0');

        $product = $this->product(1500);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('shop-pdp-havale', false);
    }

    public function test_checkout_quote_includes_havale_discount_for_the_havale_method(): void
    {
        SiteSetting::set('havale_discount_percent', '3');

        $product = $this->product(8271.37);

        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Havale indirimi (%3)', false)
            ->assertSee('data-checkout-havale', false)
            ->assertSee('"havale_discount":248.14', false);
    }
}
