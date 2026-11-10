<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteSettingDefaultCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function missing_key_does_not_cache_caller_default(): void
    {
        Cache::forget('setting.missing_default_key');

        $this->assertSame('alpha', SiteSetting::get('missing_default_key', 'alpha'));
        $this->assertSame('beta', SiteSetting::get('missing_default_key', 'beta'));
    }
}
