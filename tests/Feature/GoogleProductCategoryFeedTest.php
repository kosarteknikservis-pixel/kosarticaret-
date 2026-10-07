<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleProductCategoryFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_omits_banned_ids_and_unmapped_products(): void
    {
        $well = Category::query()->create([
            'slug' => 'derin-kuyu-dalgic-pompa',
            'name' => 'Derin kuyu',
            'active' => true,
        ]);
        $this->product('derin-kuyu-test', '4 inç derin kuyu', $well);

        $unknown = Category::query()->create([
            'slug' => 'bilinmeyen-kategori',
            'name' => 'Bilinmeyen',
            'active' => true,
        ]);
        $this->product('kimliksiz-test', 'Kimliksiz ürün', $unknown);

        $xml = $this->get('/urun-feed.xml')->assertOk()->streamedContent();

        $this->assertStringContainsString('<g:google_product_category>500100</g:google_product_category>', $xml);
        $this->assertStringNotContainsString('>1869<', $xml);
        $this->assertStringNotContainsString('>1795<', $xml);
        $this->assertSame(1, substr_count($xml, '<g:google_product_category>'));
    }

    public function test_product_page_html_does_not_carry_the_feed_category(): void
    {
        $product = Product::query()->create([
            'slug' => 'sayfa-html-test',
            'sku' => 'HTML-1',
            'name' => 'Sayfa HTML Test Pompası',
            'price' => 250,
            'stock' => 3,
            'is_active' => true,
            'image' => 'products/html-test.jpg',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('<h1 class="shop-pdp-info__title">Sayfa HTML Test Pompası</h1>', false)
            ->assertDontSee('google_product_category', false)
            ->assertDontSee('1869', false)
            ->assertDontSee('1795', false);
    }

    private function product(string $slug, string $name, Category $category): Product
    {
        $product = Product::query()->create([
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'name' => $name,
            'price' => 100,
            'stock' => 2,
            'is_active' => true,
            'image' => 'products/'.$slug.'.jpg',
        ]);
        $product->categories()->attach($category->id);

        return $product;
    }
}
