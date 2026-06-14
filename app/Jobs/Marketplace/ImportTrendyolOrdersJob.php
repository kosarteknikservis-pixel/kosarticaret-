<?php

namespace App\Jobs\Marketplace;

use App\Services\Marketplace\Trendyol\TrendyolOrderImporter;

class ImportTrendyolOrdersJob extends MarketplaceJob
{
    public function handle(TrendyolOrderImporter $importer): void
    {
        $importer->import();
    }
}
