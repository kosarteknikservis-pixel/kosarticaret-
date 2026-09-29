<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Canlıdaki eski açıklamanın md5'i; panelden değiştirilmişse üzerine yazılmaz. */
    private const EXPECTED_OLD_DESCRIPTION_MD5 = '8e0d9f378661e81b3e9ef08e1a564c3a';

    private const DRENAJ = ['su-pompalari', 'dalgic-pompalar', 'drenaj-dalgic-pompa'];

    private const PASLANMAZ_DRENAJ = ['su-pompalari', 'dalgic-pompalar', 'drenaj-dalgic-pompa', 'paslanmaz-drenaj-dalgic-pompa'];

    private const BICAKLI_FOSEPTIK = ['su-pompalari', 'dalgic-pompalar', 'foseptik-dalgic-pompa', 'bicakli-dalgic-pompa'];

    private const KESON = ['su-pompalari', 'ozel-amacli-pompalar', 'keson-kuyu-pompa'];

    private const YAGMUR_SUYU = ['su-pompalari', 'ozel-amacli-pompalar', 'yagmur-suyu-tahliye-pompasi', 'dalgic-pompalar', 'drenaj-dalgic-pompa'];

    private const KADEMELI = ['su-pompalari', 'kademeli-pompalar'];

    private const DIZEL_MOTOPOMP = ['su-pompalari', 'ozel-amacli-pompalar', 'dizel-su-motorlari'];

    /** [ürün slug'ı, eklenecek kategori slug'ları] (mevcut atamalar korunur). */
    private function categoryMap(): array
    {
        return [
            ['winpo-qdp-400-a-flatorlu-drenaj-dalgic-pompa-7mss-72m3h-monofaze220v', self::DRENAJ],
            ['winpo-qdp-400-aw-flatorlu-drenaj-dalgic-pompa-7mss-81m3h-monofoze220v', self::DRENAJ],
            ['winpo-qdp-550-a-flatorlu-drenaj-dalgic-pompa-10mss-81m3h-monofaze220v', self::DRENAJ],
            ['winpo-qdp-550-aw-flatorlu-drenaj-dalgic-pompa-8mss-81m3h-monofaze220v', self::DRENAJ],
            ['winpo-qdp-750-a-flatorlu-drenaj-dalgic-pompa-10mss-81m3h-monofaze220v', self::DRENAJ],
            ['winpo-qdp-750-aw-flatorlu-drenaj-dalgic-pompa-9mss81m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-15-16-pf-flatorlu-drenaj-dalgic-pompa-05-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-15-25-pf-flatorlu-drenaj-dalgic-pompa-075-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-15-32-flatorlu-drenaj-pompa-33-mss84-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-15-32-pf-flatorlu-drenaj-dalgic-pompa-1-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-10-16-pf-flatorlu-drenaj-dalgic-pompa-1-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-15-18-pf-flatorlu-drenaj-dalgic-pompa-2-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-400-a-gf-gizli-flatorlu-drenaj-dalgic-pompa-8-mss-9-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-400-a-flatorlu-drenaj-dalgic-pompa-6-mss-9-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v', self::DRENAJ],
            ['winpo-wnp-550-a-gf-gizli-flatorlu-drenaj-dalgic-pompa-9-mss-9-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-550-a-flatorlu-drenaj-dalgic-pompa-9-mss-6-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-550-aw-flatorlu-drenaj-dalgic-pompa-8mss-9m3h-monofoze220v', self::DRENAJ],
            ['winpo-wnp-6-26-pf-flatorlu-drenaj-dalgic-pompa-15-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-6-282-flatorludrenaj-pompa-33mss15-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-6-33-pf-flatorlu-drenaj-dalgic-pompa-2-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-6-393-flatorlu-drenaj-pompa-40-mss15-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-7-18-pf-flatorlu-drenaj-dalgic-pompa-1-hp-220-volt', self::DRENAJ],
            ['winpo-wnp-750-a-gf-gizli-flatorlu-drenaj-dalgic-pompa-10-mss-12-m3h-monofaze220v', self::DRENAJ],
            ['winpo-wnp-750-aw-flatorlu-drenaj-dalgic-pompa-9mss-11m3h-monofoze220v', self::DRENAJ],
            ['winpo-wnp-750-aw-gf-gizli-flatorlu-drenaj-dalgic-pompa-9-mss-12-m3h-monofoze220v', self::DRENAJ],

            ['winpo-wnp-750-pd-paslanmaz-govdeli-drenaj-pompa-10-mss-12-m3h-monofaze220v', self::PASLANMAZ_DRENAJ],
            ['winpo-wnp-qck-100m-flatorlu-paslanmaz-drenaj-pompa-15-mss18-m3h-monofaze220v', self::PASLANMAZ_DRENAJ],
            ['winpo-wnp-qck-150m-flatorlu-paslanmaz-drenaj-pompa-18-mss21-m3h-monofaze220v', self::PASLANMAZ_DRENAJ],
            ['winpo-wnp-qck-45m-flatorlu-paslanmaz-drenaj-pompa-8-mss9-m3h-monofaze220v', self::PASLANMAZ_DRENAJ],
            ['winpo-wnp-qck-55m-flatorlu-paslanmaz-drenaj-pompa-11-mss15-m3h-monofaze220v', self::PASLANMAZ_DRENAJ],
            ['winpo-wnp-qck-75m-flatorlu-paslanmaz-drenaj-pompa-13-mss15-m3h-monofaze220v', self::PASLANMAZ_DRENAJ],

            ['winpo-v-1100-d-f-flatorlu-bicakli-foseptik-drenaj-dalgic-pompa-10mss21m3h-monofaze220v', self::BICAKLI_FOSEPTIK],
            ['winpo-wnp-7-12-gr-flatorlu-kiricili-foseptik-drenaj-dalgic-pompa-16mss17m3h-monofaze220v', self::BICAKLI_FOSEPTIK],
            ['winpo-wnp-7-12t-kiricili-foseptik-drenaj-dalgic-pompa-16-mss-15-m3h-trifaze380v', self::BICAKLI_FOSEPTIK],
            ['winpo-wnp-7-16-gr-flatorlu-kiricili-foseptik-drenaj-dalgic-pompa-20-mss20-m3h-monofaze220v', self::BICAKLI_FOSEPTIK],
            ['winpo-wnp-7-16t-kiricili-foseptik-drenaj-dalgic-pompa-20-mss-20-m3h-trifaze380v', self::BICAKLI_FOSEPTIK],
            ['winpo-wnp-7-8-gr-flatorlu-kiricili-foseptik-drenaj-dalgic-pompa-12-mss11-m3h-monofaze220v', self::BICAKLI_FOSEPTIK],
            ['winpo-wnp-9-18t-kiricili-foseptik-drenaj-dalgic-pompa-25-mss-20-m3h-trifaze380v', self::BICAKLI_FOSEPTIK],

            ['winpo-4skm-100-keson-kuyu-dalgic-pompa-55-mss-3-m3h-1-hp-220v', self::KESON],
            ['winpo-4skm-150-keson-kuyu-dalgic-pompa-98-mss-3-m3h-15-hp-220v', self::KESON],

            ['winpo-wnp-v-370-f-yagmur-suyu-tahliye-pompasi-8-mss-150-ltdk', self::YAGMUR_SUYU],
            ['winpo-wnp-v-750-f-yagmur-suyu-tahliye-pompasi-13-mss-180-ltdk', self::YAGMUR_SUYU],

            ['winpo-cmf-2-60m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-1hp-220-volt-58-mss-35-m3h', self::KADEMELI],
            ['winpo-cmf-2-60m-ss6-full-paslanmaz-aisi-316-cok-kademeli-santrifuj-pompa-1hp-220-volt-58-mss-35-m3h', self::KADEMELI],
            ['winpo-cmf-2-60t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-1hp-380-volt-58-mss-35-m3h', self::KADEMELI],
            ['winpo-cmf-2-60t-ss6-full-paslanmaz-aisi-316-cok-kademeli-santrifuj-pompa-1hp-380-volt-58-mss-35-m3h', self::KADEMELI],
            ['winpo-cmf-4-60m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-15hp-220-volt-59-mss-7-m3h', self::KADEMELI],
            ['winpo-cmf-4-60m-ss6-full-paslanmaz-aisi-316-cok-kademeli-santrifuj-pompa-15hp-220-volt-59-mss-7-m3h', self::KADEMELI],
            ['winpo-cmf-4-60t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-15hp-380-volt-59-mss-7-m3h', self::KADEMELI],
            ['winpo-cmf-4-60t-ss6-full-paslanmaz-aisi-316-cok-kademeli-santrifuj-pompa-15hp-220-volt-59-mss-7-m3h', self::KADEMELI],
            ['winpo-cmf-8-25m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-2hp-220-volt-49-mss-10-m3h', self::KADEMELI],
            ['winpo-cmf-8-25t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-2hp-380-volt-49-mss-10-m3h', self::KADEMELI],
            ['winpo-cmf-8-30m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-25hp-220-volt-56-mss-10-m3h', self::KADEMELI],
            ['winpo-cmf-8-30t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-25hp-380-volt-56-mss-10-m3h', self::KADEMELI],
            ['winpo-cmf-8-40t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-3hp-380-volt-71-mss-10-m3h', self::KADEMELI],
            ['winpo-cmi-2-6m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-1hp-220-volt-58-mss-48-m3h', self::KADEMELI],
            ['winpo-cmi-2-6t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-1hp-380-volt-58-mss-48-m3h', self::KADEMELI],
            ['winpo-cmi-2-7m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-135hp-220-volt-67-mss-48-m3h', self::KADEMELI],
            ['winpo-cmi-2-7t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-135-hp-380-volt-58-mss-48-m3h', self::KADEMELI],
            ['winpo-cmi-4-6m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-135hp-220-volt-60-mss-65-m3h', self::KADEMELI],
            ['winpo-cmi-4-6t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-175hp-380-volt-60-mss-65-m3h', self::KADEMELI],
            ['winpo-cmi-4-7m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-2hp-220-volt-72-mss-65-m3h', self::KADEMELI],
            ['winpo-cmi-4-7t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-2hp-380-volt-72-mss-65-m3h', self::KADEMELI],
            ['winpo-cmi-8-25m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-2hp-220-volt-60-mss-65-m3h', self::KADEMELI],
            ['winpo-cmi-8-25t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-2hp-380-volt-49-mss-10-m3h', self::KADEMELI],
            ['winpo-cmi-8-30t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-25hp-380-volt-56-mss-10-m3h', self::KADEMELI],
            ['winpo-cmi-8-40m-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-3hp-220-volt-71-mss-10-m3h', self::KADEMELI],
            ['winpo-cmi-8-40t-full-paslanmaz-aisi-304-cok-kademeli-santrifuj-pompa-3hp-380-volt-71-mss-10-m3h', self::KADEMELI],
        ];
    }

    /** Diğer markalarda [marka slug'ı, ürün slug'ı, eklenecek kategori slug'ları]. */
    private function otherBrandCategoryMap(): array
    {
        return [
            ['sumak', 'sumak-smkt750-d-dizel-motopomp', self::DIZEL_MOTOPOMP],
            ['sumak', 'sumak-dsmt7503-d-dizel-motopomp', self::DIZEL_MOTOPOMP],
            ['sumak', 'sumak-dsm-3002-d-dizel-motopomp', self::DIZEL_MOTOPOMP],
        ];
    }

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'winpo')->first();
        if (! $brand) {
            return;
        }

        if (md5((string) $brand->getRawOriginal('description')) === self::EXPECTED_OLD_DESCRIPTION_MD5) {
            // config:cache bu migration'dan sonra çalıştığı için önbellekteki eski config okunmamalı.
            $seo = (require config_path('brand_seo.php'))['winpo'];

            $brand->forceFill([
                'meta_title' => $seo['meta_title'],
                'meta_description' => $seo['meta_description'],
                'description' => $seo['description'],
                'faq' => $seo['faq'],
            ])->save();
        }

        $categoryIds = Category::query()->pluck('id', 'slug');
        $brandIds = Brand::query()->pluck('id', 'slug');

        $additions = array_merge(
            array_map(fn (array $row) => ['winpo', $row[0], $row[1]], $this->categoryMap()),
            $this->otherBrandCategoryMap(),
        );

        foreach ($additions as [$brandSlug, $productSlug, $categorySlugs]) {
            $product = $this->findProduct($brandIds[$brandSlug] ?? null, $productSlug);
            if (! $product) {
                continue;
            }

            $ids = collect($categorySlugs)->map(fn (string $slug) => $categoryIds[$slug] ?? null)->filter()->values()->all();
            $product->categories()->syncWithoutDetaching($ids);
        }
    }

    private function findProduct(?int $brandId, string $productSlug): ?Product
    {
        if (! $brandId) {
            return null;
        }

        return Product::query()->where('slug', $productSlug)->where('brand_id', $brandId)->first();
    }

    public function down(): void
    {
        // İçerik ve kategori eklemesi; geri alma için yedekten dönülür.
    }
};
