<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use App\Services\Marketplace\ProductListingService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PublishMarketplaceProductCommand extends Command
{
    protected $signature = 'marketplace:publish-product
        {slug : Ürün slug}
        {--channel=trendyol : Kanal anahtarı}
        {--dry-run : API çağrısı yapmadan doğrula ve fiyatı göster}';

    protected $description = 'Ürünü pazaryeri kanalına gönderir (varsayılan Trendyol).';

    public function handle(ProductListingService $listingService, MarketplaceChannelPricing $pricing): int
    {
        $slug = (string) $this->argument('slug');
        $channelKey = (string) $this->option('channel');

        $product = Product::query()->with(['brand', 'categories', 'images'])->where('slug', $slug)->first();
        if ($product === null) {
            $this->error('Ürün bulunamadı: '.$slug);

            return self::FAILURE;
        }

        $channel = MarketplaceChannel::query()->where('key', $channelKey)->first();
        if ($channel === null) {
            $this->error('Kanal bulunamadı: '.$channelKey);

            return self::FAILURE;
        }

        $sale = $pricing->salePrice($product, $channel);
        $breakdown = $channel->setting('price_mode') === 'margin'
            ? $pricing->marginBreakdown($product, $channel)
            : null;

        $this->line('Ürün: '.$product->name.' (#'.$product->id.')');
        $this->line('SKU: '.($product->sku ?: '—').' | Barkod: '.($product->barcode ?: '—'));
        $this->line('Maliyet: '.(($product->cost_price !== null) ? number_format((float) $product->cost_price, 2, ',', '.').' ₺' : 'yok'));
        $this->line('Kanal fiyat modu: '.(string) $channel->setting('price_mode', 'site'));
        $this->line('Gönderilecek satış fiyatı: '.number_format($sale, 2, ',', '.').' ₺');

        if (is_array($breakdown)) {
            $this->line(sprintf(
                'Margin: komisyon %s%% + diğer %s%% + hedef kâr %s%% → tahmini net %s ₺',
                $breakdown['commission_percent'],
                $breakdown['other_fee_percent'],
                $breakdown['target_margin_percent'],
                number_format((float) ($breakdown['estimated_profit'] ?? 0), 2, ',', '.')
            ));
        }

        $validation = $listingService->validateForPublish($product, $channel);
        if (! $validation['ok']) {
            $this->error('Gönderim engellendi:');
            foreach ($validation['reasons'] as $reason) {
                $this->line(' - '.$reason);
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run: doğrulama geçti, API çağrısı yapılmadı.');

            return self::SUCCESS;
        }

        try {
            $result = $listingService->publish($product->id, $channelKey);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->{$result['success'] ? 'info' : 'error'}($result['message']);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
