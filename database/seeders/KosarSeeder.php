<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KosarSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@kosar.com.tr'],
            [
                'name' => 'Kosar Admin',
                'password' => Hash::make(config('kosar.admin_password', 'kosar-dev')),
                'is_admin' => true,
            ],
        );

        $categories = [
            ['key' => 'pompalar', 'slug' => 'pompalar', 'name' => 'Pompalar', 'description' => 'Santrifüj, jet, dalgıç ve endüstriyel pompa çözümleri', 'featured' => true, 'sort' => 1],
            ['key' => 'santrifuj', 'slug' => 'santrifuj-pompalar', 'name' => 'Santrifüj Pompalar', 'parent' => 'pompalar', 'sort' => 1],
            ['key' => 'jet', 'slug' => 'jet-pompalar-derinden-emisli', 'name' => 'Jet Pompalar', 'parent' => 'pompalar', 'sort' => 2],
            ['key' => 'hidroforlar', 'slug' => 'hidroforlar', 'name' => 'Hidroforlar', 'description' => 'Ev tipi, paket ve endüstriyel hidrofor sistemleri', 'featured' => true, 'sort' => 2],
            ['key' => 'ev-tipi-hidrofor', 'slug' => 'ev-tipi-hidroforlar', 'name' => 'Ev Tipi Hidroforlar', 'parent' => 'hidroforlar', 'featured' => true, 'sort' => 1],
            ['key' => 'karavan-hidrofor', 'slug' => '12-ve-24-volt-hidroforlar', 'name' => 'Karavan & Tekne Hidroforları', 'parent' => 'hidroforlar', 'sort' => 2],
            ['key' => 'dalgic', 'slug' => 'dalgic-pompalar', 'name' => 'Dalgıç Pompalar', 'description' => 'Derin kuyu, drenaj, temiz ve kirli su pompaları', 'featured' => true, 'sort' => 3],
            ['key' => 'solar-dc', 'slug' => 'solar-dc-dalgic-pompalar', 'name' => 'Solar DC Dalgıç Pompalar', 'parent' => 'dalgic', 'featured' => true, 'sort' => 1],
            ['key' => 'sirkulasyon', 'slug' => 'sirkulasyon-pompalari', 'name' => 'Sirkülasyon Pompaları', 'featured' => true, 'sort' => 4],
            ['key' => 'fan', 'slug' => 'fan-ve-aspirator', 'name' => 'Fan & Havalandırma', 'description' => 'Sanayi vantilatörü, aspiratör ve kanal tipi fanlar', 'featured' => true, 'sort' => 5],
            ['key' => 'petek-temizleme', 'slug' => 'petek-temizleme-makineleri', 'name' => 'Petek Temizleme Makineleri', 'description' => 'Kosar imalatı kombi radyatör temizleme makineleri', 'featured' => true, 'sort' => 6],
            ['key' => 'ekipman', 'slug' => 'ekipmanlar', 'name' => 'Ekipman & Yedek Parça', 'sort' => 7],
            ['key' => 'termostat', 'slug' => 'oda-termostatlari', 'name' => 'Oda Termostatları', 'sort' => 8],
        ];

        $catMap = [];
        foreach ($categories as $row) {
            $cat = Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'parent_id' => isset($row['parent']) ? ($catMap[$row['parent']] ?? null) : null,
                    'featured' => $row['featured'] ?? false,
                    'show_in_menu' => true,
                    'active' => true,
                    'sort_order' => $row['sort'] ?? 0,
                ],
            );
            $catMap[$row['key']] = $cat->id;
        }

        $brandSlugs = [
            'Seaflo' => 'seaflo', 'Kosar' => 'kosar', 'Welko' => 'welko', 'Püfür' => 'pufur',
            'Leo' => 'leo', 'Pedrollo' => 'pedrollo', 'Grundfos' => 'grundfos', 'Wilo' => 'wilo',
        ];
        $brandMap = [];
        foreach ($brandSlugs as $name => $slug) {
            $i = count($brandMap);
            $brand = Brand::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'featured' => $i < 8, 'active' => true, 'sort_order' => $i],
            );
            $brandMap[$name] = $brand->id;
        }

        $products = [
            ['slug' => 'seaflo-24-volt-2-lt-tankli-otomatik-hidrofor', 'sku' => 'SEA-24V-2L', 'name' => 'Seaflo 24 Volt 2 Lt Tanklı Otomatik Tekne, Karavan Hidroforu', 'brand' => 'Seaflo', 'price' => 4263.36, 'compare' => 4844.72, 'stock' => 12, 'cats' => ['karavan-hidrofor', 'hidroforlar'], 'featured' => true],
            ['slug' => 'seaflo-12-volt-2-lt-tankli-otomatik-hidrofor', 'sku' => 'SEA-12V-2L', 'name' => 'Seaflo 12 Volt 2 Lt Tanklı Otomatik Hidrofor', 'brand' => 'Seaflo', 'price' => 4871.46, 'compare' => 5535.75, 'stock' => 8, 'cats' => ['karavan-hidrofor', 'hidroforlar'], 'featured' => true],
            ['slug' => 'kosar-portatif-tuvalet-20-litre', 'sku' => 'KOS-WC-20', 'name' => 'Kosar Portatif Tuvalet 20 Litre', 'brand' => 'Kosar', 'price' => 2499.99, 'compare' => 4166.65, 'stock' => 25, 'cats' => ['ekipman'], 'featured' => true],
            ['slug' => 'sessiz-hidrofor-daire-ici-sessiz-guclendirici', 'sku' => 'KOS-SESSIZ-25', 'name' => 'SESSİZ Hidrofor Daire İçi Güçlendirici', 'brand' => 'Kosar', 'price' => 4199.99, 'compare' => 4421.04, 'stock' => 40, 'cats' => ['ev-tipi-hidrofor', 'hidroforlar'], 'featured' => true],
            ['slug' => 'welko-lpa-32-12-sirkulasyon-pompasi', 'sku' => 'WEL-LPA32-12', 'name' => 'Welko LPA 32-12 Frekans Konvertörlü Sirkülasyon Pompası', 'brand' => 'Welko', 'price' => 7508.85, 'compare' => 12514.75, 'stock' => 5, 'cats' => ['sirkulasyon']],
            ['slug' => 'pufur-65-cm-sanayi-duvar-tipi-vantilator', 'sku' => 'PUF-65-WALL', 'name' => 'Püfür 65 Cm Sanayi Duvar Tipi Vantilatör', 'brand' => 'Püfür', 'price' => 6500, 'compare' => 10833.33, 'stock' => 15, 'cats' => ['fan'], 'featured' => true],
            ['slug' => 'kosar-v-flow-2-petek-temizleme-makinesi', 'sku' => 'KOS-VF2', 'name' => 'Kosar V-Flow-2 Petek Temizleme Makinesi', 'brand' => 'Kosar', 'price' => 15500, 'compare' => 22142.86, 'stock' => 6, 'cats' => ['petek-temizleme'], 'featured' => true],
            ['slug' => 'kosar-v-flow-1-petek-temizleme-makinesi', 'sku' => 'KOS-VF1', 'name' => 'Kosar V-Flow-1 Petek Temizleme Makinesi', 'brand' => 'Kosar', 'price' => 13500, 'compare' => 19285.71, 'stock' => 8, 'cats' => ['petek-temizleme'], 'featured' => true],
            ['slug' => '24-48-volt-solar-dc-jet-pompa', 'sku' => 'SOL-DC-JET-33', 'name' => '24/48 Volt Solar DC Jet Pompa', 'brand' => 'Kosar', 'price' => 12834, 'compare' => 17112, 'stock' => 4, 'cats' => ['solar-dc', 'dalgic', 'pompalar'], 'featured' => true],
            ['slug' => 'leo-apm37-24lt-ev-tipi-hidrofor', 'sku' => 'LEO-APM37', 'name' => 'Leo Apm37-24Lt Tanklı Ev Tipi Hidrofor', 'brand' => 'Leo', 'price' => 4593.5, 'compare' => 7066.92, 'stock' => 10, 'cats' => ['ev-tipi-hidrofor', 'hidroforlar']],
        ];

        foreach ($products as $row) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'short_description' => $row['name'],
                    'description' => $row['name'],
                    'brand_id' => $brandMap[$row['brand']] ?? null,
                    'price' => $row['price'],
                    'compare_at_price' => $row['compare'] ?? null,
                    'stock' => $row['stock'],
                    'rating' => 4.5,
                    'badges' => $row['stock'] > 0 ? ['stoktan', 'ucretsiz-kargo'] : [],
                    'specs' => [],
                    'tags' => [],
                    'featured' => $row['featured'] ?? false,
                ],
            );
            $ids = collect($row['cats'])->map(fn ($k) => $catMap[$k] ?? null)->filter()->values();
            $product->categories()->sync($ids);
        }

        Coupon::query()->updateOrCreate(
            ['code' => 'KOSAR10'],
            ['percent' => 10, 'min_amount' => 500, 'active' => true],
        );
        Coupon::query()->updateOrCreate(
            ['code' => 'ILKALISVERIS'],
            ['percent' => 5, 'active' => true],
        );
    }
}
