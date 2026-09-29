<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const BRANDS = ['sumak', 'pedrollo', 'winpo', 'kaysu'];

    private const TITLE_LIMIT = 60;

    private const NOUN = '/^(Hidrofor|Hidroforu|Pompa|Pompası|Pompalar|Motoru|Motor|Hidromat|Şalteri|Tankı|Panosu)$/u';

    private const MODS = ['Çok', 'Çift', 'Tek', 'Kademeli', 'Santrifüj', 'Yangın', 'Bina', 'Dalgıç', 'Drenaj', 'Foseptik', 'Jet', 'Su', 'Sirkülasyon', 'Preferikal', 'Paket', 'Asit', 'Kimyasal,', 'Flanşlı', 'Hidrofor'];

    private const BAD_END = '/^([\d.,\/+-]+|ve|ile|-|–|\/|\(.*|Kat|Daire|Lt|mss|m³\/h|HP|İki|Tek|Çift|Üç|Çok|Su|AISI|Açık|Kapalı|Full|,|.*,)$/u';

    public function up(): void
    {
        $rows = DB::table('products')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->whereIn('brands.slug', self::BRANDS)
            ->orderBy('products.id')
            ->get(['products.id', 'products.name', 'products.meta_title', 'products.description', 'products.short_description', 'products.meta_description']);

        $renames = $this->renames();
        $changed = 0;

        foreach ($rows as $row) {
            $oldName = (string) $row->name;
            $name = $oldName;
            if (isset($renames[$row->id]) && $renames[$row->id][0] === $oldName) {
                $name = $renames[$row->id][1];
            }
            $name = $this->fixText(preg_replace('/\s+/u', ' ', trim($name)));

            $update = [];
            if ($name !== $oldName) {
                $update['name'] = $name;
            }

            $currentTitle = $this->stripSuffix(trim((string) $row->meta_title));
            if ($this->isAutoTitle($currentTitle, $oldName)) {
                $update['meta_title'] = $this->buildTitle($name);
            } else {
                $title = str_replace('Periferik', 'Preferikal', $this->fixText($currentTitle));
                if ($title !== $currentTitle) {
                    $update['meta_title'] = $title;
                }
            }

            foreach (['description', 'short_description', 'meta_description'] as $field) {
                $value = $row->{$field};
                if (! is_string($value) || $value === '') {
                    continue;
                }
                $fixed = $this->fixText($value);
                if ($fixed !== $value) {
                    $update[$field] = $fixed;
                }
            }

            if ($update === []) {
                continue;
            }

            $update['updated_at'] = now();
            DB::table('products')->where('id', $row->id)->update($update);
            $changed++;
        }

        if ($changed > 0) {
            try {
                app(UrlIndexingNotifier::class)->clearSitemapCache();
            } catch (\Throwable) {
            }
        }
    }

    public function down(): void {}

    /** @return array<int, array{0: string, 1: string}> */
    private function renames(): array
    {
        return [
            1465 => ['Pedrollo PKM60 İtalyan Ev Tipi 2 Kat 2 Daire Hidrofor, Bağ Bahçe Sulama ve Basınç Arttırıcı Hidrofor', 'Pedrollo PKM60 Ev Tipi Hidrofor 2 Kat 2 Daire Bağ Bahçe Sulama'],
            2676 => ['Winpo WNP 200 7 Kat 10 Daire Hidrofor, 50 Litre Yatay Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 200 Ev Tipi Hidrofor 7 Kat 10 Daire 50 Litre Yatay Tanklı'],
            2677 => ['Winpo WNP 150 5 Kat 5 Daire Hidrofor, 50 Litre Yatay Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 150 Ev Tipi Hidrofor 5 Kat 5 Daire 50 Litre Yatay Tanklı'],
            2678 => ['Winpo WNP 150 5 Kat 5 Daire Hidrofor, 24 Litre Yatay Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 150 Ev Tipi Hidrofor 5 Kat 5 Daire 24 Litre Yatay Tanklı'],
            2679 => ['Winpo WNP 150 5 Kat 5 Daire Hidrofor, 24 Litre Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 150 Ev Tipi Hidrofor 5 Kat 5 Daire 24 Litre Tanklı'],
            2680 => ['Winpo WNP 150 5 Kat 5 Daire Hidrofor, Hidromatlı Paket Hidrofor', 'Winpo WNP 150 Hidromatlı Paket Hidrofor 5 Kat 5 Daire'],
            2712 => ['Winpo WNP 100 4 Kat 4 Daire Hidrofor, 50 Litre Yatay Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 100 Ev Tipi Hidrofor 4 Kat 4 Daire 50 Litre Yatay Tanklı'],
            2713 => ['Winpo WNP 100 4 Kat 4 Daire Hidrofor, 24 Litre Yatay Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 100 Ev Tipi Hidrofor 4 Kat 4 Daire 24 Litre Yatay Tanklı'],
            2714 => ['Winpo WNP 100 4 Kat 4 Daire Hidrofor, 24 Litre Tanklı Hidrofor, Ev Tipi Hidrofor', 'Winpo WNP 100 Ev Tipi Hidrofor 4 Kat 4 Daire 24 Litre Tanklı'],
            2715 => ['Winpo WNP 100 4 Kat 4 Daire Hidrofor, Hidromatlı Paket Hidrofor', 'Winpo WNP 100 Hidromatlı Paket Hidrofor 4 Kat 4 Daire'],
        ];
    }

    /** Ad, açıklama ve başlıkta ortak yazım düzeltmeleri; URL/slug parçalarına dokunmaz. */
    private function fixText(string $s): string
    {
        $map = [
            '/PedrolloCP/u' => 'Pedrollo CP',
            '/Santrafüj/u' => 'Santrifüj',
            '/santrafüj/u' => 'santrifüj',
            '/SantrifüjPompa/u' => 'Santrifüj Pompa',
            '/\bSantrifuj(?= )/u' => 'Santrifüj',
            '/Dalğıç/u' => 'Dalgıç',
            '/dalğıç/u' => 'dalgıç',
            '/\bManofaze\b/u' => 'Monofaze',
            '/\bmanofaze\b/u' => 'monofaze',
            '/\bMonofoze\b/u' => 'Monofaze',
            '/\bmonofoze\b/u' => 'monofaze',
            '/\bDerinkuyu(?= )/u' => 'Derin Kuyu',
            '/\bHidrofu\b/u' => 'Hidroforu',
            '/\b(Monofaze|Trifaze)\(/u' => '$1 (',
            '/(\d)mss/u' => '$1 mss',
            '/\bmss(\d)/u' => 'mss $1',
            '/(\d)m³\/h/u' => '$1 m³/h',
            '/(\d)m³h\b/u' => '$1 m³/h',
            '/Bıçaklı,Öğütücülü/u' => 'Bıçaklı, Öğütücülü',
            '/FlatörlüDrenaj/u' => 'Flatörlü Drenaj',
        ];

        return preg_replace(array_keys($map), array_values($map), $s);
    }

    private function stripSuffix(string $title): string
    {
        do {
            $before = $title;
            $title = trim(preg_replace('/\s*[|\-–—]\s*Ko[sş]ar(?: Ticaret)?\s*$/iu', '', $title));
        } while ($title !== $before);

        return $title;
    }

    private function isAutoTitle(string $title, string $name): bool
    {
        if ($title === '') {
            return true;
        }
        $norm = fn (string $v) => mb_strtolower(str_replace(', ', ',', preg_replace('/\s+/u', ' ', trim($v))));

        return str_starts_with($norm($name), $norm($title));
    }

    private function buildTitle(string $name): string
    {
        $map = [
            '/(\d)\s?(HP|Hp|hp)\b/u' => '$1 HP',
            '/\b(?:Monofaze|Trifaze)\s*\((\d{3})\s*(?:V|Volt)\)/u' => '$1V',
            '/\b(\d{3})\s*(?:V|Volt)\s+(?:Monofaze|Trifaze)\b/u' => '$1V',
            '/\b(?:Monofaze|Trifaze)\s+(\d{3})\s*(?:V|Volt)\b/u' => '$1V',
            '/\b(\d{3}) Volt\b/u' => '$1V',
            '/\bLitre\b/u' => 'Lt',
            '/\bKAT\b/u' => 'Kat',
            '/\bDAİRE\b/u' => 'Daire',
            '/\s+-\s+(?=\d)/u' => ' ',
            '/(\d)\s?m3\/h\b/u' => '$1 m³/h',
            '/(\d)\s?m³h\b/u' => '$1 m³/h',
            '/\b(?:Mss|MSS)\b/u' => 'mss',
            '/\s{2,}/u' => ' ',
        ];
        $s = trim(preg_replace(array_keys($map), array_values($map), $name));

        return $this->cut($this->slim($s));
    }

    private function slim(string $s): string
    {
        $drops = [
            '/\s\d{3,4}\s?d\/d\b/u',
            '/\s\d+\s?Mt Kablolu\b/u',
            '/\sPaslanmaz Çark ve Difüzörlü\b/u',
            '/\sRijit Kaplinli\b/u',
            '/\sKomple(?= Paslanmaz)/u',
            '/\s(?:İNOX|INOX)(?= (?:Komple )?Paslanmaz)/u',
            'FK',
            'INV',
            '/\s(?:Düşey|Dik|Yatay) Milli(?=\s(?:\S+\s){0,2}Kademeli)/u',
            '/\sSerisi Motorlu\b/u',
            '/\sTermoplastik Tanklı\b/u',
            '/\s(?:Döküm|Plastik|Bronz|Pik Döküm) Gövdeli\b/u',
            '/(?<=Paslanmaz) Gövdeli?\b/u',
            '/\sFull(?= Paslanmaz)/u',
            '/\s(?:Kendinden|Sıfırdan|Yandan|Derinden) Emişli\b/u',
            '/\sEmişli\b/u',
            '/\sYükseklik\b/u',
            '/\s\d+(?:[.,]\d+)?\s?(?:Lt|L|LT)\.? (?:Yatık |Yatay |Küre )?Tanklı\b/u',
        ];
        foreach ($drops as $re) {
            if (mb_strlen($s) <= self::TITLE_LIMIT) {
                break;
            }
            if ($re === 'FK') {
                if (preg_match('/-?FK\b/u', $s)) {
                    $s = str_replace(' Frekans Kontrollü', '', $s);
                }

                continue;
            }
            if ($re === 'INV') {
                if (preg_match('/\b[Iİ]NV\b/u', $s)) {
                    $s = str_replace(' Frekans Konvertörlü', '', $s);
                }

                continue;
            }
            $s = trim(preg_replace('/\s{2,}/u', ' ', preg_replace($re, '', $s)));
        }

        return $s;
    }

    private function cut(string $s): string
    {
        if (mb_strlen($s) <= self::TITLE_LIMIT) {
            return $s;
        }
        $full = $s;
        $s = $this->tailCut($s);
        foreach (explode(' ', $s) as $w) {
            if (preg_match(self::NOUN, $w)) {
                return $s;
            }
        }

        $words = explode(' ', $full);
        $nounIdx = null;
        foreach ($words as $i => $w) {
            if (preg_match(self::NOUN, $w)) {
                $nounIdx = $i;
                break;
            }
        }
        if ($nounIdx === null) {
            return $s;
        }
        $start = $nounIdx;
        while ($start > 1 && $nounIdx - $start < 3 && in_array($words[$start - 1], self::MODS, true)) {
            $start--;
        }
        $phrase = implode(' ', array_slice($words, $start, $nounIdx - $start + 1));
        $head = [];
        foreach (array_slice($words, 0, $start) as $w) {
            if (mb_strlen(implode(' ', array_merge($head, [$w]))) + 1 + mb_strlen($phrase) > self::TITLE_LIMIT) {
                break;
            }
            $head[] = $w;
        }
        while (count($head) > 2 && preg_match(self::BAD_END, end($head))) {
            array_pop($head);
        }
        $s = rtrim(implode(' ', $head), ' ,-/').' '.$phrase;

        return $this->tailCut(trim($s.' '.implode(' ', array_slice($words, $nounIdx + 1))));
    }

    private function tailCut(string $s): string
    {
        if (mb_strlen($s) <= self::TITLE_LIMIT) {
            return $s;
        }
        $acc = [];
        foreach (explode(' ', $s) as $w) {
            if (mb_strlen(implode(' ', array_merge($acc, [$w]))) > self::TITLE_LIMIT) {
                break;
            }
            $acc[] = $w;
        }
        while (count($acc) > 3) {
            $last = end($acc);
            $prev = $acc[count($acc) - 2];
            $unpairedParen = str_contains($last, '(') && ! str_contains($last, ')');
            if (! $unpairedParen && ! preg_match(self::BAD_END, $last)) {
                break;
            }
            if (! $unpairedParen && in_array($last, ['Kat', 'Daire', 'Lt', 'mss', 'm³/h', 'HP'], true) && preg_match('/^[\d.,]+$/u', $prev)) {
                break;
            }
            array_pop($acc);
        }
        while (count($acc) > 3 && substr_count(implode(' ', $acc), '(') > substr_count(implode(' ', $acc), ')')) {
            array_pop($acc);
        }

        return rtrim(implode(' ', $acc), ' ,-/');
    }
};
