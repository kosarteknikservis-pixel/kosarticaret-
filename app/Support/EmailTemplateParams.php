<?php

namespace App\Support;

use App\Models\Order;

class EmailTemplateParams
{
    /** @return array<string, string> */
    public static function order(Order $order): array
    {
        $tracking = (string) ($order->shipping_tracking ?? '');

        return [
            'site_name' => config('kosar.name', config('app.name')),
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name ?: 'Değerli müşterimiz',
            'status' => OrderStatus::label($order->status),
            'payment_status' => PaymentStatus::label($order->payment_status),
            'total' => number_format((float) $order->total, 2, ',', '.').' ₺',
            'tracking_number' => $tracking,
            'tracking_text' => $tracking !== '' ? 'Kargo takip numaranız: '.$tracking : 'Kargo takip numarası eklendiğinde ayrıca bilgilendirileceksiniz.',
            'tracking_url' => route('tracking.show'),
        ];
    }

    /** @return array<string, string> */
    public static function campaign(string $title): array
    {
        return [
            'site_name' => config('kosar.name', config('app.name')),
            'campaign_title' => $title,
            'home_url' => route('home'),
        ];
    }
}
