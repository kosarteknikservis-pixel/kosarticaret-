<?php

namespace App\Console\Commands;

use App\Services\Shipping\OrderShipmentService;
use App\Support\CarrierConfig;
use Illuminate\Console\Command;

class SyncShipmentStatusesCommand extends Command
{
    protected $signature = 'shipments:sync-status';

    protected $description = 'Aktif kargo kolilerinin durumunu taşıyıcıdan senkronize eder';

    public function handle(OrderShipmentService $service): int
    {
        if (! CarrierConfig::isEnabled()) {
            $this->warn('Kargo entegrasyonu kapalı.');

            return self::SUCCESS;
        }

        $count = $service->syncActiveShipments();
        $this->info("{$count} koli senkronize edildi.");

        return self::SUCCESS;
    }
}
