<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsVisitor;
use App\Models\User;
use App\Services\AnalyticsTracker;
use App\Support\AnalyticsIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_timeout_starts_new_visitor_after_inactivity(): void
    {
        Config::set('kosar.analytics_session_timeout_minutes', 30);

        $session = $this->app['session']->driver();
        $session->start();

        $oldId = (string) Str::uuid();
        $session->put('analytics_visitor_id', $oldId);
        $session->put('analytics_last_activity_at', now()->subMinutes(31)->timestamp);

        AnalyticsVisitor::query()->create([
            'id' => $oldId,
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
        ]);

        $request = Request::create('/urunler', 'GET');
        $request->setLaravelSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');

        $visitor = app(AnalyticsTracker::class)->visitor($request);

        $this->assertNotSame($oldId, $visitor->id);
        $this->assertSame($visitor->id, $session->get('analytics_visitor_id'));
    }

    public function test_same_session_keeps_visitor_within_timeout_window(): void
    {
        Config::set('kosar.analytics_session_timeout_minutes', 30);

        $session = $this->app['session']->driver();
        $session->start();

        $id = (string) Str::uuid();
        $session->put('analytics_visitor_id', $id);
        $session->put('analytics_last_activity_at', now()->subMinutes(10)->timestamp);

        $request = Request::create('/urunler', 'GET');
        $request->setLaravelSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');

        $visitor = app(AnalyticsTracker::class)->visitor($request);

        $this->assertSame($id, $visitor->id);
    }

    public function test_logged_in_user_events_merge_into_single_identity(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $visitorA = AnalyticsVisitor::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now()->subDay(),
        ]);

        $visitorB = AnalyticsVisitor::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        foreach ([$visitorA, $visitorB] as $visitor) {
            AnalyticsEvent::query()->create([
                'visitor_id' => $visitor->id,
                'user_id' => $user->id,
                'event_type' => 'page_view',
                'occurred_at' => now(),
            ]);
        }

        $count = AnalyticsIdentity::countDistinct(
            AnalyticsEvent::query()
                ->where('event_type', 'page_view')
                ->where('occurred_at', '>=', Carbon::now()->subDays(7))
        );

        $this->assertSame(1, $count);
    }

    public function test_link_authenticated_user_attaches_user_id_to_current_visitor(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $session = $this->app['session']->driver();
        $session->start();

        $visitorId = (string) Str::uuid();
        $session->put('analytics_visitor_id', $visitorId);
        $session->put('analytics_last_activity_at', now()->timestamp);

        AnalyticsVisitor::query()->create([
            'id' => $visitorId,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        AnalyticsEvent::query()->create([
            'visitor_id' => $visitorId,
            'event_type' => 'page_view',
            'occurred_at' => now(),
        ]);

        $request = Request::create('/hesabim', 'GET');
        $request->setLaravelSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');
        $request->setUserResolver(fn () => $user);

        app(AnalyticsTracker::class)->linkAuthenticatedUser($request);

        $this->assertSame($user->id, AnalyticsVisitor::query()->find($visitorId)?->user_id);
        $this->assertSame($user->id, AnalyticsEvent::query()->first()?->user_id);
    }
}
