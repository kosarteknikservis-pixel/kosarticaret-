<?php

namespace App\Support;

use App\Models\Order;

class PaymentMethod
{
    public const COD_TENDERS = ['nakit', 'kart'];

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'kredi_karti' => 'Kredi kartı',
            'havale' => 'Havale / EFT',
            'kapida_odeme' => 'Kapıda ödeme',
            'pazaryeri' => 'Pazaryeri',
        ];
    }

    /** @return array<string, string> */
    public static function codTenderLabels(): array
    {
        return [
            'nakit' => 'Nakit',
            'kart' => 'Kredi kartı',
        ];
    }

    public static function label(?string $method): string
    {
        $method = trim((string) $method);

        return self::labels()[$method] ?? ($method !== '' ? $method : '—');
    }

    public static function codTenderLabel(?string $tender): string
    {
        $tender = trim((string) $tender);

        return self::codTenderLabels()[$tender] ?? ($tender !== '' ? $tender : '—');
    }

    public static function badgeClasses(?string $method): string
    {
        return match ($method) {
            'kapida_odeme' => 'bg-amber-50 text-amber-800',
            'kredi_karti' => 'bg-sky-50 text-sky-800',
            'havale' => 'bg-violet-50 text-violet-800',
            'pazaryeri' => 'bg-slate-100 text-slate-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public static function codTenderFromOrder(Order $order): ?string
    {
        if ($order->payment_method !== 'kapida_odeme') {
            return null;
        }

        $teslimat = is_array($order->shipping_address['teslimat'] ?? null)
            ? $order->shipping_address['teslimat']
            : [];

        $raw = $teslimat['kapidaOdemeSekli']
            ?? $teslimat['kapida_odeme_sekli']
            ?? null;

        $tender = trim((string) $raw);
        if ($tender === 'kredi_karti') {
            $tender = 'kart';
        }

        return in_array($tender, self::COD_TENDERS, true) ? $tender : null;
    }

    public static function displayLabel(Order|string|null $methodOrOrder, ?string $codTender = null): string
    {
        if ($methodOrOrder instanceof Order) {
            $method = (string) $methodOrOrder->payment_method;
            $codTender = self::codTenderFromOrder($methodOrOrder);
        } else {
            $method = (string) $methodOrOrder;
        }

        $base = self::label($method);
        if ($method === 'kapida_odeme' && $codTender) {
            return $base.' · '.self::codTenderLabel($codTender);
        }

        return $base;
    }
}
