<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class OrderShipmentPlanner
{
    /**
     * @return list<array{
     *   package_number: int,
     *   items: list<array{order_item_id: int, product_id: ?int, product_name: string, sku: ?string, quantity: int}>,
     *   weight_kg: float,
     *   desi: float
     * }>
     */
    public function suggest(Order $order): array
    {
        $order->loadMissing(['items.product']);
        $packages = [];
        $packageNumber = 1;

        foreach ($order->items as $item) {
            $chunks = $this->splitItemQuantity($item);

            foreach ($chunks as $quantity) {
                $packages[] = $this->packagePayload($packageNumber++, $item, $quantity);
            }
        }

        if ($packages === []) {
            $packages[] = [
                'package_number' => 1,
                'items' => [],
                'weight_kg' => 1.0,
                'desi' => 1.0,
            ];
        }

        return $packages;
    }

    /** @return list<int> */
    private function splitItemQuantity(OrderItem $item): array
    {
        $quantity = max(1, (int) $item->quantity);
        $product = $item->product;
        $unitsPerCarton = max(1, (int) ($product?->units_per_carton ?? 0));

        if ($unitsPerCarton <= 1) {
            return [$quantity];
        }

        $chunks = [];
        $remaining = $quantity;

        while ($remaining > 0) {
            $take = min($unitsPerCarton, $remaining);
            $chunks[] = $take;
            $remaining -= $take;
        }

        return $chunks;
    }

    /**
     * @return array{
     *   package_number: int,
     *   items: list<array{order_item_id: int, product_id: ?int, product_name: string, sku: ?string, quantity: int}>,
     *   weight_kg: float,
     *   desi: float
     * }
     */
    private function packagePayload(int $packageNumber, OrderItem $item, int $quantity): array
    {
        $product = $item->product;
        $weight = $this->lineWeight($product, $quantity);
        $desi = $this->lineDesi($product, $quantity);

        return [
            'package_number' => $packageNumber,
            'items' => [[
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $quantity,
            ]],
            'weight_kg' => $weight,
            'desi' => $desi,
        ];
    }

    private function lineWeight(?Product $product, int $quantity): float
    {
        $unit = (float) ($product?->weight_kg ?? 1);

        return max(0.1, round($unit * $quantity, 3));
    }

    private function lineDesi(?Product $product, int $quantity): float
    {
        if ($product && $product->desi()) {
            return max(1.0, round((float) $product->desi() * $quantity, 2));
        }

        return max(1.0, round($this->lineWeight($product, $quantity), 2));
    }
}
