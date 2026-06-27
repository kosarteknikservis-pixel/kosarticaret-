<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderShipment;
use App\Models\ShipmentEvent;
use App\Support\CarrierConfig;
use App\Support\OrderStatus;
use App\Support\ShipmentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderShipmentService
{
    public function __construct(
        private CarrierManager $carriers,
        private OrderShipmentPlanner $planner,
        private ShipmentNotificationService $notifications,
    ) {}

    /** @param  list<array<string, mixed>>  $packages */
    public function saveDraftPackages(Order $order, array $packages, ?int $adminId = null): Order
    {
        return DB::transaction(function () use ($order, $packages, $adminId): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $locked = $order->shipments()
                ->whereNotIn('status', ['draft'])
                ->exists();

            if ($locked) {
                throw new \RuntimeException('Gönderilmiş koliler varken taslak plan yeniden yazılamaz.');
            }

            $order->shipments()->delete();

            foreach ($packages as $index => $package) {
                $order->shipments()->create([
                    'package_number' => (int) ($package['package_number'] ?? ($index + 1)),
                    'carrier' => CarrierConfig::defaultCarrier(),
                    'status' => 'draft',
                    'items' => $package['items'] ?? [],
                    'weight_kg' => $package['weight_kg'] ?? 1,
                    'desi' => $package['desi'] ?? 1,
                    'cod_amount' => $order->payment_method === 'kapida_odeme' ? $this->packageCodAmount($order, $package) : null,
                ]);
            }

            $this->log($order, 'shipment_plan_saved', 'Kargo koli planı kaydedildi.', $adminId);

            return $order->fresh('shipments');
        });
    }

    public function generatePlan(Order $order, ?int $adminId = null): Order
    {
        return $this->saveDraftPackages($order, $this->planner->suggest($order), $adminId);
    }

    public function submitShipment(OrderShipment $shipment, ?int $adminId = null): OrderShipment
    {
        if (! $shipment->canSubmit()) {
            throw new \RuntimeException('Bu koli DHL\'e gönderilemez.');
        }

        $shipment->loadMissing('order.items');
        $provider = $this->carriers->provider($shipment->carrier);
        $result = $provider->createShipment($shipment->order, $shipment);

        if (! $result['ok']) {
            $shipment->update([
                'status' => 'failed',
                'error_message' => $result['error'] ?? 'Kargo oluşturulamadı.',
            ]);

            $this->log($shipment->order, 'shipment_failed', $shipment->error_message ?? 'Kargo hatası', $adminId, [
                'package_number' => $shipment->package_number,
            ]);

            throw new \RuntimeException($result['error'] ?? 'Kargo oluşturulamadı.');
        }

        return DB::transaction(function () use ($shipment, $result, $adminId): OrderShipment {
            $shipment->update([
                'status' => 'submitted',
                'external_id' => $result['external_id'] ?? null,
                'tracking_number' => $result['tracking_number'] ?? null,
                'barcode' => $result['barcode'] ?? null,
                'label_path' => $result['label_path'] ?? null,
                'carrier_payload' => $result['payload'] ?? null,
                'error_message' => null,
                'submitted_at' => now(),
            ]);

            $this->recordEvent($shipment, 'submitted', 'Kargo kaydı oluşturuldu.');

            $order = $shipment->order()->lockForUpdate()->first();
            $this->syncOrderFromShipments($order);

            $this->log($order, 'shipment_submitted', 'Koli DHL\'e bildirildi: '.$shipment->tracking_number, $adminId, [
                'tracking_number' => $shipment->tracking_number,
                'package_number' => $shipment->package_number,
            ]);

            $this->notifications->afterShipmentSubmitted($order->fresh('shipments'), $shipment->fresh(), $adminId);

            return $shipment->fresh(['events', 'order']);
        });
    }

    public function submitAllDrafts(Order $order, ?int $adminId = null): Order
    {
        $order->loadMissing('shipments');

        foreach ($order->shipments->where('status', 'draft') as $shipment) {
            $this->submitShipment($shipment, $adminId);
        }

        return $order->fresh('shipments');
    }

    public function syncShipment(OrderShipment $shipment, ?int $adminId = null): OrderShipment
    {
        if (blank($shipment->tracking_number) && blank($shipment->external_id)) {
            return $shipment;
        }

        $result = $this->carriers->provider($shipment->carrier)->trackShipment($shipment);
        if (! $result['ok']) {
            $shipment->update(['last_synced_at' => now()]);

            return $shipment;
        }

        return DB::transaction(function () use ($shipment, $result, $adminId): OrderShipment {
            $mappedStatus = CarrierConfig::mapCarrierStatus($shipment->carrier, (string) ($result['status'] ?? $shipment->status));

            if ($mappedStatus !== $shipment->status) {
                $shipment->status = $mappedStatus;
                if ($mappedStatus === 'delivered') {
                    $shipment->delivered_at = now();
                }
            }

            $shipment->last_synced_at = now();
            $shipment->save();

            foreach ($result['events'] ?? [] as $event) {
                $this->recordEvent(
                    $shipment,
                    (string) ($event['status'] ?? 'update'),
                    $event['description'] ?? null,
                    $event['location'] ?? null,
                    isset($event['occurred_at']) ? Carbon::parse($event['occurred_at']) : now(),
                    is_array($event) ? $event : null,
                );
            }

            $order = $shipment->order()->lockForUpdate()->first();
            $this->syncOrderFromShipments($order);

            return $shipment->fresh(['events', 'order']);
        });
    }

    public function syncActiveShipments(): int
    {
        $count = 0;

        OrderShipment::query()
            ->whereNotIn('status', ['draft', 'delivered', 'cancelled', 'failed', 'returned'])
            ->whereNotNull('tracking_number')
            ->orderBy('id')
            ->chunkById(50, function ($shipments) use (&$count): void {
                foreach ($shipments as $shipment) {
                    $this->syncShipment($shipment);
                    $count++;
                }
            });

        return $count;
    }

    public function labelContents(OrderShipment $shipment): ?string
    {
        if (! $shipment->label_path || ! Storage::disk('local')->exists($shipment->label_path)) {
            return null;
        }

        return Storage::disk('local')->get($shipment->label_path);
    }

    private function syncOrderFromShipments(Order $order): void
    {
        $order->loadMissing('shipments');
        $shipments = $order->shipments;

        if ($shipments->isEmpty()) {
            return;
        }

        $trackingNumbers = $shipments
            ->pluck('tracking_number')
            ->filter()
            ->unique()
            ->values();

        if ($trackingNumbers->isNotEmpty()) {
            $order->shipping_tracking = $trackingNumbers->implode(', ');
            $order->shipping_carrier = $shipments->first()->carrier;
        }

        if ($shipments->every(fn (OrderShipment $s) => $s->status === 'delivered')) {
            $order->status = 'teslim_edildi';
        } elseif ($shipments->contains(fn (OrderShipment $s) => ShipmentStatus::isActive($s->status) || $s->status === 'submitted')) {
            $order->status = 'kargoda';
        }

        $order->save();
    }

    private function recordEvent(
        OrderShipment $shipment,
        string $status,
        ?string $description = null,
        ?string $location = null,
        ?\DateTimeInterface $occurredAt = null,
        ?array $raw = null,
    ): void {
        ShipmentEvent::query()->create([
            'order_shipment_id' => $shipment->id,
            'status' => $status,
            'description' => $description,
            'location' => $location,
            'occurred_at' => $occurredAt ?? now(),
            'raw' => $raw,
        ]);
    }

    /** @param  array<string, mixed>  $package */
    private function packageCodAmount(Order $order, array $package): float
    {
        $totalQty = max(1, (int) collect($order->items)->sum('quantity'));
        $packageQty = max(1, (int) collect($package['items'] ?? [])->sum('quantity'));

        return round(((float) $order->total) * ($packageQty / $totalQty), 2);
    }

    /** @param  array<string, mixed>  $context */
    private function log(Order $order, string $type, string $message, ?int $adminId = null, array $context = []): void
    {
        OrderLog::query()->create([
            'order_id' => $order->id,
            'user_id' => $adminId,
            'type' => $type,
            'message' => $message,
            'new_values' => $context !== [] ? $context : null,
        ]);
    }
}
