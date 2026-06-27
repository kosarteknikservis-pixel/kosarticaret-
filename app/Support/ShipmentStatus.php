<?php

namespace App\Support;

class ShipmentStatus
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'draft' => 'Taslak',
            'submitted' => 'Kargoya bildirildi',
            'picked_up' => 'Kargo alındı',
            'in_transit' => 'Taşımada',
            'delivered' => 'Teslim edildi',
            'returned' => 'İade',
            'failed' => 'Hata',
            'cancelled' => 'İptal',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status ?? ''] ?? ($status ?? '—');
    }

    public static function badgeClasses(?string $status): string
    {
        return match ($status) {
            'submitted', 'picked_up' => 'bg-blue-50 text-blue-700',
            'in_transit' => 'bg-indigo-50 text-indigo-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            'failed', 'returned' => 'bg-red-50 text-red-700',
            'cancelled' => 'bg-slate-100 text-slate-600',
            default => 'bg-amber-50 text-amber-800',
        };
    }

    public static function isActive(?string $status): bool
    {
        return in_array($status, ['submitted', 'picked_up', 'in_transit'], true);
    }

    public static function isTerminal(?string $status): bool
    {
        return in_array($status, ['delivered', 'returned', 'cancelled', 'failed'], true);
    }
}
