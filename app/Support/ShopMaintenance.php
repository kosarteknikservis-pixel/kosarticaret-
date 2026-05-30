<?php

namespace App\Support;

use App\Models\SiteSetting;

class ShopMaintenance
{
    public static function isEnabled(): bool
    {
        return SiteSetting::get('shop_maintenance_enabled', '0') === '1';
    }

    public static function title(): string
    {
        $custom = trim((string) SiteSetting::get('shop_maintenance_title', ''));

        return $custom !== '' ? $custom : 'Bakım çalışması';
    }

    public static function message(): string
    {
        $custom = trim((string) SiteSetting::get('shop_maintenance_message', ''));

        return $custom !== '' ? $custom : 'Mağazamız kısa süreliğine güncelleniyor. Lütfen biraz sonra tekrar ziyaret edin.';
    }
}
