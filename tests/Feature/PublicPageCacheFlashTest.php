<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPageCacheFlashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function flash_session_skips_public_page_cache_and_is_not_stored(): void
    {
        $flashResponse = $this->withSession([
            'success' => 'GIZLI-FLASH-123',
            '_flash' => [
                'old' => ['success'],
                'new' => [],
            ],
        ])->get('/kategoriler');

        $flashResponse->assertOk();
        $flashResponse->assertSee('GIZLI-FLASH-123');
        $this->assertStringNotContainsString(
            's-maxage',
            (string) $flashResponse->headers->get('Cache-Control')
        );

        $this->flushSession();

        $cleanResponse = $this->get('/kategoriler');
        $cleanResponse->assertOk();
        $cleanResponse->assertDontSee('GIZLI-FLASH-123');
        $this->assertStringContainsString(
            's-maxage',
            (string) $cleanResponse->headers->get('Cache-Control')
        );
    }
}
