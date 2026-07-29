<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Telegram\OrderTelegramNotifier;
use Illuminate\Support\Facades\DB;

class AdminOrderCreator
{
    public function __construct(
        private CheckoutCalculator $calculator,
        private OrderMailService $mail,
        private OrderTelegramNotifier $telegram,
        private StoreConfig $store,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $data, array $items, ?int $adminId = null): Order
    {
        $lines = $this->normalizeItems($items);
        if ($lines === []) {
            throw new \RuntimeException('En az bir ürün seçilmelidir.');
        }

        $sendConfirmation = (bool) ($data['send_confirmation_email'] ?? false);
        $sendTelegram = (bool) ($data['send_telegram'] ?? true);

        return DB::transaction(function () use ($data, $lines, $adminId, $sendConfirmation, $sendTelegram) {
            $paymentMethod = (string) $data['payment_method'];
            $shippingMethod = (string) $data['kargo_yontemi'];
            $discount = max(0, round((float) ($data['discount'] ?? 0), 2));

            $lineSubtotal = 0.0;
            $preparedItems = [];

            foreach ($lines as $row) {
                $product = Product::query()->lockForUpdate()->findOrFail((int) $row['product_id']);
                $quantity = max(1, (int) $row['quantity']);
                $unitPrice = round((float) str_replace(',', '.', (string) ($row['unit_price'] ?? 0)), 2);

                if ($unitPrice <= 0) {
                    $unitPrice = (float) $product->price;
                }

                if ((int) $product->stock < $quantity) {
                    throw new \RuntimeException("{$product->name}: stokta en fazla {$product->stock} adet var.");
                }

                $lineTotal = round($quantity * $unitPrice, 2);
                $lineSubtotal += $lineTotal;

                $preparedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $totals = $this->calculator->totals(
                $lineSubtotal,
                $discount,
                $shippingMethod,
                $paymentMethod,
            );

            $shippingMethodData = collect($this->store->shippingMethods(true))
                ->firstWhere('id', $shippingMethod);

            $status = (string) $data['status'];
            $paymentStatus = (string) $data['payment_status'];

            if ($paymentMethod === 'kredi_karti' && $status === 'hazirlaniyor' && $paymentStatus === 'basarili') {
                $status = 'odeme_bekliyor';
                $paymentStatus = 'bekliyor';
            }

            $userId = User::query()
                ->customers()
                ->where('email', $data['eposta'])
                ->value('id');

            $order = Order::query()->create([
                'user_id' => $userId,
                'order_number' => $this->calculator->orderNumber(),
                'email' => $data['eposta'],
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'customer_name' => trim(($data['ad'] ?? '').' '.($data['soyad'] ?? '')),
                'phone' => $data['telefon'],
                'shipping_address' => $this->buildShippingAddress($data, $shippingMethod, $shippingMethodData, $totals),
                'shipping_tracking' => filled($data['shipping_tracking'] ?? null) ? $data['shipping_tracking'] : null,
                'admin_note' => $data['admin_note'] ?? null,
                'subtotal' => $totals['subtotal'],
                'shipping_cost' => $totals['shipping'],
                'discount' => $discount,
                'total' => $totals['total'],
                'sales_channel' => 'website',
                'order_source' => 'admin',
                'order_medium' => 'manuel',
            ]);

            foreach ($preparedItems as $item) {
                $product = $item['product'];
                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $order = $order->fresh(['items.product']);

            $order->logs()->create([
                'user_id' => $adminId,
                'type' => 'admin_create',
                'message' => 'Sipariş panelden manuel oluşturuldu.',
                'new_values' => [
                    'order_number' => $order->order_number,
                    'total' => number_format((float) $order->total, 2, '.', ''),
                    'payment_method' => $paymentMethod,
                ],
            ]);

            if ($sendConfirmation && ! $order->isPendingPayment()) {
                $this->mail->sendOrderConfirmation($order);
            }

            if ($sendTelegram && ! $order->isPendingPayment()) {
                $this->telegram->queue($order);
            }

            return $order;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return array_values(array_filter($items, function (array $row): bool {
            return ! empty($row['product_id']);
        }));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $shippingMethodData
     * @param  array{subtotal: float, discount: float, shipping: float, cod_fee: float, vat: float, total: float}  $totals
     * @return array<string, mixed>
     */
    private function buildShippingAddress(array $data, string $shippingMethod, ?array $shippingMethodData, array $totals): array
    {
        $teslimat = [
            'ad' => $data['ad'],
            'soyad' => $data['soyad'],
            'eposta' => $data['eposta'],
            'telefon' => $data['telefon'],
            'il' => $data['il'],
            'ilce' => $data['ilce'],
            'adres' => $data['adres'],
            'postaKodu' => $data['posta_kodu'] ?? null,
        ];

        if (! empty($data['kurumsal_fatura'])) {
            $teslimat['kurumsalFatura'] = [
                'firmaAdi' => $data['firma_adi'] ?? '',
                'vergiNumarasi' => $data['vergi_numarasi'] ?? '',
                'vergiDairesi' => $data['vergi_dairesi'] ?? '',
                'faturaAdresi' => $data['fatura_adresi'] ?? '',
            ];
        }

        return [
            'teslimat' => $teslimat,
            'kargo_yontemi' => $shippingMethod,
            'kargo_firma' => $shippingMethodData,
            'kdv' => $totals['vat'],
            'kapida_ucret' => $totals['cod_fee'],
        ];
    }
}
