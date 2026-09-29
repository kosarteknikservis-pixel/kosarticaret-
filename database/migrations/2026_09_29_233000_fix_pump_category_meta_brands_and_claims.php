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

        foreach ($this->updates() as $id => $fields) {
            $current = DB::table('categories')->where('id', $id)->first(array_keys($fields));
            if ($current === null) {
                $this->misses[$id][] = 'kategori yok';

                continue;
            }

            $data = [];
            foreach ($fields as $column => [$expectedPrefix, $value]) {
                $old = (string) $current->{$column};
                if ($old === $value) {
                    continue;
                }
                if (! str_starts_with($old, $expectedPrefix)) {
                    $this->misses[$id][] = $column;

                    continue;
                }
                $data[$column] = $value;
            }

            if ($data !== []) {
                DB::table('categories')->where('id', $id)->update($data + ['updated_at' => now()]);
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

    /** @return array<int, array<string, array{0: string, 1: string}>> */
    private function updates(): array
    {
        return [
            128 => [
                'meta_title' => ['Hidrofor Fiyatları: Pedrollo, Sumak', 'Hidrofor Fiyatları ve Modelleri | Koşar Ticaret'],
                'meta_description' => ['Ev ve apartman tipi hidrofor sistemleri. Pedrollo ve Sumak', 'Ev tipi paket hidroforlar, sessiz frekanslı modeller ve bina hidroforları. Sumak SM ve SMJ, Pedrollo JSWm, Winpo WNP ve Kaysu HKJM; 24–100 litre tanklı.'],
            ],
            119 => [
                'meta_description' => ['Ev tipi, çok katlı apartman ve sanayi hidrofor sistemleri. Pedrollo ve Sumak', 'Ev tipi hidrofor, bina hidroforu, hidrofor grubu ve hidromat. Sumak, Pedrollo, Winpo ve Kaysu modelleri; kat ve daire sayısına göre seçim desteği.'],
            ],
            130 => [
                'meta_description' => ['Temiz su, drenaj, foseptik, kirli su ve derin kuyu dalgıç pompaları. Pedrollo ve Sumak', 'Temiz su, drenaj, kirli su, foseptik ve derin kuyu dalgıç pompaları. Pedrollo, Sumak, Winpo ve Kaysu; monofaze ve trifaze, flatörlü modeller.'],
            ],
            132 => [
                'meta_description' => ['Tek fanlı, çift fanlı, paslanmaz çelik ve salyangoz', 'Tek fanlı, çift fanlı, paslanmaz ve salyangoz santrifüj pompalar. Pedrollo, Sumak ve Winpo modelleri; sulama, bina tesisatı ve sanayi uygulamaları.'],
            ],
            140 => [
                'meta_description' => ['Çok katlı bina ve sanayi için in-line dikey kademeli', 'Bina hidroforu, sulama ve proses hatları için dik milli kademeli pompalar: Pedrollo MK, Sumak SHM ve SHT, Winpo WNP VM ve CVL. Monofaze ve trifaze modeller.'],
            ],
            133 => [
                'meta_description' => ['Sulama, bina tesisatı ve sanayi için tek kademeli santrifüj', 'Sulama, bina tesisatı ve sanayi için tek fanlı santrifüj pompalar. Pedrollo ve Sumak başta olmak üzere monofaze ve trifaze modeller, teknik seçim desteği.'],
            ],
            127 => [
                'meta_description' => ['Güneş enerjisi ve merkezi sıcak su tesisatı için EPDM', 'Güneş enerjisi ve sıcak su tesisatında basınç için sıcak su hidroforları: Sumak SM 7-SH ve Winpo WNP 226 modelleri, 1" bağlantılı.'],
            ],
            122 => [
                'meta_description' => ['8-35 metre derinlikte kuyu suyu için jet pompalar', 'Kuyu, depo ve şebekeden su basmak için kendinden emişli jet pompalar: Pedrollo JSWm ve JCRm, Sumak SMJ, Winpo WNP ve Kaysu HKJM. Monofaze ve trifaze.'],
            ],
            135 => [
                'meta_description' => ['Havuz, deniz suyu ve kimyasal atık su için AISI', 'Temiz ve az kirli su tahliyesi için paslanmaz gövdeli drenaj dalgıç pompaları: Pedrollo RX ve RXm, Sumak SDF, Winpo WNP QCK. Flatörlü ve gizli flatörlü.'],
            ],
            158 => [
                'meta_description' => ['Tarım sulaması, yangın rezervuarı ve sanayi için salyangoz', 'Tarım sulaması ve sanayi için Sumak SMT 250 serisi motorlu salyangoz pompalar. 7.5–55 kW motor gücü, 2900 d/d, yüksek debi.'],
            ],
            162 => [
                'meta_description' => ['Elektriksiz tarım ve şantiye için dizel motorlu', 'Elektriğin olmadığı tarla ve şantiyeler için dizel motorlu su pompaları: Sumak SMKT, SMT, SYT ve DSM motopomp modelleri, mazotlu çalışma.'],
            ],
            151 => [
                'meta_description' => ['Yüzme havuzu sirkülasyonu için sepet filtreli', 'Yüzme havuzu filtrasyon ve sirkülasyonu için ön filtreli havuz pompaları: Sumak SMH ve SMHT, Winpo Pool-1 ve Pool-2. Monofaze ve trifaze, 0.5–4 HP.'],
            ],
            143 => [
                'meta_description' => ['Fosseptik ve pissu tahliyesi için bıçaklı', 'Foseptik ve pis su tahliyesi için bıçaklı (öğütücülü) dalgıç pompalar: Sumak SBRM ve SBRT, Pedrollo TR, Winpo WNP GR ve Kaysu CUT modelleri.'],
            ],
            148 => [
                'meta_description' => ['Şantiye, kazı alanı ve çamurlu su tahliyesi için kirli su', 'Şantiye, bodrum ve drenaj kanalında kirli su tahliyesi için dalgıç pompalar: Sumak SDF, SDT ve SDTV, Winpo WNP ve QDP, Kaysu HWD. Flatörlü modeller.'],
            ],
        ];
    }
};
