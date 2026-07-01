<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Services\Seo\IndexNowClient;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndexNowClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_submits_urls_when_enabled(): void
    {
        config(['app.url' => 'https://kosarticaret.com']);

        SiteSetting::set('indexnow_enabled', '1');
        SiteSetting::set('indexnow_key', 'abc1234567890abcdef1234567890ab12');

        Http::fake([
            'api.indexnow.org/*' => Http::response('', 202),
        ]);

        $result = app(IndexNowClient::class)->submit([
            route('home', absolute: true),
        ]);

        $this->assertTrue($result['ok']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.indexnow.org/indexnow'
                && $request['host'] === 'kosarticaret.com'
                && $request['key'] === 'abc1234567890abcdef1234567890ab12'
                && $request['keyLocation'] === Seo::absolute('/abc1234567890abcdef1234567890ab12.txt');
        });
    }

    public function test_it_skips_when_disabled(): void
    {
        SiteSetting::set('indexnow_enabled', '0');

        Http::fake();

        $result = app(IndexNowClient::class)->submit(['https://kosarticaret.com/blog/test']);

        $this->assertTrue($result['skipped'] ?? false);
        Http::assertNothingSent();
    }
}
