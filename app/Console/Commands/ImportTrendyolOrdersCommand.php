<?php

namespace App\Console\Commands;

use App\Jobs\Marketplace\ImportTrendyolOrdersJob;
use App\Services\Marketplace\Trendyol\TrendyolOrderImporter;
use Illuminate\Console\Command;

class ImportTrendyolOrdersCommand extends Command
{
    protected $signature = 'marketplace:import-trendyol-orders {--sync : Kuyruk yerine anında çalıştır}';

    protected $description = 'Trendyol siparişlerini mağaza siparişlerine aktarır.';

    public function handle(TrendyolOrderImporter $importer): int
    {
        if ($this->option('sync')) {
            $result = $importer->import();
            $this->info(sprintf(
                'Tamamlandı: %d yeni, %d güncellendi, %d atlandı.',
                $result['imported'],
                $result['updated'],
                $result['skipped'],
            ));

            foreach ($result['errors'] as $error) {
                $this->warn($error);
            }

            return self::SUCCESS;
        }

        ImportTrendyolOrdersJob::dispatch();
        $this->info('Trendyol sipariş import işi kuyruğa eklendi.');

        return self::SUCCESS;
    }
}
