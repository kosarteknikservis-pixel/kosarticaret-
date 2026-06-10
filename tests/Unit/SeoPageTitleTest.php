<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Support\Seo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoPageTitleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::set('site_name', 'Koşar');
    }

    #[Test]
    public function it_appends_site_name_to_plain_product_title(): void
    {
        $this->assertSame(
            'KSV-DK750 Sanayi Tipi Vantilatör 30" | Koşar',
            Seo::pageTitle('KSV-DK750 Sanayi Tipi Vantilatör 30"')
        );
    }

    #[Test]
    public function it_strips_legacy_yoast_suffix_before_appending_site_name(): void
    {
        $this->assertSame(
            'Koşar KSV-DK750 Sanayi Tipi Vantilatör 30" | Koşar',
            Seo::pageTitle('Koşar KSV-DK750 Sanayi Tipi Vantilatör 30" | Koşar Ticaret')
        );
    }

    #[Test]
    public function it_removes_duplicate_site_suffixes_from_imported_titles(): void
    {
        $this->assertSame(
            'Ürün Adı | Koşar',
            Seo::pageTitle('Ürün Adı | Koşar Ticaret | Koşar')
        );
    }

    #[Test]
    public function it_returns_only_site_name_for_homepage_title(): void
    {
        $this->assertSame('Koşar', Seo::pageTitle('Koşar'));
    }

    #[Test]
    public function it_supports_dash_separators_from_old_seo_titles(): void
    {
        $this->assertSame(
            'İletişim | Koşar',
            Seo::pageTitle('İletişim — Koşar Ticaret')
        );
    }
}
