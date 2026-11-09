<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnknownProductUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unknown_product_slug_returns_404(): void
    {
        $this->get('/urun/boyle-bir-urun-yok')->assertNotFound();
    }

    public function test_inactive_product_with_redirect_to_returns_301(): void
    {
        $category = Category::query()->where('active', true)->orderBy('id')->first();
        $this->assertNotNull($category);

        $path = parse_url($category->storefrontUrl(), PHP_URL_PATH);
        $this->assertIsString($path);
        $this->assertNotSame('', $path);

        $product = Product::query()->create([
            'name' => 'Pasif Yonlendirmeli Urun',
            'slug' => 'pasif-yonlendirmeli-urun',
            'sku' => 'PASSIVE-REDIR-1',
            'price' => 100,
            'stock' => 0,
            'is_active' => false,
            'redirect_to' => $path,
        ]);

        $this->get('/urun/'.$product->slug)
            ->assertRedirect($path);
    }

    public function test_inactive_product_without_redirect_returns_410_noindex(): void
    {
        $product = Product::query()->create([
            'name' => 'Pasif 410 Urun',
            'slug' => 'pasif-410-urun',
            'sku' => 'PASSIVE-410-1',
            'price' => 100,
            'stock' => 0,
            'is_active' => false,
            'redirect_to' => null,
        ]);

        $this->get('/urun/'.$product->slug)
            ->assertStatus(410)
            ->assertSee('noindex', false);
    }

    public function test_configured_legacy_product_redirect_still_works(): void
    {
        $this->get('/urun/10-mm-soketli-sinyal-lmb-yesil-sari-kirmizi')
            ->assertRedirect('/urunler');
    }

    public function test_active_product_uppercase_slug_redirects_to_canonical(): void
    {
        $product = Product::query()->active()->orderBy('id')->first();
        $this->assertNotNull($product);

        $this->get('/urun/'.strtoupper($product->slug))
            ->assertRedirect('/urun/'.$product->slug);
    }
}
