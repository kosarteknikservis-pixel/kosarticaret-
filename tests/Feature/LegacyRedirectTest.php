<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    public function test_legacy_marka_filter_urls_redirect_to_brand(): void
    {
        $this->get('/marka/sumak-bicakli-foseptik-dalgic-pompa?filtering=1&filter_product_brand=190')
            ->assertRedirect('/marka/sumak');
    }

    public function test_legacy_cart_filter_urls_redirect_to_cart(): void
    {
        $this->get('/sepet?filtering=1&filter_product_brand=189')
            ->assertRedirect('/sepet');
    }

    public function test_legacy_cart_pagination_redirects_to_cart(): void
    {
        $this->get('/sepet/page/3?remove_item=abc&_wpnonce=xyz')
            ->assertRedirect('/sepet');
    }

    public function test_plain_cart_page_is_not_redirected(): void
    {
        $this->get('/sepet')->assertOk();
    }

    public function test_legacy_add_to_cart_query_strips_to_product(): void
    {
        $this->get('/urun/sumak-smjk100-jet-pompa?add-to-cart=4456')
            ->assertRedirect('/urun/sumak-smjk100-jet-pompa');
    }

    public function test_legacy_product_feed_path_redirects_to_product(): void
    {
        $this->get('/urun/sumak-smac-2200-b-termoplastik-tankli-foseptik-dalgic-pompa/feed/')
            ->assertRedirect('/urun/sumak-smac-2200-b-termoplastik-tankli-foseptik-dalgic-pompa');
    }

    public function test_legacy_category_paths_redirect_to_new_nested_categories(): void
    {
        $this->get('/urun-kategori/hidroforlar/ev-tipi-hidrofor?add-to-cart=7330')
            ->assertRedirect('/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar');

        $this->get('/urun-kategori/bahce-yapi-market/dalgic-pompa')
            ->assertRedirect('/kategoriler/su-pompalari/dalgic-pompalar');

        $this->get('/urun-kategori/bahce-yapi-market/page/42')
            ->assertRedirect('/kategoriler/su-pompalari');
    }

    public function test_legacy_shop_urls_redirect_to_products_list(): void
    {
        $this->get('/magaza/page/28/')->assertRedirect('/urunler');
        $this->get('/page/18/?product_cat=bahce-yapi-market')->assertRedirect('/urunler');
    }

    public function test_legacy_markalar_paths_redirect_to_brand(): void
    {
        $this->get('/markalar/sumak-pompa')->assertRedirect('/marka/sumak');
        $this->get('/markalar/winpo-jet-su-pompa')->assertRedirect('/marka/winpo');
        $this->get('/markalar/sumak-santrifuj-pompa/page/2')->assertRedirect('/marka/sumak');
    }

    public function test_legacy_blog_root_posts_redirect(): void
    {
        $this->get('/hidrofor-nedir-ne-ise-yarar/')
            ->assertRedirect('/blog/hidrofor-nedir-ne-ise-yarar-nasil-calisir');
    }

    public function test_legacy_misc_paths_redirect(): void
    {
        $this->get('/favori-listesi')->assertRedirect('/favoriler');
        $this->get('/siparisler')->assertRedirect('/hesabim');
        $this->get('/kategori/dalgic-pompalar')->assertRedirect('/kategoriler/su-pompalari/dalgic-pompalar');
    }

    public function test_gsc_five_xx_product_slug_redirects(): void
    {
        $this->get('/urun/kosar-ksv-750-sanayi-tipi-vantilator/')
            ->assertRedirect('/urun/kosar-ksv-750-sanayi-tipi-vantilator-30-ayakli');

        $this->get('/urun-kategori/su-pompasi/yatay-kademeli-pompalar/')
            ->assertRedirect('/kategoriler/su-pompalari/kademeli-pompalar/yatay-kademeli-pompalar');

        $this->get('/markalar/sumak-keson-kuyu-dalgic-pompa/')
            ->assertRedirect('/marka/sumak');
    }

    public function test_removed_product_slug_redirects_to_category_or_products(): void
    {
        $this->get('/urun/pedrollo-kaldirilmis-eski-urun-slug')
            ->assertRedirect('/kategoriler/su-pompalari');

        $this->get('/marka/sumak-bicakli-foseptik-dalgic-pompa')
            ->assertRedirect('/marka/sumak');

        $this->get('/markalar/marmara/page/37')
            ->assertRedirect('/markalar');

        $this->get('/shop/page/28')
            ->assertRedirect('/urunler');
    }

    public function test_legacy_top_level_category_redirects_to_specific_category(): void
    {
        $this->get('/urun-kategori/su-pompalari')
            ->assertRedirect('/kategoriler/su-pompalari');

        $this->get('/urun-kategori/hidroforlar')
            ->assertRedirect('/kategoriler/hidrofor-sistemleri/hidroforlar');
    }

    public function test_trailing_slash_redirects_to_canonical_product_url(): void
    {
        $product = \App\Models\Product::query()->active()->first();
        if ($product === null) {
            $this->markTestSkipped('Aktif urun yok.');
        }

        $this->get('/urun/'.$product->slug.'/')
            ->assertRedirect('/urun/'.$product->slug);
    }

    public function test_legacy_catalog_query_params_are_stripped(): void
    {
        $this->get('/marka/pedrollo?filtering=1&filter_product_brand=194&page=29')
            ->assertRedirect('/marka/pedrollo?page=29');

        $this->get('/kategoriler/su-pompalari/kademeli-pompalar?page=1')
            ->assertRedirect('/kategoriler/su-pompalari/kademeli-pompalar');
    }

    public function test_home_add_to_cart_and_lang_tr_redirect_to_home(): void
    {
        $this->get('/?add-to-cart=9233')
            ->assertRedirect('/');

        $this->get('/?add-to-cart=7274&lang=tr')
            ->assertRedirect('/');
    }

    public function test_product_lang_tr_query_is_stripped(): void
    {
        $this->get('/urun/sumak-smjk100-jet-pompa?lang=tr')
            ->assertRedirect('/urun/sumak-smjk100-jet-pompa');
    }

    public function test_flat_category_paths_redirect_to_nested(): void
    {
        $this->get('/kategoriler/drenaj-dalgic-pompa')
            ->assertRedirect('/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa');

        $this->get('/kategoriler/hidroforlar')
            ->assertRedirect('/kategoriler/hidrofor-sistemleri/hidroforlar');
    }
}
