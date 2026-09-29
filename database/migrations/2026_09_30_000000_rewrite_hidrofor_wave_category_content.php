<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $changed = 0;

        foreach ($this->content() as $id => $fields) {
            $current = DB::table('categories')->where('id', $id)->first(['description', 'buying_guide', 'faq']);
            if ($current === null) {
                $this->misses[$id][] = 'kategori yok';

                continue;
            }

            $data = [];

            foreach (['description', 'buying_guide'] as $column) {
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

    /** @return array<int, array{description: array{0: string, 1: string}, buying_guide: array{0: string, 1: string}, faq: array{0: string, 1: list<array{q: string, a: string}>}}> */
    private function content(): array
    {
        return [
            128 => [
                'description' => ['<h2>Hidrofor Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Hidrofor</strong>; pompa, basınç tankı veya hidromat ve basınç şalterinden oluşan, şebeke ya da depo suyunu musluklara sabit basınçla ulaştıran hazır sistemdir. Bu kategoride tek daireli evden 28 katlı binaya kadar farklı ihtiyaçlar için Sumak, Pedrollo, Winpo ve Kaysu hidrofor modelleri yer alır.</p>
<h3>Hidrofor Çeşitleri</h3>
<ul>
<li><strong>Ev tipi paket hidroforlar:</strong> Tek pompa ile 24–50 litre tank ya da hidromat birleşimidir. Sumak SM ve SMJ, Pedrollo PKm 60, JSWm ve 4CPm, Winpo WNP 100/150/200 ve Kaysu HKJM serileri 1–8 kat arası konutlarda kullanılır. Ayrıntılar için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a> kategorisine bakabilirsiniz.</li>
<li><strong>Sessiz ve elektronik kontrollü modeller:</strong> Sumak SMH 120 BOX frekanslı ve sessiz çalışır; Pedrollo 2CPm ile JSWm, JCRm ve 4CPm serilerinin dijital versiyonları basıncı elektronik olarak izler.</li>
<li><strong>Bina hidroforları:</strong> Sumak SYMH yatay kademeli (100 litre tanklı), SMINOX paslanmaz kademeli ve SMKT emişli çift kademeli modeller orta ve büyük apartmanlar içindir.</li>
<li><strong>Çok pompalı hidroforlar:</strong> Sumak SHT, SHM ve paslanmaz SHTP serileri ile Winpo WNP1 ve WNP2 VM; tek, çift veya üç pompalı yapıda site, otel ve yüksek binalarda kullanılır. Frekans kontrollü (FK) versiyonlar da vardır. Çok pompalı sistemler için <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a> kategorisini inceleyebilirsiniz.</li>
</ul>
<h3>Hidrofor Fiyatlarını Ne Belirler?</h3>
<p>Hidrofor fiyatı; pompa gücü ve kademe sayısı, tank hacmi, gövde malzemesi (döküm veya paslanmaz), pompa adedi ve frekans kontrolü olup olmamasına göre değişir. Aynı pompanın hidromatlı versiyonu genellikle tanklı versiyonundan daha uygundur; paslanmaz gövde ve çok pompalı yapı fiyatı yükseltir. Maliyet kalemlerini <a href="/blog/hidrofor-fiyatlari-2026-ev-apartman">hidrofor fiyatını belirleyen etkenler</a> yazımızda ayrıntılı anlattık.</p>
<p>Markaya göre incelemek için <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak hidrofor</a> ve <a href="/kategoriler/hidrofor-sistemleri/pedrollo-hidrofor">Pedrollo hidrofor</a> kategorilerine, güneş enerjisi tesisatı için <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforu</a> modellerine göz atabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Ev Tipi Hidrofor Rehberi</h3>', <<<'HTML'
<h3>Hidrofor Nasıl Seçilir?</h3>
<p>Doğru hidrofor için dört bilgi yeterlidir: binanın kat sayısı, daire (kullanım noktası) sayısı, suyun geldiği kaynak (şebeke, depo veya kuyu) ve aynı anda açık kalan musluk sayısı. Ürün adlarındaki “4 Kat 6 Daire” gibi ifadeler, üreticinin önerdiği kullanım sınırını gösterir.</p>
<h3>Kat ve Daire Sayısına Göre Seçim</h3>
<ul>
<li><strong>1–2 kat, 1–2 daire:</strong> Kaysu HQBM60, Pedrollo PKm 60 ve Sumak SM5 gibi küçük paket hidroforlar.</li>
<li><strong>3–5 kat, 4–10 daire:</strong> Sumak SMJ 85–150, Pedrollo JSWm 2CX/2AX, Winpo WNP 100–150 ve Kaysu HKJM jet pompalı paket hidroforlar.</li>
<li><strong>6–14 kat:</strong> Sumak SYMH yatay kademeli bina hidroforları, SMINOX paslanmaz kademeli modeller ya da tek pompalı Sumak SHT ve Winpo WNP1 VM.</li>
<li><strong>Çok daireli apartman, site ve otel:</strong> Çift veya üç pompalı Sumak SHT B/C, SHTP ve Winpo WNP2 VM. Yedek pompa sayesinde bir pompa arızalandığında su kesintisi riski azalır.</li>
</ul>
<h3>Tanklı mı, Hidromatlı mı?</h3>
<p>Tanklı hidroforda basınç tankı küçük su çekimlerini karşılar ve pompa daha seyrek çalışır. Hidromatlı modeller daha az yer kaplar ve daha ekonomiktir, ancak her musluk açılışında pompa devreye girer. Farkları <a href="/blog/hidrofor-hidromat-farki">hidromat ile hidrofor farkı</a> yazımızda anlattık.</p>
<h3>Kurulum</h3>
<p>Hidrofor kuru, donmayan ve bakım için erişilebilir bir yere kurulmalıdır. Depo veya kuyudan emişte emiş hattına çekvalf ya da dip klapesi eklenmelidir.</p>
HTML.self::CONTACT],
                'faq' => ['Hidrofor ile normal pompa arasındaki fark nedir?', [
                    ['q' => 'Hidrofor nedir, ne işe yarar?', 'a' => 'Hidrofor; pompa, basınç tankı veya hidromat ve basınç şalterinden oluşan hazır bir sistemdir. Musluk açıldığında tesisattaki basınç düşer ve pompa devreye girer; musluk kapanınca basınç yükselir ve pompa durur. Şebeke basıncının düşük olduğu evlerde, depo veya kuyu suyu kullanılan yerlerde ve üst katlara su çıkmayan binalarda kullanılır.'],
                    ['q' => 'Kaç katlı binaya hangi hidrofor seçilmeli?', 'a' => '1–2 katlı evlerde küçük paket hidroforlar, 3–5 katlı binalarda jet pompalı paket hidroforlar yeterlidir. 6 kattan yüksek binalarda kademeli bina hidroforları, çok daireli apartman ve sitelerde ise çift veya üç pompalı hidroforlar tercih edilir. Ürün adındaki kat ve daire değerleri başlangıç noktasıdır; ayrıntılı örnekler için <a href="/blog/kac-katli-binaya-hangi-hidrofor">kaç katlı binaya hangi hidrofor</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Hidrofor fiyatları neden bu kadar farklı?', 'a' => 'Fiyatı belirleyen başlıca etkenler pompa gücü ve kademe sayısı, tank hacmi, gövde malzemesi, pompa adedi ve frekans kontrolüdür. Tek pompalı hidromatlı bir ev tipi model ile üç pompalı, paslanmaz gövdeli bir bina hidroforu arasında bu yüzden büyük fark bulunur.'],
                    ['q' => 'Hidrofor pompası neden sürekli çalışıyor?', 'a' => 'En sık nedenler <strong>basınç tankı membranının yırtılması, tank ön basıncının düşmesi, basınç şalteri ayarının kayması veya tesisatta su kaçağıdır</strong>. Membran yırtılırsa tankta hava yastığı kalmaz ve pompa sık sık devreye girer. Sistemi kapatıp tank basıncını ve tesisatı kontrol ettirmeniz önerilir.'],
                    ['q' => 'Hangi hidrofor markası seçilmeli?', 'a' => 'Seçim markadan önce ihtiyaca göre yapılmalıdır. Sumak, ev tipi paket hidrofordan çok pompalı bina hidroforlarına kadar geniş bir seriye sahip yerli bir üreticidir. İtalyan Pedrollo ev tipi jet, sessiz ve dijital paket hidroforlarda öne çıkar. Winpo ev tipi paket hidroforların yanında WNP VM dik milli bina hidroforları sunar; Kaysu ise ev tipi paket hidroforlarda ekonomik seçenekler sunar.'],
                ]],
            ],
            129 => [
                'description' => ['<h2>Ev Tipi Hidrofor Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Ev Tipi Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Ev tipi hidrofor</strong>; müstakil ev, villa, yazlık ve az daireli binalarda şebeke ya da depo suyunu musluklara sabit basınçla ulaştıran tek pompalı paket sistemdir. Pompa, basınç tankı veya hidromat ve basınç şalteri birleştirilmiş olarak gelir.</p>
<p>Bu kategoride Pedrollo PKm 60, JSWm, JCRm ve 4CPm; Winpo WNP 100, 150 ve 200; Kaysu HKJM ve HQBM60 serilerinden 1 kat 1 daireden 7 kat 10 daireye kadar modeller bulunur.</p>
<h3>Ev Tipi Hidrofor Çeşitleri</h3>
<ul>
<li><strong>Preferikal (sürtme fanlı) mini hidroforlar:</strong> Pedrollo PKm 60 ve Kaysu PS-370A; 1–2 daireli evler, bağ-bahçe ve düşük debili kullanım içindir.</li>
<li><strong>Jet pompalı paket hidroforlar:</strong> Pedrollo JSWm ve JCRm, Winpo WNP 100/150 ve Kaysu HKJM; kendinden emişli yapıları sayesinde pompadan aşağıda kalan depodan veya sığ kuyudan su çekebilir.</li>
<li><strong>Sessiz paket hidroforlar:</strong> Pedrollo 4CPm 80-C kademeli yapısıyla daha sessiz çalışır ve 4 kat 8 daireye kadar kullanılır.</li>
<li><strong>Dijital kontrollü modeller:</strong> Pedrollo JSWm, JCRm ve 4CPm serilerinin dijital versiyonları basıncı elektronik olarak izler.</li>
</ul>
<p>Sumak'ın ev tipi SM ve SMJ paket hidroforları için <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak hidrofor</a> kategorisine, 8 kattan yüksek binalar için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">tüm hidrofor modellerine</a> bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Ev Tipi Hidrofor Nasıl Seçilir?</h3>
<ul>
<li><strong>Kat ve daire sayısı:</strong> Ürün adındaki “2 Kat 2 Daire”, “4 Kat 6 Daire” gibi değerler seçimin başlangıç noktasıdır.</li>
<li><strong>Su kaynağı:</strong> Hidrofor şebeke basıncını artırmak için mi, yoksa depodan ya da kuyudan su çekmek için mi kullanılacak? Pompadan aşağıda kalan kaynaktan su çekilecekse kendinden emişli jet pompalı model seçin.</li>
<li><strong>Tank tipi:</strong> Hidromatlı modeller kompakt ve ekonomiktir. 24 litre tank küçük su çekimlerinde pompanın sık çalışmasını önler; 50 litre tank aynı anda birden fazla musluğun açık olduğu evlerde daha rahat kullanım sağlar. Yatık tanklı modeller alçak tavanlı alanlara uygundur.</li>
<li><strong>Ses:</strong> Hidrofor yaşam alanına yakın kurulacaksa sessiz veya kademeli modelleri tercih edin.</li>
</ul>
<h3>2 Katlı ve Müstakil Ev İçin Örnek Seçim</h3>
<ul>
<li><strong>Tek daire veya bahçe sulama:</strong> Pedrollo PKm 60 ya da Kaysu HQBM60.</li>
<li><strong>2–3 katlı müstakil ev:</strong> Winpo WNP 100, Kaysu HKJM veya Pedrollo JSWm 2CX; 24 litre tanklı ya da hidromatlı.</li>
<li><strong>4–5 katlı, 5–8 daireli bina:</strong> Winpo WNP 150, Kaysu HKJM15H veya Pedrollo 4CPm 80-C.</li>
</ul>
<p>Daha fazla örnek için <a href="/blog/ev-tipi-hidrofor-rehberi-mustakil-ev-villa">müstakil ev ve villa hidrofor rehberimize</a> göz atabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Ev tipi hidrofor şebeke suyu olan bir evde gerekli mi?', [
                    ['q' => '2 katlı ev için hangi hidrofor alınmalı?', 'a' => '2 katlı müstakil evlerde genellikle “2 Kat 2 Daire” veya “4 Kat 4 Daire” sınıfındaki paket hidroforlar yeterlidir. Winpo WNP 100, Kaysu HKJM ve Pedrollo JSWm 2CX bu sınıftadır. Su depodan ya da kuyudan çekilecekse kendinden emişli jet pompalı model seçin; aynı anda birden fazla banyo kullanılıyorsa 24 litre yerine 50 litre tank tercih edin.'],
                    ['q' => 'Ev tipi hidrofor şebeke suyu olan bir evde gerekli mi?', 'a' => 'Şebeke basıncı <strong>sürekli ve yeterli (2–3 bar)</strong> olan evlerde hidrofor zorunlu değildir. Basınç zaman zaman düşüyorsa, üst katlara yeterli su çıkmıyorsa ya da depodan su kullanıyorsanız hidrofor gerekir. Önce şebeke basıncını bir manometre ile ölçmeniz önerilir.'],
                    ['q' => 'Hidromatlı mı, tanklı ev tipi hidrofor mu seçilmeli?', 'a' => 'Hidromatlı modeller az yer kaplar ve daha ekonomiktir, ancak her su çekiminde pompa çalışır. Tanklı modellerde basınç tankı küçük su çekimlerini karşılar; pompa daha seyrek çalışır ve basınç daha dengeli olur. Günlük kullanımı yoğun evlerde tanklı model daha uygundur.'],
                    ['q' => 'Ev tipi hidrofor kombi ile uyumlu mu?', 'a' => 'Evet. Hidrofor, kombi veya şofben girişinden önce tesisata bağlandığında cihaza gelen su basıncını artırır. Şebeke basıncı kombinin çalışma basıncının altına düştüğünde kombi hata verebilir; hidrofor bu sorunu giderir.'],
                    ['q' => 'Ev tipi hidrofor dışarıya kurulabilir mi?', 'a' => 'Önerilmez. Sıfırın altındaki sıcaklıklarda pompa, basınç tankı ve bağlantı parçaları <strong>donarak çatlayabilir</strong>; yağmur ve güneş de elektrik aksamını yıpratır. Hidroforu kuru, donmayan ve havalandırılan bir iç mekâna kurun; dışarıda kalacaksa korumalı bir dolap kullanın.'],
                ]],
            ],
            127 => [
                'description' => ['<h2>Sıcak Su Hidroforu Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Sıcak Su Hidroforu Modelleri ve Fiyatları</h2>
<p><strong>Sıcak su hidroforu</strong>, çatıdaki güneş enerjisi deposundan düşük basınçla gelen sıcak suyu basınçlandırarak musluk ve duşa şebeke suyuyla dengeli basınçta ulaştıran küçük pompalı sistemdir. Sıcak su soğuk sudan düşük basınçla geldiğinde bataryada sıcaklık ayarı zorlaşır; sıcak su hidroforu bu farkı giderir.</p>
<p>Kategoride iki model bulunur:</p>
<ul>
<li><strong>Sumak SM 7-SH:</strong> 0,5 HP, 220 V, 1" giriş ve çıkış; GG25 döküm gövde, bronz çark ve AISI 304 paslanmaz mil. Üretici verisine göre 0–80 °C sıcak su ile 2 daireye kadar kullanılır.</li>
<li><strong>Winpo WNP 226:</strong> 0,5 HP, 220 V, 1" bağlantı ve 2 litre tank; 1–2 katlı, 1–2 daireli evlerde güneş enerjisi sıcak suyunu basınçlandırır.</li>
</ul>
<h3>Kullanım Alanları</h3>
<ul>
<li>Güneş enerjisi sistemli müstakil ev ve yazlıklar</li>
<li>Sıcak suyun çatı deposundan düşük basınçla geldiği 1–2 daireli binalar</li>
</ul>
<p>Kapalı ısıtma devresinde suyu dolaştırmak için hidrofor değil <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/sicak-su-pompalari">sıcak su sirkülasyon pompası</a> gerekir. Soğuk su basıncı için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlara</a> bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Sıcak Su Hidroforu Seçerken</h3>
<ul>
<li><strong>Daire sayısı:</strong> Kategorideki iki model de 1–2 daireli kullanım içindir. Daha fazla daireye sıcak su dağıtılacaksa merkezi sistem tasarımı gerekir.</li>
<li><strong>Su sıcaklığı:</strong> Güneş enerjisi deposundaki su yaz aylarında çok ısınabilir. Pompanın üretici tarafından belirtilen sıcaklık sınırı aşılmamalıdır; Sumak SM 7-SH için bu değer 80 °C'dir.</li>
<li><strong>Montaj:</strong> Pompa sıcak su hattına, depo çıkışından sonra ve bataryalardan önce bağlanır. Winpo, WNP 226 için yere paralel montaj önerir ve soğuk şebeke hattına bağlanmasını önermez.</li>
<li><strong>Tank kontrolü:</strong> Tanklı modellerde basınç tankının ön basıncı düzenli kontrol edilmelidir; Winpo bu kontrolü 6 ayda bir önerir.</li>
</ul>
<p>Güneş enerjisi ve kombi uygulamaları için <a href="/blog/sicak-su-hidroforu-secimi">sıcak su hidroforu seçimi</a> yazımızı inceleyebilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Sıcak su hidroforu ile sirkülasyon pompası arasındaki fark nedir?', [
                    ['q' => 'Sıcak su hidroforu ne işe yarar?', 'a' => 'Güneş enerjisi deposundan gelen sıcak su çoğu zaman yalnızca depo ile musluk arasındaki yükseklik farkı kadar basınçla gelir; şebekeden gelen soğuk su ise daha yüksek basınçlıdır. Sıcak su hidroforu sıcak su hattının basıncını yükselterek iki hattı dengeler; duşta ve bataryada sıcaklık ayarı kolaylaşır.'],
                    ['q' => 'Sıcak su hidroforu ile sirkülasyon pompası arasındaki fark nedir?', 'a' => '<strong>Sirkülasyon pompası</strong> kapalı devrede (kalorifer, güneş enerjisi kolektör devresi) aynı suyu sürekli dolaştırır; musluk basıncını artırmaz. <strong>Sıcak su hidroforu</strong> ise açık devrede, musluk açıldığında devreye girerek kullanım sıcak suyunun basıncını artırır.'],
                    ['q' => 'Normal hidrofor sıcak suda kullanılabilir mi?', 'a' => 'Önerilmez. Soğuk su hidroforlarının conta, membran ve tankları yüksek sıcaklığa göre seçilmez; sıcak suda erken yıpranabilir. Sıcak su hattında üreticinin sıcak suya uygun olduğunu belirttiği bir model kullanın ve belirtilen sıcaklık sınırını aşmayın.'],
                    ['q' => 'Güneş enerjisinde sıcak su neden az basınçlı gelir?', 'a' => 'Çatıdaki güneş enerjisi deposu basınçsız çalışır ve suyu yalnızca yükseklik farkıyla gönderir. Kabaca her 10 metre yükseklik 1 bar basınç sağlar; depo ile musluk arasındaki mesafe kısaysa, özellikle üst katlarda sıcak su zayıf akar.'],
                    ['q' => 'Sıcak su hidroforunun bakımı nasıl yapılır?', 'a' => 'Tanklı modellerde basınç tankının ön basıncını sistem soğukken ve boşken kontrol edin. Boru bağlantılarında sızıntı olup olmadığına bakın; kireçli suyun olduğu bölgelerde pompa ve bağlantıları daha sık kontrol edin. Kış aylarında sistem kullanılmayacaksa donmaya karşı boşaltılması önerilir.'],
                ]],
            ],
            123 => [
                'description' => ['<h2>Hidromat Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Hidromat Modelleri ve Fiyatları</h2>
<p><strong>Hidromat</strong>, pompanın çıkışına takılan ve pompayı basınç ile akışa göre otomatik çalıştırıp durduran elektronik kontrol cihazıdır. Musluk açıldığında basınç düşer ve hidromat pompayı çalıştırır; musluk kapanıp akış durduğunda pompayı kapatır. Su kesildiğinde pompayı durdurarak susuz çalışmayı önleyen modeller de vardır.</p>
<h3>Kategorideki Modeller</h3>
<ul>
<li><strong>Kaysu Water Bender DSK2.2:</strong> 220/240 V, 10 A; devreye giriş basıncı 1,5–3 bar arasında ayarlanabilir. IP65 gövde, 1" erkek bağlantı ve 1 metre fişli kablo; 90 °C'ye kadar suya uygundur.</li>
<li><strong>Pedrollo Easy Small II:</strong> 2 HP (1,5 kW) pompaya kadar, 16 A, 1" giriş ve çıkış; santrifüj pompayı tanksız otomatik hidrofora dönüştürür.</li>
<li><strong>Winpo WNP-10H:</strong> 1,5 HP'ye kadar pompalar için; entegre çekvalf, manometre, 150 cm fişli kablo ve arıza durumunu gösteren üç uyarı ışığı.</li>
</ul>
<h3>Hidromat mı, Tanklı Hidrofor mu?</h3>
<p>Hidromat az yer kaplar ve tanklı sisteme göre daha ekonomiktir; ancak su depolamadığı için her küçük su çekiminde pompa çalışır. Günlük kullanımı yoğun evlerde basınç tanklı <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a> pompanın daha seyrek çalışmasını sağlar. Pompa ile hidromatın birlikte geldiği hazır paketler için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidrofor modellerine</a> bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Hidromat Seçerken Nelere Bakılmalı?</h3>
<ul>
<li><strong>Pompa gücü ve akımı:</strong> Pompanın etiket akımı hidromatın akım sınırını aşmamalıdır (DSK2.2 için 10 A, Easy Small II için 16 A).</li>
<li><strong>Devreye giriş basıncı:</strong> Hidromatın açma basıncı, en üstteki musluk ile hidromat arasındaki yükseklik farkını karşılamalıdır. Kabaca her 10 metre yükseklik 1 bar basınç demektir.</li>
<li><strong>Pompa basıncı:</strong> Pompanın üretebildiği en yüksek basınç, hidromatın devreye giriş basıncının belirgin şekilde üzerinde olmalıdır; aksi halde sistem düzgün çalışmaz.</li>
<li><strong>Montaj:</strong> Hidromat pompa çıkışına, gövdesindeki akış yönü okuna uygun şekilde takılır. Kaysu ve Pedrollo modelleri 1" bağlantılıdır.</li>
<li><strong>Su kalitesi:</strong> Kum veya kireç taşıyan suda girişe filtre eklemek cihaz ömrünü uzatır.</li>
</ul>
<p>Hidromat ile hidrofor arasındaki farkı <a href="/blog/hidrofor-hidromat-farki">hidromat ve hidrofor farkı rehberimizde</a> ayrıntılı anlattık.</p>
HTML.self::CONTACT],
                'faq' => ['Hidromat ne işe yarar?', [
                    ['q' => 'Hidromat nedir, ne işe yarar?', 'a' => 'Hidromat, pompayı otomatik açıp kapatan elektronik kontrol cihazıdır. Musluk açılınca basınç düşüşünü ve akışı algılayıp pompayı çalıştırır; akış bitince pompayı durdurur. Böylece pompa elle açılıp kapatılmadan hidrofor gibi çalışır.'],
                    ['q' => 'Hidromat basınç tankının yerine geçer mi?', 'a' => 'Küçük sistemlerde kısmen geçer; ancak basınç tankı kadar su depolamadığı için pompa daha sık çalışır. Yoğun kullanımda tanklı hidrofor daha sağlıklıdır.'],
                    ['q' => 'Hidromat kuru çalışma koruması yapar mı?', 'a' => 'Birçok hidromat modelinde kuru çalışma koruması vardır. Su gelmediğini algıladığında pompayı durdurur ve motorun susuz çalışarak zarar görmesini önler. Modelin bu özelliğe sahip olup olmadığını ürün sayfasından kontrol edin.'],
                    ['q' => 'Hidromat hangi pompalarla çalışır?', 'a' => 'Santrifüj, jet ve preferikal yüzey pompalarıyla kullanılabilir. Pompanın basıncı hidromatın devreye giriş basıncını karşılamalı, pompa akımı da hidromatın akım sınırını aşmamalıdır.'],
                    ['q' => 'Hidromat arızası nasıl anlaşılır?', 'a' => 'Pompa hiç çalışmıyorsa, sürekli çalışıyorsa veya musluk kapalıyken sık sık devreye giriyorsa hidromat sensörü, çekvalf ya da basınç ayarı kontrol edilmelidir. Tesisattaki küçük bir su kaçağı da pompanın kısa aralıklarla çalışmasına yol açar.'],
                ]],
            ],
        ];
    }
};
