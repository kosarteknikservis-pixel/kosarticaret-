<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderPaymentReminderMail;
use App\Models\Order;
use App\Support\MailSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderMailService
{
    public function sendOrderConfirmation(Order $order): void
    {
        try {
            MailSettings::apply();
            Mail::to($order->email)->send(new OrderConfirmationMail($order->load('items')));
        } catch (\Throwable $e) {
            Log::error('Sipariş e-postası gönderilemedi', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendPaymentReminder(Order $order): array
    {
        if (! $order->isPendingPayment()) {
            return ['ok' => false, 'error' => 'Sipariş ödeme bekliyor durumunda değil.'];
        }

        if (! $order->email) {
            return ['ok' => false, 'error' => 'Siparişte müşteri e-postası yok.'];
        }

        if (! MailSettings::isConfigured()) {
            return ['ok' => false, 'error' => 'SMTP ayarları yapılandırılmamış. Site ayarlarından e-posta gönderimini açın.'];
        }

        try {
            MailSettings::apply();
            Mail::to($order->email)->send(new OrderPaymentReminderMail($order->load('items')));

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Ödeme hatırlatma e-postası gönderilemedi', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
