<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Canlıdaki eski açıklamanın md5'i; panelden değiştirilmişse üzerine yazılmaz. */
    private const EXPECTED_OLD_DESCRIPTION_MD5 = '0f43b715fc95aa6a4a8ce05171f0a95e';

    /** Markası boş kalmış Kaysu ürünleri. */
    private const UNBRANDED_SLUGS = [
        'kaysu-hcpf-70-tek-fanli-pompa',
        'kaysu-hmc145-6sh-yatay-milli-cok-kademeli-pompa',
    ];

    private const HIDROFOR = ['hidrofor-sistemleri', 'hidroforlar'];

    private const EV_TIPI_HIDROFOR = ['hidrofor-sistemleri', 'hidroforlar', 'ev-tipi-hidroforlar'];

    private const DALGIC = ['su-pompalari', 'dalgic-pompalar'];

    private const TEMIZ_SU = ['su-pompalari', 'dalgic-pompalar', 'temiz-su-dalgic-pompasi'];

    private const FOSEPTIK = ['su-pompalari', 'dalgic-pompalar', 'foseptik-dalgic-pompa'];

    private const BICAKLI_FOSEPTIK = ['su-pompalari', 'dalgic-pompalar', 'foseptik-dalgic-pompa', 'bicakli-dalgic-pompa'];

    /** Ürün slug'ı => eklenecek kategori slug'ları (mevcut atamalar korunur). */
    private function categoryMap(): array
    {
        return [
            'kaysu-2hcp-160-cift-kademeli-hidrofor-7-kat-14-daire-50-litre-tankli' => self::HIDROFOR,
            'kaysu-pompa-hmc145-65h-paket-hidrofor-8-kat-18-daire-100-litre-tankli' => self::HIDROFOR,
            'kaysu-pompa-hkjm-paket-hidrofor-1-hp-4-kat-4-daire-24-litre-yatik-tankli' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hkjm15h-paket-hidrofor-15-hp-4-kat-8-daire-24-litre-tankli' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hkjm15h-paket-hidrofor-15-hp-4-kat-8-daire-24-litre-yatik-tankli' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hkjm15h-paket-hidrofor-15-hp-5-kat-8-daire-50-litre-yatik-tankli' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hkjmh-10h-jet-hidrofor-4-kat-4-daire-24l-tankli' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hqbm60-hidromatli-paket-hidrofor-1-hp-4-kat-4-daire' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hqbm60-paket-hidrofor-050-hp-2-kat-2-daire' => self::EV_TIPI_HIDROFOR,
            'kaysu-pompa-hidrofor-050-hp-1-kat-1-daire' => self::EV_TIPI_HIDROFOR,
            'kaysu-ps-370a-sessiz-surtme-fanli-hidrofor' => self::EV_TIPI_HIDROFOR,
            'kaysu-water-bender-dsk22-1-metre-fisli-kablolu-hidromat' => ['hidrofor-sistemleri', 'hidromat'],

            'kaysu-hkjm100-derinden-emisli-jet-su-pompasi' => ['su-pompalari', 'jet-pompalar-derinden-emisli'],
            'kaysu-hkjm150-derinden-emisli-jet-su-pompasi' => ['su-pompalari', 'jet-pompalar-derinden-emisli'],
            'kaysu-hqbm60-preferikal-surtme-fanli-su-pompasi' => ['su-pompalari', 'preferikal-pompalar-surtme-fanli'],
            'kaysu-hqbm80-preferikal-surtme-fanli-su-pompasi' => ['su-pompalari', 'preferikal-pompalar-surtme-fanli'],
            'kaysu-2hcp-160-cift-kademeli-pompa' => ['su-pompalari', 'kademeli-pompalar'],
            'kaysu-hmc145-6sh-yatay-milli-cok-kademeli-pompa' => ['su-pompalari', 'kademeli-pompalar', 'yatay-kademeli-pompalar'],
            'kaysu-hcpf-70-tek-fanli-pompa' => ['su-pompalari', 'santrifuj-pompalar', 'tek-fanli-santrifuj-pompa'],

            'kaysu-sp400-a-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-spauto400-a-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-sp750-a-plastik-su-pompasi-dalgic-temiz-su' => self::TEMIZ_SU,
            'kaysu-spauto750-a-gizli-flatorlu-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-qdx15-16-037fh-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-qdx15-32-075fh-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-qdx6-32-15b-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-qdx6-393-15b-dalgic-pompa' => self::TEMIZ_SU,
            'kaysu-sp100050-kapali-fanli-plastik-drenaj-dalgic-su-pompasi' => ['su-pompalari', 'dalgic-pompalar', 'drenaj-dalgic-pompa'],
            'kaysu-sp130050-acik-fanli-plastik-drenaj-dalgic-su-pompasi' => ['su-pompalari', 'dalgic-pompalar', 'drenaj-dalgic-pompa'],
            'kaysu-hwd-1100s-paslanmaz-govdeli-kirli-su-dalgic-pompasi-15-hp-220-volt' => ['su-pompalari', 'dalgic-pompalar', 'kirli-su-dalgic-pompa'],
            'kaysu-wqd370-b-dalgic-pompa' => self::DALGIC,
            'kaysu-h1100f-b-foseptik-dalgic-su-pompasi' => self::FOSEPTIK,
            'kaysu-wqd750-b-foseptik-dalgic-su-pompasi' => self::FOSEPTIK,
            'kaysu-wqd1100-s-foseptik-dalgic-su-pompasi' => self::FOSEPTIK,
            'kaysu-wqd1500-s-foseptik-dalgic-su-pompasi' => self::FOSEPTIK,
            'kaysu-wqh1500-foseptik-dalgic-su-pompasi' => self::FOSEPTIK,
            'kaysu-cut1500-bicakliogutuculu-dalgic-su-pompasi-monofaze' => self::BICAKLI_FOSEPTIK,
            'kaysu-cut1500-t-bicakliogutuculu-dalgic-su-pompasi-trifaze-panolu' => self::BICAKLI_FOSEPTIK,
            'kaysu-wqh2200qg-dokum-govdeli-ogutuculu-foseptik-dalgic-3-hp-380-volt' => self::BICAKLI_FOSEPTIK,
        ];
    }

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'kaysu')->first();
        if (! $brand) {
            return;
        }

        if (md5((string) $brand->getRawOriginal('description')) === self::EXPECTED_OLD_DESCRIPTION_MD5) {
            // config:cache bu migration'dan sonra çalıştığı için önbellekteki eski config okunmamalı.
            $seo = (require config_path('brand_seo.php'))['kaysu'];

            $brand->forceFill([
                'meta_title' => $seo['meta_title'],
                'meta_description' => $seo['meta_description'],
                'description' => $seo['description'],
                'faq' => $seo['faq'],
            ])->save();
        }

        Product::query()
            ->whereIn('slug', self::UNBRANDED_SLUGS)
            ->whereNull('brand_id')
            ->update(['brand_id' => $brand->id]);

        $categoryIds = Category::query()->pluck('id', 'slug');

        foreach ($this->categoryMap() as $productSlug => $categorySlugs) {
            $product = Product::query()->where('slug', $productSlug)->first();
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
