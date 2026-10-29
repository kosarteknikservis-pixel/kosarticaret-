<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiClient;
use Illuminate\Console\Command;

class PrepareHepsiburadaMappingsCommand extends Command
{
    protected $signature = 'marketplace:hb-prepare-mappings
        {--query= : Kategori arama metni (varsayılan: pompa)}
        {--category-id= : HB leaf kategori ID (API aramasını atlar)}';

    protected $description = 'Hepsiburada pompa kategori + marka eşleştirmelerini hazırlar.';

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

    public function handle(HepsiburadaApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'hepsiburada')->first();
        if ($channel === null) {
            $this->error('hepsiburada kanalı yok.');

            return self::FAILURE;
        }

        if (! $channel->isConfigured()) {
            $this->error('Hepsiburada API bilgileri eksik.');

            return self::FAILURE;
        }

        if (! $channel->is_active) {
            $channel->update(['is_active' => true]);
            $this->info('Hepsiburada kanalı aktifleştirildi.');
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
            $this->error('Uygun Hepsiburada leaf kategori bulunamadı. --category-id verin.');

            return self::FAILURE;
        }

        $mapped = $this->mapCategories((int) $category['categoryId'], (string) $category['name']);
        $this->info(sprintf(
            'Kategori eşleştirme: %d yerel kategori → HB #%d (%s)',
            $mapped,
            $category['categoryId'],
            $category['name']
        ));

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
                    'channel_key' => 'hepsiburada',
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
     * @return array{categoryId: int, name: string}|null
     */
    private function resolveCategory(HepsiburadaApiClient $client): ?array
    {
        $forcedId = $this->option('category-id');
        if ($forcedId !== null && $forcedId !== '') {
            return [
                'categoryId' => (int) $forcedId,
                'name' => (string) config('marketplace.hepsiburada.default_pump_category_name', 'Su Motoru'),
            ];
        }

        $configId = config('marketplace.hepsiburada.default_pump_category_id');
        if (filled($configId)) {
            return [
                'categoryId' => (int) $configId,
                'name' => (string) config('marketplace.hepsiburada.default_pump_category_name', 'Su Motoru'),
            ];
        }

        $queries = array_values(array_unique(array_filter([
            (string) ($this->option('query') ?: ''),
            'su motoru',
            'su pompa',
            'dalgıç pompa',
            'pompa',
        ])));

        $preferred = null;
        foreach ($queries as $query) {
            $matches = $client->searchLeafCategories($query, 40);
            foreach ($matches as $match) {
                $hay = mb_strtolower($match['name'].' '.$match['paths']);
                if (str_contains($hay, 'su motor') || str_contains($hay, 'su pompa') || str_contains($hay, 'dalgıç')) {
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
                    'channel_key' => 'hepsiburada',
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
