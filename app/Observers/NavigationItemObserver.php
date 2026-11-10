<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class NavigationItemObserver
{
    public function saved(): void
    {
        $this->clearLayoutNavCache();
    }

    public function deleted(): void
    {
        $this->clearLayoutNavCache();
    }

    private function clearLayoutNavCache(): void
    {
        Cache::forget('layout.nav.header');
        Cache::forget('layout.nav.footer');
    }
}
