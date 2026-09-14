<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ardonat ısıtıcı markası + ısıtma kategori ağacını oluşturur.
 * SEO metinleri: config/brand_seo.php + CategorySeoFacts + seo:enrich-categories.
 */
final class SeedArdonatHeatingCatalogCommand extends Command
{
    protected $signature = 'catalog:seed-ardonat-heating
                            {--force-meta : Kategori meta / FAQ alanlarinin ustune yazar}';

    protected $description = 'Ardonat markasi ve isitma-sistemleri kategori agacini olusturur (urun yukleme hazirligi).';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $this->seedBrand();
            $this->seedCategories();
        });

        $this->info('Katalog agaci hazir. Sonraki adimlar:');
        $this->line('  php artisan seo:seed-brands --force');
        $this->line('  php artisan seo:enrich-categories --path=isitma-sistemleri --path=isitma-sistemleri/elektrikli-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar/duvar-tipi-dis-mekan-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar/dikey-tower-dis-mekan-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar/dikey-ev-tipi-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar/duvar-tipi-ev-isiticilar --path=isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar/panel-isiticilar --guides --force');

        return self::SUCCESS;
    }

    private function seedBrand(): void
    {
        $brand = Brand::query()->updateOrCreate(
            ['slug' => 'ardonat'],
            [
                'name' => 'Ardonat',
                'active' => true,
                'featured' => true,
                'sort_order' => 6,
            ]
        );

        $this->line($brand->wasRecentlyCreated ? "Marka olusturuldu: ardonat (#{$brand->id})" : "Marka mevcut: ardonat (#{$brand->id})");
    }

    private function seedCategories(): void
    {
        $tree = [
            [
                'slug' => 'isitma-sistemleri',
                'name' => 'Isıtma Sistemleri',
                'show_in_menu' => true,
                'sort_order' => 4,
                'featured' => true,
                'meta_title' => 'Isıtma Sistemleri ve Elektrikli Isıtıcılar | Koşar Ticaret',
                'meta_description' => 'Dış mekân infrared, duvar tipi, dikey tower ve ev tipi elektrikli ısıtıcılar. Ardonat modelleri, teknik seçim ve hızlı kargo. Koşar Ticaret.',
                'faq' => [
                    ['q' => 'Isıtma sistemleri kategorisinde hangi ürünler var?', 'a' => 'Elektrikli infrared (kızılötesi/halojen) dış mekân ısıtıcılar, dikey tower modeller, duvar tipi ev ısıtıcıları ve panel ısıtıcılar yer alır. Kalorifer sirkülasyon pompaları ayrı olarak su pompaları kategorisindedir.'],
                    ['q' => 'Kafe terası için hangi ısıtıcı uygundur?', 'a' => 'Açık veya yarı açık teraslarda duvar tipi dış mekân infrared veya dikey tower modeller tercih edilir. Güç seçimi alan ölçüsü, rüzgâr ve montaj yüksekliğine göre yapılır.'],
                    ['q' => 'Ev tipi ile dış mekân ısıtıcı farkı nedir?', 'a' => 'Dış mekân modeller yağmur/rüzgâr koşullarına ve yüksek güce göre tasarlanır. Ev tipi modeller kapalı oda ve balkon için güvenlik mesafesi, gürültü ve tüketim dengesi önceliklidir.'],
                ],
                'children' => [
                    [
                        'slug' => 'elektrikli-isiticilar',
                        'name' => 'Elektrikli Isıtıcılar',
                        'sort_order' => 0,
                        'meta_title' => 'Elektrikli Isıtıcı Modelleri ve Fiyatları | Koşar Ticaret',
                        'meta_description' => 'Infrared, halojen, duvar tipi ve dikey elektrikli ısıtıcı modelleri. Dış mekân ve ev tipi seçenekler. Ardonat orijinal ürün, teknik destek.',
                        'faq' => [
                            ['q' => 'Infrared ısıtıcı nasıl çalışır?', 'a' => 'Kızılötesi ışınımla önce cisimleri ve insanları ısıtır; tüm odayı havalandırmadan lokal konfor sağlar. Bu nedenle açık ve yarı açık alanlarda avantajlıdır.'],
                            ['q' => 'Elektrikli ısıtıcı çok elektrik yakar mı?', 'a' => 'Tüketim watt ve kullanım süresine bağlıdır. Kademeli / kumandalı modellerde ihtiyaca göre güç düşürülerek tüketim kontrol edilir; lokal ısıtma tüm mekânı ısıtmaktan daha verimli olabilir.'],
                        ],
                        'children' => [
                            [
                                'slug' => 'dis-mekan-isiticilar',
                                'name' => 'Dış Mekân Isıtıcılar',
                                'sort_order' => 0,
                                'meta_title' => 'Dış Mekân Infrared Isıtıcı Modelleri | Kafe & Teras',
                                'meta_description' => 'Kafe, restoran ve teras için dış mekân infrared ısıtıcılar. Duvar tipi ve dikey tower seçenekleri. Ardonat Halogen Black ve Tower serileri.',
                                'faq' => [
                                    ['q' => 'Dış mekân ısıtıcı yağmurda kullanılır mı?', 'a' => 'Modelin kullanım kılavuzundaki dış mekân uygunluğu ve koruma sınıfı esas alınır. Mümkünse saçak altı / yarı açık montaj ve üretici güvenlik mesafeleri uygulanmalıdır.'],
                                    ['q' => 'Kafe için kaç watt gerekir?', 'a' => 'Lokal oturma grubuna göre 2000–4000 W aralığı yaygındır. Geniş veya rüzgârlı teraslarda Twin modeller veya birden fazla cihaz planlanır.'],
                                ],
                                'children' => [
                                    [
                                        'slug' => 'duvar-tipi-dis-mekan-isiticilar',
                                        'name' => 'Duvar Tipi Dış Mekân Isıtıcılar',
                                        'sort_order' => 0,
                                        'meta_title' => 'Duvar Tipi Dış Mekân Isıtıcı | Halogen Black',
                                        'meta_description' => 'Duvar tipi dış mekân infrared ısıtıcılar. Ardonat Halogen Black, Pro, Plus ve Twin serileri. Kafe ve restoran teras montajı.',
                                    ],
                                    [
                                        'slug' => 'dikey-tower-dis-mekan-isiticilar',
                                        'name' => 'Dikey Tower Dış Mekân Isıtıcılar',
                                        'sort_order' => 1,
                                        'meta_title' => 'Dikey Tower Dış Mekân Isıtıcı | Halogen Tower',
                                        'meta_description' => 'Dikey tower dış mekân ve yarı açık alan ısıtıcıları. Ardonat Halogen Tower ve Twin modeller. Veranda, bahçe ve kafe kullanımı.',
                                    ],
                                ],
                            ],
                            [
                                'slug' => 'ev-tipi-isiticilar',
                                'name' => 'Ev Tipi Isıtıcılar',
                                'sort_order' => 1,
                                'meta_title' => 'Ev Tipi Elektrikli Isıtıcı Modelleri | Dikey & Panel',
                                'meta_description' => 'Ev tipi dikey tower, duvar tipi ve panel elektrikli ısıtıcılar. Oda, balkon ve ofis için hızlı infrared / panel ısıtma. Ardonat modelleri.',
                                'faq' => [
                                    ['q' => 'Dikey ev tipi ısıtıcı hangi odalar için uygundur?', 'a' => 'Salon, çalışma odası, kapalı balkon ve ev ofisi gibi lokal ısı ihtiyacı olan alanlarda tercih edilir. Güç seçimi oda m² ve tavan yüksekliğine göre yapılır.'],
                                    ['q' => 'Panel ısıtıcı ile infrared farkı nedir?', 'a' => 'Infrared anlık lokal ısı verir; panel daha homojen ve sessiz iç mekân ısınması sunar. Açık teras için panel uygun değildir.'],
                                ],
                                'children' => [
                                    [
                                        'slug' => 'dikey-ev-tipi-isiticilar',
                                        'name' => 'Dikey Ev Tipi Isıtıcılar',
                                        'sort_order' => 0,
                                        'meta_title' => 'Dikey Ev Tipi Isıtıcı | Tower Infrared',
                                        'meta_description' => 'Dikey ev tipi infrared ısıtıcılar. Oda ve kapalı balkon için tower modeller. Kumandalı kademe seçenekleri. Ardonat Tower serisi.',
                                    ],
                                    [
                                        'slug' => 'duvar-tipi-ev-isiticilar',
                                        'name' => 'Duvar Tipi Ev Isıtıcıları',
                                        'sort_order' => 1,
                                        'meta_title' => 'Duvar Tipi Ev Isıtıcısı | İç Mekân Infrared',
                                        'meta_description' => 'Duvar tipi ev ısıtıcıları. İç mekân sabit montaj, yer tasarrufu. Ardonat Step Carbon ve uygun duvar tipi modeller.',
                                    ],
                                    [
                                        'slug' => 'panel-isiticilar',
                                        'name' => 'Panel Isıtıcılar',
                                        'sort_order' => 2,
                                        'meta_title' => 'Panel Isıtıcı Modelleri ve Fiyatları | İç Mekân',
                                        'meta_description' => 'Panel ısıtıcı modelleri: sessiz ve homojen iç mekân ısıtma. 1000–2500 W seçenekler. Ardonat panel serisi, Koşar Ticaret.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($tree as $node) {
            $this->upsertCategoryNode($node, null);
        }

        $this->applyMetaFromJson();
    }

    private function applyMetaFromJson(): void
    {
        $path = database_path('data/ardonat_heating_meta.json');
        if (! is_file($path)) {
            $this->warn('Meta JSON yok: database/data/ardonat_heating_meta.json');

            return;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return;
        }

        // BOM temizle
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $slug => $meta) {
            $category = Category::query()->where('slug', $slug)->first();
            if ($category === null) {
                continue;
            }
            if (! empty($meta['name'])) {
                $category->name = $meta['name'];
            }
            if (! empty($meta['meta_title'])) {
                $category->meta_title = $meta['meta_title'];
            }
            if (! empty($meta['meta_description'])) {
                $category->meta_description = $meta['meta_description'];
            }
            $category->save();
            $this->line("Meta JSON uygulandi: {$slug}");
        }
    }

    /**
     * @param  array{
     *     slug: string,
     *     name: string,
     *     sort_order?: int,
     *     show_in_menu?: bool,
     *     featured?: bool,
     *     meta_title?: string,
     *     meta_description?: string,
     *     faq?: list<array{q: string, a: string}>,
     *     children?: list<array<string, mixed>>
     * }  $node
     */
    private function upsertCategoryNode(array $node, ?int $parentId): Category
    {
        $payload = [
            'name' => $node['name'],
            'parent_id' => $parentId,
            'active' => true,
            'show_in_menu' => (bool) ($node['show_in_menu'] ?? false),
            'featured' => (bool) ($node['featured'] ?? false),
            'sort_order' => (int) ($node['sort_order'] ?? 0),
        ];

        $existing = Category::query()->where('slug', $node['slug'])->first();
        if ($existing !== null) {
            $existing->fill($payload);
            if ($this->option('force-meta') || blank($existing->meta_title)) {
                if (! empty($node['meta_title'])) {
                    $existing->meta_title = $node['meta_title'];
                }
            }
            if ($this->option('force-meta') || blank($existing->meta_description)) {
                if (! empty($node['meta_description'])) {
                    $existing->meta_description = $node['meta_description'];
                }
            }
            if (($this->option('force-meta') || blank($existing->faq)) && ! empty($node['faq'])) {
                $existing->faq = $node['faq'];
            }
            $existing->save();
            $category = $existing;
            $this->line("Kategori guncellendi: {$category->nestedSlugPath()}");
        } else {
            $payload['slug'] = $node['slug'];
            if (! empty($node['meta_title'])) {
                $payload['meta_title'] = $node['meta_title'];
            }
            if (! empty($node['meta_description'])) {
                $payload['meta_description'] = $node['meta_description'];
            }
            if (! empty($node['faq'])) {
                $payload['faq'] = $node['faq'];
            }
            $category = Category::query()->create($payload);
            $this->line("Kategori olusturuldu: {$category->nestedSlugPath()}");
        }

        foreach ($node['children'] ?? [] as $child) {
            $this->upsertCategoryNode($child, $category->id);
        }

        return $category;
    }
}
