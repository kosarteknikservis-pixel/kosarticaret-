<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = 'İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.';

    private const STYLED_TABLE_PRODUCTS = [1487, 1624, 1759, 2422];

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $ids = array_unique(array_merge(array_keys($this->swaps()), self::STYLED_TABLE_PRODUCTS));
        $changed = 0;

        foreach (DB::table('products')->whereIn('id', $ids)->pluck('description', 'id') as $id => $html) {
            $new = (string) $html;

            if (in_array($id, self::STYLED_TABLE_PRODUCTS, true)) {
                $new = preg_replace('/\sstyle\s*=\s*(["\']).*?\1/is', '', $new) ?? $new;
            }

            foreach ($this->swaps()[$id] ?? [] as $old => $replacement) {
                $new = $this->swap($id, $new, $old, $replacement);
            }

            if ($new !== $html) {
                DB::table('products')->where('id', $id)->update(['description' => $new, 'updated_at' => now()]);
                $changed++;
            }
        }

        if ($changed > 0) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        // İçerik güncellemesi geri alınmaz.
    }

    private function swap(int $id, string $html, string $old, string $replacement): string
    {
        $pattern = '/'.str_replace(' ', '\s+', preg_quote($old, '/')).'/u';
        $count = 0;
        $result = preg_replace_callback($pattern, fn () => $replacement, $html, 1, $count);
        if ($count === 0 || $result === null) {
            $this->misses[$id][] = mb_substr($old, 0, 60);

            return $html;
        }

        return $result;
    }

    /** @return array<int, array<string, string>> */
    private function swaps(): array
    {
        return [
            // Türkiye geneli servis ağı → İstanbul servis cümlesi
            1489 => ['Uzun ömürlü kullanımı, sağlam yapısı ve geniş servis ağı ile Türkiye\'de yaygın olarak tercih edilmektedir.' => 'Uzun ömürlü kullanımı ve sağlam yapısıyla yaygın olarak tercih edilmektedir. '.self::CONTACT],
            1492 => ['Türkiye genelinde geniş bir servis ağına sahip olan bu ürün, uzun ömürlü' => 'Bu ürün, uzun ömürlü', 'güvenilir bir su çözümü arayanlar için idealdir.' => 'güvenilir bir su çözümü arayanlar için idealdir. '.self::CONTACT],
            1495 => ['Türkiye genelinde yaygın servis ağına sahip olan Pedrollo, kullanıcılarına uzun ömürlü ve yüksek performanslı pompa çözümleri sunmaktadır.' => 'Pedrollo, kullanıcılarına uzun ömürlü ve yüksek performanslı pompa çözümleri sunmaktadır. '.self::CONTACT],
            1496 => ['Türkiye genelinde yaygın servis ağı ve 2 yıl fabrika garantisi ile kullanıcılarına uzun ömürlü bir deneyim yaşatmayı hedeflemektedir.' => '2 yıl üretici garantisi ile kullanıcılarına uzun ömürlü bir deneyim yaşatmayı hedeflemektedir. '.self::CONTACT],
            1503 => ['İtalyan kalitesi ile üretilen bu ürün, Türkiye genelinde yaygın bir servis ağına sahiptir.' => 'Bu ürün İtalyan kalitesiyle üretilmektedir. '.self::CONTACT],
            1505 => ['Türkiye genelinde yaygın servis ağı ile müşterilerine her zaman destek sunmaktadır.' => self::CONTACT],
            1950 => ['Türkiye genelinde yaygın bir yetkili servis ağına sahip olması, yedek parça temini konusunda da kullanıcıların endişelerini ortadan kaldırmaktadır.' => self::CONTACT],

            // Doğrulanamayan üstünlük iddiaları
            1421 => ['sektördeki en iyi çözümlerden biridir' => 'güvenilir bir çözümdür'],
            1474 => ['su yönetiminde en iyi çözümlerden birini sunmaktadır' => 'su yönetiminde güvenilir bir çözüm sunmaktadır'],
            1509 => ['sektördeki lider konumunu sürdürmektedir' => 'sektördeki güçlü konumunu sürdürmektedir'],
            1544 => ['santrifüj pompa teknolojisinin en iyi örneklerinden biridir' => 'santrifüj pompa teknolojisinin başarılı örneklerinden biridir'],
            1593 => ['ihtiyacınız olduğunda en iyi çözümü sunar' => 'ihtiyacınız olduğunda etkili bir çözüm sunar'],
            1606 => ['tercih edilebilecek en iyi çözüm alternatiflerinden biridir' => 'tercih edilebilecek güçlü alternatiflerden biridir'],
            1607 => ['sektördeki en iyi seçeneklerden biri olarak öne çıkmaktadır' => 'güçlü bir seçenek olarak öne çıkmaktadır'],
            1639 => ['Bahçeniz için en iyi sulama çözümlerinden biri olan bu pompa' => 'Bahçe sulaması için uygun bir çözüm olan bu pompa'],
            1703 => ['santrifüj pompa teknolojisinin en iyi örneklerinden biridir' => 'santrifüj pompa teknolojisinin başarılı örneklerinden biridir'],
            1787 => ['karşılayacak en iyi çözüm olarak öne çıkıyor' => 'karşılayacak güçlü bir çözüm olarak öne çıkıyor'],
            1793 => ['karşılamak için en iyi tercih olup' => 'karşılamak için güvenilir bir tercih olup'],
            1795 => ['foseptik su tahliyesi için en iyi tercihlerden biridir' => 'foseptik su tahliyesi için güvenilir bir tercihtir'],
            1796 => ['onu sektördeki en iyi çözümlerden biri haline getirir' => 'onu güvenilir bir çözüm haline getirir'],
            1897 => ['sektördeki en iyi tercihlerden biridir' => 'güvenilir bir tercihtir'],
            1918 => ['sektördeki en iyi dalgıç pompalar arasında yer almaktadır' => 'güvenilir bir dalgıç pompa seçeneğidir'],
            1978 => ['sektördeki en iyi seçeneklerden biridir' => 'güçlü bir seçenektir'],
            2020 => ['karşılamak için en iyi seçenektir' => 'karşılamak için güçlü bir seçenektir'],
            2035 => ['tasarlanmış en iyi çözümlerden biridir' => 'tasarlanmış güvenilir bir çözümdür'],
            2054 => ['sağlanması için en iyi çözümüdür' => 'sağlanması için güvenilir bir çözümdür'],
            2065 => ['yönelik en iyi çözümü sunarak' => 'yönelik etkili bir çözüm sunarak'],
            2068 => ['karşılayacak en iyi tercihlerden biridir' => 'karşılayacak güvenilir bir tercihtir'],
            2089 => ['Su sistemlerinizdeki en iyi çözüm olan' => 'Su sistemleriniz için güvenilir bir çözüm olan'],
            2114 => ['kolaylaştıracak en iyi çözümlerden birini sunmaktadır' => 'kolaylaştıracak güvenilir bir çözüm sunmaktadır'],
            2125 => ['su tahliyesi konusunda en iyi tercihlerden biridir' => 'su tahliyesi konusunda güvenilir bir tercihtir'],
            2148 => ['sıcak su pompa çözümleri arasında en iyi tercihlerden biridir' => 'sıcak su pompa çözümleri arasında güvenilir bir tercihtir'],
            2162 => ['santrifüj pompa teknolojisinin en iyi örneklerinden biridir' => 'santrifüj pompa teknolojisinin başarılı örneklerinden biridir'],
            2199 => ['karşılamak için en iyi tercihlerden biridir' => 'karşılamak için güvenilir bir tercihtir'],
            2205 => ['su taşıma sistemlerinizdeki en iyi yardımcıdır' => 'su taşıma sistemlerinizde güvenilir bir yardımcıdır'],
            2212 => ['sektördeki en iyi çözümlerden biridir' => 'güvenilir bir çözümdür'],
            2293 => ['sektördeki en iyi seçeneklerden biridir' => 'güçlü bir seçenektir'],
            2346 => ['karşılamada en iyi yardımcıdır' => 'karşılamada güvenilir bir yardımcıdır'],
            2366 => ['sulama işlerinizde en iyi çözümü sunar' => 'sulama işlerinizde etkili bir çözüm sunar'],
            2445 => ['yangın güvenliğinde en iyi çözümü sunar' => 'yangın güvenliği uygulamalarında güvenilir bir çözüm sunar'],
            2489 => ['su tahliyesinde en iyi seçimdir' => 'su tahliyesinde güvenilir bir seçimdir'],
            2495 => ['ev tipi hidrofor arayışında en iyi seçeneklerden biridir' => 'ev tipi hidrofor arayanlar için güçlü bir seçenektir'],
            2500 => ['bu alandaki en iyi seçeneklerden biridir' => 'bu alanda güçlü bir seçenektir'],
            2501 => ['tercih edebileceğiniz en iyi seçeneklerden biridir' => 'tercih edebileceğiniz güçlü bir seçenektir'],
            2542 => ['karşılamak için en iyi seçimlerden biridir' => 'karşılamak için güvenilir bir seçimdir'],
            2594 => ['sektördeki en iyi seçeneklerden biridir' => 'güçlü bir seçenektir'],
            2598 => ['kullanılabilecek en iyi çözümlerden biridir' => 'kullanılabilecek güvenilir bir çözümdür'],
            2638 => ['sanayi sektöründeki en iyi yardımcılarınızdan biri olacaktır' => 'sanayi uygulamalarında güvenilir bir yardımcınız olacaktır'],
            2683 => ['ihtiyacınız için en iyi tercihlerden biridir' => 'ihtiyacınız için güvenilir bir tercihtir'],
            2739 => ['su boşaltma işlemlerinde en iyi çözüm ortağınızdır' => 'su boşaltma işlemlerinde güvenilir bir çözümdür'],
        ];
    }
};
