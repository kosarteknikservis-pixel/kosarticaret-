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

    public static function badgeClasses(?string $status): string
    {
        return match ($status) {
            'basarili' => 'bg-emerald-50 text-emerald-700',
            'bekliyor' => 'bg-amber-50 text-amber-800',
            'basarisiz' => 'bg-red-50 text-red-700',
            'iade', 'iptal' => 'bg-slate-100 text-slate-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
