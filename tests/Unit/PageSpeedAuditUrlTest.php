<?php

namespace Tests\Unit;

use App\Support\PageSpeedAuditUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageSpeedAuditUrlTest extends TestCase
{
    #[Test]
    public function it_rejects_localhost_urls(): void
    {
        $this->assertFalse(PageSpeedAuditUrl::isPublic('http://127.0.0.1:8001'));
        $this->assertFalse(PageSpeedAuditUrl::isPublic('http://localhost'));
    }

    #[Test]
    public function it_accepts_public_https_urls(): void
    {
        $this->assertTrue(PageSpeedAuditUrl::isPublic('https://kosarticaret.com'));
    }
}
