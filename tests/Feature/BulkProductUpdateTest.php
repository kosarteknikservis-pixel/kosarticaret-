<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkProductUpdateTest extends TestCase
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

    public function test_admin_can_open_bulk_update_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.products.bulk-update'))
            ->assertOk()
            ->assertSee('Toplu ürün güncelleme', false);
    }

    public function test_bulk_price_increase_by_percent_for_category(): void
    {
        $brand = Brand::query()->create(['name' => 'Test Marka', 'slug' => 'test-marka', 'active' => true]);
        $category = Category::query()->create(['name' => 'Test Kat', 'slug' => 'test-kat', 'active' => true]);
        $product = Product::query()->create([
            'name' => 'Ürün A',
            'slug' => 'urun-a',
            'sku' => 'SKU-A',
            'brand_id' => $brand->id,
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id);

        $other = Product::query()->create([
            'name' => 'Ürün B',
            'slug' => 'urun-b',
            'sku' => 'SKU-B',
            'price' => 200,
            'stock' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.bulk-update.apply'), [
                'confirm' => '1',
                'filter_category_ids' => [$category->id],
                'act_price' => '1',
                'price_mode' => 'add_percent',
                'price_value' => '10',
            ])
            ->assertRedirect(route('admin.products.bulk-update'));

        $product->refresh();
        $other->refresh();

        $this->assertSame('110.00', $product->price);
        $this->assertSame('200.00', $other->price);
    }

    public function test_bulk_preview_returns_json_count(): void
    {
        Product::query()->create([
            'name' => 'X',
            'slug' => 'x',
            'sku' => 'X-1',
            'price' => 10,
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.products.bulk-update.preview'), [
                'filter_stock' => 'out_of_stock',
            ])
            ->assertOk()
            ->assertJson(['count' => 1]);
    }
}
