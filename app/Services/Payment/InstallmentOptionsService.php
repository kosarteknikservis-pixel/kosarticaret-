<?php

namespace App\Services\Payment;

use App\Support\PaymentGatewayConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InstallmentOptionsService
{
    public function __construct(
        private readonly IyzicoApiClient $iyzicoApi,
        private readonly PaytrInstallmentClient $paytrClient,
    ) {}

    /**
     * @return array{
     *     available: bool,
     *     provider: ?string,
     *     provider_label: ?string,
     *     amount: float,
     *     message: ?string,
     *     columns: list<int>,
     *     rows: list<array{label: string, cells: array<int, array{monthly: float, total: float}|null>}>
     * }
     */
    public function forAmount(float $amount): array
    {
        $amount = round(max(0, $amount), 2);

        if ($amount <= 0) {
            return $this->empty($amount, __('shop.installments_invalid_amount'));
        }

        if (! PaymentGatewayConfig::isLive()) {
            return $this->empty($amount, __('shop.installments_not_configured'));
        }

        $provider = PaymentGatewayConfig::activeProvider();

        return match ($provider) {
            'iyzico' => $this->fromIyzico($amount),
            'paytr' => $this->fromPaytr($amount),
            default => $this->empty($amount, __('shop.installments_not_configured')),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function fromIyzico(float $amount): array
    {
        $cacheKey = 'iyzico:installments:'.(int) round($amount * 100);

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ($cached['ok'] ?? false)) {
            $payload = $cached;
        } else {
            $data = $this->iyzicoApi->post('/payment/iyzipos/installment', [
                'locale' => app()->getLocale() === 'en' ? 'en' : 'tr',
                'conversationId' => 'pdp-'.(int) round($amount * 100),
                'price' => number_format($amount, 2, '.', ''),
            ]);

            if (($data['status'] ?? '') !== 'success') {
                Log::warning('iyzico installment inquiry failed', ['response' => $data]);

                return $this->empty($amount, $data['errorMessage'] ?? __('shop.installments_load_error'));
            }

            $payload = ['ok' => true, 'details' => is_array($data['installmentDetails'] ?? null) ? $data['installmentDetails'] : []];
            Cache::put($cacheKey, $payload, now()->addMinutes(30));
        }

        if (! ($payload['ok'] ?? false)) {
            return $this->empty($amount, $payload['message'] ?? __('shop.installments_load_error'));
        }

        return $this->buildTable(
            $amount,
            'iyzico',
            $this->mapIyzicoDetails($payload['details'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fromPaytr(float $amount): array
    {
        $ratesResponse = $this->paytrClient->fetchRates();

        if (! ($ratesResponse['ok'] ?? false)) {
            return $this->empty($amount, $ratesResponse['message'] ?? __('shop.installments_load_error'));
        }

        $table = $this->paytrClient->tableForAmount(
            $amount,
            $ratesResponse['rates'] ?? [],
            (int) ($ratesResponse['max_installment'] ?? 12),
        );

        if (! ($table['ok'] ?? false)) {
            return $this->empty($amount, $table['message'] ?? __('shop.installments_load_error'));
        }

        return $this->buildTable($amount, 'paytr', $table['rows'] ?? [], $table['columns'] ?? []);
    }

    /**
     * @param  list<array<string, mixed>>  $details
     * @return list<array{label: string, cells: array<int, array{monthly: float, total: float}|null>}>
     */
    private function mapIyzicoDetails(array $details): array
    {
        $rows = [];

        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            $cardType = (string) ($detail['cardType'] ?? '');
            if ($cardType === 'DEBIT_CARD') {
                continue;
            }

            $label = trim((string) ($detail['cardFamilyName'] ?? $detail['bankName'] ?? ''));
            if ($label === '') {
                continue;
            }

            $cells = [];
            $prices = is_array($detail['installmentPrices'] ?? null) ? $detail['installmentPrices'] : [];

            foreach ($prices as $priceRow) {
                if (! is_array($priceRow)) {
                    continue;
                }

                $count = (int) ($priceRow['installmentNumber'] ?? 0);
                if ($count < 1) {
                    continue;
                }

                $monthly = (float) ($priceRow['installmentPrice'] ?? 0);
                $total = (float) ($priceRow['totalPrice'] ?? 0);

                if ($monthly <= 0) {
                    continue;
                }

                $cells[$count] = [
                    'monthly' => round($monthly, 2),
                    'total' => round($total > 0 ? $total : $monthly * $count, 2),
                ];
            }

            if ($cells !== []) {
                ksort($cells);
                $rows[] = ['label' => $label, 'cells' => $cells];
            }
        }

        usort($rows, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $rows;
    }

    /**
     * @param  list<array{label: string, cells: array<int, array{monthly: float, total: float}|null>}>  $rows
     * @param  list<int>  $columns
     * @return array<string, mixed>
     */
    private function buildTable(float $amount, string $provider, array $rows, array $columns = []): array
    {
        if ($rows === []) {
            return $this->empty($amount, __('shop.installments_empty'));
        }

        if ($columns === []) {
            $columns = [];
            foreach ($rows as $row) {
                foreach (array_keys($row['cells']) as $count) {
                    $columns[$count] = $count;
                }
            }
            $columns = array_values($columns);
            sort($columns);
        }

        return [
            'available' => true,
            'provider' => $provider,
            'provider_label' => PaymentGatewayConfig::label($provider),
            'amount' => $amount,
            'message' => null,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function empty(float $amount, string $message): array
    {
        return [
            'available' => false,
            'provider' => PaymentGatewayConfig::isLive() ? PaymentGatewayConfig::activeProvider() : null,
            'provider_label' => PaymentGatewayConfig::isLive() ? PaymentGatewayConfig::label() : null,
            'amount' => $amount,
            'message' => $message,
            'columns' => [],
            'rows' => [],
        ];
    }
}
