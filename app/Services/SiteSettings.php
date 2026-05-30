<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Support\SiteHeroImage;

class SiteSettings
{
    public function get(string $key, ?string $default = null): ?string
    {
        return SiteSetting::get($key, $default ?? config("kosar.defaults.{$key}"));
    }

    /** @return array<string, string> */
    public function hero(): array
    {
        return [
            'badge' => $this->get('hero_badge', 'Havalandırma ve Sulama E-Ticaret'),
            'title' => $this->get('hero_title', 'Su ve Hava Sistemlerinde Güvenilir Çözüm Ortağınız'),
            'subtitle' => $this->get('hero_subtitle', config('kosar.description')),
            'image' => SiteHeroImage::url(),
        ];
    }
}
