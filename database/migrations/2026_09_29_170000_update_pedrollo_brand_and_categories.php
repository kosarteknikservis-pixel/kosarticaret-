<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Canlıdaki eski açıklamanın md5'i; panelden değiştirilmişse üzerine yazılmaz. */
    private const EXPECTED_OLD_DESCRIPTION_MD5 = '465120f7b0c7ecdd8da006a2a4e78bd4';

    private const DRENAJ = ['su-pompalari', 'dalgic-pompalar', 'drenaj-dalgic-pompa'];

    private const PASLANMAZ_DRENAJ = ['su-pompalari', 'dalgic-pompalar', 'drenaj-dalgic-pompa', 'paslanmaz-drenaj-dalgic-pompa'];

    private const FOSEPTIK = ['su-pompalari', 'dalgic-pompalar', 'foseptik-dalgic-pompa'];

    private const KESON = ['su-pompalari', 'ozel-amacli-pompalar', 'keson-kuyu-pompa'];

    private const TEMIZ_SU = ['su-pompalari', 'dalgic-pompalar', 'temiz-su-dalgic-pompasi'];

    private const YATAY_KADEMELI = ['su-pompalari', 'kademeli-pompalar', 'yatay-kademeli-pompalar'];

    private const EV_TIPI_HIDROFOR = ['hidrofor-sistemleri', 'hidroforlar', 'ev-tipi-hidroforlar'];

    /** [ürün slug'ı, eklenecek kategori slug'ları] (mevcut atamalar korunur). */
    private function categoryMap(): array
    {
        return [
            ['pedrollo-rx-540-full-paslanmaz-drenaj-dalgic-pompalar-trifaze-380v-13-mss-228-m3h', self::DRENAJ],
            ['pedrollo-rxm-320-gm-vortex-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-rxm-4-gm-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-rxm-440-flatorlu-full-paslanmaz-drenaj-dalgic-pompalar-monofaze-220v-10-mss-162-m3h', self::DRENAJ],
            ['pedrollo-rxm-440-gm-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompalar-monofaze-220v-10-mss-162-m3h', self::DRENAJ],
            ['pedrollo-rxm-5-gm-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-rxm-540-flatorlu-full-paslanmaz-drenaj-dalgic-pompalar-monofaze-220v-13-mss-228-m3h', self::DRENAJ],
            ['pedrollo-top-2-gm-plastik-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-top-2-vortex-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-7-mss-108-m3h', self::DRENAJ],
            ['pedrollo-top-2-vortex-gmflatorlu-plastik-govdeli-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-top-3-gm-gizli-flatorlu-plastik-govdeli-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-top-3-vortex-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-7-mss-108-m3h', self::DRENAJ],
            ['pedrollo-top-3-vortex-gm-flatorlu-plastik-govdeli-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-top-4-gm-gizli-flatorlu-plastik-govdeli-drenaj-dalgic-pompa', self::DRENAJ],
            ['pedrollo-top-5-gm-gizli-flatorlu-drenaj-dalgic-pompa-plastik-govdeli', self::DRENAJ],
            ['pedrollo-top-5-n-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-155-mss216-m3h', self::DRENAJ],
            ['pedrollo-top-1-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-7-mss96-m3h', self::DRENAJ],
            ['pedrollo-top-2-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-8-mss132-m3h', self::DRENAJ],
            ['pedrollo-top-3-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-10-mss156-m3h', self::DRENAJ],
            ['pedrollo-top-4-n-flatorlu-plastik-govdeli-drenaj-dalgic-pompa-13-mss18-m3h', self::DRENAJ],
            ['pedrollo-top2-floor-sifirdan-emisli-plastik-govdeli-drenaj-dalgic-pompa-9-mss-96-m3h', self::DRENAJ],

            ['pedrollo-rx-5-flatorlu-full-paslanmaz-drenaj-dalgic-pompa-trifaze380-volt-20-mss-18-m3h', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-1-flatorlu-full-paslanmaz-drenaj-dalgic-pompa-monofaze220-volt-75-mss-96-m3h', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-1-gm-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompa', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-2-flatorlu-full-paslanmaz-drenaj-dalgic-pompa-monofaze220-volt-10-mss-132-m3h', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-2-gm-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompa', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-3-flatorlu-full-paslanmaz-drenaj-dalgic-pompa-monofaze220-volt-12-mss-132-m3h', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-3-gm-gizli-flatorlu-full-paslanmaz-drenaj-dalgic-pompa', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-320-flatorlu-full-paslanmaz-drenaj-dalgic-pompalar-monofaze-220v-9-mss-108-m3h', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-4-flatorlu-full-paslanmaz-drenaj-dalgic-pompa-monofaze220-volt-16-mss-156-m3h', self::PASLANMAZ_DRENAJ],
            ['pedrollo-rxm-5-flatorlu-full-paslanmaz-drenaj-dalgic-pompa-monofaze220-volt-20-mss-18-m3h', self::PASLANMAZ_DRENAJ],

            ['pedrollo-vx-1550-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', self::FOSEPTIK],
            ['pedrollo-vx3040-dokum-govdeli-foseptik-dalgic-pompa', self::FOSEPTIK],
            ['pedrollo-vx4040-dokum-govdeli-foseptik-dalgic-pompa', self::FOSEPTIK],
            ['pedrollo-vx5540-dokum-govdeli-foseptik-dalgic-pompa', self::FOSEPTIK],
            ['pedrollo-vxm-1035-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', self::FOSEPTIK],
            ['pedrollo-vxm-1050-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', self::FOSEPTIK],
            ['pedrollo-vxm-1550-st-flatorlu-full-paslanmaz-foseptik-dalgic-pompa', self::FOSEPTIK],

            ['pedrollo-top-multi-2-flatorlu-plastik-govdeli-drenaj-dalgic-ve-keson-kuyu-pompasi-42-mss-48-m3h', self::KESON],
            ['pedrollo-top-multi-2-tech-otomatik-hidroforlu-drenaj-dalgic-pompa', self::KESON],
            ['pedrollo-top-multi-3-flatorlu-plastik-govdeli-drenaj-dalgic-pompalar', self::KESON],
            ['pedrollo-top-multi-3-tech-otomatik-hidroforlu-drenaj-dalgic-pompa', self::KESON],

            ['pedrollo-top-multi-1-flatorlu-plastik-govdeli-drenaj-dalgic-pompalar', self::TEMIZ_SU],
            ['pedrollo-top-multi-2-tech-otomatik-hidroforlu-drenaj-dalgic-pompa', self::TEMIZ_SU],
            ['pedrollo-top-multi-2-evo-flatorlu-plastik-govdeli-drenaj-dalgic-pompalar', self::TEMIZ_SU],
            ['pedrollo-top-multi-3-tech-otomatik-hidroforlu-drenaj-dalgic-pompa', self::TEMIZ_SU],
            ['pedrollo-top-multi-3-evo-plastik-drenaj-dalgic-pompa', self::TEMIZ_SU],

            ['pedrollo-3cr-80-n-paslanmaz-govdeli-kademeli-jet-pompa-trifaze-40-mss-48-m3h', self::YATAY_KADEMELI],
            ['pedrollo-3crm-80-n-paslanmaz-govdeli-kademeli-jet-pompa-40-mss-48-m3h', self::YATAY_KADEMELI],
            ['pedrollo-4crm-100-n-paslanmaz-govdeli-kademeli-pompa-50-mss-78-m3h', self::YATAY_KADEMELI],
            ['pedrollo-4crm-80-n-paslanmaz-govdeli-kademeli-pompa-52-mss-48-m3h', self::YATAY_KADEMELI],
            ['pedrollo-5cr-100-n-paslanmaz-govdeli-kademeli-pompa-trifaze-63-mss-78-m3h', self::YATAY_KADEMELI],
            ['pedrollo-5cr-80-n-paslanmaz-govdeli-kademeli-pompa-trifaze-67-mss-48-m3h', self::YATAY_KADEMELI],
            ['pedrollo-5crm-100-n-paslanmaz-govdeli-kademeli-pompa-63-mss-78-m3h', self::YATAY_KADEMELI],
            ['pedrollo-5crm-80-n-paslanmaz-govdeli-kademeli-pompa-67-mss-48-m3h', self::YATAY_KADEMELI],

            ['pedrollo-4cpm-80-c-dijital-sessiz-paket-hidrofor-4-kat-8-daire-24-litre-tankli', self::EV_TIPI_HIDROFOR],
            ['pedrollo-4cpm-80-c-sessiz-paket-hidrofor-4-kat-8-daire-hidromatli', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jcrm-1a-dijital-paslanmaz-jet-hidrofor-2-kat-4-daire-24-litre-tankli', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jcrm-1a-paslanmaz-jet-hidrofor-2-kat-4-daire-hidromatli', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jswm-2cx-n-24cl-paket-hidrofor', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jswm-2cx-dijital-akilli-paket-hidrofor-4-kat-6-daire-24-litre-tankli', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jswm-2cx-paket-hidrofor-4-kat-6-daire-hidromatli', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jswm-2cx-y-24-lt-su-pompasi-paket-hidrofor-4-kat-6-daire', self::EV_TIPI_HIDROFOR],
            ['pedrollo-jswm-2cx-y-50-lt-su-pompasi-paket-hidrofor-4-kat-6-daire', self::EV_TIPI_HIDROFOR],
            ['pedrollo-pkm-60-mini-hidrofor-2-kat-2-daire-kucuk-ev-tipi-hidrofor', self::EV_TIPI_HIDROFOR],
            ['pedrollo-pkm60-italyan-ev-tipi-2-kat-2-daire-hidrofor-bag-bahce-sulama-ve-basinc-arttirici-hidrofor', self::EV_TIPI_HIDROFOR],
        ];
    }

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'pedrollo')->first();
        if (! $brand) {
            return;
        }

        if (md5((string) $brand->getRawOriginal('description')) === self::EXPECTED_OLD_DESCRIPTION_MD5) {
            // config:cache bu migration'dan sonra çalıştığı için önbellekteki eski config okunmamalı.
            $seo = (require config_path('brand_seo.php'))['pedrollo'];

            $brand->forceFill([
                'meta_title' => $seo['meta_title'],
                'meta_description' => $seo['meta_description'],
                'description' => $seo['description'],
                'faq' => $seo['faq'],
            ])->save();
        }

        $categoryIds = Category::query()->pluck('id', 'slug');

        foreach ($this->categoryMap() as [$productSlug, $categorySlugs]) {
            $product = Product::query()
                ->where('slug', $productSlug)
                ->where('brand_id', $brand->id)
                ->first();
            if (! $product) {
                continue;
            }

            $ids = collect($categorySlugs)->map(fn (string $slug) => $categoryIds[$slug] ?? null)->filter()->values()->all();
            $product->categories()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        // İçerik ve kategori eklemesi; geri alma için yedekten dönülür.
    }
};
