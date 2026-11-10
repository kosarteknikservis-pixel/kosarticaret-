<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SlugRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugChangeRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_slug_change_redirects_old_url_to_new(): void
    {
        $product = Product::query()->create([
            'slug' => 'eski-urun',
            'sku' => 'SLUG-1',
            'name' => 'Eski Ürün',
            'price' => 100,
            'stock' => 1,
            'is_active' => true,
        ]);

        $product->update(['slug' => 'yeni-urun']);

        $this->assertDatabaseHas('slug_redirects', [
            'from_path' => '/urun/eski-urun',
            'to_path' => '/urun/yeni-urun',
        ]);

        $this->get('/urun/eski-urun')
            ->assertRedirect('/urun/yeni-urun')
            ->assertStatus(301);

        $this->get('/urun/yeni-urun')->assertOk();
    }

    public function test_category_slug_change_redirects_self_and_child_paths(): void
    {
        $parent = Category::query()->create([
            'slug' => 'eski-ust',
            'name' => 'Eski Üst',
            'active' => true,
        ]);

        $child = Category::query()->create([
            'slug' => 'alt-kategori',
            'name' => 'Alt Kategori',
            'parent_id' => $parent->id,
            'active' => true,
        ]);

        $parent->update(['slug' => 'yeni-ust']);
        $child->refresh();

        $this->assertDatabaseHas('slug_redirects', [
            'from_path' => '/kategoriler/eski-ust',
            'to_path' => '/kategoriler/yeni-ust',
        ]);
        $this->assertDatabaseHas('slug_redirects', [
            'from_path' => '/kategoriler/eski-ust/alt-kategori',
            'to_path' => '/kategoriler/yeni-ust/alt-kategori',
        ]);

        $this->get('/kategoriler/eski-ust')
            ->assertRedirect('/kategoriler/yeni-ust')
            ->assertStatus(301);

        $this->get('/kategoriler/eski-ust/alt-kategori')
            ->assertRedirect('/kategoriler/yeni-ust/alt-kategori')
            ->assertStatus(301);

        $this->get($child->storefrontUrl())->assertOk();
    }

    public function test_two_slug_changes_collapse_chain_to_final_target(): void
    {
        $product = Product::query()->create([
            'slug' => 'slug-a',
            'sku' => 'SLUG-A',
            'name' => 'Ürün A',
            'price' => 50,
            'stock' => 1,
            'is_active' => true,
        ]);

        $product->update(['slug' => 'slug-b']);
        $product->update(['slug' => 'slug-c']);

        $this->assertDatabaseHas('slug_redirects', [
            'from_path' => '/urun/slug-a',
            'to_path' => '/urun/slug-c',
        ]);
        $this->assertDatabaseHas('slug_redirects', [
            'from_path' => '/urun/slug-b',
            'to_path' => '/urun/slug-c',
        ]);
        $this->assertDatabaseMissing('slug_redirects', [
            'from_path' => '/urun/slug-a',
            'to_path' => '/urun/slug-b',
        ]);

        $this->get('/urun/slug-a')
            ->assertRedirect('/urun/slug-c')
            ->assertStatus(301);
    }

    public function test_slug_reverted_does_not_create_loop_and_original_returns_200(): void
    {
        $product = Product::query()->create([
            'slug' => 'slug-a',
            'sku' => 'SLUG-LOOP',
            'name' => 'Ürün Loop',
            'price' => 50,
            'stock' => 1,
            'is_active' => true,
        ]);

        $product->update(['slug' => 'slug-b']);
        $product->update(['slug' => 'slug-a']);

        $this->assertDatabaseMissing('slug_redirects', [
            'from_path' => '/urun/slug-a',
        ]);
        $this->assertSame(0, SlugRedirect::query()->where('from_path', '/urun/slug-a')->count());

        $this->get('/urun/slug-a')->assertOk();

        $this->get('/urun/slug-b')
            ->assertRedirect('/urun/slug-a')
            ->assertStatus(301);
    }
}
