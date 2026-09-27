<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ticari "X fiyatları" başlığı kategori sayfasında kalır.
     * Yazı, maliyeti neyin belirlediğini anlatan rehber olarak kalır.
     *
     * @var array<string, array{title: string, meta_title: string, meta_description: string}>
     */
    private array $posts = [
        'dalgic-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Dalgıç Pompa Maliyetini Ne Belirler? Tip, Derinlik ve Marka',
            'meta_title' => 'Dalgıç Pompa Maliyetini Ne Belirler?',
            'meta_description' => 'Temiz su, drenaj, foseptik ve derin kuyu dalgıç pompada maliyeti kuyu derinliği, motor gücü ve malzeme belirler.',
        ],
        'derin-kuyu-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Derin Kuyu Pompa Maliyetini Ne Belirler?',
            'meta_title' => 'Derin Kuyu Pompa Maliyeti: Derinlik ve Motor Gücü',
            'meta_description' => 'Derin kuyu pompa bütçesini kuyu derinliği, debi ve motor gücü belirler. Seçim ölçütleri ve montaj notları.',
        ],
        'hidrofor-fiyatlari-2026-ev-apartman' => [
            'title' => 'Hidrofor Fiyatını Ne Belirler? 2026 Ev ve Apartman Maliyet Rehberi',
            'meta_title' => 'Hidrofor Maliyetini Ne Belirler? Ev ve Apartman',
            'meta_description' => 'Ev ve apartman hidrofor maliyetini tank hacmi, motor gücü, marka ve frekans kontrolü belirler.',
        ],
        'hidrofor-grubu-fiyatlari-2026-rehberi' => [
            'title' => 'Hidrofor Grubu Maliyetini Ne Belirler?',
            'meta_title' => 'Hidrofor Grubu Maliyeti: Kapasite ve Pompa Sayısı',
            'meta_description' => 'Çok pompalı hidrofor grubunda bütçeyi pompa sayısı, inverter ve tank hacmi belirler.',
        ],
        'jet-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Jet Pompa Maliyetini Ne Belirler?',
            'meta_title' => 'Jet Pompa Maliyeti: Model ve Emme Yüksekliği',
            'meta_description' => 'Jet pompa bütçesini emme yüksekliği, motor gücü ve ejektör tipi belirler.',
        ],
        'kademeli-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Kademeli Pompa Maliyetini Neler Belirler?',
            'meta_title' => 'Kademeli Pompa Maliyeti: Kademe ve Motor Gücü',
            'meta_description' => 'Kademeli pompa bütçesini kademe sayısı, motor gücü ve yatay ya da dikey montaj belirler.',
        ],
        'santrifuj-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Santrifüj Pompa Maliyetini Ne Belirler?',
            'meta_title' => 'Santrifüj Pompa Maliyeti: Tip ve Kapasite',
            'meta_description' => 'Tek fanlı, çift fanlı, paslanmaz ve salyangoz santrifüj pompada maliyeti debi, basınç ve malzeme belirler.',
        ],
        'sirkulasyon-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Sirkülasyon Pompası Maliyetini Ne Belirler?',
            'meta_title' => 'Sirkülasyon Pompası Maliyeti: Rekorlu, ECM ve Flanşlı',
            'meta_description' => 'Sirkülasyon pompası bütçesini rekorlu, ECM, inline veya flanşlı bağlantı ile debi belirler.',
        ],
        'su-pompasi-fiyatlari-2026-rehberi' => [
            'title' => 'Su Pompası Maliyetini Ne Belirler?',
            'meta_title' => 'Su Pompası Maliyeti: Ev, Bahçe ve Sanayi',
            'meta_description' => 'Ev, bahçe ve sanayi su pompasında bütçeyi debi, motor gücü ve pompa tipi belirler.',
        ],
        'vantilator-fiyatlari-2026-rehberi' => [
            'title' => 'Vantilatör Maliyetini Ne Belirler?',
            'meta_title' => 'Vantilatör Maliyeti: Çap, Debi ve Motor Gücü',
            'meta_description' => 'Ev ve sanayi vantilatöründe bütçeyi çap, debi ve motor gücü belirler.',
        ],
        'yangin-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Yangın Pompası Maliyetini Ne Belirler?',
            'meta_title' => 'Yangın Pompası Maliyeti: Elektrikli, Dizel ve Jockey',
            'meta_description' => 'Yangın pompası bütçesini elektrikli ya da dizel tahrik, jockey pompa ve bina debisi belirler.',
        ],
    ];

    /** @var array<string, array{title: string, meta_title: ?string, meta_description: ?string}> */
    private array $previous = [
        'dalgic-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Dalgıç Pompa Fiyatları 2026: Tip, Derinlik ve Marka Rehberi',
            'meta_title' => 'Dalgıç Pompa Fiyatları 2026 Rehberi',
            'meta_description' => 'Dalgıç pompa fiyatları 2026: temiz su, drenaj, foseptik ve derin kuyu fiyat aralıkları. Kuyu derinliği, HP ve markaya göre dalgıç pompa maliyeti.',
        ],
        'derin-kuyu-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Derin Kuyu Pompa Fiyatları 2026: Fiyat Aralıkları ve Seçim Rehberi',
            'meta_title' => 'Derin Kuyu Pompa Fiyatları 2026',
            'meta_description' => 'Derin kuyu pompa fiyatları 2026: dalgıç pompa fiyat aralıkları, derinlik ve motor gücüne göre maliyet, seçim ve montaj rehberi.',
        ],
        'hidrofor-fiyatlari-2026-ev-apartman' => [
            'title' => 'Hidrofor Fiyatını Ne Belirler? 2026 Ev ve Apartman Maliyet Rehberi',
            'meta_title' => 'Hidrofor Fiyatları 2026 → Ev & Apartman Aralıkları',
            'meta_description' => 'Ev ve apartman hidrofor maliyetini belirleyen tank hacmi, motor gücü, marka ve frekans kontrolü. İhtiyacınıza göre bütçe planlama rehberi.',
        ],
        'hidrofor-grubu-fiyatlari-2026-rehberi' => [
            'title' => 'Hidrofor Grubu Fiyatları 2026: Kapasite ve Pompa Sayısına Göre',
            'meta_title' => 'Hidrofor Grubu Fiyatları 2026',
            'meta_description' => '2026 hidrofor grubu fiyat aralıkları: çok pompalı sistem, inverter, tank hacmi ve markaya göre fiyat rehberi.',
        ],
        'jet-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Jet Pompa Fiyatları 2026: Model ve Kapasiteye Göre Rehber',
            'meta_title' => 'Jet Pompa Fiyatları 2026',
            'meta_description' => '2026 jet pompa fiyat aralıkları: sığ kuyu, derinden emişli modeller, motor gücü ve fiyatı belirleyen faktörler.',
        ],
        'kademeli-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Kademeli Pompa Fiyatları 2026 Rehberi: Fiyatı Etkileyen Faktörler',
            'meta_title' => 'Kademeli Pompa Fiyatları 2026 Rehberi',
            'meta_description' => 'Kademeli pompa fiyatları 2026 ne kadar? Kademe sayısı, motor gücü, montaj tipi ve markaya göre fiyat aralıkları ve bütçe rehberi.',
        ],
        'santrifuj-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Santrifüj Pompa Fiyatları 2026: Tip ve Kapasiteye Göre Rehber',
            'meta_title' => 'Santrifüj Pompa Fiyatları 2026',
            'meta_description' => '2026 santrifüj pompa fiyat aralıkları: tek fanlı, çift fanlı, paslanmaz ve salyangoz modellerde fiyatı belirleyen faktörler.',
        ],
        'sirkulasyon-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Sirkülasyon Pompa Fiyatları 2026: Rekorlu, ECM ve Flanşlı',
            'meta_title' => 'Sirkülasyon Pompa Fiyatları 2026 Rehberi',
            'meta_description' => 'Sirkülasyon pompa fiyatları 2026: rekorlu, ECM, inline ve flanşlı modeller. Fiyatı etkileyen faktörler ve doğru seçim ipuçları.',
        ],
        'su-pompasi-fiyatlari-2026-rehberi' => [
            'title' => 'Su Pompası Fiyatları 2026: Ev, Bahçe ve Sanayi Rehberi',
            'meta_title' => 'Su Pompası Fiyatları 2026: Seçim Rehberi',
            'meta_description' => 'Su pompası fiyatları 2026: santrifüj, jet, dalgıç ve hidrofor fiyat aralıkları. Debi, motor gücü ve markaya göre pompa fiyatı rehberi.',
        ],
        'vantilator-fiyatlari-2026-rehberi' => [
            'title' => 'Vantilatör Fiyatları 2026: Ev ve Sanayi Rehberi',
            'meta_title' => 'Vantilatör Fiyatları 2026: Ev ve Sanayi',
            'meta_description' => 'Vantilatör fiyatları 2026: ev tipi ve sanayi tipi vantilatör fiyat aralıkları. Çap, debi, motor gücü ve markaya göre seçim rehberi.',
        ],
        'yangin-pompa-fiyatlari-2026-rehberi' => [
            'title' => 'Yangın Pompa Fiyatları 2026: Elektrikli, Dizel ve Jockey Rehberi',
            'meta_title' => 'Yangın Pompa Fiyatları 2026',
            'meta_description' => 'Yangın pompa fiyatları 2026: elektrikli, dizel, jockey pompa ve yangın hidroforu sistem maliyeti. Bina yangın pompası bütçe rehberi.',
        ],
    ];

    public function up(): void
    {
        $this->apply($this->posts);
    }

    public function down(): void
    {
        $this->apply($this->previous);
    }

    /**
     * @param  array<string, array{title: string, meta_title: ?string, meta_description: ?string}>  $rows
     */
    private function apply(array $rows): void
    {
        if (! Schema::hasTable('blog_posts')) {
            return;
        }

        foreach ($rows as $slug => $fields) {
            DB::table('blog_posts')->where('slug', $slug)->update([
                'title' => $fields['title'],
                'meta_title' => $fields['meta_title'],
                'meta_description' => $fields['meta_description'],
                'updated_at' => now(),
            ]);
        }

        if (class_exists(UrlIndexingNotifier::class)) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }
};
