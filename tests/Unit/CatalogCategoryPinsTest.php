<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Support\CatalogCategoryPins;
use App\Support\CatalogPaginationSeo;
use App\Support\CatalogSpecFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

class CatalogCategoryPinsTest extends TestCase
{
    public function test_hidroforlar_pins_loaded_from_config(): void
    {
        $category = new Category(['slug' => 'hidroforlar']);
        $skus = CatalogCategoryPins::skuOrderFor($category);

        $this->assertNotEmpty($skus);
        $this->assertContains('HMJH-15', $skus);
        $this->assertContains('PKMH60-24-LT', $skus);
        $this->assertNotContains('WNPH100', $skus);
    }

    public function test_user_sort_disables_pins(): void
    {
        $category = new Category(['slug' => 'hidroforlar']);

        $this->assertTrue(CatalogCategoryPins::appliesToDefaultSort($category, ''));
        $this->assertFalse(CatalogCategoryPins::appliesToDefaultSort($category, 'fiyat-artan'));
        $this->assertFalse(CatalogCategoryPins::appliesToDefaultSort($category, 'isim'));
    }

    public function test_other_category_has_no_pins(): void
    {
        $category = new Category(['slug' => 'dalgic-pompalar']);

        $this->assertSame([], CatalogCategoryPins::skuOrderFor($category));
        $this->assertFalse(CatalogCategoryPins::appliesToDefaultSort($category, ''));
    }

    public function test_voltaj_is_noindex_filter_key(): void
    {
        $this->assertContains('voltaj', CatalogPaginationSeo::FILTER_QUERY_KEYS);

        $request = Request::create('/kategoriler/hidrofor-sistemleri/hidroforlar?voltaj=220', 'GET');
        $this->assertTrue(CatalogPaginationSeo::hasActiveFilters($request));
        $this->assertSame(
            \App\Support\Seo::ROBOTS_NOINDEX,
            CatalogPaginationSeo::robots($request, 1)
        );
    }

    public function test_voltaj_combined_with_marka_is_noindex(): void
    {
        $request = Request::create(
            '/kategoriler/hidrofor-sistemleri/hidroforlar?voltaj=380&marka=sumak&stokta=1',
            'GET'
        );
        $this->assertTrue(CatalogPaginationSeo::hasActiveFilters($request));
        $this->assertSame(
            \App\Support\Seo::ROBOTS_NOINDEX,
            CatalogPaginationSeo::robots($request, 1)
        );
    }

    public function test_normalize_voltaj_excludes_dual_and_does_not_merge_230_400(): void
    {
        $this->assertSame('220', CatalogSpecFilters::normalizeVoltaj('220 Volt (Monofaze)'));
        $this->assertSame('380', CatalogSpecFilters::normalizeVoltaj('380 Volt (Trifaze)'));
        $this->assertNull(CatalogSpecFilters::normalizeVoltaj('220V monofaze (380V seçenekli)'));
        $this->assertNull(CatalogSpecFilters::normalizeVoltaj('230 V'));
        $this->assertNull(CatalogSpecFilters::normalizeVoltaj('400 V'));
        $this->assertNull(CatalogSpecFilters::normalizeVoltaj('220/380 V'));
        $this->assertNull(CatalogSpecFilters::normalizeVoltaj(''));
    }
}
