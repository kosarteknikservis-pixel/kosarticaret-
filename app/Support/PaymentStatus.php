<?php

namespace App\Support;

class PaymentStatus
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'bekliyor' => 'Bekliyor',
            'basarili' => 'Başarılı',
            'basarisiz' => 'Başarısız',
            'iade' => 'İade edildi',
            'iptal' => 'İptal edildi',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status ?? ''] ?? ($status ?? '—');
    }
}
