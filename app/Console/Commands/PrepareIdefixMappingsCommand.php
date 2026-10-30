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
        {--query= : Tek kategori arama (grup eşlemesini atlar)}
        {--category-id= : Tek Idefix leaf ID (tüm yerel slug’lara yazar)}
        {--delivery-duration= : Teslimat süresi (gün) kanala yaz}';

    protected $description = 'Idefix pompa kategori (gruplu) + marka eşleştirmelerini hazırlar.';

    /** @var list<string> */
    private array $brandSlugs = ['sumak', 'kaysu', 'pedrollo', 'winpo', 'troy'];

    /**
     * Yerel kategori slug grupları → Idefix arama / sabit ID.
     *
     * @var array<string, array{query: string, fallback_id: int, slugs: list<string>}>
     */
    private array $categoryGroups = [
        'dalgic' => [
            'query' => 'dalgic pompa',
            'fallback_id' => 530114652,
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
            'fallback_id' => 530173799, // Bahçe Hidrofonları (Idefix leaf)
            'slugs' => [
                'hidroforlar',
                'sumak-hidrofor',
                'ev-tipi-hidroforlar',
                'karavan-hidroforu',
                'hidrofor-sistemleri',
                'hidrofor-grubu',
            ],
        ],
        'jet_preferikal' => [
            'query' => 'su motor',
            'fallback_id' => 530114652,
            'slugs' => [
                'jet-pompalar-derinden-emisli',
                'preferikal-pompalar-surtme-fanli',
                'su-pompalari',
            ],
        ],
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

            $forcedId = (int) ($this->option('category-id') ?: 0);
            $queryOpt = trim((string) ($this->option('query') ?: ''));

            if ($forcedId > 0 || $queryOpt !== '') {
                $category = $this->resolveCategory($client, $queryOpt, $forcedId);
                if ($category === null) {
                    $this->error('Uygun Idefix leaf kategori bulunamadı.');

                    return self::FAILURE;
                }
                $allSlugs = [];
                foreach ($this->categoryGroups as $group) {
                    $allSlugs = array_merge($allSlugs, $group['slugs']);
                }
                $mapped = $this->mapCategorySlugs($allSlugs, (int) $category['id'], (string) $category['name']);
                $this->info(sprintf(
                    'Tek kategori: %d yerel → Idefix #%d (%s)',
                    $mapped,
                    $category['id'],
                    $category['name']
                ));
            } else {
                $leaves = $this->loadLeaves($client);
                foreach ($this->categoryGroups as $key => $group) {
                    $category = $this->pickLeaf($leaves, $group['query'], (int) $group['fallback_id']);
                    if ($category === null) {
                        $this->warn("Grup {$key}: kategori bulunamadı, atlandı.");
                        continue;
                    }
                    $mapped = $this->mapCategorySlugs($group['slugs'], (int) $category['id'], (string) $category['name']);
                    $this->info(sprintf(
                        'Grup %s: %d yerel → Idefix #%d (%s) [%s]',
                        $key,
                        $mapped,
                        $category['id'],
                        $category['name'],
                        $category['path'] ?? ''
                    ));
                }
            }
        } catch (\Throwable $e) {
            $this->error('Hazırlık başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function mapBrands(IdefixApiClient $client): int
    {
        $count = 0;
        $sumakExternal = null;

        foreach ($this->brandSlugs as $slug) {
            $brand = Brand::query()->where('slug', $slug)->first();
            if ($brand === null) {
                $this->warn('Yerel marka yok: '.$slug);
                continue;
            }

            // Troy Idefix’te Sumak olarak gönderilir → troy brand_id → Sumak external ID.
            $searchName = $slug === 'troy' ? 'Sumak' : (string) $brand->name;
            $match = null;
            foreach (array_unique([$searchName, mb_strtoupper($searchName), mb_strtolower($searchName), ucfirst(mb_strtolower($searchName))]) as $variant) {
                try {
                    $row = $client->searchBrandByName($variant);
                } catch (\Throwable) {
                    continue;
                }
                $id = (int) ($row['id'] ?? 0);
                $title = (string) ($row['title'] ?? '');
                if ($id > 0) {
                    $match = ['id' => $id, 'title' => $title !== '' ? $title : $searchName];
                    break;
                }
            }

            if ($match === null) {
                $this->warn('Idefix marka bulunamadı: '.$searchName.' (yerel '.$slug.')');
                continue;
            }

            if ($slug === 'sumak' || $slug === 'troy') {
                $sumakExternal = $match;
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
            $label = $slug === 'troy' ? 'Troy→Sumak' : (string) $brand->name;
            $this->line(sprintf('  %s → #%d (%s)', $label, $match['id'], $match['title']));
            $count++;
        }

        if ($sumakExternal !== null) {
            $this->line(sprintf('  Sumak referans: #%d (%s)', $sumakExternal['id'], $sumakExternal['title']));
        }

        return $count;
    }

    /**
     * @return list<array{id: int, name: string, path: string}>
     */
    private function loadLeaves(IdefixApiClient $client): array
    {
        $tree = $client->getProductCategories();
        $items = $tree['items'] ?? $tree;
        if (! is_array($items)) {
            return [];
        }

        return $this->flattenLeaves($items);
    }

    /**
     * @param  list<array{id: int, name: string, path: string}>  $leaves
     * @return array{id: int, name: string, path?: string}|null
     */
    private function pickLeaf(array $leaves, string $query, int $fallbackId): ?array
    {
        $needle = $this->fold($query);
        $best = null;
        $bestScore = -1;

        foreach ($leaves as $leaf) {
            $hay = $this->fold($leaf['path'].' '.$leaf['name']);
            $nameFold = $this->fold($leaf['name']);
            $score = 0;
            if ($needle !== '' && str_contains($hay, $needle)) {
                $score += 20;
            }
            if ($nameFold === $needle) {
                $score += 15;
            }
            if (str_contains($hay, 'dalgic') && str_contains($needle, 'dalgic')) {
                $score += 25;
            }
            if ((str_contains($hay, 'hidrofor') || str_contains($hay, 'hidrofon')) && str_contains($needle, 'hidrofor')) {
                $score += 25;
            }
            if (str_contains($hay, 'su motor') && str_contains($needle, 'su motor')) {
                $score += 10;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $leaf;
            }
        }

        if ($best !== null && $bestScore >= 15) {
            return $best;
        }

        if ($fallbackId > 0) {
            return [
                'id' => $fallbackId,
                'name' => 'Kategori #'.$fallbackId,
                'path' => 'fallback',
            ];
        }

        return $bestScore >= 5 ? $best : null;
    }

    /**
     * @return array{id: int, name: string, path?: string}|null
     */
    private function resolveCategory(IdefixApiClient $client, string $query, int $forcedId): ?array
    {
        if ($forcedId > 0) {
            $attrs = $client->getCategoryAttributes($forcedId);
            $name = (string) ($attrs['name'] ?? ('Kategori #'.$forcedId));

            return ['id' => $forcedId, 'name' => $name, 'path' => $name];
        }

        $leaves = $this->loadLeaves($client);

        return $this->pickLeaf($leaves, $query, 0);
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

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return strtr($value, [
            'ş' => 's', 'ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c',
            'Ş' => 's', 'İ' => 'i', 'I' => 'i', 'Ğ' => 'g', 'Ü' => 'u', 'Ö' => 'o', 'Ç' => 'c',
        ]);
    }
}
