<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

final class GoogleProductCategory
{
    /**
     * En alt kategori kazanır. Eşleşmeyen ürün kimliksiz gider.
     *
     * @var array<string, int>
     */
    private const SLUG_MAP = [
        'derin-kuyu-dalgic-pompa' => 500100,
        'keson-kuyu-pompa' => 500100,
        'foseptik-dalgic-pompa' => 500102,
        'bicakli-dalgic-pompa' => 500102,
        'kirli-su-dalgic-pompa' => 500102,
        'drenaj-dalgic-pompa' => 500102,
        'paslanmaz-drenaj-dalgic-pompa' => 500102,
        'sintine-pompasi' => 500102,
        'karistiricili-camur-pompasi' => 500102,
        'yagmur-suyu-tahliye-pompasi' => 500102,
        'foseptik-tahliye-cihazi' => 500102,
        'on-filtreli-havuz-pompasi' => 500098,
        'jakuzi-pompasi' => 500098,
        'hidrofor-sistemleri' => 500097,
        'hidrofor-grubu' => 500097,
        'hidroforlar' => 500097,
        'ev-tipi-hidroforlar' => 500097,
        'pedrollo-hidrofor' => 500097,
        'sumak-hidrofor' => 500097,
        'sicak-su-hidroforu' => 500097,
        'yangin-pompalari' => 500097,
        'santrifuj-pompalar-sulama' => 500097,
        'jet-pompalar-derinden-emisli' => 500101,
        'preferikal-pompalar-surtme-fanli' => 500101,
        'tek-fanli-santrifuj-pompa' => 500101,
        'cift-fanli-santrifuj-pompa' => 500101,
        'salyangoz-pompalar-bol-su-veren' => 500101,
        'klapeli-pompalar' => 500101,
        'dizel-su-motorlari' => 500101,
        'sicak-su-pompalari' => 500101,
        'dalgic-pompalar' => 500101,
        'paslanmaz-pompalar-kimyasal' => 500096,
        'sirkulasyon-pompalari' => 500096,
        'rekorlu-disli-sirkulasyon-pompalari' => 500096,
        'inline-sirkulasyon-pompalari' => 500096,
        'flansli-sirkulasyon-pompalari' => 500096,
        'vantilatorler' => 608,
        'ev-tipi-vantilator' => 608,
        'sanayi-tipi-vantilator' => 608,
        'hidromat' => 499932,
        'dis-mekan-isiticilar' => 2649,
        'duvar-tipi-dis-mekan-isiticilar' => 2649,
        'dikey-tower-dis-mekan-isiticilar' => 2649,
        'ev-tipi-isiticilar' => 611,
        'dikey-ev-tipi-isiticilar' => 611,
        'duvar-tipi-ev-isiticilar' => 611,
        'panel-isiticilar' => 611,
    ];

    /**
     * Temiz su kategorisinde duran Sumak SDF/SDT. Kullanım foseptik ve tahliye.
     *
     * @var list<string>
     */
    private const SUMAK_SEWAGE_SKUS = [
        'sdf123',
        'sdf83',
        'sdf52',
        'sdf151',
        'sdf252-m',
        'sdt252',
    ];

    /** @var list<string> */
    private const KADEMELI_SLUGS = [
        'kademeli-pompalar',
        'dikey-kademeli-pompalar',
        'monoblok-yatay-kademeli',
        'norm-tipi-yatay-kademeli',
        'yatay-kademeli-pompalar',
    ];

    public static function forProduct(Product $product): ?int
    {
        $name = self::normalize((string) $product->name);
        $sku = self::normalize((string) $product->sku);
        $primary = $product->primaryCategory();
        $slug = $primary?->slug;

        $exception = self::productException($name, $slug, $sku);
        if ($exception !== false) {
            return $exception;
        }

        if ($primary instanceof Category) {
            return self::forCategory($primary);
        }

        return null;
    }

    public static function forCategory(Category $category): ?int
    {
        foreach (array_reverse($category->ancestorsAndSelf()) as $node) {
            if (isset(self::SLUG_MAP[$node->slug])) {
                return self::SLUG_MAP[$node->slug];
            }
        }

        return null;
    }

    /**
     * false: kategori haritasına bırak. null: kimliksiz.
     */
    private static function productException(string $name, ?string $slug, string $sku): int|false|null
    {
        if (in_array($sku, self::SUMAK_SEWAGE_SKUS, true)) {
            return 500102;
        }

        if (self::has($name, 'basınç şalter') || self::has($name, 'basinc salter')) {
            return 499932;
        }

        if (self::has($name, 'aspiratör') || self::has($name, 'aspirator')) {
            return 4485;
        }

        if ($slug === 'elektrik-ve-aydinlatma' && (self::has($name, 'led') || self::has($name, 'armatür') || self::has($name, 'armatur'))) {
            return 3006;
        }

        if (in_array($slug, ['sanayi-tipi-vantilator', 'ev-tipi-vantilator', 'vantilatorler'], true)) {
            if (self::has($name, 'ayaklı') || self::has($name, 'ayakli')) {
                return 2535;
            }

            if (self::has($name, 'duvar') || self::has($name, 'uzaktan kumanda') || self::has($name, 'ksv-dk')) {
                return 8090;
            }

            return 608;
        }

        if ($slug === 'keson-kuyu-pompa' && self::isKesonDrainage($name)) {
            return 500102;
        }

        if (in_array($slug, self::KADEMELI_SLUGS, true)) {
            return self::kademeliId($name);
        }

        if ($slug === 'temiz-su-dalgic-pompasi') {
            return self::temizSuId($name);
        }

        return false;
    }

    private static function kademeliId(string $name): int
    {
        if (
            self::has($name, 'hidrofor')
            || self::has($name, 'sulama')
            || self::has($name, 'basınç')
            || self::has($name, 'basinc')
            || self::has($name, 'dik milli')
            || self::has($name, 'jet')
            || preg_match('/\d+(?:[.,]\d+)?\s*bar\b/u', $name) === 1
            || preg_match('/\d+(?:[.,]\d+)?\s*mss\b/u', $name) === 1
        ) {
            return 500097;
        }

        return 500101;
    }

    private static function temizSuId(string $name): ?int
    {
        if (preg_match('/(?:^|[^\d])[46]\s*(?:"|″|inç|inc|inch)\b/u', $name) === 1 || self::has($name, 'derin kuyu')) {
            return 500100;
        }

        if (
            self::has($name, 'drenaj')
            || self::has($name, 'tahliye')
            || self::has($name, 'flatör')
            || self::has($name, 'flator')
            || self::has($name, 'vortex')
            || self::has($name, 'plastik')
            || self::has($name, 'qdx')
            || self::has($name, 'qdp')
            || self::has($name, 'sp400')
            || self::has($name, 'sp750')
            || self::has($name, 'spauto')
        ) {
            return 500101;
        }

        return null;
    }

    private static function isKesonDrainage(string $name): bool
    {
        return self::has($name, 'top multi')
            || self::has($name, 'wnp 6-39/3')
            || self::has($name, 'wnp 6-28/2');
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    private static function has(string $haystack, string $needle): bool
    {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }
}
