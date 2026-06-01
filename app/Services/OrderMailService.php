<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
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
}
