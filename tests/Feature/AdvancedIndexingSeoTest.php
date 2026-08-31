<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedIndexingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sitemap_index_uses_xsl_and_child_sitemaps(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('xml-stylesheet', $xml);
        $this->assertStringContainsString('sitemapindex', $xml);
        $this->assertStringContainsString('/sitemap-categories.xml', $xml);
        $this->assertStringContainsString('/sitemap-products-1.xml', $xml);
        $this->assertStringContainsString('/sitemap.xsl', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
    }

    public function test_sitemap_stylesheet_is_served(): void
    {
        $this->get('/sitemap.xsl')
            ->assertOk()
            ->assertHeader('content-type', 'text/xsl; charset=UTF-8')
            ->assertSee('xsl:stylesheet', false);
    }

    public function test_llms_txt_lists_real_storefront_urls(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('Kategoriler', false)
            ->assertSee('/kategoriler', false)
            ->assertSee('/blog', false)
            ->assertSee('Rehberler', false)
            ->assertSee('/site-haritasi', false);
    }

    public function test_html_sitemap_and_rss_are_indexable(): void
    {
        $this->get('/site-haritasi')
            ->assertOk()
            ->assertSee(__('shop.html_sitemap_title'), false)
            ->assertSee('index, follow', false);

        $this->get('/feed.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/rss+xml; charset=UTF-8');
    }

    public function test_robots_points_to_index_and_llms(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: ', false)
            ->assertSee('/sitemap.xml', false)
            ->assertDontSee('Sitemap: '.url('/sitemap-images.xml'), false)
            ->assertSee('llms.txt', false)
            ->assertSee('GPTBot', false)
            ->assertSee('Google-Extended', false)
            ->assertSee('Claude-SearchBot', false)
            ->assertSee('Perplexity-User', false)
            ->assertSee('Applebot-Extended', false)
            ->assertSee('Amazonbot', false)
            ->assertSee('cohere-ai', false)
            ->assertDontSee('filter', false)
            ->assertDontSee('Disallow: /urun-kategori', false)
            ->assertDontSee('Disallow: /magaza', false)
            ->assertDontSee('Disallow: /shop', false)
            ->assertSee('Disallow: /urun-etiket', false)
            ->assertSee('Disallow: /tag/', false);
    }

    public function test_bing_site_auth_xml_is_served_when_configured(): void
    {
        SiteSetting::set('bing_site_auth_xml', '<user>1574AB4B2731BD4765E799EE759774C9</user>');

        $this->get('/BingSiteAuth.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('1574AB4B2731BD4765E799EE759774C9', false)
            ->assertSee('<users>', false);
    }

    public function test_bing_site_auth_xml_returns_404_when_empty(): void
    {
        SiteSetting::set('bing_site_auth_xml', '');

        $this->get('/BingSiteAuth.xml')->assertNotFound();
    }

    public function test_product_offer_includes_shipping_and_return_policy(): void
    {
        $product = Product::query()->firstOrFail();

        $html = $this->get('/urun/'.$product->slug)->assertOk()->getContent();

        $this->assertStringContainsString('OfferShippingDetails', $html);
        $this->assertStringContainsString('MerchantReturnPolicy', $html);
        $this->assertStringContainsString('shippingDestination', $html);
        $this->assertStringNotContainsString('SearchAction', $html);
    }

    public function test_homepage_keeps_organization_without_blocked_searchaction(): void
    {
        SiteSetting::set('social_youtube_url', 'https://youtube.com/@kosar');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('OnlineStore', $html);
        $this->assertStringContainsString('hasMerchantReturnPolicy', $html);
        $this->assertStringContainsString('sameAs', $html);
        $this->assertStringNotContainsString('SearchAction', $html);
    }

    public function test_homepage_title_does_not_repeat_brand(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<title>Su Pompası, Hidrofor, Dalgıç Pompa ve Vantilatör \| [^<]+<\/title>/u',
            $html
        );
        $this->assertStringNotContainsString('Koşar Ticaret | Su Pompası', $html);
    }

    public function test_empty_leaf_category_is_noindex_and_omitted_from_sitemap(): void
    {
        $parent = \App\Models\Category::query()->create([
            'slug' => 'test-hub',
            'name' => 'Test Hub',
            'active' => true,
        ]);
        $leaf = \App\Models\Category::query()->create([
            'slug' => 'bos-yaprak',
            'name' => 'Boş Yaprak',
            'parent_id' => $parent->id,
            'active' => true,
        ]);

        $this->get($leaf->storefrontUrl())
            ->assertOk()
            ->assertSee('noindex, follow', false);

        \Illuminate\Support\Facades\Cache::flush();

        $this->get('/sitemap-categories.xml')
            ->assertOk()
            ->assertDontSee($leaf->storefrontUrl(), false)
            ->assertSee($parent->storefrontUrl(), false);
    }

    public function test_legacy_sayfa_query_redirects_to_page(): void
    {
        $category = \App\Models\Category::query()->where('active', true)->firstOrFail();

        $this->get($category->storefrontUrl().'?sayfa=2')
            ->assertRedirect($category->storefrontUrl().'?page=2');
    }

    public function test_pdp_main_image_reserves_dimensions(): void
    {
        $product = Product::query()->active()->whereNotNull('image')->firstOrFail();

        $this->get('/urun/'.$product->slug)
            ->assertOk()
            ->assertSee('id="pdp-main-img"', false)
            ->assertSee('width="1200"', false)
            ->assertSee('height="1200"', false);
    }
}
