<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCardFreeShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => 'Ücretsiz Kargo Test',
            'slug' => 'ucretsiz-kargo-test',
            'active' => true,
            'sort_order' => 0,
        ]);
    }

    private function product(float $price): Product
    {
        $product = Product::query()->create([
            'slug' => 'ucretsiz-kargo-rozet-'.(int) $price,
            'sku' => 'UKR-'.(int) $price,
            'name' => 'Ücretsiz Kargo Rozet Test',
            'price' => $price,
            'stock' => 5,
            'is_active' => true,
        ]);
        $product->categories()->attach($this->category()->id);

        return $product;
    }

    public function test_category_card_shows_free_shipping_badge_above_threshold(): void
    {
        SiteSetting::set('product_card_free_shipping_badge', '1');
        SiteSetting::set('free_shipping_min', '1000');

        $product = $this->product(1500);

        $this->get($this->category()->storefrontUrl())
            ->assertOk()
            ->assertSee(__('shop.product_card_free_shipping'), false)
            ->assertSee('shop-product-card__shipping', false);
    }

    public function test_product_page_shows_free_shipping_badge_above_threshold(): void
    {
        SiteSetting::set('product_card_free_shipping_badge', '1');
        SiteSetting::set('free_shipping_min', '1000');

        $product = $this->product(1500);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(__('shop.product_card_free_shipping'), false)
            ->assertSee('shop-pdp-price-box__shipping', false);
    }

    public function test_product_page_hides_free_shipping_badge_below_threshold(): void
    {
        SiteSetting::set('product_card_free_shipping_badge', '1');
        SiteSetting::set('free_shipping_min', '1000');

        $product = $this->product(500);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('shop-pdp-price-box__shipping', false);
    }

    public function test_category_card_hides_badge_below_threshold(): void
    {
        SiteSetting::set('product_card_free_shipping_badge', '1');
        SiteSetting::set('free_shipping_min', '1000');

        $product = $this->product(500);

        $response = $this->get($this->category()->storefrontUrl());
        $response->assertOk();

        $cardSnippet = substr(
            $response->getContent(),
            (int) strpos($response->getContent(), $product->name),
            800
        );

        $this->assertStringNotContainsString('shop-product-card__shipping', $cardSnippet);
    }

    public function test_admin_can_disable_product_card_free_shipping_badge(): void
    {
        $admin = User::query()->where('is_admin', true)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                '_tab' => 'general',
                'product_card_free_shipping_badge' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('0', SiteSetting::get('product_card_free_shipping_badge'));
    }
}
