<?php

namespace Tests\Unit;

use App\Support\CatalogPaginationSeo;
use App\Support\Seo;
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

        $this->assertSame(Seo::ROBOTS_INDEX, CatalogPaginationSeo::robots($request));
    }

    public function test_second_page_is_indexable(): void
    {
        $request = Request::create('/urunler', 'GET');

        $this->assertSame(Seo::ROBOTS_INDEX, CatalogPaginationSeo::robots($request, 2));
    }

    public function test_paginated_pages_are_self_canonical_without_filters(): void
    {
        $base = 'https://kosarticaret.com/kategoriler/su-pompalari';

        $this->assertSame($base, CatalogPaginationSeo::canonical($base, 1));
        $this->assertSame($base.'?page=3', CatalogPaginationSeo::canonical($base, 3));
        $this->assertSame(
            'https://kosarticaret.com/ara?q=pompa&page=2',
            CatalogPaginationSeo::canonical('https://kosarticaret.com/ara?q=pompa', 2),
        );
    }

    public function test_legacy_sayfa_param_redirects_to_page(): void
    {
        $request = Request::create('https://kosarticaret.com/urunler?sayfa=2', 'GET');

        $redirect = CatalogPaginationSeo::redirectLegacyPageParam($request);

        $this->assertNotNull($redirect);
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame('https://kosarticaret.com/urunler?page=2', $redirect->getTargetUrl());
    }

    public function test_legacy_sirala_param_redirects_to_siralama(): void
    {
        $request = Request::create(
            'https://kosarticaret.com/kategoriler/su-pompalari?sirala=fiyat-artan&page=2',
            'GET'
        );

        $redirect = CatalogPaginationSeo::redirectLegacySortParam($request);

        $this->assertNotNull($redirect);
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame(
            'https://kosarticaret.com/kategoriler/su-pompalari?page=2&siralama=fiyat-artan',
            $redirect->getTargetUrl()
        );
    }

    public function test_sirala_with_existing_siralama_drops_sirala_only(): void
    {
        $request = Request::create(
            'https://kosarticaret.com/urunler?sirala=isim&siralama=fiyat-azalan&marka=sumak',
            'GET'
        );

        $redirect = CatalogPaginationSeo::redirectLegacySortParam($request);

        $this->assertNotNull($redirect);
        $this->assertSame(
            'https://kosarticaret.com/urunler?siralama=fiyat-azalan&marka=sumak',
            $redirect->getTargetUrl()
        );
    }

    public function test_invalid_sirala_is_stripped_without_inventing_siralama(): void
    {
        $request = Request::create(
            'https://kosarticaret.com/kategoriler/su-pompalari?sirala=uydurma&page=2',
            'GET'
        );

        $redirect = CatalogPaginationSeo::redirectLegacySortParam($request);

        $this->assertNotNull($redirect);
        $this->assertSame(
            'https://kosarticaret.com/kategoriler/su-pompalari?page=2',
            $redirect->getTargetUrl()
        );
    }

    public function test_siralama_param_still_triggers_noindex(): void
    {
        $request = Request::create('/urunler', 'GET', ['siralama' => 'fiyat-artan']);

        $this->assertSame('noindex, follow', CatalogPaginationSeo::robots($request));
    }

    public function test_sirala_alone_is_not_treated_as_filter_key(): void
    {
        // sirala URL'leri 301 ile taşınır; FILTER_QUERY_KEYS'e eklenmez.
        $request = Request::create('/urunler', 'GET', ['sirala' => 'fiyat-artan']);

        $this->assertFalse(CatalogPaginationSeo::hasActiveFilters($request));
    }
}
