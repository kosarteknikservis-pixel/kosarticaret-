<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Models\Order;
use App\Support\PaymentGatewayConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IyzicoPaymentProvider implements PaymentProvider
{
    public function baslat(Order $order): array
    {
        $apiKey = PaymentGatewayConfig::iyzicoApiKey();
        $secret = PaymentGatewayConfig::iyzicoSecretKey();

        if (! $apiKey || ! $secret) {
            return (new MockPaymentProvider)->baslat($order);
        }

        $order->load('items');
        $teslimat = $order->shipping_address['teslimat'] ?? [];
        $ad = $teslimat['ad'] ?? 'Musteri';
        $soyad = $teslimat['soyad'] ?? 'Kosar';
        $adSoyad = trim("{$ad} {$soyad}");

        $basketItems = $order->items->map(fn ($item, $i) => [
            'id' => (string) ($item->product_id ?? $i),
            'name' => mb_substr($item->product_name, 0, 100),
            'category1' => 'Genel',
            'itemType' => 'PHYSICAL',
            'price' => number_format($item->line_total, 2, '.', ''),
        ])->values()->all();

        $body = [
            'locale' => 'tr',
            'conversationId' => $order->order_number,
            'price' => number_format($order->total, 2, '.', ''),
            'paidPrice' => number_format($order->total, 2, '.', ''),
            'currency' => 'TRY',
            'basketId' => $order->order_number,
            'paymentGroup' => 'PRODUCT',
            'callbackUrl' => route('payment.iyzico.callback'),
            'enabledInstallments' => [1, 2, 3, 6, 9],
            'buyer' => [
                'id' => $order->order_number,
                'name' => $ad,
                'surname' => $soyad,
                'gsmNumber' => preg_replace('/\D/', '', $order->phone ?? '5550000000'),
                'email' => $order->email,
                'identityNumber' => '11111111111',
                'registrationAddress' => $teslimat['adres'] ?? 'Turkiye',
                'ip' => request()->ip() ?? '127.0.0.1',
                'city' => $teslimat['il'] ?? 'Istanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $adSoyad,
                'city' => $teslimat['il'] ?? 'Istanbul',
                'country' => 'Turkey',
                'address' => $teslimat['adres'] ?? 'Turkiye',
            ],
            'billingAddress' => [
                'contactName' => $adSoyad,
                'city' => $teslimat['il'] ?? 'Istanbul',
                'country' => 'Turkey',
                'address' => $teslimat['adres'] ?? 'Turkiye',
            ],
            'basketItems' => $basketItems,
        ];

        $data = $this->request($apiKey, $secret, '/payment/iyzipos/checkoutform/initialize/auth/ecom', $body);

        if (($data['status'] ?? '') !== 'success' || empty($data['paymentPageUrl'])) {
            Log::warning('iyzico init failed', ['response' => $data]);

            return [
                'basarili' => false,
                'odeme_url' => null,
                'demo' => false,
                'mesaj' => $data['errorMessage'] ?? 'Ödeme başlatılamadı',
            ];
        }

        $order->update([
            'shipping_address' => array_merge($order->shipping_address ?? [], [
                'iyzico_token' => $data['token'] ?? null,
            ]),
        ]);

        return [
            'basarili' => true,
            'odeme_url' => $data['paymentPageUrl'],
            'demo' => false,
            'mesaj' => null,
        ];
    }

    /**
     * @return array{ok: bool, status?: string, paymentStatus?: string}
     */
    public function retrieveCheckout(string $token): array
    {
        $apiKey = PaymentGatewayConfig::iyzicoApiKey();
        $secret = PaymentGatewayConfig::iyzicoSecretKey();

        if (! $apiKey || ! $secret) {
            return ['ok' => false];
        }

        $body = ['locale' => 'tr', 'token' => $token];
        $data = $this->request($apiKey, $secret, '/payment/iyzipos/checkoutform/auth/ecom/detail/retrieve', $body);

        return [
            'ok' => ($data['status'] ?? '') === 'success',
            'status' => $data['status'] ?? null,
            'paymentStatus' => $data['paymentStatus'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $apiKey, string $secret, string $path, array $body): array
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $random = (string) (time().random_int(1000, 9999));
        $signature = hash_hmac('sha256', $random.$apiKey.$secret.$json, $secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "IYZWSv2 {$signature}",
            'x-iyzi-rnd' => $random,
            'x-iyzi-client-version' => 'iyzipay-php-1.0',
        ])->withBody($json, 'application/json')
            ->post(PaymentGatewayConfig::iyzicoBaseUrl().$path);

        return $response->json() ?? [];
    }
}
