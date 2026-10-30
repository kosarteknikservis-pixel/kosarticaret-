<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\PttAvm\PttAvmApiClient;
use Illuminate\Console\Command;

class PreparePttAvmMappingsCommand extends Command
{
    protected $signature = 'marketplace:pttavm-prepare-mappings
        {--delivery-days=2 : Tahmini kargo hazırlık günü}
        {--category-id= : Tek PTT leaf ID (tüm pompa slug’larına yazar)}';

    protected $description = 'PTT AVM pompa kategori + marka eşleştirmelerini hazırlar.';

    /** @var list<string> */
    private array $brandSlugs = ['sumak', 'kaysu', 'pedrollo', 'winpo', 'troy'];

    /**
     * Yerel slug grupları. ID’ler dump sonrası sabitlenir; yoksa API araması.
     *
     * @var array<string, array{query: string, fallback_id: int, slugs: list<string>}>
     */
    private array $categoryGroups = [
        'dalgic' => [
            'query' => 'dalgıç',
            'fallback_id' => 0,
            'slugs' => [
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
                'foseptik-tahliye-cihazi',
            ],
        ],
        'hidrofor' => [
            'query' => 'hidrofor',
            'fallback_id' => 0,
            'slugs' => [
                'hidroforlar',
                'sumak-hidrofor',
                'ev-tipi-hidroforlar',
                'hidrofor-sistemleri',
                'hidrofor-grubu',
                'karavan-hidroforu',
            ],
        ],
        'santrifuj' => [
            'query' => 'santrifüj',
            'fallback_id' => 0,
            'slugs' => [
                'preferikal-pompalar-surtme-fanli',
                'santrifuj-pompalar',
            ],
        ],
        'jet' => [
            'query' => 'jet pompa',
            'fallback_id' => 0,
            'slugs' => [
                'jet-pompalar-derinden-emisli',
                'su-pompalari',
            ],
        ],
    ];

    public function handle(PttAvmApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'pttavm')->first();
        if ($channel === null) {
            $this->error('pttavm kanalı yok. Önce migrate çalıştırın.');

            return self::FAILURE;
        }

        if (! $channel->isConfigured()) {
            $this->error('PTT AVM API bilgileri eksik (api_key / access_token). Panelden girin.');

            return self::FAILURE;
        }

        if (! $channel->is_active) {
            $channel->update(['is_active' => true]);
            $this->info('PTT AVM kanalı aktifleştirildi.');
        }

        $delivery = max(0, min(30, (int) $this->option('delivery-days')));
        $settings = $channel->settings ?? [];
        $settings['estimated_courier_delivery'] = $delivery;
        $channel->update(['settings' => $settings]);
        $this->info('Teslimat hazırlık günü: '.$delivery);

        $forcedId = (int) ($this->option('category-id') ?: 0);

        try {
            $client = $apiClient->forChannel($channel);
            $brandCount = $this->mapBrands();
            $this->info("Marka eşleştirme: {$brandCount} kayıt.");

            if ($forcedId > 0) {
                $name = 'PTT #'.$forcedId;
                try {
                    $info = $client->getCategory($forcedId);
                    $name = (string) (data_get($info, 'category.name') ?: data_get($info, 'name') ?: $name);
                } catch (\Throwable) {
                }
                $slugs = [];
                foreach ($this->categoryGroups as $group) {
                    $slugs = array_merge($slugs, $group['slugs']);
                }
                $mapped = $this->mapCategorySlugs(array_values(array_unique($slugs)), $forcedId, $name);
                $this->info("Tek kategori #{$forcedId} ({$name}): {$mapped} yerel slug.");

                return self::SUCCESS;
            }

            foreach ($this->categoryGroups as $key => $group) {
                $leaf = $this->pickLeaf($client, $group['query'], (int) $group['fallback_id']);
                if ($leaf === null) {
                    $this->error("Grup {$key}: uygun leaf bulunamadı (query={$group['query']}). --category-id kullanın.");

                    return self::FAILURE;
                }
                $mapped = $this->mapCategorySlugs($group['slugs'], $leaf['id'], $leaf['name']);
                $this->info(sprintf(
                    'Grup %s: %d yerel → PTT #%d (%s) [%s]',
                    $key,
                    $mapped,
                    $leaf['id'],
                    $leaf['name'],
                    $leaf['path']
                ));
            }
        } catch (\Throwable $e) {
            $this->error('Hazırlık başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function mapBrands(): int
    {
        $count = 0;
        foreach ($this->brandSlugs as $slug) {
            $brand = Brand::query()->where('slug', $slug)->first();
            if ($brand === null) {
                $this->warn('Yerel marka yok: '.$slug);
                continue;
            }
            $name = $slug === 'troy' ? 'Sumak' : (string) $brand->name;
            MarketplaceBrandMapping::query()->updateOrCreate(
                ['channel_key' => 'pttavm', 'brand_id' => $brand->id],
                ['external_brand_id' => $name, 'external_brand_name' => $name],
            );
            $this->line(sprintf('  %s → %s', $slug === 'troy' ? 'Troy→Sumak' : $brand->name, $name));
            $count++;
        }

        return $count;
    }

    /**
     * @return array{id: int, name: string, path: string}|null
     */
    private function pickLeaf(PttAvmApiClient $client, string $query, int $fallbackId): ?array
    {
        $leaves = $client->searchLeafCategories($query, 40);
        $best = null;
        $needle = $this->fold($query);
        foreach ($leaves as $leaf) {
            $nameFold = $this->fold($leaf['name']);
            $pathFold = $this->fold($leaf['path']);
            if ($nameFold === $needle || str_contains($nameFold, $needle)) {
                $best = $leaf;
                break;
            }
            if ($best === null && str_contains($pathFold, $needle)) {
                $best = $leaf;
            }
        }
        if ($best !== null) {
            return $best;
        }
        if ($fallbackId > 0) {
            return ['id' => $fallbackId, 'name' => 'PTT #'.$fallbackId, 'path' => 'fallback'];
        }

        return $leaves[0] ?? null;
    }

    /**
     * @param  list<string>  $slugs
     */
    private function mapCategorySlugs(array $slugs, int $externalId, string $externalName): int
    {
        $count = 0;
        foreach ($slugs as $slug) {
            $category = Category::query()->where('slug', $slug)->first();
            if ($category === null) {
                continue;
            }
            MarketplaceCategoryMapping::query()->updateOrCreate(
                ['channel_key' => 'pttavm', 'category_id' => $category->id],
                [
                    'external_category_id' => (string) $externalId,
                    'external_category_name' => $externalName,
                ],
            );
            $count++;
        }

        return $count;
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $map = ['ı' => 'i', 'İ' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c'];

        return strtr($value, $map);
    }
}
