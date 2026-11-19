<?php

namespace Tests\Feature;

use App\Support\CatalogPaginationSeo;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * HTTP feature senaryoları yerel sqlite + artisan ile doğrulanır
 * (phpunit :memory: shop middleware/DB gerektirir). Bu sınıf redirect mantığını
 * Request üzerinden birim olarak sabitler.
 */
class CatalogSortParamTest extends TestCase
{
    public function test_sirala_redirects_to_siralama_preserving_filters(): void
    {
        $request = Request::create(
            'https://kosarticaret.com/urunler?sirala=isim&marka=sumak',
            'GET'
        );

        $redirect = CatalogPaginationSeo::redirectLegacySortParam($request);

        $this->assertNotNull($redirect);
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame(
            'https://kosarticaret.com/urunler?marka=sumak&siralama=isim',
            $redirect->getTargetUrl()
        );
    }

    public function test_clean_request_has_no_sort_redirect(): void
    {
        $request = Request::create('https://kosarticaret.com/kategoriler/su-pompalari', 'GET');

        $this->assertNull(CatalogPaginationSeo::redirectLegacySortParam($request));
    }
}
