<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\RelatedProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InternalLinkingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_default_catalog_sort_pins_featured_products_first(): void
    {
        $category = Category::query()->where('slug', 'hidroforlar')->firstOrFail();
        $products = Product::query()
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $category->id))
            ->orderBy('id')
            ->get();

        $this->assertGreaterThanOrEqual(2, $products->count());

        $featured = $products->first();
        $regular = $products->last();

        $featured->forceFill([
            'featured' => true,
            'stock' => 3,
            'created_at' => now()->subMonth(),
        ])->save();

        $regular->forceFill([
            'featured' => false,
            'stock' => 8,
            'created_at' => now(),
        ])->save();

        $this->get($category->storefrontUrl())
            ->assertOk()
            ->assertSeeInOrder([$featured->name, $regular->name]);
    }

    public function test_related_products_exclude_the_current_product(): void
    {
        $product = Product::query()->active()->with('categories')->firstOrFail();
        $related = app(RelatedProductService::class)->for($product, 6);

        $this->assertFalse($related->contains(fn (Product $item) => $item->id === $product->id));
        $this->assertLessThanOrEqual(6, $related->count());
    }

    public function test_product_page_shows_internal_hub_links(): void
    {
        $product = Product::query()->active()->with(['brand', 'categories'])->firstOrFail();

        $this->get('/urun/'.$product->slug)
            ->assertOk()
            ->assertSee(__('shop.pdp_hub_title'), false);
    }

    public function test_leaf_category_lists_sibling_categories(): void
    {
        $leaf = Category::query()->where('slug', 'ev-tipi-hidroforlar')->firstOrFail();

        $this->get($leaf->storefrontUrl())
            ->assertOk()
            ->assertSee(__('shop.sibling_categories'), false)
            ->assertSee('Karavan', false);
    }

    public function test_blog_tags_link_to_tag_hubs(): void
    {
        $post = BlogPost::query()->where('slug', 'hidrofor-secimi-rehberi')->firstOrFail();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('/blog/etiket/hidrofor', false)
            ->assertDontSee('<span class="shop-article-tag">', false);
    }

    public function test_thin_blog_tag_page_is_noindex_until_clustered(): void
    {
        $this->get('/blog/etiket/hidrofor')
            ->assertOk()
            ->assertSee('noindex', false);

        BlogPost::query()->create([
            'slug' => 'apartman-hidrofor-secimi',
            'title' => 'Apartman için hidrofor nasıl seçilir',
            'excerpt' => 'Kat sayısı ve eşzamanlı kullanım.',
            'content' => '<p>Apartman hidrofor seçiminde daire sayısı belirleyicidir.</p>',
            'tags' => ['hidrofor', 'rehber'],
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get('/blog/etiket/hidrofor')
            ->assertOk()
            ->assertSee('index, follow', false);

        $this->get('/blog/hidrofor-secimi-rehberi')
            ->assertOk()
            ->assertSee('Apartman için hidrofor', false)
            ->assertSee(__('shop.related_posts'), false);

        Cache::flush();

        $this->get('/sitemap-blog.xml')
            ->assertOk()
            ->assertSee('/blog/etiket/hidrofor', false);

        $this->get('/site-haritasi')
            ->assertOk()
            ->assertSee(__('shop.html_sitemap_blog'), false);
    }
}
