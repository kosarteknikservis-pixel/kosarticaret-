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
        $ids = array_unique(array_merge(array_keys($this->tables()), array_keys($this->values())));
        $rows = DB::table('products')->whereIn('id', $ids)->pluck('specs', 'id');
        $changed = 0;

        foreach ($ids as $id) {
            if (! $rows->has($id)) {
                $this->misses[$id][] = 'ürün yok';

                continue;
            }

            $specs = json_decode((string) $rows[$id], true);
            if (! is_array($specs)) {
                $this->misses[$id][] = 'specs okunamadı';

                continue;
            }
            $original = $specs;

            if (isset($this->tables()[$id])) {
                ['from' => $from, 'to' => $to] = $this->tables()[$id];
                if ($specs === $from) {
                    $specs = $to;
                } elseif ($specs !== $to) {
                    $this->misses[$id][] = 'tablo';
                }
            }

            foreach ($this->values()[$id] ?? [] as [$key, $from, $to]) {
                $current = $specs[$key] ?? null;
                if ($current === $from) {
                    $specs[$key] = $to;
                } elseif ($current !== $to) {
                    $this->misses[$id][] = $key;
                }
            }

            if ($specs !== $original) {
                DB::table('products')->where('id', $id)->update(['specs' => json_encode($specs), 'updated_at' => now()]);
                $changed++;
            }
        }

        if ($changed > 0) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        // Veri düzeltmesi geri alınmaz; eski değerler bu dosyadaki "from" alanlarında durur.
    }

    /** Satırları kaymış tablolar: başlık ile değer yer değiştirmiş. */
    private function tables(): array
    {
        return [
            2415 => ['from' => ['Motor Gücü' => 'Voltaj', '3 Hp (2.2 kW)' => '380V (Trifaze)'], 'to' => ['Motor Gücü' => '3 Hp (2.2 kW)', 'Voltaj' => '380V (Trifaze)']],
            1985 => ['from' => ['Tip' => 'kW', '5SD7' => '1.1'], 'to' => ['Güç' => '1.1 kW']],
            1986 => ['from' => ['Tip' => 'kW', '5SD5' => '0.75'], 'to' => ['Güç' => '0.75 kW']],
            1983 => ['from' => ['Tip' => 'kW', '5SDT7' => '1.1'], 'to' => ['Güç' => '1.1 kW']],
            1929 => ['from' => ['Tip' => 'Hacim (lt)', 'SMAC 1800 A' => '150'], 'to' => ['Tank Hacmi' => '150 lt']],
            1928 => ['from' => ['Tip' => 'Hacim (lt)', 'SMAC 2200 A' => '200'], 'to' => ['Tank Hacmi' => '200 lt']],
            1927 => ['from' => ['Tip' => 'Hacim (lt)', 'SMAC 2200 B' => '500'], 'to' => ['Tank Hacmi' => '500 lt']],
            1786 => ['from' => ['Model' => 'Güç (W)', 'SMAC700 BR' => '700'], 'to' => ['Güç' => '700 W']],
            2119 => ['from' => ['Tip' => 'Güç (W)', 'STN750 G' => '36'], 'to' => ['Güç' => '36 W']],
            2440 => ['from' => ['Tip' => 'Devir', 'SMKT 750/2 DJY' => '3000'], 'to' => ['Devir' => '3000 d/d']],
            2122 => ['from' => ['Ürün Tipi' => 'KW', 'SSP50/12 INV' => '0.035'], 'to' => ['Güç' => '0.035 kW']],
            1876 => ['from' => ['MOTOR' => 'POMPA TİP', 'SSP50/15 INV' => '0.075'], 'to' => ['Motor Gücü' => '0.075 kW']],
            1874 => ['from' => ['Model' => 'Güç (KW)', 'SSP65/10 INV' => '0.06'], 'to' => ['Güç' => '0.06 kW']],
        ];
    }

    /** Eski filtre aralığı yerine ürün adındaki kesin değer; birim ve yazım hataları. */
    private function values(): array
    {
        return [
            2640 => [
                ['Motor Gücü', '1.5 Hp (0.75 kW)', '1.5 Hp (1.1 kW)'],
            ],
            2318 => [
                ['Giriş/Çıkış', '3" (50 mm)', '3" (80 mm)'],
            ],
            2302 => [
                ['Güç', '2 HP (1.5 HP)', '2 HP (1.5 kW)'],
            ],
            1616 => [
                ['Voltaj', '220 Volt', '380 Volt (Trifaze)'],
            ],
            1419 => [
                ['Maksimum Debi', '0-5 m³/h', '2.4 m³/h'],
                ['Maksimum Yükseklik', '31-40 mSS', '40 mSS'],
            ],
            1438 => [
                ['Max. Debi', '6-10 m³/h', '7.2 m³/h'],
                ['Maks. Yükseklik', '31-40 mss', '37 mss'],
            ],
            1479 => [
                ['Maksimum Debi', '26-50 m³/h', '27 m³/h'],
            ],
            1527 => [
                ['Max. Debi', '6-10 m³/h', '7.5 m³/h'],
                ['Max. Yükseklik', '11-20 mss', '16.5 mss'],
            ],
            1528 => [
                ['Max. Debi', '6-10 m³/h', '7.5 m³/h'],
                ['Max. Yükseklik', '11-20 mss', '16.5 mss'],
            ],
            1543 => [
                ['Maks. Debi', '11-15 m³/h', '15 m³/h'],
            ],
            1551 => [
                ['Maksimum Debi', '11-15 m³/h', '13.8 m³/h'],
            ],
            1561 => [
                ['Debi Aralığı', '6-10 m³/h', '7.2 m³/h'],
            ],
            1570 => [
                ['Max. Debi', '51-75 m³/h', '54 m³/h'],
                ['Max. Yükseklik', '51-75 mss', '59 mss'],
            ],
            1571 => [
                ['Maksimum Debi', '51-75 m³/h', '54 m³/h'],
                ['Maksimum Yükseklik', '51-75 mSS', '51 mSS'],
            ],
            1579 => [
                ['Maksimum Debi', '6-10 m³/h', '7.8 m³/h'],
            ],
            1623 => [
                ['Max. Yükseklik', '21-30 mss', '26 mss'],
            ],
            1700 => [
                ['Debi Aralığı', '26-50 m³/h', '30 m³/h'],
            ],
            1710 => [
                ['Maksimum Debi', '51-75 m³/h', '54 m³/h'],
            ],
            1711 => [
                ['Maks. Debi', '51-75 m³/h', '51 m³/h'],
            ],
            1713 => [
                ['Maksimum Debi', '26-50 m³/h', '27 m³/h'],
                ['Maksimum Yükseklik', '41-50 mSS', '49 mSS'],
            ],
            1714 => [
                ['Maksimum Debi', '26-50 m³/h', '27 m³/h'],
            ],
            1720 => [
                ['Maks. Debi', '11-15 m³/h', '15 m³/h'],
            ],
            1727 => [
                ['Maksimum Debi', '0-5 m³/h', '4.8 m³/h'],
                ['Maksimum Yükseklik', '31-40 mss', '40 mss'],
            ],
            1743 => [
                ['Max. Debi', '6-10 m³/h', '7.2 m³/h'],
            ],
            1760 => [
                ['Maksimum Yükseklik', '31-40 mss', '40 mss'],
            ],
            1761 => [
                ['Maks. Yükseklik', '41-50 mSS', '42 mSS'],
            ],
            1794 => [
                ['Maks. Yükseklik (mSS)', '11-20 mSS', '19.5 mSS'],
            ],
            1795 => [
                ['Maks. Yükseklik (mSS)', '11-20 mSS', '16.5 mSS'],
            ],
            1796 => [
                ['Maks. Yükseklik', '11-20 mSS', '13 mSS'],
            ],
            1797 => [
                ['Maks. Yükseklik', '11-20 mss', '16.5 mss'],
            ],
            1798 => [
                ['Maks. Yükseklik', '11-20 mss', '13 mss'],
            ],
            1799 => [
                ['Maks. Yükseklik (mSS)', '21-30 mSS', '23.5 mSS'],
            ],
            1801 => [
                ['Maks. Yükseklik', '11-20 mss', '16.5 mss'],
            ],
            1803 => [
                ['Maks. Yükseklik (mSS)', '11-20 mss', '16.5 mss'],
            ],
            1804 => [
                ['Maks. Yükseklik (mSS)', '31-40 mSS', '32 mSS'],
            ],
            1805 => [
                ['Maks. Yükseklik', '21-30 mSS', '27 mSS'],
            ],
            1806 => [
                ['Maks. Yükseklik', '21-30 mss', '23 mss'],
            ],
            1807 => [
                ['Maks. Yükseklik (mSS)', '21-30 mSS', '27 mSS'],
            ],
            1808 => [
                ['Maks. Yükseklik mSS', '21-30 mss', '23 mss'],
            ],
            1817 => [
                ['Max Debi', '6-10 m³/h', '6 m³/h'],
            ],
            1818 => [
                ['Kapasite (m³/h)', '6-10 m³/h', '6 m³/h'],
            ],
            1819 => [
                ['Max. Debi', '6-10 m³/h', '6 m³/h'],
            ],
            1824 => [
                ['Kapasite', '0-5 m³/h', '4 m³/h'],
            ],
            1950 => [
                ['Max. Debi', '11-15 m³/h', '14 m³/h'],
            ],
            1975 => [
                ['Kat Sayısı', '11-20 Kat', '20 Kat'],
            ],
            2015 => [
                ['Kat Sayısı', '11 - 20', '17'],
            ],
            2059 => [
                ['Kat Sayısı', '11-20 Kat', '17 Kat'],
            ],
            2070 => [
                ['Kat Sayısı', '21-40 Kat', '22 Kat'],
            ],
            2074 => [
                ['Kat Sayısı', '11-20 Kat', '14 Kat'],
            ],
            2081 => [
                ['Kat Sayısı', '11-20 Kat', '14 Kat'],
            ],
            2082 => [
                ['Kat Sayısı', '11-20 Kat', '11 Kat'],
            ],
            2084 => [
                ['Kat Sayısı', '21-40 Kat', '22 Kat'],
            ],
            2095 => [
                ['Kat Sayısı', '11-20', '14'],
            ],
            2096 => [
                ['Kat Sayısı', '11-20 Kat', '11 Kat'],
            ],
            2099 => [
                ['Kat Sayısı', '11-20 Kat', '19 Kat'],
            ],
            2103 => [
                ['Kat Sayısı', '11-20 Kat', '11 Kat'],
            ],
            2109 => [
                ['Kat Sayısı', '11-20 Kat', '14 Kat'],
            ],
            2110 => [
                ['Kat Sayısı', '11-20 Kat', '11 Kat'],
            ],
            2115 => [
                ['Max Yükseklik', '51-75 mss', '55 mss'],
            ],
            2120 => [
                ['Max. Debi', '0-5 m³/h', '2 m³/h'],
                ['Max. Yükseklik', '21-30 mSS', '30 mSS'],
            ],
            2326 => [
                ['Daire Sayısı', '16-25 Daire', '16 Daire'],
            ],
            2512 => [
                ['Basma Yüksekliği', '21-30 mss', '25 mss'],
            ],
            2513 => [
                ['Basma Yüksekliği (mss)', '11-20 mss', '20 mss'],
            ],
            2514 => [
                ['Basma Yüksekliği (mss)', '11-20 mss', '16 mss'],
            ],
            2516 => [
                ['Basma Yüksekliği', '1-10 mss', '10 mss'],
            ],
            2517 => [
                ['Basma Yüksekliği', '1-10 mss', '9 mss'],
            ],
            2530 => [
                ['Kat Sayısı', '11-20 Kat', '17 Kat'],
            ],
            2531 => [
                ['Kat Sayısı', '11-20 Kat', '13 Kat'],
            ],
            2532 => [
                ['Kat Sayısı', '11-20 Kat', '16 Kat'],
                ['Daire Sayısı', '41-60 Daire', '42 Daire'],
            ],
            2533 => [
                ['Kat Sayısı', '11-20 Kat', '16 Kat'],
                ['Daire Sayısı', '41-60 Daire', '42 Daire'],
            ],
            2534 => [
                ['Kat Sayısı', '11-20 Kat', '13 Kat'],
            ],
            2535 => [
                ['Kat Sayısı', '11-20 Kat', '13 Kat'],
            ],
            2536 => [
                ['Kat Sayısı', '11-20 Kat', '15 Kat'],
            ],
            2537 => [
                ['Kat Sayısı', '11-20 Kat', '15 Kat'],
            ],
            2540 => [
                ['Kat Sayısı', '11-20 Kat', '11 Kat'],
            ],
            2543 => [
                ['Kat Sayısı', '11-20 Kat', '20 Kat'],
                ['Daire Sayısı', '41-60 Daire', '42 Daire'],
            ],
            2546 => [
                ['Kat Sayısı', '11-20 Kat', '12 Kat'],
                ['Daire Sayısı', '16-25 Daire', '24 Daire'],
            ],
            2547 => [
                ['Kat Sayısı', '11-20 Kat', '12 Kat'],
                ['Daire Sayısı', '16-25 Daire', '24 Daire'],
            ],
            2550 => [
                ['Kat Sayısı', '11-20 Kat', '12 Kat'],
            ],
            2555 => [
                ['Max. Debi', '11-15 m³/h', '14 m³/h'],
            ],
            2556 => [
                ['Max. Debi', '11-15 m³/h', '14 m³/h'],
                ['Max. Yükseklik', '101-150 mss', '101 mss'],
            ],
            2558 => [
                ['Max. Debi', '6-10 m³/h', '6 m³/h'],
                ['Max. Yükseklik', '101-150 mss', '126 mss'],
            ],
            2559 => [
                ['Max. Debi', '6-10 m³/h', '6 m³/h'],
                ['Max. Yükseklik', '76-100 mss', '86 mss'],
            ],
            2560 => [
                ['Debi Miktarı', '16-25 m³/h', '22 m³/h'],
            ],
            2564 => [
                ['Max. Debi', '11-15 m³/h', '14 m³/h'],
                ['Max. Yükseklik', '101-150 mss', '101 mss'],
            ],
            2566 => [
                ['Max. Debi', '6-10 m³/h', '6.5 m³/h'],
                ['Max. Yükseklik', '101-150 mSS', '106 mSS'],
            ],
            2578 => [
                ['Maksimum Debi', '51-75 m³/h', '55 m³/h'],
                ['Maksimum Yükseklik', '51-75 mSS', '56 mSS'],
            ],
            2580 => [
                ['Max. Debi', '51-75 m³/h', '55 m³/h'],
                ['Max. Yükseklik', '31-40 mss', '38 mss'],
            ],
            2599 => [
                ['Maks. Debi', '76-100 m³/h', '100 m³/h'],
                ['Maks. Yükseklik', '21-30 mss', '26 mss'],
            ],
            2603 => [
                ['Max Debi', '51-75 m³/h', '55 m³/h'],
            ],
            2605 => [
                ['Max. Debi', '51-75 m³/h', '55 m³/h'],
                ['Maks. Yükseklik', '31-40 mss', '38 mss'],
            ],
            2606 => [
                ['Maksimum Debi', '51-75 m³/h', '55 m³/h'],
            ],
            2618 => [
                ['Maks. Debi', '6-10 m³/h', '10 m³/h'],
            ],
            2619 => [
                ['Maks. Debi', '6-10 m³/h', '10 m³/h'],
            ],
            2620 => [
                ['Maks. Debi', '6-10 m³/h', '10 m³/h'],
            ],
            2621 => [
                ['Maks. Debi (m³/h)', '6-10 m³/h', '10 m³/h'],
                ['Maks. Yükseklik (mSS)', '41-50 mSS', '49 mSS'],
            ],
            2622 => [
                ['Max. Debi', '6-10 m³/h', '6.5 m³/h'],
            ],
            2631 => [
                ['Maks. Debi', '6-10 m³/h', '7 m³/h'],
                ['Maks. Yükseklik', '51-75 mSS', '59 mSS'],
            ],
            2633 => [
                ['Maks. Debi (m³/h)', '11-15 m³/h', '14 m³/h'],
                ['Maks. Yükseklik (mSS)', '31-40 mSS', '35 mSS'],
            ],
            2634 => [
                ['Maksimum Debi', '11-15 m³/h', '14 m³/h'],
            ],
            2635 => [
                ['Max. Debi', '11-15 m³/h', '11.4 m³/h'],
                ['Max. Yükseklik', '31-40 mss', '31 mss'],
            ],
            2636 => [
                ['Maks. Debi', '11-15 m³/h', '11.4 m³/h'],
                ['Maks. Yükseklik', '21-30 mss', '28 mss'],
            ],
            2637 => [
                ['Maksimum Debi', '11-15 m³/h', '11.4 m³/h'],
                ['Max. Yükseklik', '21-30 mSS', '28 mSS'],
            ],
            2665 => [
                ['Maksimum Debi', '6-10 m³/h', '10 m³/h'],
            ],
            2668 => [
                ['Maks. Debi', '6-10 m³/h', '10 m³/h'],
            ],
            2718 => [
                ['Basma Yüksekliği (mss)', '11-20 mss', '16 mss'],
            ],
            2719 => [
                ['Basma Yüksekliği', '31-40 mss', '40 mss'],
            ],
            2722 => [
                ['Basma Yüksekliği', '1-10 mss', '10 mss'],
            ],
            2723 => [
                ['Basma Yüksekliği', '1-10 mss', '8 mss'],
            ],
            2724 => [
                ['Maks. Debi', '11-15 m³/h', '14.4 m³/h'],
                ['Maks. Yükseklik', '21-30 mss', '21 mss'],
            ],
            2733 => [
                ['Max Debi', '0-5 m³/h', '3 m³/h'],
            ],
            2735 => [
                ['Basma Yüksekliği', '1-10 mss', '9 mss'],
            ],
            2740 => [
                ['Max. Debi', '0-5 m³/h', '4.2 m³/h'],
                ['Max. Yükseklik', '51-75 mss', '52 mss'],
            ],
            2742 => [
                ['Max. Debi', '0-5 m³/h', '3 m³/h'],
            ],
        ];
    }
};
