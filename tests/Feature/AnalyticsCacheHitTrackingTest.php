<?php

namespace Tests\Feature;

use App\Http\Middleware\CachePublicPages;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\AnalyticsTracker;
use App\Support\PublicPageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalyticsCacheHitTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cached_page_hit_still_records_page_view(): void
    {
        Cache::flush();
        PublicPageCache::forgetAll();

        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');
        $this->app->instance('request', $request);
        $this->startSession();
        $request->setLaravelSession($this->app['session']->driver());

        $key = PublicPageCache::key($request).':v'.PublicPageCache::versionSuffix();
        Cache::put($key, [
            'content' => '<html>cached home</html>',
            'status' => 200,
            'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
        ], 60);

        $response = app(CachePublicPages::class)->handle($request, function () {
            $this->fail('Cache miss should not call next');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, AnalyticsEvent::query()->where('event_type', 'page_view')->count());
    }

    public function test_admin_storefront_cart_is_recorded_but_excluded_from_customer_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $session = $this->app['session']->driver();
        $session->start();

        $request = Request::create('/sepet/ajax/ekle/test', 'POST');
        $request->setLaravelSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(app(AnalyticsTracker::class)->shouldTrackInteraction($request));
        $this->assertTrue(app(AnalyticsTracker::class)->isStaffRequest($request));
    }
}
