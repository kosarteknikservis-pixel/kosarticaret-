<?php

namespace App\Jobs\Marketplace;

use App\Services\Marketplace\ProductListingService;
use InvalidArgumentException;

class PublishTrendyolListingJob extends MarketplaceJob
{
    public function __construct(
        public int $productId,
        public string $channelKey = 'trendyol',
    ) {}

    public function handle(ProductListingService $listingService): void
    {
        try {
            $listingService->publish($this->productId, $this->channelKey);
        } catch (InvalidArgumentException) {
            // Validation errors are persisted on listing by service when applicable.
        }
    }
}
