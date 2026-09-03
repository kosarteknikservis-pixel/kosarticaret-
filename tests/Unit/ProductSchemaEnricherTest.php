<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Seo\ProductSchemaEnricher;
use App\Support\Seo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductSchemaEnricherTest extends TestCase
{
    #[Test]
    public function it_detects_generic_template_meta_descriptions(): void
    {
        $this->assertTrue(Seo::isGenericProductDescription(
            'Koşar KSV-DK600 için fiyat, stok ve teknik özellikleri inceleyin. Garantili alışveriş, hızlı teslimat ve teknik destek.'
        ));
        $this->assertFalse(Seo::isGenericProductDescription(
            'Koşar KSV-DK600 Sanayi Tipi Vantilatör, 24 inç uzaktan kumandalı modeli ile endüstriyel alanlarda güçlü serinlik sunar.'
        ));
    }

    #[Test]
    public function product_description_prefers_short_over_template_meta(): void
    {
        $product = new Product([
            'name' => 'Test Pompa',
            'meta_description' => 'Test Pompa için fiyat, stok ve teknik özellikleri inceleyin. Garantili alışveriş, hızlı teslimat ve teknik destek.',
            'short_description' => 'Test Pompa, temiz su transferi için tasarlanmış monofaze dalgıç pompadır.',
            'description' => '<p>Uzun açıklama burada yer alır ve teknik detay verir.</p>',
        ]);

        $text = Seo::productDescriptionText($product, 160);

        $this->assertStringContainsString('temiz su transferi', $text);
        $this->assertStringNotContainsString('fiyat, stok ve teknik', $text);
    }

    #[Test]
    public function it_extracts_specs_from_html_tables(): void
    {
        $html = <<<'HTML'
<table>
<tr><th>Özellik</th><th>Değer</th></tr>
<tr><td>Motor Gücü</td><td>1 HP (0,75 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V Monofaze</td></tr>
</table>
HTML;

        $specs = (new ProductSchemaEnricher)->extractSpecsFromDescription($html);

        $this->assertSame('1 HP (0,75 kW)', $specs['Motor Gücü']);
        $this->assertSame('220 V Monofaze', $specs['Voltaj']);
        $this->assertArrayNotHasKey('Özellik', $specs);
    }

    #[Test]
    public function it_builds_meta_from_short_description(): void
    {
        $product = new Product([
            'name' => 'Test Pompa',
            'short_description' => 'Test Pompa, temiz su transferi için tasarlanmış monofaze dalgıç pompadır ve bodrum tahliyesi için uygundur.',
            'meta_description' => 'Test Pompa için fiyat, stok ve teknik özellikleri inceleyin. Garantili alışveriş, hızlı teslimat ve teknik destek.',
        ]);

        $meta = (new ProductSchemaEnricher)->buildMetaDescription($product);

        $this->assertNotNull($meta);
        $this->assertStringContainsString('temiz su transferi', $meta);
        $this->assertLessThanOrEqual(160, mb_strlen($meta));
    }
}
