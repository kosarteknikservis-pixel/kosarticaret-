<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\SupportAssistant\SupportAssistantTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportAssistantCatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Product::query()->delete();
        Category::query()->where('name', 'like', '%sıtıcı%')->update(['active' => false]);

        $ardonat = Brand::query()->updateOrCreate(['slug' => 'ardonat'], ['name' => 'Ardonat', 'active' => true]);
        $renato = Brand::query()->updateOrCreate(['slug' => 'renato'], ['name' => 'Renato', 'active' => true]);
        $root = Category::query()->updateOrCreate(['slug' => 'test-isitma'], ['name' => 'Isıtma Sistemleri', 'active' => true]);
        $heaters = Category::query()->updateOrCreate(['slug' => 'test-elektrikli-isiticilar'], ['name' => 'Elektrikli Isıtıcılar', 'active' => true, 'parent_id' => $root->id]);
        $panel = Category::query()->updateOrCreate(['slug' => 'test-panel-isiticilar'], ['name' => 'Panel Isıtıcılar', 'active' => true, 'parent_id' => $heaters->id]);

        foreach ([
            ['ardonat-duvar', 'Ardonat Halogen 2000W Duvar Tipi Dış Mekân Isıtıcı', $ardonat, 10],
            ['ardonat-tower', 'Ardonat Halogen Tower 3000W Dikey Dış Mekân Isıtıcı', $ardonat, 10],
            ['ardonat-pro', 'Ardonat Halogen Pro 2000W Kumandalı Isıtıcı', $ardonat, 10],
            ['renato-karbon', 'Renato R-3001 2500W Dikey Karbon Isıtıcı', $renato, 5],
        ] as [$slug, $name, $brand, $stock]) {
            $product = Product::query()->create(['slug' => $slug, 'name' => $name, 'price' => 1000, 'stock' => $stock, 'is_active' => true, 'brand_id' => $brand->id]);
            $product->categories()->attach($panel->id);
        }

        Product::query()->create(['slug' => 'panel-x', 'name' => 'Ardonat Konvektör Panel 1500W', 'price' => 900, 'stock' => 3, 'is_active' => true, 'brand_id' => $ardonat->id])
            ->categories()->attach($panel->id);
    }

    private function search(array $args): array
    {
        return app(SupportAssistantTools::class)->execute('search_products', $args);
    }

    #[Test]
    public function turkish_case_and_ascii_spellings_find_heaters(): void
    {
        foreach (['ısıtıcı', 'ISITICI', 'isitici', 'ısıtıcılar', 'Isıtıcı fiyatları'] as $query) {
            $result = $this->search(['query' => $query]);
            $names = array_column($result['urunler'], 'ad');

            $this->assertCount(5, $names, $query);
            $this->assertStringContainsString('Isıtıcı', $names[0], $query);
            $this->assertSame('Elektrikli Isıtıcılar', $result['kategoriler'][0]['ad'] ?? null, $query);
        }
    }

    #[Test]
    public function results_mix_brands_and_list_them(): void
    {
        $result = $this->search(['query' => 'karbon ısıtıcı']);
        $this->assertSame(['Renato R-3001 2500W Dikey Karbon Isıtıcı'], array_column($result['urunler'], 'ad'));

        $result = $this->search(['query' => 'ısıtıcı']);
        $brands = array_column($result['urunler'], 'marka');
        $this->assertContains('Renato', array_slice($brands, 0, 4));
        $this->assertSame(['Ardonat', 'Renato'], array_column($result['markalar'], 'marka'));
    }

    #[Test]
    public function brand_filter_narrows_and_reports_missing_brand(): void
    {
        $result = $this->search(['query' => 'ısıtıcı', 'brand' => 'renato']);
        $this->assertSame(['Renato'], array_values(array_unique(array_column($result['urunler'], 'marka'))));

        $result = $this->search(['query' => 'ısıtıcı', 'brand' => 'Sumak']);
        $this->assertTrue($result['bulunamadi']);
        $this->assertSame(['Ardonat', 'Renato'], array_column($result['diger_markalar'], 'marka'));
    }

    #[Test]
    public function a_one_letter_typo_finds_the_catalog_word(): void
    {
        $ardonat = Brand::query()->where('slug', 'ardonat')->first();
        Product::query()->create([
            'slug' => 'led-armatur-60',
            'name' => 'Ardonat Led Armatür 60 Led',
            'price' => 800,
            'stock' => 4,
            'is_active' => true,
            'brand_id' => $ardonat->id,
        ]);
        Cache::flush();

        $result = $this->search(['query' => 'armatör']);

        $this->assertSame(['Ardonat Led Armatür 60 Led'], array_column($result['urunler'], 'ad'));
        $this->assertStringContainsString('armator → armatur', $result['yazim_duzeltmesi']);
    }

    #[Test]
    public function unknown_product_is_not_invented(): void
    {
        $result = $this->search(['query' => 'buzdolabı']);
        $this->assertSame([], $result['urunler']);
        $this->assertStringContainsString('uydurma', $result['not']);
    }
}
