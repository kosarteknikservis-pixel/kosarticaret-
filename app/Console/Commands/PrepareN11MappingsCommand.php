<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\N11\N11ApiClient;
use Illuminate\Console\Command;

class PrepareN11MappingsCommand extends Command
{
    protected $signature = 'marketplace:n11-prepare-mappings
        {--query= : Kategori arama}
        {--category-id= : N11 leaf kategori ID}
        {--shipment-template= : Kargo şablon adını kanala yaz}';

    protected $description = 'N11 pompa kategori + marka eşleştirmelerini hazırlar.';

    /** @var list<string> */
    private array $brandSlugs = ['sumak', 'kaysu', 'pedrollo', 'winpo', 'troy'];

    /** @var list<string> */
    private array $categorySlugs = [
        'dalgic-pompalar',
        'drenaj-dalgic-pompa',
        'kirli-su-dalgic-pompa',
        'foseptik-dalgic-pompa',
        'bicakli-dalgic-pompa',
        'temiz-su-dalgic-pompasi',
        'paslanmaz-drenaj-dalgic-pompa',
        'derin-kuyu-dalgic-pompa',
        'sintine-pompasi',
        'keson-kuyu-pompa',
        'hidroforlar',
        'sumak-hidrofor',
        'ev-tipi-hidroforlar',
        'karavan-hidroforu',
        'jet-pompalar-derinden-emisli',
        'preferikal-pompalar-surtme-fanli',
        'su-pompalari',
        'foseptik-tahliye-cihazi',
    ];

    public function handle(N11ApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'n11')->first();
        if ($channel === null) {
            $this->error('n11 kanalı yok.');

            return self::FAILURE;
        }

        if (! $channel->isConfigured()) {
            $this->error('N11 API bilgileri eksik (app_key / app_secret).');

            return self::FAILURE;
        }

        if (! $channel->is_active) {
            $channel->update(['is_active' => true]);
            $this->info('N11 kanalı aktifleştirildi.');
        }

        $shipment = trim((string) $this->option('shipment-template'));
        if ($shipment !== '') {
            $settings = $channel->settings ?? [];
            $settings['shipment_template'] = $shipment;
            $channel->update(['settings' => $settings]);
            $this->info('Kargo şablonu kaydedildi: '.$shipment);
        }

        $brandCount = $this->mapBrands();
        $this->info("Marka eşleştirme: {$brandCount} kayıt.");

        try {
            $client = $apiClient->forChannel($channel);
            $category = $this->resolveCategory($client);
        } catch (\Throwable $e) {
            $this->error('Kategori çözülemedi: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($category === null) {
            $this->error('Uygun N11 leaf kategori bulunamadı. --category-id verin.');

            return self::FAILURE;
        }

        $mapped = $this->mapCategories((int) $category['id'], (string) $category['name']);
        $this->info(sprintf(
            'Kategori eşleştirme: %d yerel kategori → N11 #%d (%s) [%s]',
            $mapped,
            $category['id'],
            $category['name'],
            $category['path'] ?? ''
        ));

        $template = (string) $channel->fresh()->setting('shipment_template', '');
        if ($template === '') {
            $this->warn('Uyarı: shipment_template boş. Ürün gönderimi için panelden kargo şablon adını girin veya --shipment-template kullanın.');
        } else {
            $this->info('Kargo şablonu: '.$template);
        }

        return self::SUCCESS;
    }

    private function mapBrands(): int
    {
        $count = 0;
        foreach ($this->brandSlugs as $slug) {
            $brand = Brand::query()->where('slug', $slug)->first();
            if ($brand === null) {
                continue;
            }

            MarketplaceBrandMapping::query()->updateOrCreate(
                [
                    'channel_key' => 'n11',
                    'brand_id' => $brand->id,
                ],
                [
                    'external_brand_id' => (string) $brand->name,
                    'external_brand_name' => (string) $brand->name,
                ],
            );
            $count++;
        }

        return $count;
    }

    /**
     * @return array{id: int, name: string, path?: string}|null
     */
    private function resolveCategory(N11ApiClient $client): ?array
    {
        $forcedId = $this->option('category-id');
        if ($forcedId !== null && $forcedId !== '') {
            return [
                'id' => (int) $forcedId,
                'name' => (string) config('marketplace.n11.default_pump_category_name', 'Su Motoru ve Pompa'),
                'path' => '',
            ];
        }

        $configId = config('marketplace.n11.default_pump_category_id');
        if (filled($configId)) {
            return [
                'id' => (int) $configId,
                'name' => (string) config('marketplace.n11.default_pump_category_name', 'Su Motoru ve Pompa'),
                'path' => '',
            ];
        }

        $queries = array_values(array_unique(array_filter([
            (string) ($this->option('query') ?: ''),
            'su motoru',
            'su pompa',
            'dalgıç pompa',
            'hidrofor',
            'pompa',
        ])));

        $preferred = null;
        foreach ($queries as $query) {
            $matches = $client->searchLeafCategories($query, 40);
            foreach ($matches as $match) {
                $hay = mb_strtolower($match['name'].' '.($match['path'] ?? ''));
                if (
                    str_contains($hay, 'su motor')
                    || str_contains($hay, 'su pompa')
                    || str_contains($hay, 'dalgıç')
                    || str_contains($hay, 'hidrofor')
                ) {
                    return $match;
                }
                $preferred ??= $match;
            }
        }

        return $preferred;
    }

    private function mapCategories(int $externalId, string $externalName): int
    {
        $count = 0;
        foreach ($this->categorySlugs as $slug) {
            $category = Category::query()->where('slug', $slug)->first();
            if ($category === null) {
                continue;
            }

            MarketplaceCategoryMapping::query()->updateOrCreate(
                [
                    'channel_key' => 'n11',
                    'category_id' => $category->id,
                ],
                [
                    'external_category_id' => (string) $externalId,
                    'external_category_name' => $externalName,
                ],
            );
            $count++;
        }

        return $count;
    }
}
