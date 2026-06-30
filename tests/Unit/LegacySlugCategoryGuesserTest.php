<?php

namespace Tests\Unit;

use App\Support\LegacySlugCategoryGuesser;
use PHPUnit\Framework\TestCase;

class LegacySlugCategoryGuesserTest extends TestCase
{
    public function test_hidrofor_slug_maps_to_hidrofor_category(): void
    {
        $path = LegacySlugCategoryGuesser::pathForSlug('etna-1-hf-ko-10-5-22-frekans-kontrollu-tek-pompali-hidrofor');

        $this->assertSame('/kategoriler/hidrofor-sistemleri/hidroforlar', $path);
    }

    public function test_dalgic_slug_maps_to_dalgic_category(): void
    {
        $path = LegacySlugCategoryGuesser::pathForSlug('pedrollo-vx-40-65-dokum-govdeli-foseptik-dalgic-pompa');

        $this->assertSame('/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa', $path);
    }

    public function test_brand_only_slug_maps_to_su_pompalari(): void
    {
        $path = LegacySlugCategoryGuesser::pathForSlug('pedrollo-kaldirilmis-eski-urun-slug');

        $this->assertSame('/kategoriler/su-pompalari', $path);
    }
}
