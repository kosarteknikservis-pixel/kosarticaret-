<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductContentLastmodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    #[Test]
    public function stock_change_does_not_bump_content_updated_at_but_name_and_price_do(): void
    {
        $createdAt = Carbon::parse('2026-11-08 10:00:00', 'UTC');
        Carbon::setTestNow($createdAt);

        $product = Product::query()->create([
            'name' => 'Lastmod Test Pompa',
            'slug' => 'lastmod-test-pompa',
            'sku' => 'LASTMOD-001',
            'price' => 1500,
            'stock' => 10,
            'is_active' => true,
        ]);

        $product->refresh();
        $this->assertNotNull($product->content_updated_at);
        $contentStamp = $product->content_updated_at->copy();

        Carbon::setTestNow($createdAt->copy()->addHour());
        $product->update(['stock' => 9]);
        $product->refresh();

        $this->assertTrue(
            $product->content_updated_at->equalTo($contentStamp),
            'Salt stok değişimi content_updated_at güncellememeli.'
        );

        $stockXml = $this->get('/sitemap-products-1.xml')->assertOk()->getContent();
        $this->assertSitemapLastmod($stockXml, $product, $contentStamp->toAtomString());

        Carbon::setTestNow($createdAt->copy()->addHours(2));
        $product->update(['name' => 'Lastmod Test Pompa Güncel']);
        $product->refresh();

        $this->assertTrue($product->content_updated_at->equalTo(Carbon::now()));
        $nameStamp = $product->content_updated_at->toAtomString();

        $nameXml = $this->get('/sitemap-products-1.xml')->assertOk()->getContent();
        $this->assertSitemapLastmod($nameXml, $product, $nameStamp);

        Carbon::setTestNow($createdAt->copy()->addHours(3));
        $product->update(['price' => 1650]);
        $product->refresh();

        $this->assertTrue($product->content_updated_at->equalTo(Carbon::now()));
        $priceStamp = $product->content_updated_at->toAtomString();

        $entry = SitemapGenerator::chunkUrls('products-1')
            ->firstWhere('loc', route('products.show', $product));

        $this->assertNotNull($entry);
        $this->assertSame($priceStamp, $entry['lastmod']);
    }

    private function assertSitemapLastmod(string $xml, Product $product, string $expectedLastmod): void
    {
        $loc = preg_quote(route('products.show', $product), '/');
        $this->assertMatchesRegularExpression(
            '/<loc>'.$loc.'<\/loc>\s*<lastmod>'.preg_quote($expectedLastmod, '/').'<\/lastmod>/',
            $xml
        );
    }
}
