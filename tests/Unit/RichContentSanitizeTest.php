<?php

namespace Tests\Unit;

use App\Support\RichContent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichContentSanitizeTest extends TestCase
{
    #[Test]
    public function strips_quoted_event_handlers(): void
    {
        $out = RichContent::sanitizeHtml('<p onmouseover="x">a</p>');

        $this->assertStringNotContainsString('onmouseover', $out);
        $this->assertStringContainsString('a', $out);
    }

    #[Test]
    public function strips_slash_and_unquoted_event_handlers(): void
    {
        $out = RichContent::sanitizeHtml('<p/onmouseover=alert(1)>a</p>');

        $this->assertStringNotContainsString('onmouseover', $out);
        $this->assertStringContainsString('a', $out);
    }

    #[Test]
    public function removes_javascript_href(): void
    {
        $out = RichContent::sanitizeHtml('<a href="javascript:x">a</a>');

        $this->assertStringNotContainsString('href', $out);
        $this->assertStringContainsString('a', $out);
    }

    #[Test]
    public function rewrites_safe_anchor_with_rel_only(): void
    {
        $out = RichContent::sanitizeHtml('<a href="/urun/x" target="_blank" class="y">a</a>');

        $this->assertSame('<a href="/urun/x" rel="noopener noreferrer">a</a>', $out);
    }

    #[Test]
    public function keeps_safe_style_and_drops_url_style(): void
    {
        $safe = RichContent::sanitizeHtml('<p style="text-align:center">a</p>');
        $this->assertStringContainsString('style="text-align:center"', $safe);

        $unsafe = RichContent::sanitizeHtml('<p style="background:url(x)">a</p>');
        $this->assertStringNotContainsString('style=', $unsafe);
        $this->assertStringContainsString('<p>', $unsafe);
    }

    #[Test]
    public function keeps_table_class_and_colspan(): void
    {
        $out = RichContent::sanitizeHtml('<table class="spec-table"><tr><td colspan="2">x</td></tr></table>');

        $this->assertStringContainsString('class="spec-table"', $out);
        $this->assertStringContainsString('colspan="2"', $out);
        $this->assertStringContainsString('x', $out);
    }

    #[Test]
    public function keeps_heading_id_but_not_paragraph_id(): void
    {
        $heading = RichContent::sanitizeHtml('<h2 id="kurulum">K</h2>');
        $this->assertStringContainsString('id="kurulum"', $heading);

        $paragraph = RichContent::sanitizeHtml('<p id="x">a</p>');
        $this->assertStringNotContainsString('id=', $paragraph);
        $this->assertStringContainsString('<p>a</p>', $paragraph);
    }

    #[Test]
    public function removes_img_and_script_with_content(): void
    {
        $this->assertStringNotContainsString('img', RichContent::sanitizeHtml('<img src=x onerror=y>'));

        $out = RichContent::sanitizeHtml('<script>x</script><p>a</p>');
        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('x', $out);
        $this->assertStringContainsString('<p>a</p>', $out);
    }

    #[Test]
    public function preserves_turkish_characters_without_entities(): void
    {
        $html = '<p>Dalğıç pompa şartları ğüşıöç</p>';
        $out = RichContent::sanitizeHtml($html);

        $this->assertSame($html, $out);
        $this->assertStringNotContainsString('&', $out);
    }
}
