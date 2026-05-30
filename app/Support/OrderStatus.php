<?php

namespace App\Support;

class OrderStatus
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'odeme_bekliyor' => 'Ödeme bekleniyor',
            'hazirlaniyor' => 'Hazırlanıyor',
            'kargoda' => 'Kargoda',
            'teslim_edildi' => 'Teslim edildi',
            'iptal' => 'İptal edildi',
            'beklemede' => 'Beklemede',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status ?? ''] ?? ($status ?? '—');
    }
}
