<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\OrderShipment;
use App\Support\CarrierConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DhlEcommerceClient
{
    public function __construct(private DhlEcommerceTokenCache $tokens) {}

    /** @return array{ok: bool, message?: string} */
    public function ping(): array
    {
        try {
            $this->tokens->forget();
            $this->token();

            return ['ok' => true, 'message' => 'DHL eCommerce token alındı. API bağlantısı hazır.'];
        } catch (\Throwable $e) {
            Log::warning('DHL token ping failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * CreateOrder (tüm taslak koliler) + CreateBarcode (tek koli).
     *
     * @return array{ok: bool, external_id?: string, tracking_number?: string, barcode?: string, label_path?: string, payload?: array<string, mixed>, error?: string}
     */
    public function createShipment(Order $order, OrderShipment $shipment): array
    {
        $order->loadMissing('shipments');
        $drafts = $order->shipments->where('status', 'draft')->sortBy('package_number')->values();

        if ($drafts->doesntContain(fn (OrderShipment $s) => $s->id === $shipment->id)) {
            return ['ok' => false, 'error' => 'Koli taslak durumda değil veya bulunamadı.'];
        }

        if (! $this->isOrderRegistered($order)) {
            $orderResult = $this->createOrder($order, $drafts);
            if (! $orderResult['ok']) {
                return $orderResult;
            }
        }

        return $this->createBarcode($order, $shipment);
    }

    /** @return array{ok: bool, status?: string, events?: list<array<string, mixed>>, error?: string} */
    public function trackShipment(OrderShipment $shipment): array
    {
        return [
            'ok' => false,
            'error' => 'DHL eCommerce takip API entegrasyonu henüz tanımlı değil.',
        ];
    }

    public function isOrderRegistered(Order $order): bool
    {
        return $order->shipments()
            ->where('carrier_payload->order_registered', true)
            ->exists();
    }

    /**
     * @param  Collection<int, OrderShipment>  $shipments
     * @return array{ok: bool, payload?: array<string, mixed>, error?: string}
     */
    public function createOrder(Order $order, Collection $shipments): array
    {
        $referenceId = $this->referenceId($order);
        $payload = [
            'order' => $this->buildOrderHeader($order, $referenceId),
            'orderPieceList' => $shipments
                ->sortBy('package_number')
                ->map(fn (OrderShipment $s) => $this->buildPiecePayload($order, $s))
                ->values()
                ->all(),
            'recipient' => $this->buildRecipient($order),
        ];

        try {
            $response = Http::timeout(45)
                ->withHeaders($this->headers($this->token()))
                ->acceptJson()
                ->post($this->endpoint('/mngapi/api/standardcmdapi/createOrder'), $payload);

            if (! $response->successful()) {
                return ['ok' => false, 'error' => $this->httpError('CreateOrder', $response->status(), $response->body())];
            }

            $data = $response->json();
            $row = is_array($data) ? ($data[0] ?? $data) : [];

            $meta = [
                'reference_id' => $referenceId,
                'order_registered' => true,
                'order_invoice_id' => data_get($row, 'orderInvoiceId'),
                'order_invoice_detail_id' => data_get($row, 'orderInvoiceDetailId'),
                'shipper_branch_code' => data_get($row, 'shipperBranchCode'),
                'create_order_response' => $data,
            ];

            foreach ($shipments as $draft) {
                $draft->update([
                    'carrier_payload' => array_merge(is_array($draft->carrier_payload) ? $draft->carrier_payload : [], $meta, [
                        'piece_barcode' => $this->pieceBarcode($order, $draft),
                    ]),
                ]);
            }

            return ['ok' => true, 'payload' => $meta];
        } catch (\Throwable $e) {
            Log::error('DHL CreateOrder failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'CreateOrder hatası: '.$e->getMessage()];
        }
    }

    /** @return array{ok: bool, external_id?: string, tracking_number?: string, barcode?: string, label_path?: string, payload?: array<string, mixed>, error?: string} */
    public function createBarcode(Order $order, OrderShipment $shipment): array
    {
        $referenceId = $this->referenceId($order);
        $payload = [
            'referenceId' => $referenceId,
            'billOfLandingId' => Str::limit($order->order_number, 30, ''),
            'isCOD' => $order->payment_method === 'kapida_odeme' ? 1 : 0,
            'codAmount' => (float) ($shipment->cod_amount ?? 0),
            'printReferenceBarcodeOnError' => 1,
            'message' => '',
            'additionalContent1' => Str::limit($order->order_number, 30, ''),
            'additionalContent2' => '',
            'additionalContent3' => '',
            'additionalContent4' => '',
            'packagingType' => (int) config('carriers.dhl.packaging_type', 3),
            'orderPieceList' => [$this->buildPiecePayload($order, $shipment)],
        ];

        try {
            $response = Http::timeout(45)
                ->withHeaders($this->headers($this->token()))
                ->acceptJson()
                ->post($this->endpoint('/mngapi/api/barcodecmdapi/createbarcode'), $payload);

            if (! $response->successful()) {
                return ['ok' => false, 'error' => $this->httpError('CreateBarcode', $response->status(), $response->body())];
            }

            $data = $response->json();
            $row = is_array($data) ? ($data[0] ?? $data) : [];
            $barcodeRow = collect(data_get($row, 'barcodes', []))->first(fn ($item) => is_array($item)) ?? [];
            $zpl = (string) data_get($barcodeRow, 'value', '');
            $tracking = (string) (data_get($row, 'shipmentId') ?? data_get($barcodeRow, 'barcode') ?? '');
            $invoiceId = (string) (data_get($row, 'invoiceId') ?? '');

            if ($tracking === '') {
                return ['ok' => false, 'error' => 'CreateBarcode yanıtında shipmentId bulunamadı.'];
            }

            $labelPath = $this->storeLabel($order, $shipment, $zpl !== '' ? $zpl : $this->fallbackLabel($order, $shipment, $tracking));

            return [
                'ok' => true,
                'external_id' => $invoiceId !== '' ? $invoiceId : $referenceId,
                'tracking_number' => $tracking,
                'barcode' => (string) (data_get($barcodeRow, 'barcode') ?? $tracking),
                'label_path' => $labelPath,
                'payload' => is_array($row) ? $row : [],
            ];
        } catch (\Throwable $e) {
            Log::error('DHL CreateBarcode failed', [
                'order' => $order->order_number,
                'package' => $shipment->package_number,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'CreateBarcode hatası: '.$e->getMessage()];
        }
    }

    private function token(): string
    {
        return $this->tokens->get(fn (): array => $this->requestToken());
    }

    /** @return array{ok: bool, token?: string, message?: string} */
    private function requestToken(): array
    {
        $settings = CarrierConfig::dhlSettings();

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-ibm-client-id' => (string) $settings['client_id'],
                    'x-ibm-client-secret' => (string) $settings['client_secret'],
                ])
                ->post($this->endpoint('/mngapi/api/token'), [
                    'customerNumber' => (string) $settings['customer_number'],
                    'password' => (string) $settings['password'],
                    'identityType' => 1,
                ]);

            if (! $response->successful()) {
                return ['ok' => false, 'message' => $this->httpError('Token', $response->status(), $response->body())];
            }

            $jwt = (string) data_get($response->json(), 'jwt', '');
            if ($jwt === '') {
                return ['ok' => false, 'message' => 'Token yanıtında JWT bulunamadı.'];
            }

            return ['ok' => true, 'token' => $jwt];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Token bağlantı hatası: '.$e->getMessage()];
        }
    }

    /** @return array<string, string> */
    private function headers(string $token): array
    {
        $settings = CarrierConfig::dhlSettings();

        return [
            'Content-Type' => 'application/json',
            'x-ibm-client-id' => (string) $settings['client_id'],
            'x-ibm-client-secret' => (string) $settings['client_secret'],
            'Authorization' => 'Bearer '.$token,
        ];
    }

    private function endpoint(string $path): string
    {
        return rtrim(CarrierConfig::apiBaseUrl(), '/').$path;
    }

    /** @return array<string, mixed> */
    private function buildOrderHeader(Order $order, string $referenceId): array
    {
        $isCod = $order->payment_method === 'kapida_odeme';

        return [
            'referenceId' => $referenceId,
            'barcode' => $referenceId,
            'billOfLandingId' => Str::limit($order->order_number, 30, ''),
            'isCOD' => $isCod ? 1 : 0,
            'codAmount' => $isCod ? (float) $order->total : 0,
            'shipmentServiceType' => (int) config('carriers.dhl.shipment_service_type', 1),
            'packagingType' => (int) config('carriers.dhl.packaging_type', 3),
            'content' => Str::limit($this->orderContent($order), 200, ''),
            'smsPreference1' => (int) config('carriers.dhl.sms_preference1', 0),
            'smsPreference2' => (int) config('carriers.dhl.sms_preference2', 0),
            'smsPreference3' => (int) config('carriers.dhl.sms_preference3', 0),
            'paymentType' => (int) config('carriers.dhl.payment_type', 1),
            'deliveryType' => (int) config('carriers.dhl.delivery_type', 1),
            'description' => Str::limit('Siparis '.$order->order_number, 150, ''),
            'marketPlaceShortCode' => '',
            'marketPlaceSaleCode' => '',
            'pudoId' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function buildPiecePayload(Order $order, OrderShipment $shipment): array
    {
        return [
            'barcode' => $this->pieceBarcode($order, $shipment),
            'desi' => max(1, (int) round((float) ($shipment->desi ?? 1))),
            'kg' => max(1, (int) round((float) ($shipment->weight_kg ?? 1))),
            'content' => Str::limit($this->packageContent($shipment), 150, ''),
        ];
    }

    /** @return array<string, mixed> */
    private function buildRecipient(Order $order): array
    {
        $teslimat = $order->shipping_address['teslimat'] ?? [];
        $ad = trim((string) ($teslimat['ad'] ?? ''));
        $soyad = trim((string) ($teslimat['soyad'] ?? ''));
        $fullName = trim($order->customer_name ?: ($ad.' '.$soyad));

        return [
            'customerId' => '',
            'refCustomerId' => '',
            'cityCode' => 0,
            'districtCode' => 0,
            'cityName' => Str::upper(Str::limit(trim((string) ($teslimat['il'] ?? '')), 30, '')),
            'districtName' => Str::upper(Str::limit(trim((string) ($teslimat['ilce'] ?? '')), 30, '')),
            'address' => Str::limit(trim((string) ($teslimat['adres'] ?? '')), 200, ''),
            'bussinessPhoneNumber' => '',
            'email' => Str::limit((string) $order->email, 50, ''),
            'taxOffice' => '',
            'taxNumber' => '',
            'fullName' => Str::limit($fullName !== '' ? $fullName : 'ALICI', 150, ''),
            'homePhoneNumber' => '',
            'mobilePhoneNumber' => $this->normalizeMobile((string) ($order->phone ?? ($teslimat['telefon'] ?? ''))),
        ];
    }

    private function referenceId(Order $order): string
    {
        $raw = Str::upper(preg_replace('/[^A-Z0-9\-]/', '', Str::upper($order->order_number)) ?? Str::upper($order->order_number));

        return Str::limit($raw !== '' ? $raw : 'ORD'.$order->id, 20, '');
    }

    private function pieceBarcode(Order $order, OrderShipment $shipment): string
    {
        $barcode = $this->referenceId($order).'_P'.$shipment->package_number;

        return Str::limit(Str::upper($barcode), 30, '');
    }

    private function orderContent(Order $order): string
    {
        $order->loadMissing('items');

        return $order->items->pluck('product_name')->filter()->implode(', ') ?: 'Siparis';
    }

    private function packageContent(OrderShipment $shipment): string
    {
        $lines = collect($shipment->itemLines())
            ->map(fn (array $line) => ($line['quantity'] ?? 1).'x '.($line['product_name'] ?? 'Urun'))
            ->implode(', ');

        return $lines !== '' ? $lines : 'Paket';
    }

    private function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '90') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return Str::limit($digits, 10, '');
    }

    private function storeLabel(Order $order, OrderShipment $shipment, string $contents): string
    {
        $path = 'shipping-labels/'.$order->order_number.'-p'.$shipment->package_number.'.zpl';
        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    private function fallbackLabel(Order $order, OrderShipment $shipment, string $tracking): string
    {
        return implode("\n", [
            '^XA',
            '^FO50,50^A0N,40,40^FD'.$tracking.'^FS',
            '^FO50,120^A0N,28,28^FD'.$order->order_number.' P'.$shipment->package_number.'^FS',
            '^XZ',
        ]);
    }

    private function httpError(string $service, int $status, string $body): string
    {
        $body = trim(Str::limit($body, 500, '…'));

        return $service.' HTTP '.$status.($body !== '' ? ': '.$body : '');
    }
}
