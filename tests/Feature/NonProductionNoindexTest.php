<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonProductionNoindexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_testing_environment_homepage_sends_noindex_header(): void
    {
        $this->assertSame('testing', app()->environment());

        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_live_host_does_not_receive_noindex_even_outside_production(): void
    {
        $this->assertSame('testing', app()->environment());

        $response = $this->get('http://kosarticaret.com/');

        $response->assertOk();
        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }

    public function test_merchant_feed_keeps_its_own_noindex_header(): void
    {
        $this->get('/urun-feed.xml')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
