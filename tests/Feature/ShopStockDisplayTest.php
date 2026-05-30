<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopStockDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function product(int $stock = 5): Product
    {
        return Product::query()->create([
            'slug' => 'stok-gosterim-test',
            'sku' => 'SGT-1',
            'name' => 'Stok Gösterim Test',
            'price' => 100,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_pdp_hides_quantity_when_setting_off(): void
    {
        $product = $this->product(5);

        SiteSetting::set('shop_show_stock_quantity', '0');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(__('shop.in_stock'), false)
            ->assertDontSee('(5', false);
    }

    public function test_pdp_shows_quantity_when_setting_on(): void
    {
        $product = $this->product(5);

        SiteSetting::set('shop_show_stock_quantity', '1');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(__('shop.in_stock_with_qty', ['qty' => 5, 'units' => __('shop.units')]), false);
    }

    public function test_admin_can_toggle_stock_quantity_setting(): void
    {
        $admin = User::query()->where('is_admin', true)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                '_tab' => 'general',
                'shop_show_stock_quantity' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('1', SiteSetting::get('shop_show_stock_quantity'));
    }
}
