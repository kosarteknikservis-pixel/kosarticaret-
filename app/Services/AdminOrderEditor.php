<?php

namespace App\Services;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\MailSettings;
use App\Support\OrderStatus;
use App\Support\PaymentStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminOrderEditor
{
    public function __construct(private StoreConfig $store) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @return array{order: Order, notify: bool}
     */
    public function update(Order $order, array $data, array $items, ?int $adminId = null): array
    {
        $notify = false;

        $updated = DB::transaction(function () use ($order, $data, $items, $adminId, &$notify) {
            $order = Order::query()->with('items.product')->lockForUpdate()->findOrFail($order->id);
            $before = $this->snapshot($order);
            $oldStatus = $order->status;
            $oldTracking = (string) ($order->shipping_tracking ?? '');

            $stockRestored = (bool) ($order->shipping_address['stock_restored'] ?? false);
            $this->syncItems($order, $items, ! $stockRestored);
            $order->load('items.product');
            $this->syncAddress($order, $data);

            $order->fill([
                'email' => $data['eposta'],
                'customer_name' => trim(($data['ad'] ?? '').' '.($data['soyad'] ?? '')),
                'phone' => $data['telefon'],
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'shipping_tracking' => $data['shipping_tracking'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            $this->recalculateTotals($order);
            $this->syncCancelledStock($order, $oldStatus);
            $order->save();

            $order = $order->fresh(['items.product', 'logs.user']);
            $after = $this->snapshot($order);
            $changes = $this->diffSnapshot($before, $after);

            if ($changes !== []) {
                $order->logs()->create([
                    'user_id' => $adminId,
                    'type' => 'admin_update',
                    'message' => 'Sipariş panelden güncellendi.',
                    'old_values' => Arr::pluck($changes, 'old', 'field'),
                    'new_values' => Arr::pluck($changes, 'new', 'field'),
                ]);
            }

            $notify = $oldStatus !== $order->status || $oldTracking !== (string) ($order->shipping_tracking ?? '');

            return $order;
        });

        if ($notify) {
            $this->sendStatusMail($updated);
        }

        return ['order' => $updated, 'notify' => $notify];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncItems(Order $order, array $rows, bool $adjustStock = true): void
    {
        $kept = [];

        foreach ($rows as $row) {
            $remove = (bool) ($row['remove'] ?? false);
            $itemId = isset($row['id']) ? (int) $row['id'] : 0;
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $unitPrice = round((float) str_replace(',', '.', (string) ($row['unit_price'] ?? 0)), 2);
            if ($unitPrice < 0) {
                throw new \RuntimeException('Ürün fiyatı negatif olamaz.');
            }

            if ($itemId > 0) {
                $item = $order->items->firstWhere('id', $itemId);
                if (! $item) {
                    continue;
                }

                if ($remove) {
                    if ($adjustStock) {
                        $this->restoreStock($item, $item->quantity);
                    }
                    $item->delete();
                    continue;
                }

                if ($adjustStock) {
                    $this->applyStockDelta($item, $quantity - (int) $item->quantity);
                }
                $item->update([
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($quantity * $unitPrice, 2),
                ]);
                $kept[] = $item->id;
                continue;
            }

            if ($remove || empty($row['product_id'])) {
                continue;
            }

            $product = Product::query()->findOrFail((int) $row['product_id']);
            if ($adjustStock && (int) $product->stock < $quantity) {
                throw new \RuntimeException("{$product->name}: stokta en fazla {$product->stock} adet var.");
            }

            if ($adjustStock) {
                $product->decrement('stock', $quantity);
            }
            $item = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice > 0 ? $unitPrice : $product->price,
                'line_total' => round($quantity * ($unitPrice > 0 ? $unitPrice : (float) $product->price), 2),
            ]);
            $kept[] = $item->id;
        }

        if ($order->items()->count() === 0) {
            throw new \RuntimeException('Siparişte en az bir ürün kalmalı.');
        }
    }

    private function applyStockDelta(OrderItem $item, int $delta): void
    {
        if ($delta === 0 || ! $item->product_id || ! $item->product) {
            return;
        }

        if ($delta > 0) {
            if ((int) $item->product->stock < $delta) {
                throw new \RuntimeException("{$item->product_name}: stokta ek {$delta} adet yok.");
            }
            $item->product->decrement('stock', $delta);

            return;
        }

        $item->product->increment('stock', abs($delta));
    }

    private function restoreStock(OrderItem $item, int $quantity): void
    {
        if ($item->product_id && $item->product) {
            $item->product->increment('stock', max(0, $quantity));
        }
    }

    /** @param array<string, mixed> $data */
    private function syncAddress(Order $order, array $data): void
    {
        $address = $order->shipping_address ?? [];
        $address['teslimat'] = [
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
            $address['teslimat']['kurumsalFatura'] = [
                'firmaAdi' => $data['firma_adi'] ?? '',
                'vergiNumarasi' => $data['vergi_numarasi'] ?? '',
                'vergiDairesi' => $data['vergi_dairesi'] ?? '',
                'faturaAdresi' => $data['fatura_adresi'] ?? '',
            ];
        }

        $order->shipping_address = $address;
    }

    private function recalculateTotals(Order $order): void
    {
        $lineSubtotal = (float) $order->items()->sum('line_total');
        $discount = min((float) $order->discount, $lineSubtotal);
        $subtotal = max(0, round($lineSubtotal - $discount, 2));
        $shipping = (float) $order->shipping_cost;
        $codFee = (float) ($order->shipping_address['kapida_ucret'] ?? 0);
        $vat = $this->store->shouldAddVat()
            ? round(($subtotal + $shipping) * $this->store->vatRate(), 2)
            : (float) ($order->shipping_address['kdv'] ?? 0);
        $address = $order->shipping_address ?? [];
        $address['kdv'] = $vat;
        $address['kapida_ucret'] = $codFee;

        $order->subtotal = $subtotal;
        $order->shipping_address = $address;
        $order->total = round($subtotal + $shipping + $vat + $codFee, 2);
    }

    private function syncCancelledStock(Order $order, ?string $oldStatus): void
    {
        $address = $order->shipping_address ?? [];
        $restored = (bool) ($address['stock_restored'] ?? false);

        if ($order->status === 'iptal' && ! $restored) {
            foreach ($order->items as $item) {
                $this->restoreStock($item, (int) $item->quantity);
            }
            $address['stock_restored'] = true;
            $order->shipping_address = $address;

            return;
        }

        if ($oldStatus === 'iptal' && $order->status !== 'iptal' && $restored) {
            foreach ($order->items as $item) {
                $this->applyStockDelta($item, (int) $item->quantity);
            }
            $address['stock_restored'] = false;
            $order->shipping_address = $address;
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Order $order): array
    {
        $teslimat = $order->shipping_address['teslimat'] ?? [];

        return [
            'status' => OrderStatus::label($order->status),
            'payment_status' => PaymentStatus::label($order->payment_status),
            'shipping_tracking' => $order->shipping_tracking,
            'admin_note' => $order->admin_note,
            'customer' => $order->customer_name.' / '.$order->email.' / '.$order->phone,
            'address' => trim(($teslimat['adres'] ?? '').' '.($teslimat['ilce'] ?? '').' '.($teslimat['il'] ?? '')),
            'items' => $order->items->map(fn (OrderItem $item) => $item->product_name.' x '.$item->quantity.' = '.number_format((float) $item->line_total, 2, '.', ''))->values()->all(),
            'total' => number_format((float) $order->total, 2, '.', ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<int, array{field: string, old: mixed, new: mixed}>
     */
    private function diffSnapshot(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $value) {
            if (($before[$field] ?? null) !== $value) {
                $changes[] = ['field' => $field, 'old' => $before[$field] ?? null, 'new' => $value];
            }
        }

        return $changes;
    }

    private function sendStatusMail(Order $order): void
    {
        try {
            MailSettings::apply();
            Mail::to($order->email)->send(new OrderStatusUpdatedMail($order->load('items')));
        } catch (\Throwable $e) {
            Log::error('Sipariş durum e-postası gönderilemedi', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
