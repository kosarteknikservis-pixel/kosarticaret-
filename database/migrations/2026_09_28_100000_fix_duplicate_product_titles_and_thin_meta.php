<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Toplu içe aktarmada kesilen meta_title'lar varyantı (tank, HP, voltaj) kaybetmişti;
     * iki farklı ürün aynı başlığı taşıyordu. Değerler ürün adı ve teknik özelliklerden alındı.
     *
     * @var array<string, string>
     */
    private array $titles = [
        'kaysu-pompa-hkjm15h-paket-hidrofor-15-hp-4-kat-8-daire-24-litre-tankli' => 'Kaysu HKJM15H Paket Hidrofor 1.5 HP 24 Lt Tanklı',
        'kaysu-pompa-hkjm15h-paket-hidrofor-15-hp-4-kat-8-daire-24-litre-yatik-tankli' => 'Kaysu HKJM15H Paket Hidrofor 1.5 HP 24 Lt Yatık Tank',
        'pedrollo-4cpm-100-c-sessiz-paket-hidrofor-5-kat-12-daire-24-litre-tankli' => 'Pedrollo 4CPm 100-C Sessiz Paket Hidrofor 24 Lt',
        'pedrollo-4cpm-100-c-sessiz-paket-hidrofor-5-kat-12-daire-50-litre-tankli' => 'Pedrollo 4CPm 100-C Sessiz Paket Hidrofor 50 Lt',
        'sumak-smj-85-jet-paket-hidrofor-4-kat-4-daire-24-litre-tankli' => 'Sumak SMJ 85 Jet Paket Hidrofor 24 Litre Tanklı',
        'sumak-smj-85-jet-paket-hidrofor-4-kat-4-daire-50-litre-tankli' => 'Sumak SMJ 85 Jet Paket Hidrofor 50 Litre Tanklı',
        'sumak-sminox-20032-rijit-kaplinli-paslanmaz-santrifuj-pompa-380v-55hp' => 'Sumak SMINOX 200/32 Paslanmaz Santrifüj 5.5 HP 380V',
        'sumak-sminox-20032-rijit-kaplinli-paslanmaz-santrifuj-pompa-380v-4hp' => 'Sumak SMINOX 200/32 Paslanmaz Santrifüj 4 HP 380V',
        'sumak-sminox-16032-rijit-kaplinli-paslanmaz-santrifuj-pompa-380v-3hp' => 'Sumak SMINOX 160/32 Paslanmaz Santrifüj 3 HP 380V',
        'sumak-sminox-16032-rijit-kaplinli-paslanmaz-santrifuj-pompa-380v-2hp' => 'Sumak SMINOX 160/32 Paslanmaz Santrifüj 2 HP 380V',
        'sumak-symh6-1006-yatay-kademeli-bina-hidroforu-100-lt-tankli-6-kat-13-daire-380-volt-1-hp' => 'Sumak SYMH6-100/6 Kademeli Bina Hidroforu 380V 1 HP',
        'sumak-symh6-1006-yatay-kademeli-bina-hidroforu-100-lt-tankli-6-kat-13-daire-220-volt-1-hp' => 'Sumak SYMH6-100/6 Kademeli Bina Hidroforu 220V 1 HP',
        'sumak-smj-150-jet-paket-hidrofor-5-kat-10-daire-50-litre-tankli' => 'Sumak SMJ 150 Jet Paket Hidrofor 50 Litre Tanklı',
        'sumak-smj-150-jet-paket-hidrofor-5-kat-10-daire-24-litre-tankli' => 'Sumak SMJ 150 Jet Paket Hidrofor 24 Litre Tanklı',
        'sumak-smj-100-jet-paket-hidrofor-4-kat-6-daire-50-litre-tankli' => 'Sumak SMJ 100 Jet Paket Hidrofor 50 Litre Tanklı',
        'sumak-smj-100-jet-paket-hidrofor-4-kat-6-daire-24-litre-tankli' => 'Sumak SMJ 100 Jet Paket Hidrofor 24 Litre Tanklı',
        'winpo-wnp-60-preferikal-pompa-38mss-18m3h-monofaze220v' => 'Winpo WNP 60 Periferik Pompa 38 mSS Monofaze 220V',
        'winpo-wnp-60-preferikal-pompa-38mss-18m3h-trifaze' => 'Winpo WNP 60 Periferik Pompa 38 mSS Trifaze 380V',
        'winpo-wnp-150-5-kat-5-daire-hidrofor-24-litre-yatay-tankli-hidrofor-ev-tipi-hidrofor' => 'Winpo WNP 150 Ev Tipi Hidrofor 24 Lt Yatay Tanklı',
        'winpo-wnp-150-5-kat-5-daire-hidrofor-24-litre-tankli-hidrofor-ev-tipi-hidrofor' => 'Winpo WNP 150 Ev Tipi Hidrofor 24 Lt Küre Tanklı',
        'winpo-wnp-816-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-3-hp-111-mss-12-m3h' => 'Winpo WNP 8/16 Derin Kuyu Dalgıç Pompa 3 HP 380V',
        'winpo-wnp-816-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-3-hp-111-mss-12-m3h' => 'Winpo WNP 8/16 Derin Kuyu Dalgıç Pompa 3 HP 220V',
        'winpo-wnp-813-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-2-hp-81-mss-12-m3h' => 'Winpo WNP 8/13 Derin Kuyu Dalgıç Pompa 2 HP 380V',
        'winpo-wnp-813-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-2-hp-81-mss-12-m3h' => 'Winpo WNP 8/13 Derin Kuyu Dalgıç Pompa 2 HP 220V',
        'winpo-wnp-621-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-3-hp-144-mss-84-m3h' => 'Winpo WNP 6/21 Derin Kuyu Dalgıç Pompa 3 HP 380V',
        'winpo-wnp-621-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-3-hp-144-mss-84-m3h' => 'Winpo WNP 6/21 Derin Kuyu Dalgıç Pompa 3 HP 220V',
        'winpo-wnp-616-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-2-hp-110-mss-84-m3h' => 'Winpo WNP 6/16 Derin Kuyu Dalgıç Pompa 2 HP 380V',
        'winpo-wnp-616-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-2-hp-110-mss-84-m3h' => 'Winpo WNP 6/16 Derin Kuyu Dalgıç Pompa 2 HP 220V',
        'winpo-wnp-611-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-15-hp-75-mss-84-m3h' => 'Winpo WNP 6/11 Derin Kuyu Dalgıç Pompa 1.5 HP 380V',
        'winpo-wnp-611-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-15-hp-75-mss-84-m3h' => 'Winpo WNP 6/11 Derin Kuyu Dalgıç Pompa 1.5 HP 220V',
        'winpo-wnp-425-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-3-hp-175-mss-6-m3h' => 'Winpo WNP 4/25 Derin Kuyu Dalgıç Pompa 3 HP 380V',
        'winpo-wnp-425-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-3-hp-175-mss-6-m3h' => 'Winpo WNP 4/25 Derin Kuyu Dalgıç Pompa 3 HP 220V',
        'winpo-wnp-419-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-2-hp-132-mss-6-m3h' => 'Winpo WNP 4/19 Derin Kuyu Dalgıç Pompa 2 HP 380V',
        'winpo-wnp-419-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-2-hp-132-mss-6-m3h' => 'Winpo WNP 4/19 Derin Kuyu Dalgıç Pompa 2 HP 220V',
        'winpo-wnp-414-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-15-hp-98-mss-6-m3h' => 'Winpo WNP 4/14 Derin Kuyu Dalgıç Pompa 1.5 HP 380V',
        'winpo-wnp-414-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-15-hp-98-mss-6-m3h' => 'Winpo WNP 4/14 Derin Kuyu Dalgıç Pompa 1.5 HP 220V',
        'winpo-wnp-238-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-3-hp-271-mss-3-m3h' => 'Winpo WNP 2/38 Derin Kuyu Dalgıç Pompa 3 HP 380V',
        'winpo-wnp-238-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-3-hp-271-mss-3-m3h' => 'Winpo WNP 2/38 Derin Kuyu Dalgıç Pompa 3 HP 220V',
        'winpo-wnp-229-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-2-hp-199-mss-3-m3h' => 'Winpo WNP 2/29 Derin Kuyu Dalgıç Pompa 2 HP 380V',
        'winpo-wnp-229-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-2-hp-199-mss-3-m3h' => 'Winpo WNP 2/29 Derin Kuyu Dalgıç Pompa 2 HP 220V',
        'winpo-wnp-222-derin-kuyu-dalgic-pompa-motorlu-220-volt-monofaze-15-hp-155-mss-3-m3h' => 'Winpo WNP 2/22 Derin Kuyu Dalgıç Pompa 1.5 HP 220V',
        'winpo-wnp-222-derin-kuyu-dalgic-pompa-motorlu-380-volt-trifaze-15-hp-155-mss-3-m3h' => 'Winpo WNP 2/22 Derin Kuyu Dalgıç Pompa 1.5 HP 380V',
        'winpo-wnp-100-4-kat-4-daire-hidrofor-24-litre-yatay-tankli-hidrofor-ev-tipi-hidrofor' => 'Winpo WNP 100 Ev Tipi Hidrofor 24 Litre Yatay Tanklı',
        'winpo-wnp-100-4-kat-4-daire-hidrofor-24-litre-tankli-hidrofor-ev-tipi-hidrofor' => 'Winpo WNP 100 Ev Tipi Hidrofor 24 Litre Tanklı',
    ];

    /**
     * Kısa açıklaması ürün adıyla aynı olan ürünler; eski meta içindekiler tablosu parçası
     * ya da kalıp metindi. Metinler yalnızca ürünün teknik özelliklerinden yazıldı.
     *
     * @var array<string, string>
     */
    private array $descriptions = [
        'kaysu-spauto750-a-gizli-flatorlu-dalgic-pompa' => 'Kaysu SPAUTO750-A gizli flatörlü dalgıç pompa: 1 HP (0,75 kW), 220 V monofaze, 11–15 m³/h debi ve 10 mSS basma. Su seviyesine göre otomatik çalışır.',
        'kaysu-water-bender-1-6-hidrofor-basinc-salteri' => 'Kaysu Water Bender 1-6 hidrofor basınç şalteri: 1–6 bar ayar aralığı, 16 A maksimum akım, 220–250 V, IP44 koruma ve 1/4" dişli bağlantı.',
        'kaysu-water-bender-2-8-hidrofor-basinc-salteri' => 'Kaysu Water Bender 2-8 hidrofor basınç şalteri: 2–8 bar ayar aralığı, 16 A maksimum akım, 220–250 V, IP44 koruma ve 1/4" dişli bağlantı.',
        'kaysu-water-bender-3-11-hidrofor-basinc-salteri' => 'Kaysu Water Bender 3-11 hidrofor basınç şalteri: 3–11 bar ayar aralığı, 16 A maksimum akım, 220–250 V, IP44 koruma ve 1/4" dişli bağlantı.',
        'horoz-plastik-100luk-aspirator-12w-sessiz-havalandirma' => 'Horoz 100\'lük plastik aspiratör: 12 W motor, Ø100 mm, 90–110 m³/h hava debisi ve 25–32 dB sessiz çalışma. Banyo ve mutfak havalandırması için.',
        'horoz-5-watt-lilya-5-led-armatur' => 'Horoz Lilya-5 5 watt LED armatür. Horoz Electric aydınlatma serisinden; güncel fiyat, stok durumu ve teknik destekle Koşar Ticaret\'te.',
        'horoz-plastik-120lik-aspirator-12w-sessiz-havalandirma' => 'Horoz 120\'lik plastik aspiratör: 12 W motor, Ø120 mm çıkış, 110–140 m³/h hava debisi ve 25–32 dB. Banyo, mutfak ve ofis havalandırması için.',
        'horoz-plastik-150lik-aspirator-12watt-sessiz-havalandirma' => 'Horoz 150\'lik plastik aspiratör: 12 W motor, Ø150 mm çıkış, 150–200 m³/h hava debisi ve 25–32 dB sessiz çalışma. Geniş alan havalandırması için.',
        'horoz-plastik-200luk-aspirator-12w-sessiz-havalandirma' => 'Horoz 200\'lük plastik aspiratör, 12 W sessiz havalandırma. Horoz Electric ürünü; güncel fiyat, stok durumu ve teknik destekle Koşar Ticaret\'te.',
        'horoz-12-watt-led-slim-panel' => 'Horoz 12 watt LED slim panel: 900–1000 lümen, 3000–6500 K renk sıcaklığı, 220–240 V. Alüminyum gövde ve PC difüzörlü enerji tasarruflu aydınlatma.',
        'horoz-18-watt-led-slim-panel' => 'Horoz 18 watt LED slim panel: 1400–1500 lümen, 6500 K beyaz ışık, 220–240 V. Alüminyum gövde ve PC difüzörle geniş alanlar için aydınlatma.',
        'horoz-9-watt-led-slim-panel' => 'Horoz 9 watt LED slim panel: 720 lümen, 6500 K beyaz ışık, 220–240 V AC. 120 mm yuvarlak veya 120x120 mm kare ölçülü.',
        'horoz-6-watt-led-slim-panel' => 'Horoz 6 watt LED slim panel: 480 lümen, 4000 K / 6500 K ışık seçeneği, 220–240 V AC. 100 mm yuvarlak veya 100x100 mm kare ölçülü.',
        'horoz-20-watt-alexa-20-led-armatur' => 'Horoz Alexa-20 20 watt LED armatür. Horoz Electric aydınlatma serisinden; güncel fiyat, stok durumu ve teknik destekle Koşar Ticaret\'te.',
        'horoz-stella-8-led-panel-8-watt' => 'Horoz Stella-8 LED panel: 8 watt, 640 lümen, 4000 K / 6500 K ışık seçeneği, 220–240 V AC. Enerji verimli modern panel aydınlatma.',
        'horoz-sonia-5-led-armatur-5-watt' => 'Horoz Sonia-5 5 watt LED armatür. Horoz Electric aydınlatma serisinden; güncel fiyat, stok durumu ve teknik destekle Koşar Ticaret\'te.',
        'horoz-3-watt-led-slim-panel' => 'Horoz 3 watt LED slim panel: 240 lümen, 4000 K / 6500 K ışık seçeneği, 220–240 V AC. 75 mm yuvarlak veya 75x75 mm kare ölçülü.',
        'horoz-gama-80-watt-bant-armatur' => 'Horoz Gama 80 watt LED bant armatür: 8000 lümen, 6500 K beyaz ışık, 220–240 V AC. Endüstriyel alanlar için yüksek verimli aydınlatma.',
        'horoz-ultra-5w-led-ampul-e14-ince-duy-beyaz' => 'Horoz Ultra 5W LED ampul, E14 ince duy: 350 lümen, 6400 K beyaz ışık, 220 V AC, 36,5 x 100 mm. Avizeler için enerji tasarruflu ampul.',
    ];

    /** @var array<string, array{meta_title: string, meta_description: string}> */
    private array $posts = [
        'hidrofor-secimi-rehberi' => [
            'meta_title' => 'Ev Tipi Hidrofor Seçimi: Nelere Dikkat Etmeli?',
            'meta_description' => 'Ev tipi hidrofor seçerken su kullanım noktaları, debi (lt/dk) ve maksimum basınç (bar) değerlerine nasıl bakılır? Kısa seçim rehberi.',
        ],
        'dalgic-pompa-bakimi' => [
            'meta_title' => 'Dalgıç Pompa Bakımı ve Ömrünü Uzatma',
            'meta_description' => 'Dalgıç pompa ömrünü uzatmak için filtre temizliği, kuru çalıştırmadan kaçınma ve elektrik kesintisi sonrası emme hattı kontrolü.',
        ],
    ];

    /** @var array<string, string> */
    private array $previousTitles = [
        'kaysu-pompa-hkjm15h' => 'Kaysu Pompa HKJM15H Paket Hidrofor 1.5 HP 4 | Koşar Ticaret',
        'pedrollo-4cpm-100-c' => 'Pedrollo 4CPm 100-C Sessiz Paket Hidrofor 5 | Koşar Ticaret',
        'sumak-smj-85-' => 'Sumak SMJ 85 Jet Paket Hidrofor 4 Kat 4 | Koşar Ticaret',
        'sumak-sminox-20032' => 'Sumak SMINOX 200/32 Rijit Kaplinli Paslanmaz | Koşar Ticaret',
        'sumak-sminox-16032' => 'Sumak SMINOX 160/32 Rijit Kaplinli Paslanmaz | Koşar Ticaret',
        'sumak-symh6-1006' => 'Sumak SYMH6-100/6 Yatay Kademeli Bina | Koşar Ticaret',
        'sumak-smj-150-' => 'Sumak SMJ 150 Jet Paket Hidrofor 5 Kat 10 | Koşar Ticaret',
        'sumak-smj-100-' => 'Sumak SMJ 100 Jet Paket Hidrofor 4 Kat 6 | Koşar Ticaret',
        'winpo-wnp-60-' => 'Winpo WNP 60 Preferikal Pompa 38mss 1.8m³/h | Koşar Ticaret',
        'winpo-wnp-150-' => 'Winpo WNP 150 5 Kat 5 Daire Hidrofor, 24 | Koşar Ticaret',
        'winpo-wnp-100-' => 'Winpo WNP 100 4 Kat 4 Daire Hidrofor, 24 | Koşar Ticaret',
    ];

    public function up(): void
    {
        if (Schema::hasTable('products')) {
            foreach ($this->titles as $slug => $title) {
                DB::table('products')->where('slug', $slug)->update(['meta_title' => $title, 'updated_at' => now()]);
            }

            foreach ($this->descriptions as $slug => $description) {
                DB::table('products')->where('slug', $slug)->update(['meta_description' => $description, 'updated_at' => now()]);
            }
        }

        if (Schema::hasTable('blog_posts')) {
            foreach ($this->posts as $slug => $fields) {
                DB::table('blog_posts')
                    ->where('slug', $slug)
                    ->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))
                    ->update([...$fields, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            foreach (array_keys($this->titles) as $slug) {
                $previous = $this->previousTitleFor($slug);
                if ($previous !== null) {
                    DB::table('products')->where('slug', $slug)->update(['meta_title' => $previous]);
                }
            }
        }

        // Eski ürün meta açıklamaları içindekiler tablosu parçasıydı; geri yüklenmez.

        if (Schema::hasTable('blog_posts')) {
            DB::table('blog_posts')
                ->whereIn('slug', array_keys($this->posts))
                ->update(['meta_title' => null, 'meta_description' => null]);
        }
    }

    private function previousTitleFor(string $slug): ?string
    {
        if (str_starts_with($slug, 'winpo-wnp-') && str_contains($slug, 'derin-kuyu')) {
            preg_match('/^winpo-wnp-(\d)(\d{2})-/', $slug, $m);

            return $m ? "Winpo WNP {$m[1]}/{$m[2]} Derin Kuyu Dalgıç Pompa | Koşar Ticaret" : null;
        }

        foreach ($this->previousTitles as $prefix => $title) {
            if (str_starts_with($slug, $prefix)) {
                return $title;
            }
        }

        return null;
    }
};
