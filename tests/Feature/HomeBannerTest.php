<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\HomeRow;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\HomeBannerSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_homepage_shows_active_banner_title(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('banner.jpg', 1440, 520)->store('banners', 'public');

        $row = HomeRow::query()->create(['columns' => [12], 'sort_order' => 0]);
        HomeBanner::query()->create([
            'type' => HomeBanner::TYPE_SLIDER,
            'home_row_id' => $row->id,
            'col_index' => 0,
            'image' => $path,
            'title' => 'Hidrofor Kampanyası',
            'subtitle' => 'Sezon indirimi',
            'image_alt' => 'Hidrofor ürünleri kampanya bannerı',
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Hidrofor Kampanyası', false)
            ->assertSee('shop-home-layout', false);
    }

    public function test_admin_can_save_layout_with_columns(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $row = HomeRow::query()->create(['columns' => [6, 6], 'sort_order' => 0]);
        $b1 = HomeBanner::query()->create(['type' => 'banner', 'home_row_id' => $row->id, 'col_index' => 0, 'image' => 'banners/a.jpg', 'active' => true]);
        $b2 = HomeBanner::query()->create(['type' => 'product', 'home_row_id' => $row->id, 'col_index' => 1, 'product_id' => Product::query()->value('id'), 'active' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.home-banners.layout.save'), [
                'rows' => [
                    ['id' => $row->id, 'columns' => [[$b2->id], [$b1->id]]],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, HomeBanner::query()->find($b1->id)->col_index);
        $this->assertSame(0, HomeBanner::query()->find($b2->id)->col_index);
    }

    public function test_admin_builder_page_loads(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.home-banners.builder'))
            ->assertOk()
            ->assertSee('Ana sayfa düzenleyici', false)
            ->assertSee('hp-builder', false);
    }

    public function test_admin_can_create_banner_with_upload(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('is_admin', true)->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->post(route('admin.home-banners.store'), [
            'type' => 'slider',
            'title' => 'Yeni sezon',
            'image_alt' => 'Fan koleksiyonu',
            'active' => '1',
            'image_file' => UploadedFile::fake()->image('slide.png', 1440, 520),
        ]);

        $response->assertRedirect(route('admin.home-banners.index'));
        $this->assertDatabaseHas('home_banners', ['title' => 'Yeni sezon']);
    }

    public function test_custom_banner_dimensions_apply_on_homepage(): void
    {
        Storage::fake('public');
        SiteSetting::set('home_banner_width', '1920');
        SiteSetting::set('home_banner_height', '640');

        $path = UploadedFile::fake()->image('b.jpg', 1920, 640)->store('banners', 'public');
        $row = HomeRow::query()->create(['columns' => [12], 'sort_order' => 0]);
        HomeBanner::query()->create([
            'type' => HomeBanner::TYPE_SLIDER,
            'home_row_id' => $row->id,
            'image' => $path,
            'title' => 'Özel ölçü test',
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('1920 / 640', false);

        $this->assertSame(1920, HomeBannerSpec::width());
        $this->assertSame(640, HomeBannerSpec::height());
    }

    public function test_admin_can_save_custom_banner_dimensions(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->put(route('admin.home-banners.dimensions'), [
                'home_banner_width' => 1600,
                'home_banner_height' => 500,
            ])
            ->assertRedirect(route('admin.home-banners.index'));

        $this->assertSame(1600, HomeBannerSpec::width());
        $this->assertSame(500, HomeBannerSpec::height());
    }

    public function test_product_tile_links_to_product_page(): void
    {
        Storage::fake('public');
        $product = Product::query()->first();
        $this->assertNotNull($product);

        $path = UploadedFile::fake()->image('p.jpg')->store('products', 'public');
        $product->update(['image' => $path]);

        $row = HomeRow::query()->create(['columns' => [6, 6], 'sort_order' => 0]);
        HomeBanner::query()->create([
            'type' => HomeBanner::TYPE_PRODUCT,
            'home_row_id' => $row->id,
            'col_index' => 0,
            'product_id' => $product->id,
            'active' => true,
            'sort_order' => 0,
        ]);

        $url = route('products.show', $product);
        $this->get('/')->assertOk()->assertSee($url, false)->assertSee('shop-home-block--product', false);
    }

    public function test_product_list_block_shows_products_from_category(): void
    {
        $category = Category::query()->create([
            'name' => 'Liste Kategori',
            'slug' => 'liste-kategori',
            'active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'Liste Ürünü',
            'slug' => 'liste-urunu',
            'sku' => 'LST-1',
            'price' => 99,
            'stock' => 5,
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id);

        $row = HomeRow::query()->create(['columns' => [12], 'sort_order' => 0]);
        HomeBanner::query()->create([
            'type' => HomeBanner::TYPE_PRODUCT_LIST,
            'home_row_id' => $row->id,
            'col_index' => 0,
            'product_source' => 'category',
            'category_id' => $category->id,
            'product_limit' => 4,
            'title' => 'Kategori Vitrini',
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kategori Vitrini', false)
            ->assertSee('Liste Ürünü', false)
            ->assertSee('shop-home-product-list', false);
    }

    public function test_admin_can_create_product_list_by_brand(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $brand = Brand::query()->create(['name' => 'Liste Marka', 'slug' => 'liste-marka', 'active' => true]);
        $row = HomeRow::query()->create(['columns' => [12], 'sort_order' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.home-banners.store'), [
                'type' => HomeBanner::TYPE_PRODUCT_LIST,
                'home_row_id' => $row->id,
                'col_index' => 0,
                'product_source' => 'brand',
                'brand_id' => $brand->id,
                'product_limit' => 6,
                'title' => 'Marka ürünleri',
                'active' => '1',
                'from_builder' => '1',
            ])
            ->assertRedirect(route('admin.home-banners.builder'));

        $this->assertDatabaseHas('home_banners', [
            'type' => HomeBanner::TYPE_PRODUCT_LIST,
            'brand_id' => $brand->id,
            'product_source' => 'brand',
        ]);
    }

    public function test_inactive_banner_not_on_homepage(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('x.jpg')->store('banners', 'public');

        $row = HomeRow::query()->create(['columns' => [12], 'sort_order' => 0]);
        HomeBanner::query()->create([
            'type' => HomeBanner::TYPE_SLIDER,
            'home_row_id' => $row->id,
            'image' => $path,
            'title' => 'Gizli kampanya',
            'active' => false,
            'sort_order' => 0,
        ]);

        $this->get('/')->assertOk()->assertDontSee('Gizli kampanya', false);
    }
}
