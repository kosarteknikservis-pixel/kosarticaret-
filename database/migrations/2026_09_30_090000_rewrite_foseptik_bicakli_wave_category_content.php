<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SD = '/kategoriler/su-pompalari/dalgic-pompalar';

    private const SO = '/kategoriler/su-pompalari/ozel-amacli-pompalar';

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $changed = 0;

        foreach ($this->content() as $id => $fields) {
            $current = DB::table('categories')->where('id', $id)->first(['description', 'buying_guide', 'faq', 'meta_description']);
            if ($current === null) {
                $this->misses[$id][] = 'kategori yok';

                continue;
            }

            $data = [];

            foreach (['description', 'buying_guide', 'meta_description'] as $column) {
                if (! isset($fields[$column])) {
                    continue;
                }
                [$expectedPrefix, $value] = $fields[$column];
                $old = trim((string) $current->{$column});
                if ($old === trim($value)) {
                    continue;
                }
                $matches = $expectedPrefix === '' ? $old === '' : str_starts_with($old, $expectedPrefix);
                if (! $matches) {
                    $this->misses[$id][] = $column;

                    continue;
                }
                $data[$column] = $value;
            }

            [$expectedFirstQuestion, $faq] = $fields['faq'];
            $newFaq = json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $oldFaq = json_decode((string) $current->faq, true);
            if ($current->faq !== $newFaq) {
                if (($oldFaq[0]['q'] ?? null) === $expectedFirstQuestion) {
                    $data['faq'] = $newFaq;
                } else {
                    $this->misses[$id][] = 'faq';
                }
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

    /** @return array<int, array<string, array{0: string, 1: mixed}>> */
    private function content(): array
    {
        $sd = self::SD;
        $so = self::SO;

        return [
            142 => [
                'description' => ['<h2>Foseptik Dalgıç Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Foseptik Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Foseptik dalgıç pompası</strong>, tuvalet atığını ve lifli, katı parçalı pis suyu foseptik çukurundan, toplama haznesinden veya atık su kuyusundan kanalizasyona ya da tahliye noktasına basan dalgıç pompadır. Çark yapısı, drenaj pompalarına göre çok daha büyük parçaların geçmesine izin verir.</p>
<p>Bu kategoride Pedrollo, Sumak, Winpo ve Kaysu markalarının foseptik dalgıç pompaları yer alır. Ürünler 0.75 HP'lik ev tipi modellerden 30 HP'lik tesis pompalarına kadar uzanır.</p>
<h3>Çark Tipleri</h3>
<ul>
<li><strong>Vortex çark:</strong> Çark gövdenin içinde geride durur ve suyu girdap etkisiyle basar. Katı parçalar çarka az temas ettiği için tıkanma riski düşüktür. Örneğin Sumak SDFV ve SDTV İNOX modelleri vortex çarklıdır.</li>
<li><strong>Kanallı çark:</strong> Aynı güçte daha yüksek verim sağlar; geçebilecek parça boyutu çark kanalının genişliğiyle sınırlıdır.</li>
<li><strong>Bıçaklı (kırıcılı) çark:</strong> Lifli atığı parçalayarak basar. Ayrıntılar için <a href="{$sd}/bicakli-dalgic-pompa">bıçaklı dalgıç pompalar</a> sayfasına bakabilirsiniz.</li>
</ul>
<h3>Foseptik Dalgıç Pompa Modelleri</h3>
<ul>
<li><strong>Pedrollo VXm ve VX:</strong> Paslanmaz gövdeli (N) ve full paslanmaz (ST) modeller; 0.75–1.5 HP, 1½–2 inç çıkış. VX 30/40, 40/40, 55/40, 40/65 ve 55/65 döküm gövdeli, 3–5.5 HP ve 380 V'tur.</li>
<li><strong>Pedrollo VXCm ve VXC:</strong> Döküm gövdeli; 1–3 HP, 2–3 inç çıkış ve 50–70 mm parça geçişi. VXCm modelleri 220 V, VXC modelleri 380 V'tur.</li>
<li><strong>Pedrollo MCm ve MC:</strong> Döküm gövdeli; 1–4 HP ve 2–3 inç çıkış. MC 15/45 N modeli 50 mm'ye kadar parça geçirir.</li>
<li><strong>Pedrollo BCm ve BC:</strong> BCm 10/50 ile BCm ve BC 15/50 paslanmaz gövdeli (N) veya full paslanmaz (ST); BC 40/35, 55/35 ve 75/35 döküm gövdeli, 4–7.5 HP, 2½ inç çıkış ve saatte 125 m³'e kadar debi.</li>
<li><strong>Pedrollo ZXm 1A/40 ve 1B/40:</strong> Paslanmaz gövdeli; 0.75–0.85 HP ve 1½ inç çıkış.</li>
<li><strong>Sumak SDF (220 V) ve SDT (380 V):</strong> 1–4 HP, 2–3 inç çıkış. SDF 14/2-A ve 18/2-A asansör flatörlü, SDF15/2 komple paslanmazdır (AISI 304).</li>
<li><strong>Sumak SDFV ve SDTV İNOX:</strong> Komple paslanmaz, vortex çarklı; 2.2–4 HP, 2 inç çıkış ve 13–19.5 mss basma.</li>
<li><strong>Sumak SDTB, SDTV ve SDTK (4 ve 6 inç):</strong> SDTB 50/4 ve 75/4, SDTV 50/4–150/4 ve SDTK 75/4–300/6 modelleri; 5.5–30 HP, 380 V ve saatte 400 m³'e kadar debi. SDTK 75/4 flatörlü ve panoludur.</li>
<li><strong>Winpo WNP-V 1100 F ve V 1500 F:</strong> Flatörlü foseptik drenaj pompaları; 1.5 ve 2 HP, 2 inç çıkış. V 1100 D (F) bıçaklı versiyondur.</li>
<li><strong>Winpo WNP GR ve T (kırıcılı):</strong> 7-8 GR, 7-12 GR ve 7-16 GR flatörlü ve 220 V; 7-12T, 7-16T ve 9-18T 380 V; 1–3 HP.</li>
<li><strong>Kaysu WQD, H1100F-B ve WQH:</strong> 0.75–2.2 kW, 2 inç çıkış ve GG25 döküm çark; WQD ve H1100F-B modelleri 10 m kablolu. WQH2200QG öğütücülü ve 380 V'tur, CUT1500 ise bıçaklıdır.</li>
</ul>
<p>Atığı tankında toplayıp basan sistemler için <a href="{$so}/foseptik-tahliye-cihazi">foseptik tahliye cihazları</a>, çamurlu su için <a href="{$sd}/kirli-su-dalgic-pompa">kirli su dalgıç pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Foseptik Dalgıç Pompa Nasıl Seçilir?</h3>', <<<HTML
<h3>Foseptik Dalgıç Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Atığın yapısı:</strong> WC atığı için vortex veya geniş kanallı çark yeterlidir. Islak mendil ve bez riski yüksekse ya da basma hattı uzun ve dar ise bıçaklı model seçilir.</li>
<li><strong>Parça geçişi:</strong> Ürün sayfasında belirtilen geçiş çapı, çukura gelebilecek en büyük parçadan büyük olmalıdır.</li>
<li><strong>Basma yüksekliği:</strong> Çukur tabanı ile tahliye noktası arasındaki dikey mesafeye yatay hat ve dirsek kayıpları eklenir.</li>
<li><strong>Debi:</strong> Hazne hacmine ve gelen atık miktarına göre seçilir. Basma hattında akış yavaş kalırsa katı parçalar boruda çöker.</li>
<li><strong>Otomatik çalışma:</strong> Flatörlü model veya ayrı seviye şalteri ve pano kullanılır. Şamandıra, pompanın emiş ağzı su dışında kalmadan durduracak şekilde ayarlanmalıdır.</li>
<li><strong>Gövde malzemesi:</strong> Paslanmaz gövde daha hafif ve darbelere dayanıklıdır; döküm gövde ağır hizmet ve büyük güçlerde tercih edilir.</li>
<li><strong>Elektrik ve yedek:</strong> Evlerde 220 V, işletmelerde 380 V model seçilir. Kesintisiz çalışması gereken bina ve tesislerde yedek pompa ve seviye alarmı önerilir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/foseptik-dalgic-pompa-secimi-rehberi">foseptik dalgıç pompa seçimi</a>, <a href="/blog/foseptik-pompa-samandira-seviye-ayari">foseptik pompada şamandıra seviye ayarı</a> ve <a href="/blog/dalgic-pompa-impeller-tipleri-vortex-kanal-bicakli">vortex, kanallı ve bıçaklı çark tipleri</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Foseptik pompası ne sıklıkla çalışmalı?', [
                    ['q' => 'Foseptik pompa ile drenaj pompası arasındaki fark nedir?', 'a' => 'Drenaj pompası temiz veya az kirli su içindir ve küçük parçaları geçirir (örneğin 10 mm). Foseptik pompanın çarkı tuvalet atığı ve lifli atık için tasarlanmıştır; Pedrollo VXC modellerinde parça geçişi 50–70 mm\'dir. Drenaj pompasını foseptik çukurunda kullanmak kısa sürede tıkanmaya yol açar.'],
                    ['q' => 'Foseptik pompası neden tıkanır?', 'a' => 'En sık nedenler ıslak mendil, bez, hijyenik ped ve yağ birikimidir. Tıkanma olduğunda önce elektrik kesilmeli, pompa çukurdan çıkarılmalı ve çark bölgesi temizlenmelidir. Bu tür atıkların tuvalete atılmaması tıkanmayı büyük ölçüde önler.'],
                    ['q' => 'Pedrollo modellerindeki m, N ve ST harfleri ne anlama gelir?', 'a' => '"m" harfi 220 V (monofaze) modeli gösterir; bu harfin olmadığı VX, VXC, MC ve BC modelleri 380 V\'tur. VX, VXm, BC ve BCm serilerinde N ile biten modeller ürün adında paslanmaz gövdeli, ST ile bitenler full paslanmaz olarak geçer. VXC ve MC serileri ise döküm gövdelidir.'],
                    ['q' => 'Ev için hangi güçte foseptik pompa gerekir?', 'a' => 'Sabit bir güç değeri yoktur; seçim çukur derinliği, tahliye hattının uzunluğu ve yüksekliğine göre yapılır. Kısa ve alçak hatlarda 1–1.5 HP sınıfındaki flatörlü modeller genellikle yeterlidir. Uzun veya yükselen hatlarda daha güçlü ya da bıçaklı model gerekir.'],
                    ['q' => 'Foseptik pompanın şamandırası nasıl ayarlanır?', 'a' => 'Şamandıra, hazne taşmadan pompayı çalıştıracak ve emiş ağzı su dışında kalmadan durduracak şekilde ayarlanır. Çalışma ve durma seviyeleri birbirine çok yakınsa pompa sık dur-kalk yapar. Ayrıntılar için <a href="/blog/foseptik-pompa-samandira-seviye-ayari">şamandıra seviye ayarı</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
            143 => [
                'description' => ['<h2>Bıçaklı Dalgıç Pompa (Macerator) Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Bıçaklı Dalgıç Pompa (Macerator) Modelleri ve Fiyatları</h2>
<p><strong>Bıçaklı dalgıç pompa</strong> (parçalayıcı, öğütücülü veya kırıcılı pompa), emiş ağzındaki kesici bıçaklarla tuvalet kâğıdı, ıslak mendil ve lifli atığı parçalayıp basan foseptik dalgıç pompadır. Atık parçalandığı için daha küçük çaplı hatlardan ve daha uzun mesafelere basılabilir.</p>
<p>Bu kategoride Pedrollo, Sumak, Winpo ve Kaysu markalarının bıçaklı dalgıç pompaları yer alır.</p>
<h3>Bıçaklı Dalgıç Pompa Modelleri</h3>
<ul>
<li><strong>Pedrollo TRm ve TR:</strong> 0.75–2.2 kW, 1¼–1½ inç çıkış ve 16.5–31 mss basma. TRm modelleri 220 V, TR modelleri 380 V'tur.</li>
<li><strong>Sumak SBRM (220 V):</strong> SBRM 15/2, 18/2, 18/2P ve 19/2P; 1.5–1.8 HP ve 2 inç çıkış.</li>
<li><strong>Sumak SBRT (380 V):</strong> 1.5–7.5 HP, 2 ve 3 inç çıkış ve 50 m'ye kadar basma. "P" ekli modeller ürün adında parçalayıcı bıçaklı olarak geçer.</li>
<li><strong>Winpo WNP GR ve T (kırıcılı):</strong> 7-8 GR, 7-12 GR ve 7-16 GR flatörlü ve 220 V; 7-12T, 7-16T ve 9-18T 380 V; 1–3 HP ve 2 inç çıkış. V 1100 D (F) flatörlü bıçaklı modeldir ve 30 mm'ye kadar parça geçirir.</li>
<li><strong>Kaysu CUT1500 ve CUT1500 T:</strong> 1.5 kW, 2 inç çıkış ve 10 m kablo; CUT1500 220 V, CUT1500 T 380 V ve panoludur. WQH2200QG döküm gövdeli, öğütücülü, 2.2 kW ve 380 V'tur.</li>
</ul>
<p>Standart WC atığı için <a href="{$sd}/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a>, bodruma eklenen tuvalet ve duş için tanklı <a href="{$so}/foseptik-tahliye-cihazi">foseptik tahliye cihazları</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Bıçaklı Dalgıç Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Ne zaman gerekir:</strong> Islak mendil ve bez riski yüksekse, basma hattı uzun veya yükseliyorsa ya da hat çapı küçükse bıçaklı pompa tercih edilir. Standart WC atığında vortex veya kanallı foseptik pompa çoğu zaman yeterlidir.</li>
<li><strong>Basma yüksekliği:</strong> Dikey yüksekliğe yatay hat ve dirsek kayıpları eklenir. Bıçaklı modeller yüksek basma verir; örneğin Pedrollo TR 31 mss'ye, Sumak SBRT 50 m'ye kadar çıkar.</li>
<li><strong>Debi:</strong> Bıçaklı modellerin debisi aynı güçteki vortex pompalardan düşük olabilir; örneğin Pedrollo TR modellerinde saatte 7.2–15.6 m³'tür. Büyük hacimli çukurlarda boşaltma süresi kontrol edilmelidir.</li>
<li><strong>Otomatik çalışma:</strong> Flatörlü model (örneğin Winpo GR) veya ayrı seviye şalteri ve pano kullanılır.</li>
<li><strong>Elektrik:</strong> Evlerde 220 V model seçilir; büyük modeller 380 V'tur ve kumanda panosu gerektirir.</li>
<li><strong>Yabancı cisim:</strong> Bıçaklar ıslak mendili parçalar, ancak plastik, metal ve kalın bez bıçaklara zarar verebilir veya pompayı tıkayabilir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/bicakli-dalgic-pompa-nedir-secim">bıçaklı dalgıç pompa nedir</a> ve <a href="/blog/dalgic-pompa-impeller-tipleri-vortex-kanal-bicakli">vortex, kanallı ve bıçaklı çark tipleri</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Bıçaklı pompa mı, vortex pompa mı tercih edilmeli?', [
                    ['q' => 'Bıçaklı pompa mı, vortex pompa mı tercih edilmeli?', 'a' => 'Vortex pompa katı parçaları çarka az temas ettirerek geçirir, bakımı basittir ve standart WC atığı için çoğu zaman yeterlidir. Bıçaklı pompa lifli atığı parçalar ve daha yüksek basma verir; ıslak mendil riski olan, uzun veya yükselen hatlarda tercih edilir. Bıçaklar zamanla aşındığı için düzenli kontrol gerektirir.'],
                    ['q' => 'Bıçaklı pompa ıslak mendil ve peçeteyi geçirir mi?', 'a' => 'Islak mendil ve peçeteyi parçalayabilir. Ancak bez, hijyenik ped, plastik ve metal parçalar bıçaklara zarar verebilir veya pompayı tıkayabilir; bu tür atıklar tuvalete atılmamalıdır.'],
                    ['q' => 'Bıçaklı pompa hangi boru çapıyla çalışır?', 'a' => 'Pompa çıkışı modele göre 1¼ inç (Pedrollo TR) ile 3 inç (Sumak SBRT /3 modelleri) arasındadır. Basma hattı pompanın çıkış çapından daha küçük seçilmemelidir.'],
                    ['q' => 'Bıçaklar ne zaman kontrol edilmeli?', 'a' => 'Kontrol aralığı kullanım yoğunluğuna ve atığın yapısına bağlıdır. Pompa normalden fazla ses yapmaya, daha çok akım çekmeye veya daha az su basmaya başladıysa bıçaklar kontrol edilmelidir.'],
                    ['q' => 'Bıçaklı pompa gürültülü çalışıyorsa ne yapmalı?', 'a' => 'En sık nedenler bıçaklar arasına sıkışmış yabancı cisim, körelmiş bıçak veya rulman arızasıdır. Önce elektrik kesilmeli, pompa çıkarılıp bıçak bölümü kontrol edilmeli ve sıkışan cisim temizlenmelidir. Sorun devam ederse teknik servise başvurulmalıdır.'],
                ]],
            ],
        ];
    }
};
