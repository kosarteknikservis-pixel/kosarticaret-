<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $changed = 0;
        $rows = DB::table('products')->whereIn('id', array_keys($this->updates()))->get(['id', 'meta_title', 'short_description'])->keyBy('id');

        foreach ($this->updates() as $id => $fields) {
            $current = $rows[$id] ?? null;
            if ($current === null) {
                $this->misses[$id][] = 'ürün yok';

                continue;
            }

            $data = [];

            [$expectedShortPrefix, $short] = $fields['short'];
            $oldShort = trim((string) $current->short_description);
            if ($oldShort !== $short) {
                if (str_starts_with($oldShort, $expectedShortPrefix)) {
                    $data['short_description'] = $short;
                } else {
                    $this->misses[$id][] = 'short_description';
                }
            }

            if (isset($fields['title'])) {
                [$expectedTitle, $title] = $fields['title'];
                $oldTitle = trim((string) $current->meta_title);
                if ($oldTitle !== $title) {
                    if ($oldTitle === $expectedTitle) {
                        $data['meta_title'] = $title;
                    } else {
                        $this->misses[$id][] = 'meta_title';
                    }
                }
            }

            if ($data !== []) {
                DB::table('products')->where('id', $id)->update($data + ['updated_at' => now()]);
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

    /** @return array<int, array{short: array{0: string, 1: string}, title?: array{0: string, 1: string}}> */
    private function updates(): array
    {
        return [
            2344 => [
                'short' => ['Sumak SMJ 150 Jet Pompa, 1.5 HP', 'Kuyu, depo ve şebekeden su basmak için 1.5 HP, 220 V kendinden emişli jet pompa. 9 metreye kadar emiş, 51–75 mSS basma sınıfı, 1" giriş ve çıkış.'],
            ],
            1619 => [
                'short' => ['Pedrollo JSWm 2AX-Y-50 LT Su Pompası, 6 katlı', '6 kat 10 daireye kadar binalar için Pedrollo JSWm 2AX jet pompalı paket hidrofor. 1.5 HP, 220 V, 50 litre tank, 9 metreye kadar emiş, 1" bağlantı.'],
                'title' => ['Pedrollo JSWm 2AX-Y-50 LT Su Pompası Paket Hidrofor 6 Kat', 'Pedrollo JSWm 2AX Paket Hidrofor 50 Litre 6 Kat 10 Daire'],
            ],
            1388 => [
                'short' => ['Kaysu WATER BENDER 3-11 Hidrofor Basınç Şalteri', 'Hidrofor ve pompalar için 3–11 bar ayar aralıklı basınç şalteri. 16 A, 220–250 V, IP44 koruma, 1/4" dişli bağlantı; pompayı basınca göre açıp kapatır.'],
                'title' => ['Kaysu WATER BENDER 3-11 Hidrofor Basınç Şalteri', 'Kaysu Water Bender 3-11 Bar Hidrofor Basınç Şalteri'],
            ],
            2272 => [
                'short' => ['Sumak SYT 32/12, güçlü yapısıyla', 'Sulama, bina ve sanayi hatları için 18.5 kW, 380 V motorlu aküple yatay milli kademeli pompa. GG25 döküm gövde ve çark, DN40 PN16 flanşlı bağlantı.'],
            ],
            2365 => [
                'short' => ['Tarım, inşaat ve gemi sektöründe', 'Su tahliyesi, tarım ve şantiye için 3 HP, 380 V kendinden klapeli pompa. 16–25 m³/h debi, 21–30 mSS basma, 2" giriş ve çıkış, 6 metre emiş.'],
            ],
            1499 => [
                'short' => ['Pedrollo 4 SR 4/18 Dalgıç Pompa, güçlü 2 HP', '4 inç (95 mm) sondaj kuyuları için motorlu derin kuyu dalgıç pompa. 2 HP (1.5 kW), 120 metreye kadar basma, 6 m³/h debi, 1¼" çıkış.'],
                'title' => ['Pedrollo 4 SR 4/18 4 inç Derin Kuyu Dalgıç Pompa Motorlu', 'Pedrollo 4SR 4/18 Derin Kuyu Dalgıç Pompa 2 HP 120 mss'],
            ],
            2284 => [
                'short' => ['Sumak SMT 250/50, 30 kW motor', 'Tarım sulaması ve sanayide yüksek debili su transferi için 30 kW, 2900 d/d motorlu salyangoz (volüt) pompa. Sumak SMT 250 serisi.'],
            ],
            2725 => [
                'short' => ['Winpo WNP - V 1100 F, foseptik su', 'Foseptik çukuru ve atık su tahliyesi için flatörlü dalgıç pompa. 1.5 HP (1.1 kW), 220 V, 2" çıkış, 30 mm\'ye kadar katı geçişi ve 5 m kablo.'],
                'title' => ['Winpo WNP - V 1100 F Flatörlü Foseptik Drenaj Dalgıç Pompa', 'Winpo WNP-V 1100 F Flatörlü Foseptik Dalgıç Pompa 1.5 HP'],
            ],
            2645 => [
                'short' => ['Winpo DWK 300T, paslanmaz gövdesi', 'Kimyasal ve asitli sıvıların transferi için AISI 304 paslanmaz gövdeli pompa. 3 HP, 380 V trifaze, 26–50 m³/h debi, 2½" giriş ve 2" çıkış.'],
            ],
            1365 => [
                'short' => ['Kaysu CUT1500 Bıçaklı Dalgıç Su Pompası (Trifaze)', 'Foseptik ve lifli atık suyu parçalayarak basan bıçaklı (öğütücülü) dalgıç pompa. 2 HP, 380 V trifaze, kumanda panolu, 2" çıkış, 10 m kablo.'],
                'title' => ['Kaysu CUT1500 T Bıçaklı, Öğütücülü Dalgıç Su Pompası', 'Kaysu CUT1500 T Bıçaklı Öğütücülü Dalgıç Pompa 2 HP 380V'],
            ],
            2327 => [
                'short' => ['Sumak SMJ 150 Jet Paket Hidrofor, 24 litre', '5 kat 10 daireye kadar binalar için Sumak SMJ 150 jet pompalı paket hidrofor. 1.5 HP, 220 V, 24 litre tank, 9 metreye kadar emiş.'],
                'title' => ['Sumak SMJ 150 Jet Paket Hidrofor 24 Litre Tanklı', 'Sumak SMJ 150 Paket Hidrofor 5 Kat 10 Daire 24 Litre'],
            ],
            2713 => [
                'short' => ['Winpo WNP 100, 24 litre yatay tankı', '4 kat 4 daireye kadar evler için Winpo WNP 100 ev tipi hidrofor. 1 HP, 220 V, 24 litre yatay tank, döküm gövde ve 8 metreye kadar emiş.'],
                'title' => ['Winpo WNP 100 Ev Tipi Hidrofor 24 Litre Yatay Tanklı', 'Winpo WNP 100 Ev Tipi Hidrofor 4 Kat 4 Daire 24 Lt Yatay'],
            ],
            1379 => [
                'short' => ['Kaysu WETPO DSK2.2 1 Metre Fişli Kablolu Hidromat', 'Pompayı basınca göre otomatik açıp kapatan hidromat. 220–240 V, 10 A, 1,5–3 bar ayarlanabilir devreye giriş, IP65, 1" erkek bağlantı, 1 m fişli kablo.'],
            ],
            1492 => [
                'short' => ['Yüksek performanslı Pedrollo 4 SR 6/31', '4 inç sondaj kuyuları için motorlu derin kuyu dalgıç pompa. 5.5 HP (4 kW), 380 V trifaze, 210 metreye kadar basma, 9 m³/h debi, 1¼" çıkış.'],
                'title' => ['Pedrollo 4 SR 6/31 4 inç Derin Kuyu Dalgıç Pompa Motorlu', 'Pedrollo 4SR 6/31 Derin Kuyu Dalgıç Pompa 5.5 HP 210 mss'],
            ],
            2665 => [
                'short' => ['Winpo CMF 8-40T, 3HP güçte', 'Bina hidroforu, sulama ve proses hatları için AISI 304 paslanmaz çok kademeli santrifüj pompa. 3 HP, 380 V, 71 mSS basma, 10 m³/h debi, 1½" bağlantı.'],
            ],
            1357 => [
                'short' => ['Kaysu WQD1500-S Foseptik Dalgıç Su Pompası, 2 HP', 'Evsel atık su ve foseptik tahliyesi için 2 HP (1.5 kW), 220 V dalgıç pompa. GG25 döküm çark, 2" çıkış, 10 m kablo; şamandırayla otomatik çalışabilir.'],
                'title' => ['Kaysu WQD1500-S Foseptik Dalgıç Su Pompası', 'Kaysu WQD1500-S Foseptik Dalgıç Pompa 2 HP 220V'],
            ],
            1745 => [
                'short' => ['Pedrollo JSWm 2AX - 24CL, 6 kat 10 daire', '6 kat 10 daireye kadar binalar için Pedrollo JSWm 2AX jet pompalı paket hidrofor. 1.5 HP, 220 V, 24 litre tank ve 1" giriş-çıkış.'],
                'title' => ['Pedrollo JSWm 2AX 24CL Su Pompası Paket Hidrofor 6 Kat', 'Pedrollo JSWm 2AX Paket Hidrofor 24 Litre 6 Kat 10 Daire'],
            ],
            2501 => [
                'short' => ['Winpo WNP 408 MF, bahçeniz için', 'Keson kuyu, depo ve sarnıçtan temiz su basmak için 5 inç dalgıç pompa. 1.5 HP (1.1 kW), 220 V, 51–75 mSS basma sınıfı ve 1¼" çıkış.'],
            ],
            2575 => [
                'short' => ['Winpo WNP 90-5 SH Çok Kademeli Santrifüj Pompa, 1.3Hp', 'Hidrofor, sulama ve basınç artırma için 1.3 HP, 220 V çok kademeli santrifüj pompa. 62 mSS basma, 5.4 m³/h debi, 1" bağlantı, termik korumalı.'],
            ],
            1490 => [
                'short' => ['Pedrollo 4 SR 6/17, 3 HP gücüyle', '4 inç sondaj kuyuları için motorlu derin kuyu dalgıç pompa. 3 HP (2.2 kW), 114 metreye kadar basma ve 9 m³/h debi.'],
                'title' => ['Pedrollo 4 SR 6/17 4 inç Derin Kuyu Dalgıç Pompa Motorlu', 'Pedrollo 4SR 6/17 Derin Kuyu Dalgıç Pompa 3 HP 114 mss'],
            ],
            2646 => [
                'short' => ['Winpo DWK 200T, kimyasal ve asit', 'Kimyasal ve asitli sıvıların transferi için AISI 304 paslanmaz gövdeli pompa. 2 HP, 380 V trifaze, 26–50 m³/h debi, 2" giriş ve çıkış.'],
            ],
            1446 => [
                'short' => ['Pedrollo PKM 60, 220V monofaze pompa', 'Ev, bahçe sulama ve küçük hidrofor sistemleri için Pedrollo PKm 60 preferikal pompa. 0.5 HP, 220 V, 40 m basma, 2.4 m³/h debi, 1" bağlantı.'],
            ],
            1507 => [
                'short' => ['Pedrollo 4 PD/10, 10HP gücü', '4 inç derin kuyu pompaları için 10 HP (7.5 kW), 380 V trifaze dalgıç motor. 2900 d/d, paslanmaz mil. Yalnızca motordur; pompa gövdesi ayrıdır.'],
            ],
            1369 => [
                'short' => ['Kaysu HCPF-70 Tek Fanlı Pompa, tek fanlı', 'Sulama ve yüksek debili su transferi için 2 HP, 220 V tek fanlı santrifüj pompa. 33 m³/h debi, 24 mSS basma, 2" giriş ve çıkış, 6 metreye kadar emiş.'],
                'title' => ['Kaysu HCPF-70 Tek Fanlı Pompa', 'Kaysu HCPF-70 Tek Fanlı Santrifüj Pompa 2 HP 220V'],
            ],
            1498 => [
                'short' => ['Pedrollo 4 SR 4/26 Dalgıç Pompa, 3 HP', '4 inç (95 mm) sondaj kuyuları için motorlu derin kuyu dalgıç pompa. 3 HP (2.2 kW), 170 metreye kadar basma, 6 m³/h debi, 1¼" çıkış.'],
                'title' => ['Pedrollo 4 SR 4/26 4 inç Derin Kuyu Dalgıç Pompa Motorlu', 'Pedrollo 4SR 4/26 Derin Kuyu Dalgıç Pompa 3 HP 170 mss'],
            ],
            2292 => [
                'short' => ['Yüksek performanslı Sumak SMT 250/32', 'Tarım sulaması ve sanayide yüksek debili su transferi için 7.5 kW, 2900 d/d motorlu salyangoz (volüt) pompa. Sumak SMT 250 serisi.'],
            ],
        ];
    }
};
