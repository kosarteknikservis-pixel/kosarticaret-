<?php



namespace App\Services\Shipping;



use App\Contracts\CarrierProvider;

use App\Models\Order;

use App\Models\OrderShipment;

use App\Support\CarrierConfig;

use App\Support\ShipmentStatus;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;



class DhlEcommerceProvider implements CarrierProvider

{

    public function __construct(private DhlEcommerceClient $client) {}



    public function key(): string

    {

        return 'dhl';

    }



    public function label(): string

    {

        return 'DHL eCommerce';

    }



    public function isConfigured(): bool

    {

        return CarrierConfig::isConfigured('dhl');

    }



    public function testConnection(): array

    {

        if (! CarrierConfig::isEnabled('dhl')) {

            return ['ok' => false, 'message' => 'DHL entegrasyonu kapalı.'];

        }



        if ($this->usesOfflineSandbox()) {

            return ['ok' => true, 'message' => 'Offline sandbox: API bilgileri girilmeden yerel test gönderileri oluşturulabilir.'];

        }



        return $this->client->ping();

    }



    public function createShipment(Order $order, OrderShipment $shipment): array

    {

        if ($this->usesOfflineSandbox()) {

            return $this->createOfflineSandboxShipment($order, $shipment);

        }



        return $this->client->createShipment($order, $shipment);

    }



    public function trackShipment(OrderShipment $shipment): array

    {

        if ($this->usesOfflineSandbox()) {

            return $this->trackOfflineSandboxShipment($shipment);

        }



        return $this->client->trackShipment($shipment);

    }



    private function usesOfflineSandbox(): bool

    {

        return CarrierConfig::isSandbox('dhl') && ! CarrierConfig::hasApiCredentials('dhl');

    }



    /** @return array{ok: bool, external_id?: string, tracking_number?: string, barcode?: string, label_path?: string, payload?: array<string, mixed>} */

    private function createOfflineSandboxShipment(Order $order, OrderShipment $shipment): array

    {

        $tracking = 'DHL'.strtoupper(Str::random(10));

        $path = $this->storeOfflineLabel($order, $shipment, $tracking);



        return [

            'ok' => true,

            'external_id' => 'sandbox-'.$shipment->id,

            'tracking_number' => $tracking,

            'barcode' => $tracking,

            'label_path' => $path,

            'payload' => ['mode' => 'offline_sandbox'],

        ];

    }



    /** @return array{ok: bool, status?: string, events?: list<array<string, mixed>>} */

    private function trackOfflineSandboxShipment(OrderShipment $shipment): array

    {

        $hours = $shipment->submitted_at?->diffInHours(now()) ?? 0;

        $status = match (true) {

            $hours >= 72 => 'delivered',

            $hours >= 24 => 'in_transit',

            $hours >= 2 => 'picked_up',

            default => 'submitted',

        };



        return [

            'ok' => true,

            'status' => $status,

            'events' => [[

                'status' => $status,

                'description' => ShipmentStatus::label($status),

                'occurred_at' => now()->toIso8601String(),

            ]],

        ];

    }



    private function storeOfflineLabel(Order $order, OrderShipment $shipment, string $tracking): string

    {

        $path = 'shipping-labels/'.$order->order_number.'-p'.$shipment->package_number.'.zpl';

        $content = implode("\n", [

            '^XA',

            '^FO40,40^A0N,36,36^FDDHL OFFLINE SANDBOX^FS',

            '^FO40,100^A0N,30,30^FD'.$tracking.'^FS',

            '^FO40,150^A0N,24,24^FD'.$order->order_number.' P'.$shipment->package_number.'^FS',

            '^XZ',

        ]);

        Storage::disk('local')->put($path, $content);



        return $path;

    }

}


