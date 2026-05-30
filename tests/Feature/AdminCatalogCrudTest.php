<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCatalogCrudTest extends TestCase
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

    #[Test]
    public function admin_can_update_category_with_empty_parent(): void
    {
        $category = Category::query()->create([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name' => 'Test Kategori Güncel',
                'slug' => 'test-kategori',
                'parent_id' => '',
                'description' => '<p>Yeni açıklama metni.</p>',
                'sort_order' => 1,
                'active' => '1',
                'show_in_menu' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $category->refresh();
        $this->assertSame('Test Kategori Güncel', $category->name);
        $this->assertNull($category->parent_id);
        $this->assertSame('test-kategori', $category->slug);
    }

    #[Test]
    public function admin_cannot_delete_category_that_has_products(): void
    {
        $category = Category::query()->create([
            'name' => 'Dolu Kategori',
            'slug' => 'dolu-kategori',
            'active' => true,
        ]);
        $brand = Brand::query()->first();
        $product = Product::query()->create([
            'name' => 'Bağlı Ürün',
            'slug' => 'bagli-urun',
            'sku' => 'TEST-001',
            'price' => 100,
            'stock' => 5,
            'brand_id' => $brand?->id,
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    #[Test]
    public function admin_can_update_product_without_deleting_it(): void
    {
        $brand = Brand::query()->first();
        $product = Product::query()->create([
            'name' => 'Eski Ad',
            'slug' => 'eski-ad-urun',
            'sku' => 'TEST-002',
            'price' => 250,
            'stock' => 3,
            'brand_id' => $brand?->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), [
                'name' => 'Yeni Ad',
                'slug' => 'eski-ad-urun',
                'sku' => 'TEST-002',
                'brand_id' => (string) ($brand?->id ?? ''),
                'price' => 299.99,
                'compare_at_price' => '',
                'stock' => 4,
                'short_description' => 'Kısa metin',
                'description' => '<p>Detaylı açıklama</p>',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Yeni Ad',
            'slug' => 'eski-ad-urun',
        ]);
    }

    #[Test]
    public function admin_can_update_brand(): void
    {
        $brand = Brand::query()->create([
            'name' => 'Test Marka',
            'slug' => 'test-marka',
            'active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.brands.update', $brand), [
                'name' => 'Test Marka Plus',
                'slug' => 'test-marka',
                'description' => '<p>Marka açıklaması</p>',
                'sort_order' => 0,
                'active' => '1',
            ])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success');

        $brand->refresh();
        $this->assertSame('Test Marka Plus', $brand->name);
        $this->assertSame('test-marka', $brand->slug);
    }

}
