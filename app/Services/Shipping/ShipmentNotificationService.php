<?php

namespace App\Services\Shipping;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderShipment;
use App\Services\Sms\SmsService;
use App\Support\MailSettings;
use Illuminate\Support\Facades\Mail;

class ShipmentNotificationService
{
    public function __construct(private SmsService $sms) {}

    public function afterShipmentSubmitted(Order $order, OrderShipment $shipment, ?int $adminId = null): void
    {
        $this->sendTrackingSmsIfNeeded($order, $shipment, $adminId);
        $this->sendStatusEmailIfNeeded($order);
    }

    private function sendTrackingSmsIfNeeded(Order $order, OrderShipment $shipment, ?int $adminId = null): void
    {
        if ($order->shipment_sms_sent_at || blank($shipment->tracking_number) || blank($order->phone)) {
            return;
        }

        $message = $this->sms->trackingMessage(
            $order->customer_name ?: 'Müşteri',
            $order->order_number,
            (string) $shipment->tracking_number,
        );

        $result = $this->sms->send((string) $order->phone, $message);
        if (! $result['ok']) {
            OrderLog::query()->create([
                'order_id' => $order->id,
                'user_id' => $adminId,
                'type' => 'sms_failed',
                'message' => 'Kargo SMS gönderilemedi: '.($result['error'] ?? 'Bilinmeyen hata'),
            ]);

            return;
        }

        $now = now();
        $order->update(['shipment_sms_sent_at' => $now]);
        $shipment->update(['sms_sent_at' => $now]);

        OrderLog::query()->create([
            'order_id' => $order->id,
            'user_id' => $adminId,
            'type' => 'sms_sent',
            'message' => 'Kargo takip SMS gönderildi: '.$shipment->tracking_number,
            'new_values' => ['phone' => $order->phone],
        ]);
    }

    private function sendStatusEmailIfNeeded(Order $order): void
    {
        if (! MailSettings::canSend() || blank($order->email)) {
            return;
        }

        try {
            Mail::to($order->email)->send(new OrderStatusUpdatedMail($order->fresh('items')));
        } catch (\Throwable) {
            // E-posta hatası kargo akışını durdurmasın.
        }
    }
}
