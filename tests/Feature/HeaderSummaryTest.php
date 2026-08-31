<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\PublicPageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class HeaderSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_summary_returns_counts_and_csrf_token(): void
    {
        Product::query()->create([
            'slug' => 'test-pompa',
            'sku' => 'T-1',
            'name' => 'Test Pompa',
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);

        session(['cart' => [1 => 2], 'favorites' => [1]]);

        $this->getJson('/header/ozet')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'cart_count' => 2,
                'favorite_count' => 1,
            ])
            ->assertJsonStructure(['csrf_token'])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_header_summary_path_is_not_public_page_cached(): void
    {
        $request = Request::create('/header/ozet', 'GET');

        $this->assertFalse(PublicPageCache::shouldCache($request));
    }

    public function test_should_cache_still_excludes_cart_favorites_and_compare_sessions(): void
    {
        $base = Request::create('/kategoriler/su-pompalari', 'GET');

        $this->assertTrue(PublicPageCache::shouldCache($base));

        session(['cart' => [1 => 1]]);
        $this->assertFalse(PublicPageCache::shouldCache($base));

        session()->forget('cart');
        session(['favorites' => [1]]);
        $this->assertFalse(PublicPageCache::shouldCache($base));

        session()->forget('favorites');
        session(['compare_slugs' => ['a']]);
        $this->assertFalse(PublicPageCache::shouldCache($base));
    }
}
