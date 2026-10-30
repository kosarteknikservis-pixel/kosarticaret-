<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\Idefix\IdefixApiClient;
use Illuminate\Console\Command;

class PrepareIdefixMappingsCommand extends Command
{
    protected $signature = 'marketplace:idefix-prepare-mappings
        {--query= : Kategori arama metni}
        {--category-id= : Idefix leaf kategori ID}
        {--delivery-duration= : Teslimat süresi (gün) kanala yaz}';

    protected $description = 'Idefix pompa kategori + marka eşleştirmelerini hazırlar.';

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

    public function handle(IdefixApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'idefix')->first();
        if ($channel === null) {
            $this->error('idefix kanalı yok.');

            return self::FAILURE;
        }

        if (! $channel->isConfigured()) {
            $this->error('Idefix API bilgileri eksik (seller_id / api_key / api_secret). Panelden canlı bilgileri kaydedin.');

            return self::FAILURE;
        }

        if (! $channel->is_active) {
            $channel->update(['is_active' => true]);
            $this->info('Idefix kanalı aktifleştirildi.');
        }

        $delivery = $this->option('delivery-duration');
        if ($delivery !== null && $delivery !== '') {
            $settings = $channel->settings ?? [];
            $settings['delivery_duration'] = max(1, (int) $delivery);
            $channel->update(['settings' => $settings]);
            $this->info('Teslimat süresi kaydedildi: '.$settings['delivery_duration'].' gün');
        }

        try {
            $client = $apiClient->forChannel($channel);
            $brandCount = $this->mapBrands($client);
            $this->info("Marka eşleştirme: {$brandCount} kayıt.");

            $category = $this->resolveCategory($client);
        } catch (\Throwable $e) {
            $this->error('Hazırlık başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($category === null) {
            $this->error('Uygun Idefix leaf kategori bulunamadı. --category-id verin.');

            return self::FAILURE;
        }

        $mapped = $this->mapCategories((int) $category['id'], (string) $category['name']);
        $this->info(sprintf(
            'Kategori eşleştirme: %d yerel kategori → Idefix #%d (%s) [%s]',
            $mapped,
            $category['id'],
            $category['name'],
            $category['path'] ?? ''
        ));

        return self::SUCCESS;
    }

    private function mapBrands(IdefixApiClient $client): int
    {
        $count = 0;
        foreach ($this->brandSlugs as $slug) {
            $brand = Brand::query()->where('slug', $slug)->first();
            if ($brand === null) {
                $this->warn('Yerel marka yok: '.$slug);
                continue;
            }

            $name = (string) $brand->name;
            $match = null;
            foreach (array_unique([$name, mb_strtoupper($name), mb_strtolower($name), ucfirst(mb_strtolower($name))]) as $variant) {
                try {
                    $row = $client->searchBrandByName($variant);
                } catch (\Throwable) {
                    continue;
                }
                $id = (int) ($row['id'] ?? 0);
                $title = (string) ($row['title'] ?? '');
                if ($id > 0) {
                    $match = ['id' => $id, 'title' => $title !== '' ? $title : $name];
                    break;
                }
            }

            if ($match === null) {
                $this->warn('Idefix marka bulunamadı: '.$name);
                continue;
            }

            MarketplaceBrandMapping::query()->updateOrCreate(
                [
                    'channel_key' => 'idefix',
                    'brand_id' => $brand->id,
                ],
                [
                    'external_brand_id' => (string) $match['id'],
                    'external_brand_name' => $match['title'],
                ],
            );
            $this->line(sprintf('  %s → #%d (%s)', $name, $match['id'], $match['title']));
            $count++;
        }

        return $count;
    }

    /**
     * @return array{id: int, name: string, path?: string}|null
     */
    private function resolveCategory(IdefixApiClient $client): ?array
    {
        $forcedId = (int) ($this->option('category-id') ?: 0);
        if ($forcedId > 0) {
            $attrs = $client->getCategoryAttributes($forcedId);
            $name = (string) ($attrs['name'] ?? ('Kategori #'.$forcedId));

            return ['id' => $forcedId, 'name' => $name, 'path' => $name];
        }

        $tree = $client->getProductCategories();
        $items = $tree['items'] ?? $tree;
        if (! is_array($items)) {
            return null;
        }

        $leaves = $this->flattenLeaves($items);
        $query = mb_strtolower(trim((string) ($this->option('query') ?: '')), 'UTF-8');
        $needles = $query !== ''
            ? [$query]
            : [
                'su motorları ve pompalar',
                'su motoru',
                'su pompasi',
                'su pompası',
                'dalgıç pompa',
                'dalgic pompa',
                'hidrofor',
            ];

        $best = null;
        $bestScore = -1;
        foreach ($leaves as $leaf) {
            $hay = mb_strtolower($leaf['path'].' '.$leaf['name'], 'UTF-8');
            $score = 0;
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($hay, $needle)) {
                    $score += 10;
                    if (mb_strtolower($leaf['name'], 'UTF-8') === $needle) {
                        $score += 8;
                    }
                }
            }
            // Genel su motoru leaf'i dalgıç/hidrofor altından daha iyi (çok tip ürün).
            if (preg_match('/su motor/u', $hay) && ! preg_match('/dalg[ıi]ç|hidrofor|jet|santrif/u', $hay)) {
                $score += 25;
            }
            if (str_contains($hay, 'dalgıç pompa') || str_contains($hay, 'dalgic pompa')) {
                $score += 12;
            }
            if (str_contains($hay, 'pompa')) {
                $score += 3;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $leaf;
            }
        }

        if ($best === null || $bestScore < 5) {
            $this->warn('Otomatik kategori skoru düşük. --query veya --category-id kullanın.');
            if ($best !== null) {
                $this->line('Aday: #'.$best['id'].' '.$best['path']);
            }

            return $bestScore >= 5 ? $best : null;
        }

        return $best;
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $nodes
     * @return list<array{id: int, name: string, path: string}>
     */
    private function flattenLeaves(array $nodes, string $prefix = ''): array
    {
        $out = [];
        $list = array_is_list($nodes) ? $nodes : (isset($nodes[0]) ? $nodes : [$nodes]);

        foreach ($list as $node) {
            if (! is_array($node)) {
                continue;
            }
            // Bazı yanıtlar tek kök object + items sarmalı olabilir.
            if (isset($node['items']) && is_array($node['items']) && ! isset($node['id'])) {
                $out = array_merge($out, $this->flattenLeaves($node['items'], $prefix));
                continue;
            }

            $id = (int) ($node['id'] ?? 0);
            $name = trim((string) ($node['name'] ?? ''));
            $path = $prefix === '' ? $name : ($prefix.' > '.$name);
            $subs = $node['subs'] ?? [];
            if (! is_array($subs) || $subs === []) {
                if ($id > 0 && $name !== '') {
                    $out[] = ['id' => $id, 'name' => $name, 'path' => $path];
                }
                continue;
            }
            $out = array_merge($out, $this->flattenLeaves($subs, $path));
        }

        return $out;
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
                    'channel_key' => 'idefix',
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
