<?php

namespace App\Services\Payment;

use App\Support\PaymentGatewayConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaytrInstallmentClient
{
    private const RATES_URL = 'https://www.paytr.com/odeme/taksit-oranlari';

    private const CACHE_KEY = 'paytr:installment_rates';

    /** @var array<string, string> */
    private const CARD_LABELS = [
        'bonus' => 'Bonus',
        'axess' => 'Axess',
        'world' => 'World',
        'maximum' => 'Maximum',
        'cardfinans' => 'Card Finans',
        'paraf' => 'Paraf',
        'advantage' => 'Advantage',
        'combo' => 'Combo',
        'saglamkart' => 'Sağlam Kart',
    ];

    /**
     * @return array{ok: bool, rates?: array<string, array<string, float>>, max_installment?: int, message?: string}
     */
    public function fetchRates(): array
    {
        $merchantId = PaymentGatewayConfig::paytrMerchantId();
        $merchantKey = PaymentGatewayConfig::paytrMerchantKey();
        $merchantSalt = PaymentGatewayConfig::paytrMerchantSalt();

        if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '') {
            return ['ok' => false, 'message' => 'PayTR credentials missing'];
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && ($cached['ok'] ?? false)) {
            return $cached;
        }

        $requestId = (string) time();
        $hashStr = $merchantId.$requestId.$merchantSalt;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));

        $response = Http::asForm()
            ->timeout(25)
            ->post(self::RATES_URL, [
                'merchant_id' => $merchantId,
                'request_id' => $requestId,
                'paytr_token' => $paytrToken,
            ]);

        $data = $response->json();

        if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
            Log::warning('paytr installment rates failed', ['response' => $data]);

            return [
                'ok' => false,
                'message' => is_array($data) ? ($data['err_msg'] ?? 'PayTR taksit oranları alınamadı') : 'PayTR yanıt vermedi',
            ];
        }

        $rates = is_array($data['rates'] ?? null) ? $data['rates'] : [];

        $result = [
            'ok' => true,
            'rates' => $this->normalizeRates($rates),
            'max_installment' => (int) ($data['max_inst_non_bus'] ?? 12),
        ];

        Cache::put(self::CACHE_KEY, $result, now()->addDay());

        return $result;
    }

    /**
     * @param  array<string, mixed>  $rawRates
     * @return array<string, array<string, float>>
     */
    private function normalizeRates(array $rawRates): array
    {
        $normalized = [];

        foreach ($rawRates as $cardKey => $installments) {
            if (! is_array($installments)) {
                continue;
            }

            $card = strtolower((string) $cardKey);
            $normalized[$card] = [];

            foreach ($installments as $instKey => $rate) {
                $count = $this->parseInstallmentCount((string) $instKey);
                if ($count === null || ! is_numeric($rate)) {
                    continue;
                }
                $normalized[$card]['taksit_'.$count] = (float) $rate;
            }
        }

        return $normalized;
    }

    private function parseInstallmentCount(string $key): ?int
    {
        if (preg_match('/(\d{1,2})/', $key, $matches)) {
            $count = (int) $matches[1];

            return $count >= 1 && $count <= 12 ? $count : null;
        }

        return null;
    }

    /**
     * @param  array<string, array<string, float>>  $rates
     * @return array{ok: bool, rows: list<array{label: string, cells: array<int, array{monthly: float, total: float}|null>}>, columns: list<int>, message?: string}
     */
    public function tableForAmount(float $amount, array $rates, int $maxInstallment = 12): array
    {
        $columns = [];
        $rows = [];

        foreach ($rates as $cardKey => $installments) {
            $cells = [];
            $label = self::CARD_LABELS[$cardKey] ?? ucfirst($cardKey);

            foreach ($installments as $instKey => $ratePercent) {
                $count = $this->parseInstallmentCount($instKey);
                if ($count === null || $count > $maxInstallment) {
                    continue;
                }

                $columns[$count] = $count;
                $cells[$count] = $this->cellForAmount($amount, $count, (float) $ratePercent);
            }

            if ($cells !== []) {
                ksort($cells);
                $rows[] = ['label' => $label, 'cells' => $cells];
            }
        }

        if ($rows === []) {
            return ['ok' => false, 'rows' => [], 'columns' => [], 'message' => 'Taksit oranı bulunamadı'];
        }

        $columns = array_values(array_unique($columns));
        sort($columns);

        return ['ok' => true, 'rows' => $rows, 'columns' => $columns];
    }

    /**
     * @return array{monthly: float, total: float}|null
     */
    private function cellForAmount(float $amount, int $count, float $ratePercent): ?array
    {
        if ($amount <= 0) {
            return null;
        }

        if ($count <= 1) {
            return ['monthly' => round($amount, 2), 'total' => round($amount, 2)];
        }

        $total = $amount * (1 + ($ratePercent / 100));

        return [
            'monthly' => round($total / $count, 2),
            'total' => round($total, 2),
        ];
    }

    public static function cardLabel(string $key): string
    {
        return self::CARD_LABELS[strtolower($key)] ?? ucfirst($key);
    }
}
