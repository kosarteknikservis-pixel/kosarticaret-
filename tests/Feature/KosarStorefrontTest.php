<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\SiteFavicon;
use App\Support\SiteLogo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KosarStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_homepage_shows_kosar_branding_not_kampa(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Kosar', false);
        $response->assertDontSee('Kampa Panel', false);
        $response->assertDontSee('KAMPA10', false);
    }

    public function test_storefront_routes_are_available(): void
    {
        $this->get('/urunler')->assertOk();
        $this->get('/blog')->assertOk();
        $this->get('/sepet')->assertOk();
        $this->get('/yonetim/giris')->assertOk();
        $this->get('/sayfa/hakkimizda')->assertOk();
        $this->get('/ara')->assertOk();
        $this->get('/favoriler')->assertOk();
        $this->get('/markalar')->assertOk();
        $this->get('/iletisim')->assertOk();
        $this->get('/giris')->assertOk();
        $this->get('/kayit')->assertOk();
        $this->get('/siparis-takip')->assertOk();
    }

    public function test_seo_endpoints(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertSee('Disallow: /ara');
        $this->get('/')->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('OnlineStore', false);
    }

    public function test_sitemap_uses_nested_category_urls(): void
    {
        $child = Category::query()->where('slug', 'ev-tipi-hidroforlar')->firstOrFail();
        $nestedUrl = $child->storefrontUrl();
        $flatUrl = route('categories.show', ['category' => $child->slug]);

        $content = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.$nestedUrl.'</loc>', $content);
        $this->assertStringNotContainsString('<loc>'.$flatUrl.'</loc>', $content);
    }

    public function test_product_page_rich_seo_markup(): void
    {
        $product = Product::query()->with('brand', 'categories')->firstOrFail();

        $this->get('/urun/'.$product->slug)
            ->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('og:type" content="product"', false)
            ->assertSee('BreadcrumbList', false);

        if ($product->sku) {
            $this->get('/urun/'.$product->slug)->assertSee($product->sku, false);
        }
    }

    public function test_search_with_query_is_noindex(): void
    {
        $this->get('/ara?q=pompa')
            ->assertOk()
            ->assertSee('noindex', false);
    }

    public function test_catalog_filter_urls_are_noindex(): void
    {
        $brand = Brand::query()->where('active', true)->firstOrFail();

        $this->get('/urunler?marka='.$brand->slug)
            ->assertOk()
            ->assertSee('noindex', false);

        $category = Category::query()->where('active', true)->firstOrFail();

        $this->get($category->storefrontUrl().'?stokta=1')
            ->assertOk()
            ->assertSee('noindex', false);
    }

    public function test_homepage_has_semantic_h1(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<h1', false);
    }

    public function test_checkout_and_account_pages_are_noindex(): void
    {
        $this->get('/giris')->assertOk()->assertSee('noindex', false);
        $this->get('/kayit')->assertOk()->assertSee('noindex', false);
        $this->get('/siparis-takip')->assertOk()->assertSee('noindex', false);
    }

    public function test_not_found_page_is_noindex(): void
    {
        $this->get('/bu-sayfa-yok-404-test')
            ->assertNotFound()
            ->assertSee('noindex', false);
    }

    public function test_blog_detail_uses_blog_posting_schema(): void
    {
        $post = BlogPost::query()->firstOrFail();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('BlogPosting', false);
    }

    public function test_product_and_blog_detail_pages(): void
    {
        $product = Product::query()->firstOrFail();
        $this->get('/urun/'.$product->slug)->assertOk();

        $post = BlogPost::query()->firstOrFail();
        $this->get('/blog/'.$post->slug)->assertOk();

        $brand = Brand::query()->where('active', true)->firstOrFail();
        $this->get('/marka/'.$brand->slug)->assertOk();
    }

    public function test_admin_user_is_kosar(): void
    {
        $this->assertTrue(
            User::query()->where('email', 'admin@kosar.com.tr')->where('is_admin', true)->exists()
        );
    }

    public function test_cart_ajax_add(): void
    {
        $product = Product::query()->where('stock', '>', 0)->firstOrFail();

        $this->postJson('/sepet/ajax/ekle/'.$product->slug, ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('count', 1);
    }

    public function test_legal_page_redirects(): void
    {
        $this->get('/hakkimizda')->assertRedirect('/sayfa/hakkimizda');
        $this->get('/on-bilgilendirme')->assertRedirect('/sayfa/on-bilgilendirme');
    }

    public function test_site_logo_appears_on_homepage_when_uploaded(): void
    {
        $path = 'branding/test-logo.png';
        Storage::disk('public')->put($path, UploadedFile::fake()->image('logo.png', 200, 60)->getContent());
        SiteSetting::set('site_logo', $path);

        $url = SiteLogo::url();
        $this->assertNotNull($url);

        $this->get('/')
            ->assertOk()
            ->assertSee($url, false);
    }

    public function test_homepage_shows_fallback_without_logo(): void
    {
        SiteSetting::set('site_logo', null);

        $this->get('/')
            ->assertOk()
            ->assertSee('shop-logo-fallback', false);
    }

    public function test_favicon_is_linked_on_storefront(): void
    {
        SiteSetting::set('site_favicon', null);

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee(asset('favicon.svg'), false);

        $this->get('/favicon.ico')->assertRedirect(asset('favicon.svg'));
    }

    public function test_custom_favicon_from_settings(): void
    {
        $path = 'branding/test-favicon.png';
        Storage::disk('public')->put($path, UploadedFile::fake()->image('favicon.png', 32, 32)->getContent());
        SiteSetting::set('site_favicon', $path);

        $url = SiteFavicon::customUrl();
        $this->assertNotNull($url);

        $this->get('/')
            ->assertOk()
            ->assertSee($url, false);
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post('/iletisim', [
            'ad_soyad' => 'Test Kullanıcı',
            'eposta' => 'test@ornek.com',
            'telefon' => '5551112233',
            'konu' => 'Destek',
            'mesaj' => 'Merhaba, yardım lazım.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'test@ornek.com',
            'subject' => 'Destek',
        ]);
        $this->assertSame(1, ContactMessage::query()->count());
    }

    public function test_search_suggest_returns_json(): void
    {
        $product = Product::query()->where('stock', '>', 0)->firstOrFail();
        $term = mb_substr($product->name, 0, 4);

        $this->getJson('/ara/oneri?q='.$term)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['results', 'search_url']);
    }

    public function test_customer_registration_and_account(): void
    {
        $this->post('/kayit', [
            'name' => 'Test Müşteri',
            'email' => 'musteri@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('account.index'));

        $this->get('/hesabim')->assertOk()->assertSee('Test Müşteri');
    }

    public function test_guest_home_shows_customer_login_not_admin_panel(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('shop.login_cta'), false)
            ->assertDontSee(__('shop.admin_panel'), false);
    }

    public function test_admin_storefront_header_user_icon_not_linked_to_panel(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $this->assertNotNull($admin);

        $html = $this->actingAs($admin)->get('/')->assertOk()->getContent();

        preg_match('/<div class="shop-header-toolbar__icons">(.*?)<\/div>/s', $html, $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $toolbarIcons = $matches[1];
        $this->assertStringNotContainsString('/yonetim', $toolbarIcons);
        $this->assertStringNotContainsString(route('login'), $toolbarIcons);
        $this->assertStringNotContainsString(route('account.index'), $toolbarIcons);
    }

    public function test_admin_cannot_use_customer_account_area(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin)
            ->get('/hesabim')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_brand_catalog_shows_pagination_range_and_page_links(): void
    {
        $brand = Brand::query()->create([
            'slug' => 'pagination-test',
            'name' => 'Pagination Test',
            'active' => true,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Product::query()->create([
                'slug' => 'pagination-test-urun-'.$i,
                'sku' => 'PT-'.$i,
                'name' => 'Test Ürün '.$i,
                'price' => 1000 + $i,
                'stock' => 10,
            ])->update(['brand_id' => $brand->id]);
        }

        $this->get(route('brands.show', $brand))
            ->assertOk()
            ->assertSee('1–12 / 15 ürün', false)
            ->assertSee('page=2', false);

        $this->get(route('brands.show', ['brand' => $brand, 'page' => 2]))
            ->assertOk()
            ->assertSee('13–15 / 15 ürün', false);
    }

    public function test_catalog_description_renders_below_product_grid(): void
    {
        $brand = Brand::query()->create([
            'slug' => 'desc-below-brand',
            'name' => 'Desc Below Brand',
            'description' => '<p>MARKA AÇIKLAMASI ÜRÜN ALTINDA</p>',
            'active' => true,
        ]);

        Product::query()->create([
            'slug' => 'desc-below-urun',
            'sku' => 'DB-1',
            'name' => 'Test',
            'price' => 100,
            'stock' => 1,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $html = $this->get(route('brands.show', $brand))->assertOk()->getContent();

        $this->assertStringContainsString('MARKA AÇIKLAMASI ÜRÜN ALTINDA', $html);
        $this->assertGreaterThan(
            strpos($html, 'shop-catalog-main'),
            strpos($html, 'shop-catalog-intro')
        );
    }
}
