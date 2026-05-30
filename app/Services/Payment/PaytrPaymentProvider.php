<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Models\Order;
use App\Support\PaymentGatewayConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaytrPaymentProvider implements PaymentProvider
{
    public function baslat(Order $order): array
    {
        $merchantId = PaymentGatewayConfig::paytrMerchantId();
        $merchantKey = PaymentGatewayConfig::paytrMerchantKey();
        $merchantSalt = PaymentGatewayConfig::paytrMerchantSalt();

        if (! $merchantId || ! $merchantKey || ! $merchantSalt) {
            return (new MockPaymentProvider)->baslat($order);
        }

        $order->load('items');
        $teslimat = $order->shipping_address['teslimat'] ?? [];
        $ad = $teslimat['ad'] ?? 'Musteri';
        $soyad = $teslimat['soyad'] ?? 'Kosar';
        $adSoyad = trim("{$ad} {$soyad}");
        $tutarKurus = (int) round($order->total * 100);

        $basket = base64_encode(json_encode(
            $order->items->map(fn ($item) => [
                $item->product_name,
                number_format($item->line_total, 2, '.', ''),
                1,
            ])->values()->all(),
            JSON_UNESCAPED_UNICODE,
        ));

        $okUrl = route('checkout.success', ['order' => $order->order_number]);
        $failUrl = route('checkout.payment', ['order' => $order->order_number]).'?durum=hata';

        $hashStr = $merchantId.$order->email.$tutarKurus.$order->order_number.$okUrl.$failUrl.$basket.'00'.$merchantSalt;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));

        $response = Http::asForm()->post('https://www.paytr.com/odeme/api/get-token', [
            'merchant_id' => $merchantId,
            'user_ip' => request()->ip() ?? '127.0.0.1',
            'merchant_oid' => $order->order_number,
            'email' => $order->email,
            'payment_amount' => $tutarKurus,
            'paytr_token' => $paytrToken,
            'user_basket' => $basket,
            'debug_on' => PaymentGatewayConfig::paytrTestMode() ? '1' : '0',
            'no_installment' => '0',
            'max_installment' => '0',
            'user_name' => $adSoyad,
            'user_address' => $teslimat['adres'] ?? 'Turkiye',
            'user_phone' => preg_replace('/\D/', '', $order->phone ?? '5550000000'),
            'merchant_ok_url' => $okUrl,
            'merchant_fail_url' => $failUrl,
            'timeout_limit' => '30',
            'currency' => 'TL',
            'test_mode' => PaymentGatewayConfig::paytrTestMode() ? '1' : '0',
        ]);

        $data = $response->json();

        if (($data['status'] ?? '') !== 'success' || empty($data['token'])) {
            Log::warning('paytr token failed', ['response' => $data]);

            return [
                'basarili' => false,
                'odeme_url' => null,
                'demo' => false,
                'mesaj' => $data['reason'] ?? 'PayTR token alınamadı',
            ];
        }

        return [
            'basarili' => true,
            'odeme_url' => 'https://www.paytr.com/odeme/guvenli/'.$data['token'],
            'demo' => false,
            'mesaj' => null,
        ];
    }
}
