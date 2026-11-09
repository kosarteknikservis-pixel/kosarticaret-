<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_second_catalog_page_is_indexable_with_page_title(): void
    {
        $category = Category::query()->create([
            'name' => 'Sayfalama Index Test',
            'slug' => 'sayfalama-index-test',
            'active' => true,
            'sort_order' => 0,
        ]);

        for ($i = 1; $i <= 13; $i++) {
            $product = Product::query()->create([
                'name' => 'Sayfalama Index Ürün '.$i,
                'slug' => 'sayfalama-index-urun-'.$i,
                'sku' => 'PAGIDX-'.$i,
                'price' => 100 + $i,
                'stock' => 3,
                'is_active' => true,
            ]);
            $product->categories()->attach($category->id);
        }

        $this->get('/kategoriler/'.$category->slug.'?page=2')
            ->assertOk()
            ->assertSee('index, follow', false)
            ->assertSee('Sayfa 2', false);
    }

    public function test_filtered_catalog_page_stays_noindex(): void
    {
        $brand = Brand::query()->create([
            'name' => 'Sayfalama Marka',
            'slug' => 'sayfalama-marka',
            'active' => true,
            'sort_order' => 0,
        ]);
        $category = Category::query()->create([
            'name' => 'Sayfalama Filtre Test',
            'slug' => 'sayfalama-filtre-test',
            'active' => true,
            'sort_order' => 0,
        ]);

        for ($i = 1; $i <= 13; $i++) {
            $product = Product::query()->create([
                'name' => 'Sayfalama Filtre Ürün '.$i,
                'slug' => 'sayfalama-filtre-urun-'.$i,
                'sku' => 'PAGFTR-'.$i,
                'price' => 100 + $i,
                'stock' => 3,
                'is_active' => true,
                'brand_id' => $brand->id,
            ]);
            $product->categories()->attach($category->id);
        }

        $this->get('/kategoriler/'.$category->slug.'?marka='.$brand->slug)
            ->assertOk()
            ->assertSee('noindex', false);
    }
}
