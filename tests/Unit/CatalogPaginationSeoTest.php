<?php

namespace Tests\Unit;

use App\Support\CatalogPaginationSeo;
use Illuminate\Http\Request;
use Tests\TestCase;

class CatalogPaginationSeoTest extends TestCase
{
    public function test_filter_query_params_trigger_noindex(): void
    {
        $request = Request::create('/urunler', 'GET', ['marka' => 'grundfos']);

        $this->assertSame('noindex, follow', CatalogPaginationSeo::robots($request));
    }

    public function test_clean_first_page_is_indexable(): void
    {
        $request = Request::create('/urunler', 'GET');

        $this->assertSame('index, follow', CatalogPaginationSeo::robots($request));
    }

    public function test_second_page_is_noindex(): void
    {
        $request = Request::create('/urunler', 'GET');

        $this->assertSame('noindex, follow', CatalogPaginationSeo::robots($request, 2));
    }
}
