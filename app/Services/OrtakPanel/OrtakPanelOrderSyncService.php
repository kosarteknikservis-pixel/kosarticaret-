<?php

namespace App\Services\OrtakPanel;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrtakPanelOrderSyncService
{
    /** @var array<string, string> */
    private const PAYMENT_LABELS = [
        'kredi_karti' => 'Kredi / Banka Kartı',
        'havale' => 'Havale / EFT',
        'kapida_odeme' => 'Kapıda Ödeme',
    ];

    public function isEnabled(): bool
    {
        if (! (bool) config('services.ortak_panel.enabled', true)) {
            return false;
        }

        $url = rtrim((string) config('services.ortak_panel.base_url'), '/');
        $key = (string) config('services.ortak_panel.api_key');
        $origin = (string) config('services.ortak_panel.site_origin');

        return $url !== '' && $key !== '' && $origin !== '';
    }

    public function shouldSync(Order $order): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($order->panel_synced_at) {
            return false;
        }

        if ($order->isPendingPayment()) {
            return false;
        }

        if ($order->payment_status !== 'basarili') {
            return false;
        }

        $channel = trim((string) ($order->sales_channel ?? ''));

        return $channel === '' || $channel === 'website';
    }

    /**
     * @return array{ok: bool, skipped?: bool, error?: string, panel_id?: int|null, response?: array<string, mixed>|null}
     */
    public function sync(Order $order): array
    {
        $order = $order->fresh(['items']) ?? $order;

        if (! $this->shouldSync($order)) {
            return ['ok' => true, 'skipped' => true];
        }

        $payload = $this->buildPayload($order);
        $url = rtrim((string) config('services.ortak_panel.base_url'), '/').'/api/receive.php';

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $body = $response->json();
            $body = is_array($body) ? $body : null;
            $httpOk = $response->successful();
            $status = is_array($body) ? (string) ($body['status'] ?? '') : '';
            $action = is_array($body) ? (string) ($body['action'] ?? '') : '';
            $panelId = is_array($body) && isset($body['id']) ? (int) $body['id'] : null;

            $accepted = $httpOk && $status === 'success' && in_array($action, ['created', 'none', ''], true);
            if (! $accepted && $httpOk && $status === 'success' && $panelId) {
                $accepted = true;
            }

            if ($accepted) {
                $order->update([
                    'panel_synced_at' => now(),
                    'panel_order_id' => $panelId ?: $order->panel_order_id,
                ]);

                OrderLog::query()->create([
                    'order_id' => $order->id,
                    'type' => 'ortak_panel_synced',
                    'message' => 'Sipariş ortak panele gönderildi.',
                    'new_values' => array_filter([
                        'panel_id' => $panelId,
                        'action' => $action !== '' ? $action : null,
                        'sms_verification' => is_array($body) ? ($body['sms_verification'] ?? null) : null,
                    ]),
                ]);

                return ['ok' => true, 'panel_id' => $panelId, 'response' => $body];
            }

            $error = is_array($body)
                ? (string) ($body['message'] ?? $response->body())
                : $response->body();
            $error = trim($error) !== '' ? $error : 'Ortak panel beklenmeyen yanıt döndü.';

            OrderLog::query()->create([
                'order_id' => $order->id,
                'type' => 'ortak_panel_sync_failed',
                'message' => 'Ortak panel senkronu başarısız: '.$error,
                'new_values' => [
                    'http_status' => $response->status(),
                    'body' => is_array($body) ? $body : mb_substr((string) $response->body(), 0, 500),
                ],
            ]);

            Log::warning('Ortak panel order sync failed', [
                'order_number' => $order->order_number,
                'http_status' => $response->status(),
                'body' => $body ?? $response->body(),
            ]);

            return ['ok' => false, 'error' => $error, 'response' => $body];
        } catch (Throwable $e) {
            OrderLog::query()->create([
                'order_id' => $order->id,
                'type' => 'ortak_panel_sync_failed',
                'message' => 'Ortak panel senkronu hata: '.$e->getMessage(),
            ]);

            Log::error('Ortak panel order sync exception', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function queue(Order|int $order): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $orderId = $order instanceof Order ? $order->id : $order;
        \App\Jobs\SyncOrderToOrtakPanelJob::dispatchSync($orderId);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Order $order): array
    {
        $order->loadMissing('items');
        $address = is_array($order->shipping_address) ? $order->shipping_address : [];
        $teslimat = is_array($address['teslimat'] ?? null) ? $address['teslimat'] : [];
        $invoice = is_array($teslimat['kurumsalFatura'] ?? null) ? $teslimat['kurumsalFatura'] : [];

        $items = [];
        $names = [];
        $qty = 0;
        foreach ($order->items as $item) {
            $name = trim((string) $item->product_name);
            if ($name !== '') {
                $names[] = $name;
            }
            $lineQty = max(1, (int) $item->quantity);
            $qty += $lineQty;
            $items[] = [
                'product_name' => $name,
                'sku' => (string) ($item->sku ?? ''),
                'quantity' => $lineQty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        }

        $productName = $names !== [] ? implode(' + ', $names) : 'Ürün';
        $paymentId = (string) $order->payment_method;
        $paymentLabel = self::PAYMENT_LABELS[$paymentId] ?? $paymentId;

        $payload = [
            'api_key' => (string) config('services.ortak_panel.api_key'),
            'site_origin' => (string) config('services.ortak_panel.site_origin'),
            'client_order_id' => (string) $order->order_number,
            'order_total' => (float) $order->total,
            'customer_name' => (string) $order->customer_name,
            'customer_phone' => (string) ($order->phone ?? ''),
            'customer_city' => (string) ($teslimat['il'] ?? ''),
            'customer_district' => (string) ($teslimat['ilce'] ?? ''),
            'customer_address' => (string) ($teslimat['adres'] ?? ''),
            'product_name' => mb_substr($productName, 0, 200),
            'product_quantity' => max(1, $qty),
            'payment_method' => $paymentLabel,
            'order_note' => trim((string) ($order->admin_note ?? '')),
            'order_date' => optional($order->created_at)?->timezone(config('app.timezone', 'Europe/Istanbul'))->format('Y-m-d H:i:s'),
            'items' => $items,
            'email' => (string) $order->email,
            'shipping_cost' => (float) $order->shipping_cost,
            'discount' => (float) $order->discount,
            'coupon_code' => (string) ($order->coupon_code ?? ''),
        ];

        if ($paymentId === 'kapida_odeme') {
            // Site checkout'ta COD OTP tamamlandıysa panel tekrar SMS istemesin.
            $payload['site_otp_verified'] = true;
        }

        if (! empty($invoice['vergiNumarasi'])) {
            $payload['invoice_tax_id'] = (string) $invoice['vergiNumarasi'];
        }
        if (! empty($invoice['vergiDairesi'])) {
            $payload['invoice_tax_office'] = (string) $invoice['vergiDairesi'];
        }
        if (! empty($invoice['unvan']) || ! empty($invoice['firmaUnvan'])) {
            $payload['invoice_company'] = (string) ($invoice['unvan'] ?? $invoice['firmaUnvan']);
        }
        if (! empty($invoice['faturaAdresi'])) {
            $payload['invoice_address'] = (string) $invoice['faturaAdresi'];
        }

        if (! empty($order->order_campaign)) {
            $payload['ref'] = mb_substr((string) $order->order_campaign, 0, 128);
        }
        if (! empty($order->order_source)) {
            $payload['utm_source'] = (string) $order->order_source;
        }
        if (! empty($order->order_medium)) {
            $payload['utm_medium'] = (string) $order->order_medium;
        }
        if (! empty($order->referrer_url)) {
            $payload['referrer'] = (string) $order->referrer_url;
            $payload['referrer_url'] = (string) $order->referrer_url;
        }

        return $payload;
    }
}
