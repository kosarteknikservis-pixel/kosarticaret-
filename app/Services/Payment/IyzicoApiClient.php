<?php

namespace App\Services\Payment;

use App\Support\PaymentGatewayConfig;
use Illuminate\Support\Facades\Http;

class IyzicoApiClient
{
    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body): array
    {
        $apiKey = PaymentGatewayConfig::iyzicoApiKey();
        $secret = PaymentGatewayConfig::iyzicoSecretKey();

        if ($apiKey === '' || $secret === '') {
            return ['status' => 'failure', 'errorMessage' => 'iyzico credentials missing'];
        }

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $random = (string) (time().random_int(1000, 9999));
        $signature = hash_hmac('sha256', $random.$apiKey.$secret.$json, $secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "IYZWSv2 {$signature}",
            'x-iyzi-rnd' => $random,
            'x-iyzi-client-version' => 'iyzipay-php-1.0',
        ])->withBody($json, 'application/json')
            ->timeout(20)
            ->post(PaymentGatewayConfig::iyzicoBaseUrl().$path);

        return $response->json() ?? [];
    }
}
