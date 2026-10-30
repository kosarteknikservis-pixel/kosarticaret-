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
        {--delivery-duration= : Teslimat süresi (gün) kanala yaz}';

    protected $description = 'Idefix pompa kategori (sabit leaf ID) + marka eşleştirmelerini hazırlar.';

    /** @var list<string> */
    private array $brandSlugs = ['sumak', 'kaysu', 'pedrollo', 'winpo', 'troy'];

    /**
     * Canlı Idefix leaf’ler (marketplace:idefix-dump-categories 2026-10-30).
     * NOT: #530173799 “Bahçe Hidrofonları” hidrofor değildir — kullanılmaz.
     *
     * @var array<string, array{id: int, name: string, slugs: list<string>}>
     */
    private array $categoryGroups = [
        'dalgic' => [
            'id' => 530114652,
            'name' => 'Dalgıç Pompa',
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
            // Teknoloji > Isıtma > Hidroforlar (paket hidrofor)
            'id' => 231078808,
            'name' => 'Hidroforlar',
            'slugs' => [
                'hidroforlar',
                'sumak-hidrofor',
                'ev-tipi-hidroforlar',
                'hidrofor-sistemleri',
                'hidrofor-grubu',
            ],
        ],
        'karavan' => [
            // Spor > Tekne Hidrofor (12V karavan)
            'id' => 771397498,
            'name' => 'Tekne Hidrofor',
            'slugs' => [
                'karavan-hidroforu',
            ],
        ],
        'santrifuj' => [
            'id' => 530141372,
            'name' => 'Santrifüj Pompalar',
            'slugs' => [
                'preferikal-pompalar-surtme-fanli',
                'santrifuj-pompalar',
            ],
        ],
        'su_motoru_jet' => [
            // Yüzey / jet / genel su motoru
            'id' => 530110498,
            'name' => 'Su Motorları',
            'slugs' => [
                'jet-pompalar-derinden-emisli',
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
            $this->error('Idefix API bilgileri eksik (seller_id / api_key / api_secret).');

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

            foreach ($this->categoryGroups as $key => $group) {
                $id = (int) $group['id'];
                $name = (string) $group['name'];

                // Leaf doğrula (yanlış ID erken patlar)
                try {
                    $attrs = $client->getCategoryAttributes($id);
                    $apiName = (string) ($attrs['name'] ?? $name);
                    if ($apiName !== '') {
                        $name = $apiName;
                    }
                } catch (\Throwable $e) {
                    $this->error("Grup {$key}: kategori #{$id} attributes alınamadı — ".$e->getMessage());

                    return self::FAILURE;
                }

                $mapped = $this->mapCategorySlugs($group['slugs'], $id, $name);
                $this->info(sprintf(
                    'Grup %s: %d yerel → Idefix #%d (%s)',
                    $key,
                    $mapped,
                    $id,
                    $name
                ));
            }
        } catch (\Throwable $e) {
            $this->error('Hazırlık başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->warn('Not: Bahçe Hidrofonları (#530173799) bilinçli olarak kullanılmıyor (hidrofor değil).');

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
                $this->warn('Idefix marka bulunamadı: '.$searchName);
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
            $label = $slug === 'troy' ? 'Troy→Sumak' : (string) $brand->name;
            $this->line(sprintf('  %s → #%d (%s)', $label, $match['id'], $match['title']));
            $count++;
        }

        return $count;
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
}
