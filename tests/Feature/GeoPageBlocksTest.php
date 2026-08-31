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

    public function test_returns_null_for_unknown_slug(): void
    {
        $this->assertNull(GeoPageBlocks::forBlog('non-existent-slug'));
    }
}
