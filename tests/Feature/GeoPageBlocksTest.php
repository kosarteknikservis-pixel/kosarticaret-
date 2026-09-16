<?php

namespace Tests\Feature;

use App\Support\GeoPageBlocks;
use Tests\TestCase;

class GeoPageBlocksTest extends TestCase
{
    public function test_resolves_category_geo_block(): void
    {
        $geo = GeoPageBlocks::forCategory('hidrofor-sistemleri');

        $this->assertNotNull($geo);
        $this->assertArrayHasKey('short_answer', $geo);
        $this->assertArrayHasKey('price_band', $geo);
        $this->assertSame('Kaç katlı binaya hangi hidrofor tipi?', $geo['selection_table']['title']);
    }

    public function test_resolves_g1_category_brand_and_blog_blocks(): void
    {
        $this->assertNotNull(GeoPageBlocks::forCategory('hidrofor-sistemleri/hidroforlar'));
        $this->assertNotNull(GeoPageBlocks::forCategory('hidrofor-sistemleri/ev-tipi-hidroforlar'));
        $this->assertNotNull(GeoPageBlocks::forCategory('hidrofor-sistemleri/hidrofor-grubu'));
        $this->assertNotNull(GeoPageBlocks::forCategory('su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa'));
        $this->assertNotNull(GeoPageBlocks::forCategory('su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa'));
        $this->assertNotNull(GeoPageBlocks::forCategory('su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa'));
        $this->assertNotNull(GeoPageBlocks::forCategory('su-pompalari/sirkulasyon-pompalari'));
        $this->assertNotNull(GeoPageBlocks::forCategory('su-pompalari/santrifuj-pompalar'));
        $this->assertNotNull(GeoPageBlocks::forCategory('su-pompalari/jet-pompalar-derinden-emisli'));

        $this->assertNotNull(GeoPageBlocks::forBrand('winpo'));
        $this->assertNotNull(GeoPageBlocks::forBrand('kaysu'));
        $this->assertNotNull(GeoPageBlocks::forBrand('kosar'));

        $blog = GeoPageBlocks::forBlog('hidrofor-kurulumu-montaj-rehberi');
        $this->assertNotNull($blog);
        $this->assertArrayHasKey('selection_table', $blog);
        $this->assertNotNull(GeoPageBlocks::forBlog('dalgic-pompa-kablo-baglantisi-kesit-secimi'));
    }

    public function test_returns_null_for_unknown_slug(): void
    {
        $this->assertNull(GeoPageBlocks::forBlog('non-existent-slug'));
    }
}
