<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use App\Support\ContactFormSpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonLdEncodingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_product_json_ld_hex_encodes_markup_characters_and_decodes_cleanly(): void
    {
        $product = Product::query()->where('is_active', true)->where('stock', '>', 0)->firstOrFail();

        ProductReview::query()->create([
            'product_id' => $product->id,
            'author_name' => 'Test Kullanıcı',
            'email' => 'jsonld@ornek.com',
            'rating' => 5,
            'title' => 'Temiz test',
            'body' => '<b>Test</b> & ürün',
            'approved' => true,
        ]);

        $html = $this->get('/urun/'.$product->slug)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $this->assertNotEmpty($matches[1]);

        $reviewBlock = null;
        foreach ($matches[1] as $block) {
            $this->assertStringNotContainsString('<', $block);
            $decoded = json_decode($block, true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), json_last_error_msg());
            $this->assertIsArray($decoded);

            $found = false;
            array_walk_recursive($decoded, function ($value) use (&$found): void {
                if ($value === '<b>Test</b> & ürün') {
                    $found = true;
                }
            });

            if ($found) {
                $reviewBlock = $block;
            }
        }

        $this->assertNotNull($reviewBlock, 'Onaylı yorum gövdesi JSON-LD içinde çözülebilir olmalı.');
        $this->assertStringContainsString('\\u003C', $reviewBlock);
        $this->assertStringContainsString('\\u0026', $reviewBlock);
    }

    public function test_review_post_strips_html_tags_before_saving(): void
    {
        $product = Product::query()->where('is_active', true)->where('stock', '>', 0)->firstOrFail();

        $token = ContactFormSpamGuard::beginForm('review');
        session(['public_form.review.started_at' => now()->subSeconds(10)->timestamp]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
            ->post(route('products.review', $product), [
                '_form_token' => $token,
                'website_url' => '',
                'author_name' => '<b>Ali</b>',
                'email' => 'yorumcu@ornek.com',
                'rating' => 4,
                'title' => '<i>Başlık</i>',
                'body' => '<b>Test</b> & ürün',
            ])
            ->assertRedirect();

        $review = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('email', 'yorumcu@ornek.com')
            ->first();

        $this->assertNotNull($review);
        $this->assertSame('Ali', $review->author_name);
        $this->assertSame('Başlık', $review->title);
        $this->assertSame('Test & ürün', $review->body);
        $this->assertFalse($review->approved);
    }
}
