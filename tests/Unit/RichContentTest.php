<?php

namespace Tests\Unit;

use App\Support\RichContent;
use App\Support\Seo;
use App\Support\SeoScore;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichContentTest extends TestCase
{
    #[Test]
    public function plain_text_strips_html_and_entities(): void
    {
        $html = '<h2>Başlık</h2><p>Metin &amp; devam.</p>';

        $this->assertSame('BaşlıkMetin & devam.', RichContent::plainText($html));
    }

    #[Test]
    public function normalize_sanitizes_html_and_trims_plain_text(): void
    {
        $this->assertSame('Merhaba', RichContent::normalize('  Merhaba  '));
        $this->assertStringContainsString('<h2>', RichContent::normalize('<h2>OK</h2><script>x</script>') ?? '');
        $this->assertStringNotContainsString('script', RichContent::normalize('<h2>OK</h2><script>x</script>') ?? '');
    }

    #[Test]
    public function normalize_preserves_product_spec_tables(): void
    {
        $html = '<p>Giriş</p><table><tr><th>Özellik</th><th>Değer</th></tr><tr><td>Marka</td><td>Sumak</td></tr></table>';

        $normalized = RichContent::normalize($html);

        $this->assertStringContainsString('<table>', $normalized ?? '');
        $this->assertStringContainsString('<th>Özellik</th>', $normalized ?? '');
        $this->assertStringContainsString('<td>Sumak</td>', $normalized ?? '');
        $this->assertStringNotContainsString('ÖzellikDeğer', RichContent::render($normalized));
    }

    #[Test]
    public function seo_description_uses_plain_text_from_html(): void
    {
        $desc = Seo::description(['<p>Kategori <strong>açıklaması</strong> burada.</p>'], 80);

        $this->assertSame('Kategori açıklaması burada.', $desc);
    }

    #[Test]
    public function seo_score_counts_words_in_html_body(): void
    {
        $words = implode(' ', array_fill(0, 210, 'kelime'));
        $body = "<h2>Başlık</h2><p>{$words}</p>";
        $result = SeoScore::analyze('product', [
            'name' => 'Test',
            'slug' => 'test',
            'description' => $body,
        ]);

        $descCheck = collect($result['checks'])->firstWhere('id', 'description');
        $this->assertNotNull($descCheck);
        $this->assertSame('good', $descCheck['status']);
    }
}
