<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductRedirectFieldTest extends TestCase
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
    public function admin_can_save_valid_redirect_to_on_product(): void
    {
        $brand = Brand::query()->first();
        $product = Product::query()->create([
            'name' => 'Yönlendirme Alanı Ürün',
            'slug' => 'yonlendirme-alani-urun',
            'sku' => 'REDIR-001',
            'price' => 100,
            'stock' => 5,
            'brand_id' => $brand?->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), [
                'name' => 'Yönlendirme Alanı Ürün',
                'slug' => 'yonlendirme-alani-urun',
                'sku' => 'REDIR-001',
                'brand_id' => (string) ($brand?->id ?? ''),
                'price' => 100,
                'compare_at_price' => '',
                'stock' => 5,
                'short_description' => 'Kısa metin',
                'description' => '<p>Detaylı açıklama</p>',
                'is_active' => '1',
                'redirect_to' => '/urunler',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'redirect_to' => '/urunler',
        ]);
    }

    #[Test]
    public function admin_invalid_redirect_to_is_stored_as_null(): void
    {
        $brand = Brand::query()->first();
        $product = Product::query()->create([
            'name' => 'Geçersiz Yönlendirme Ürün',
            'slug' => 'gecersiz-yonlendirme-urun',
            'sku' => 'REDIR-002',
            'price' => 120,
            'stock' => 2,
            'brand_id' => $brand?->id,
            'is_active' => true,
            'redirect_to' => '/urunler',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), [
                'name' => 'Geçersiz Yönlendirme Ürün',
                'slug' => 'gecersiz-yonlendirme-urun',
                'sku' => 'REDIR-002',
                'brand_id' => (string) ($brand?->id ?? ''),
                'price' => 120,
                'compare_at_price' => '',
                'stock' => 2,
                'short_description' => 'Kısa metin',
                'description' => '<p>Detaylı açıklama</p>',
                'is_active' => '1',
                'redirect_to' => 'javascript:x',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertNull($product->redirect_to);
    }
}
