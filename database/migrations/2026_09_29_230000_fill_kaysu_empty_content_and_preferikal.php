<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SITE = 'https://kosarticaret.com';

    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const PREFERIKAL_PRODUCTS = [1366, 1405, 1407, 1419, 1445, 1465, 1735];

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $changed = 0;

        foreach ($this->descriptions() as $id => $html) {
            $row = DB::table('products')->where('id', $id)->first(['description', 'specs']);
            if (! $row) {
                $this->misses[$id][] = 'ürün yok';

                continue;
            }
            $update = [];
            if (trim(strip_tags((string) $row->description)) === '') {
                $update['description'] = $html;
            } else {
                $this->misses[$id][] = 'açıklama dolu, dokunulmadı';
            }
            $specs = json_decode((string) $row->specs, true);
            if (empty($specs)) {
                $update['specs'] = json_encode($this->specs()[$id], JSON_UNESCAPED_UNICODE);
            } else {
                $this->misses[$id][] = 'specs dolu, dokunulmadı';
            }
            $changed += $this->save($id, $update);
        }

        foreach ($this->shortDescriptions() as $id => [$expectedPrefix, $text]) {
            $current = trim((string) DB::table('products')->where('id', $id)->value('short_description'));
            $replaceable = $expectedPrefix === '' ? $current === '' : str_starts_with($current, $expectedPrefix);
            if (! $replaceable) {
                $this->misses[$id][] = 'kısa açıklama beklenenden farklı, dokunulmadı';

                continue;
            }
            $changed += $this->save($id, ['short_description' => $text]);
        }

        foreach (self::PREFERIKAL_PRODUCTS as $id) {
            $row = DB::table('products')->where('id', $id)->first(['description', 'short_description', 'meta_description']);
            if (! $row) {
                continue;
            }
            $update = [];
            foreach (['description', 'short_description', 'meta_description'] as $field) {
                $old = (string) $row->$field;
                $new = preg_replace('/(P|p)eriferik(?:al)?(?![a-zçğıöşü])/u', '$1referikal', $old) ?? $old;
                if ($new !== $old) {
                    $update[$field] = $new;
                }
            }
            if ($update === []) {
                $this->misses[$id][] = 'Periferik bulunamadı';
            }
            $changed += $this->save($id, $update);
        }

        if ($changed > 0) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        // İçerik güncellemesi geri alınmaz.
    }

    /** @param array<string, string> $update */
    private function save(int $id, array $update): int
    {
        if ($update === []) {
            return 0;
        }
        $update['updated_at'] = now();
        DB::table('products')->where('id', $id)->update($update);

        return 1;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function shortDescriptions(): array
    {
        return [
            1374 => ['Kaysu QDX6-32-1.5B Dalgıç Pompa, suyun dalgıç olarak', 'Keson kuyu, sarnıç ve depolardan temiz su basmak için 1.5 kW (2 HP), 220 V döküm gövdeli dalgıç pompa. Yaklaşık 32 m basma, 1¼" çıkış.'],
            1375 => ['Kaysu QDX6-39/3-1.5B Dalgıç Pompa, derin kuyulardan', 'Keson kuyu ve depolardan yüksek noktalara temiz su basmak için 3 kademeli, 1.5 kW (2 HP) döküm gövdeli dalgıç pompa. Yaklaşık 39 m basma, 2" çıkış.'],
            1405 => ['', 'Tek katlı ev ve yazlıklar için 0.5 HP, 220 V hidrofor. 24 litre membranlı tank, 40 mSS basma, 6 metre emiş ve 1" giriş-çıkış.'],
            1406 => ['', '4 kat 4 daireye kadar binalar için 1 HP, 220 V hidromatlı paket hidrofor. Tanksız çalışır; 52 mSS basma, 8 metre emiş, 1" bağlantı.'],
            1407 => ['', '2 kat 2 daireye kadar binalar için 0.5 HP, 220 V hidromatlı paket hidrofor. 40 mSS basma, 0–2.1 m³/h debi, 6 metre emiş ve bronz fan.'],
            1408 => ['', '4 kat 4 daireye kadar binalar için 1 HP jet hidrofor. 24 litre membranlı tank, 50 mSS basma, 8 metre emiş; döküm gövde ve paslanmaz mil.'],
            1409 => ['', '4 kat 8 daireye kadar binalar için 1.5 HP, 220 V jet hidrofor. 24 litre yatık tank, 60 mSS basma, 8 metre emiş ve termik koruma.'],
            1410 => ['', '4 kat 4 daireye kadar binalar için 1 HP, 220 V jet hidrofor. 24 litre yatık tank, 0–5 m³/h debi, 8 metre emiş ve termik koruma.'],
            1411 => ['', '4 kat 8 daireye kadar binalar için 1.5 HP jet hidrofor. 24 litre yatık tank, 60 mSS basma; motor, tank, şalter, manometre ve fleks dahil.'],
            1412 => ['', '8 kat 18 daireye kadar binalar için 2.5 HP (1.85 kW), 220 V paket hidrofor. 100 litre tank, 76 mSS basma ve 9.6 m³/h debi.'],
            1413 => ['', 'Foseptik ve atık su kuyuları için 3 HP (2.2 kW), 380 V öğütücülü dalgıç pompa. Döküm gövde, 2" çıkış, 18 m basma ve 33 m³/h\'e kadar debi.'],
            1414 => ['', 'Çamurlu ve partiküllü sular için 1.5 HP, 220 V paslanmaz gövdeli dalgıç pompa. 2" çıkış, 40 mm katı geçişi, 12.5 m basma, 24 m³/h\'e kadar debi.'],
        ];
    }

    /** @return array<int, string> */
    private function descriptions(): array
    {
        $s = self::SITE;

        return [
            1370 => '<p>Kaysu 2HCP-160, iki çarkı art arda çalışan çift kademeli bir santrifüj yüzey pompasıdır. Kademeli yapısı sayesinde tek kademeli pompalara göre daha yüksek basma değerlerine ulaşır; bu nedenle hidrofor ve basınç setlerinde, depolar arası su transferinde ve sulama hatlarında kullanılır. GG25 döküm gövde, pirinç çark ve AISI 304 paslanmaz çelik mil ile üretilmiştir; motoru 220 V şebekeyle çalışır ve termik korumalıdır.</p>'
                .'<h2>Kullanım Alanları</h2>'
                .'<ul><li>Hidrofor ve basınç setlerinde su dağıtımı ve basınç yükseltme</li><li>İki depo arasında temiz su transferi</li><li>Bahçe ve tarım arazilerinde sulama hatları</li><li>Evsel, ticari ve endüstriyel temiz su uygulamaları</li></ul>'
                .'<h2>Kurulum ve Kullanım Notları</h2>'
                .'<ul><li>Temiz su ve pompa malzemelerine karşı kimyasal olarak agresif olmayan sıvılar için uygundur.</li><li>Maksimum emiş derinliği 6 metredir. Emiş hattını kısa ve hava kaçırmayacak şekilde kurun, ilk çalıştırmadan önce pompayı ve emiş hattını suyla doldurun.</li><li>Sıvı sıcaklığı en fazla 60 °C, ortam sıcaklığı en fazla 40 °C olmalıdır.</li><li>Motor IP44 korumalıdır; pompayı yağmur ve su sıçramasından korunan bir yere monte edin.</li></ul>'
                .'<p>Farklı güç ve basınç seçenekleri için <a href="'.$s.'/kategoriler/su-pompalari/kademeli-pompalar" rel="noopener noreferrer">kademeli pompalar</a> kategorisine göz atabilirsiniz. Hattınızın basma yüksekliği ve debi ihtiyacına göre model seçimi için bize danışabilirsiniz.</p>'
                .self::CONTACT,

            1372 => '<p>Kaysu SP400-A, bodrum, depo, havuz ve su birikintilerindeki temiz suyun tahliyesi ve transferi için tasarlanmış plastik gövdeli bir dalgıç pompadır. Flatörlü yapısı sayesinde su seviyesi yükseldiğinde otomatik olarak çalışır, seviye düştüğünde durur. 0.40 kW (0.50 HP) gücündeki motoru 220 V şebekeyle çalışır, IP68 korumalıdır ve termik koruması vardır.</p>'
                .'<h2>Performans</h2>'
                .'<p>Maksimum basma yüksekliği 8 metre, maksimum debi 8 m³/h’tir. Basma yüksekliği arttıkça debi düşer: 2 metrede yaklaşık 5.8 m³/h, 4 metrede 4.5 m³/h, 6 metrede 2.5 m³/h basar.</p>'
                .'<h2>Kullanım Alanları</h2>'
                .'<ul><li>Su basan bodrum, garaj ve çukurların tahliyesi</li><li>Depo, sarnıç ve havuz boşaltma</li><li>Yağmur suyu toplama çukurları</li><li>Depodan bahçe sulamasına su aktarma</li></ul>'
                .'<h2>Kurulum ve Kullanım Notları</h2>'
                .'<ul><li>Katı parçacık, kum ve lifli parça içermeyen temiz su için uygundur. Kirli sular için <a href="'.$s.'/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa" rel="noopener noreferrer">kirli su dalgıç pompaları</a> tercih edilmelidir.</li><li>Sıvı sıcaklığı en fazla 35 °C, daldırma derinliği en fazla 5 metredir.</li><li>Flatörün serbest hareket edebileceği bir alan bırakın; flatörün sığmadığı dar çukurlar için sensörlü <a href="'.$s.'/urun/kaysu-spauto400-a-dalgic-pompa" rel="noopener noreferrer">Kaysu SPAUTO400-A</a> daha uygundur.</li><li>Çıkış ağzı 1", 1¼" ve 1½" hortum bağlantılarına uygundur.</li><li>Pompayı suyun dışında çalıştırmayın; kaçak akım rölesi olan topraklı bir prize bağlayın.</li></ul>'
                .self::CONTACT,

            1373 => '<p>Kaysu SPAUTO400-A, temiz suyun tahliyesi ve transferi için tasarlanmış sensörlü, plastik gövdeli bir dalgıç pompadır. Hareketli flatör yerine sensörle çalıştığı için dar kuyu ve çukurlarda rahatça kullanılır; su seviyesine göre otomatik olarak devreye girer ve durur. 0.40 kW (0.50 HP) gücündeki motoru 220 V şebekeyle çalışır, IP68 korumalıdır ve termik koruması vardır.</p>'
                .'<h2>Performans</h2>'
                .'<p>Maksimum basma yüksekliği 8 metre, maksimum debi 9 m³/h’tir. 2 metrede yaklaşık 7.5 m³/h, 4 metrede 5.8 m³/h, 6 metrede 3.5 m³/h basar.</p>'
                .'<h2>Kullanım Alanları</h2>'
                .'<ul><li>Dar drenaj çukurları ve rögarlardaki temiz suyun tahliyesi</li><li>Su basan bodrum ve garajlar</li><li>Depo, sarnıç ve havuz boşaltma</li><li>Yağmur suyu toplama çukurları</li></ul>'
                .'<h2>Kurulum ve Kullanım Notları</h2>'
                .'<ul><li>Katı parçacık, kum ve lifli parça içermeyen temiz su için uygundur.</li><li>Sıvı sıcaklığı en fazla 35 °C, daldırma derinliği en fazla 5 metredir.</li><li>Sensör yüzeyini çamur ve tortudan temiz tutun; pompayı düz ve sağlam bir zemine yerleştirin.</li><li>Çıkış ağzı 1", 1¼" ve 1½" hortum bağlantılarına uygundur. Geniş alanlarda flatörlü <a href="'.$s.'/urun/kaysu-sp400-a-dalgic-pompa" rel="noopener noreferrer">Kaysu SP400-A</a> da kullanılabilir.</li><li>Kaçak akım rölesi olan topraklı bir prize bağlayın.</li></ul>'
                .self::CONTACT,

            1374 => '<p>Kaysu QDX6-32-1.5B, keson (geniş çaplı) kuyu, sarnıç, depo, göl ve dere gibi kaynaklardan temiz su basmak için tasarlanmış döküm gövdeli bir dalgıç pompadır. 1.5 kW (2 HP) gücündeki motoru 220 V şebekeyle çalışır, IP68 korumalıdır ve aşırı akım koruyucusu vardır. Yüksek basma değeri sayesinde suyu uzak ve yüksekteki noktalara taşıyabilir.</p>'
                .'<h2>Performans</h2>'
                .'<p>Maksimum basma yüksekliği yaklaşık 32 metredir. Debi arttıkça basma düşer: 6 m³/h’te yaklaşık 30.5 m, 9 m³/h’te 25.5 m, 12 m³/h’te 20.7 m, 15 m³/h’te 15 m basar. Daha yüksek basma gereken hatlar için üç kademeli <a href="'.$s.'/urun/kaysu-qdx6-393-15b-dalgic-pompa" rel="noopener noreferrer">Kaysu QDX6-39/3-1.5B</a> modeline bakabilirsiniz.</p>'
                .'<h2>Kullanım Alanları</h2>'
                .'<ul><li>Keson kuyu, sarnıç ve depolardan su basma</li><li>Bağ, bahçe ve tarımsal sulama</li><li>Göl, dere ve gölet gibi açık kaynaklardan su alma</li><li>Temiz taşkın sularının tahliyesi</li></ul>'
                .'<h2>Kurulum ve Kullanım Notları</h2>'
                .'<ul><li>Katı parçacık içermeyen temiz su için uygundur.</li><li>Geniş çaplı kuyular için tasarlanmıştır; dar çaplı sondaj kuyularında <a href="'.$s.'/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa" rel="noopener noreferrer">derin kuyu dalgıç pompaları</a> kullanılır.</li><li>Maksimum daldırma derinliği 5 metredir. Pompayı kuyu tabanındaki çamura değmeyecek şekilde halatla asın.</li><li>1¼" (32 mm) çıkış ve 10 metre kabloyla gelir; kablo eklerini su dışında tutun.</li></ul>'
                .self::CONTACT,

            1375 => '<p>Kaysu QDX6-39/3-1.5B, üç kademeli hidroliği sayesinde yüksek basma değerine ulaşan döküm gövdeli bir keson kuyu dalgıç pompasıdır. Keson kuyu, sarnıç ve depolardan suyu yüksekteki veya uzaktaki noktalara taşımak için kullanılır. 1.5 kW (2 HP) gücündeki motoru 220 V şebekeyle çalışır, IP68 korumalıdır ve aşırı akım koruyucusu vardır.</p>'
                .'<h2>Performans</h2>'
                .'<p>Maksimum basma yüksekliği yaklaşık 39 metredir. 6 m³/h’te yaklaşık 35 m, 9 m³/h’te 30 m, 12 m³/h’te 22.5 m, 15 m³/h’te 11.5 m basar. Daha düşük basma yeterliyse tek kademeli <a href="'.$s.'/urun/kaysu-qdx6-32-15b-dalgic-pompa" rel="noopener noreferrer">Kaysu QDX6-32-1.5B</a> modeli de değerlendirilebilir.</p>'
                .'<h2>Kullanım Alanları</h2>'
                .'<ul><li>Keson kuyu ve sarnıçlardan yüksek noktalara su basma</li><li>Eğimli arazilerde bağ, bahçe ve tarımsal sulama</li><li>Depodan uzak noktalara temiz su transferi</li><li>Yağmurlama ve damlama sulama hatlarının beslenmesi</li></ul>'
                .'<h2>Kurulum ve Kullanım Notları</h2>'
                .'<ul><li>Katı parçacık içermeyen temiz su için uygundur.</li><li>Geniş çaplı kuyular için tasarlanmıştır; dar çaplı sondaj kuyularında <a href="'.$s.'/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa" rel="noopener noreferrer">derin kuyu dalgıç pompaları</a> kullanılır.</li><li>Maksimum daldırma derinliği 5 metredir. Pompayı kuyu tabanındaki çamura değmeyecek şekilde halatla asın.</li><li>2" (50 mm) çıkış ve 10 metre kabloyla gelir; kablo eklerini su dışında tutun.</li></ul>'
                .self::CONTACT,

            1376 => '<p>Kaysu WQD370-B, evsel atık su ve kirli suların tahliyesi için tasarlanmış döküm gövdeli bir dalgıç pompadır. 15 mm’ye kadar katı parçacık geçirebilir. 0.37 kW (0.5 HP) gücündeki motoru 220 V şebekeyle çalışır, IP68 korumalıdır ve aşırı akım koruyucusu vardır. Gövde ve çark GG25 döküm, motor taşıyıcı ve mil AISI 304 paslanmaz çeliktir.</p>'
                .'<h2>Performans</h2>'
                .'<p>Maksimum basma yüksekliği 8 metredir. 3 m³/h’te yaklaşık 7 m, 6 m³/h’te 6 m, 9 m³/h’te 4.2 m, 12 m³/h’te 2.5 m basar.</p>'
                .'<h2>Kullanım Alanları</h2>'
                .'<ul><li>Bodrum, rögar ve toplama çukurlarının tahliyesi</li><li>Çamaşır ve bulaşık makinesi gibi evsel atık suların boşaltılması</li><li>İnşaat alanı ve sel sonrası kirli su tahliyesi</li><li>Yağmur suyu toplama çukurları</li></ul>'
                .'<h2>Kurulum ve Kullanım Notları</h2>'
                .'<ul><li>Kimyasal olarak agresif olmayan kirli sular için uygundur. 15 mm’den büyük katı veya bez, ıslak mendil gibi lifli atık içeren foseptik kuyularında <a href="'.$s.'/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa" rel="noopener noreferrer">bıçaklı dalgıç pompalar</a> tercih edilmelidir.</li><li>Sıvı sıcaklığı en fazla 40 °C, daldırma derinliği en fazla 5 metredir.</li><li>1¼" (32 mm) çıkış ve 10 metre kabloyla gelir. Basma hattına çekvalf takın, hattı gereksiz yere daraltmayın.</li><li>Pompayı kuyu tabanındaki kalın tortunun üzerinde kalacak şekilde konumlandırın.</li></ul>'
                .self::CONTACT,
        ];
    }

    /** @return array<int, array<string, string>> */
    private function specs(): array
    {
        return [
            1370 => [
                'Marka / Model' => 'Kaysu 2HCP-160',
                'Tip' => 'Çift kademeli santrifüj yüzey pompası',
                'Motor Gücü' => '2 HP',
                'Voltaj' => '220 V / 50 Hz (Monofaze)',
                'Devir' => '2850 d/d',
                'Maks. Emiş Derinliği' => '6 m',
                'Maks. Sıvı Sıcaklığı' => '60 °C',
                'Koruma Sınıfı' => 'IP44',
                'Termik Koruma' => 'Var',
                'Gövde Malzemesi' => 'GG25 döküm',
                'Çark Malzemesi' => 'Pirinç',
                'Mil Malzemesi' => 'AISI 304 paslanmaz çelik',
                'Mekanik Salmastra' => 'SiC / Grafit',
            ],
            1372 => [
                'Marka / Model' => 'Kaysu SP400-A',
                'Tip' => 'Flatörlü temiz su dalgıç pompası',
                'Motor Gücü' => '0.40 kW (0.50 HP)',
                'Voltaj' => '220 V / 50 Hz (Monofaze)',
                'Maks. Basma Yüksekliği' => '8 m',
                'Maks. Debi' => '8 m³/h',
                'Çıkış' => '1" – 1¼" – 1½"',
                'Devir' => '2850 d/d',
                'Koruma Sınıfı' => 'IP68',
                'Termik Koruma' => 'Var',
                'Maks. Sıvı Sıcaklığı' => '35 °C',
                'Maks. Daldırma Derinliği' => '5 m',
                'Gövde Malzemesi' => 'PP (polipropilen)',
                'Mil Malzemesi' => 'Paslanmaz çelik',
                'Mekanik Salmastra' => 'Karbon / Seramik / NBR',
            ],
            1373 => [
                'Marka / Model' => 'Kaysu SPAUTO400-A',
                'Tip' => 'Sensörlü temiz su dalgıç pompası',
                'Motor Gücü' => '0.40 kW (0.50 HP)',
                'Voltaj' => '220 V / 50 Hz (Monofaze)',
                'Maks. Basma Yüksekliği' => '8 m',
                'Maks. Debi' => '9 m³/h',
                'Çıkış' => '1" – 1¼" – 1½"',
                'Devir' => '2850 d/d',
                'Koruma Sınıfı' => 'IP68',
                'Termik Koruma' => 'Var',
                'Maks. Sıvı Sıcaklığı' => '35 °C',
                'Maks. Daldırma Derinliği' => '5 m',
                'Gövde Malzemesi' => 'PP (polipropilen)',
                'Mil Malzemesi' => 'Paslanmaz çelik',
                'Mekanik Salmastra' => 'Karbon / Seramik / NBR',
            ],
            1374 => [
                'Marka / Model' => 'Kaysu QDX6-32-1.5B',
                'Tip' => 'Döküm gövdeli keson kuyu dalgıç pompası',
                'Motor Gücü' => '1.5 kW (2 HP)',
                'Voltaj' => '220 V / 50 Hz (Monofaze)',
                'Maks. Basma Yüksekliği' => '32 m',
                'Debi Aralığı' => '0–15 m³/h',
                'Çıkış' => '1¼" (32 mm)',
                'Kablo Uzunluğu' => '10 m',
                'Devir' => '2850 d/d',
                'Koruma Sınıfı' => 'IP68',
                'Aşırı Akım Koruması' => 'Var',
                'Maks. Daldırma Derinliği' => '5 m',
                'Gövde Malzemesi' => 'GG25 döküm',
                'Çark Malzemesi' => 'GG25 döküm',
                'Mil Malzemesi' => 'AISI 304 paslanmaz çelik',
            ],
            1375 => [
                'Marka / Model' => 'Kaysu QDX6-39/3-1.5B',
                'Tip' => '3 kademeli, döküm gövdeli keson kuyu dalgıç pompası',
                'Motor Gücü' => '1.5 kW (2 HP)',
                'Voltaj' => '220 V / 50 Hz (Monofaze)',
                'Maks. Basma Yüksekliği' => '39 m',
                'Debi Aralığı' => '0–15 m³/h',
                'Çıkış' => '2" (50 mm)',
                'Kablo Uzunluğu' => '10 m',
                'Devir' => '2850 d/d',
                'Koruma Sınıfı' => 'IP68',
                'Aşırı Akım Koruması' => 'Var',
                'Maks. Daldırma Derinliği' => '5 m',
                'Gövde Malzemesi' => 'GG25 döküm',
                'Çark Malzemesi' => 'GG25 döküm',
                'Mil Malzemesi' => 'AISI 304 paslanmaz çelik',
            ],
            1376 => [
                'Marka / Model' => 'Kaysu WQD370-B',
                'Tip' => 'Döküm gövdeli kirli su dalgıç pompası',
                'Motor Gücü' => '0.37 kW (0.5 HP)',
                'Voltaj' => '220 V / 50 Hz (Monofaze)',
                'Maks. Basma Yüksekliği' => '8 m',
                'Debi Aralığı' => '0–12 m³/h',
                'Maks. Partikül Çapı' => 'Ø 15 mm',
                'Çıkış' => '1¼" (32 mm)',
                'Kablo Uzunluğu' => '10 m',
                'Devir' => '2850 d/d',
                'Koruma Sınıfı' => 'IP68',
                'Aşırı Akım Koruması' => 'Var',
                'Maks. Sıvı Sıcaklığı' => '40 °C',
                'Maks. Daldırma Derinliği' => '5 m',
                'Gövde Malzemesi' => 'GG25 döküm',
                'Çark Malzemesi' => 'GG25 döküm',
                'Motor Taşıyıcı ve Mil' => 'AISI 304 paslanmaz çelik',
                'Mekanik Salmastra' => 'SiC–SiC / Seramik–Grafit',
            ],
        ];
    }
};
