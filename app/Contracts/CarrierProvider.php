<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\OrderShipment;

interface CarrierProvider
{
    public function key(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /** @return array{ok: bool, message?: string} */
    public function testConnection(): array;

    /**
     * @return array{
     *   ok: bool,
     *   external_id?: string,
     *   tracking_number?: string,
     *   barcode?: string,
     *   label_path?: string,
     *   payload?: array<string, mixed>,
     *   error?: string
     * }
     */
    public function createShipment(Order $order, OrderShipment $shipment): array;

    /**
     * @return array{
     *   ok: bool,
     *   status?: string,
     *   events?: list<array{status: string, description?: string, location?: string, occurred_at?: string}>,
     *   error?: string
     * }
     */
    public function trackShipment(OrderShipment $shipment): array;
}
