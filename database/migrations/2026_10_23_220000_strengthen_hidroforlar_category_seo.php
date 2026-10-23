<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Onaylı tek dokunuş: /kategoriler/hidrofor-sistemleri/hidroforlar
 * H1 config ile «Hidrofor Fiyatları»; geo blok ayrı dosyada.
 * Bu migration: meta_description + FAQ (tek daire / 1 HP).
 */
return new class extends Migration
{
    private const CATEGORY_ID = 128;

    private const META_DESCRIPTION = 'Hidrofor fiyatları: ev tipi paketler yaklaşık 6.500 TL’den; apartman ve grup sistemleri üst banda. Sumak, Pedrollo, Winpo, Kaysu — 280+ model.';

    public function up(): void
    {
        $current = DB::table('categories')->where('id', self::CATEGORY_ID)->first(['meta_description', 'faq']);
        if ($current === null) {
            return;
        }

        $data = [];

        $oldMeta = trim((string) $current->meta_description);
        $expectedOldPrefixes = [
            'Ev tipi paket hidroforlar, sessiz frekanslı modeller',
            'Hidrofor fiyatları: ev tipi paketler yaklaşık 6.500',
        ];
        $metaMatches = $oldMeta === '' || collect($expectedOldPrefixes)->contains(
            fn (string $prefix) => str_starts_with($oldMeta, $prefix)
        );
        if ($metaMatches && $oldMeta !== self::META_DESCRIPTION) {
            $data['meta_description'] = self::META_DESCRIPTION;
        }

        $faq = $this->faq();
        $newFaq = json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $oldFaq = json_decode((string) $current->faq, true);
        $firstQuestion = is_array($oldFaq) ? ($oldFaq[0]['q'] ?? null) : null;
        if (in_array($firstQuestion, ['Hidrofor nedir, ne işe yarar?', 'Hidrofor ile normal pompa arasındaki fark nedir?'], true)
            && $current->faq !== $newFaq) {
            $data['faq'] = $newFaq;
        }

        if ($data === []) {
            return;
        }

        DB::table('categories')->where('id', self::CATEGORY_ID)->update($data + ['updated_at' => now()]);
        app(UrlIndexingNotifier::class)->clearSitemapCache();
    }

    public function down(): void
    {
        // İçerik güncellemesi geri alınmaz.
    }

    /** @return list<array{q: string, a: string}> */
    private function faq(): array
    {
        return [
            [
                'q' => 'Hidrofor nedir, ne işe yarar?',
                'a' => 'Hidrofor; pompa, basınç tankı veya hidromat ve basınç şalterinden oluşan hazır bir sistemdir. Musluk açıldığında tesisattaki basınç düşer ve pompa devreye girer; musluk kapanınca basınç yükselir ve pompa durur. Şebeke basıncının düşük olduğu evlerde, depo veya kuyu suyu kullanılan yerlerde ve üst katlara su çıkmayan binalarda kullanılır.',
            ],
            [
                'q' => 'Kaç katlı binaya hangi hidrofor seçilmeli?',
                'a' => '1–2 katlı evlerde küçük paket hidroforlar, 3–5 katlı binalarda jet pompalı paket hidroforlar yeterlidir. 6 kattan yüksek binalarda kademeli bina hidroforları, çok daireli apartman ve sitelerde ise çift veya üç pompalı hidroforlar tercih edilir. Ürün adındaki kat ve daire değerleri başlangıç noktasıdır; ayrıntılı örnekler için <a href="/blog/kac-katli-binaya-hangi-hidrofor">kaç katlı binaya hangi hidrofor</a> yazımıza bakabilirsiniz.',
            ],
            [
                'q' => 'Hidrofor fiyatları neden bu kadar farklı?',
                'a' => 'Fiyatı belirleyen başlıca etkenler pompa gücü ve kademe sayısı, tank hacmi, gövde malzemesi, pompa adedi ve frekans kontrolüdür. Tek pompalı hidromatlı bir ev tipi model ile üç pompalı, paslanmaz gövdeli bir bina hidroforu arasında bu yüzden büyük fark bulunur.',
            ],
            [
                'q' => 'Tek daire veya 1 HP hidrofor ne kadardan başlar?',
                'a' => 'Tek daire ve 1 HP sınıfı paket hidroforlar bu katalogda yaklaşık 6.500 TL bandından başlar; tanklı veya hidromatlı oluşu ve markaya göre yükselir. Küçük konut paketleri için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a> listesine bakın. Güncel tutar ürün kartındadır.',
            ],
            [
                'q' => 'Hidrofor pompası neden sürekli çalışıyor?',
                'a' => 'En sık nedenler <strong>basınç tankı membranının yırtılması, tank ön basıncının düşmesi, basınç şalteri ayarının kayması veya tesisatta su kaçağıdır</strong>. Membran yırtılırsa tankta hava yastığı kalmaz ve pompa sık sık devreye girer. Sistemi kapatıp tank basıncını ve tesisatı kontrol ettirmeniz önerilir.',
            ],
            [
                'q' => 'Hangi hidrofor markası seçilmeli?',
                'a' => 'Seçim markadan önce ihtiyaca göre yapılmalıdır. Sumak, ev tipi paket hidrofordan çok pompalı bina hidroforlarına kadar geniş bir seriye sahip yerli bir üreticidir. İtalyan Pedrollo ev tipi jet, sessiz ve dijital paket hidroforlarda öne çıkar. Winpo ev tipi paket hidroforların yanında WNP VM dik milli bina hidroforları sunar; Kaysu ise ev tipi paket hidroforlarda ekonomik seçenekler sunar.',
            ],
        ];
    }
};
