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

    public static function badgeClasses(?string $status): string
    {
        return match ($status) {
            'odeme_bekliyor' => 'bg-amber-50 text-amber-800',
            'hazirlaniyor' => 'bg-blue-50 text-blue-700',
            'kargoda' => 'bg-indigo-50 text-indigo-700',
            'teslim_edildi' => 'bg-emerald-50 text-emerald-700',
            'iptal' => 'bg-red-50 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
