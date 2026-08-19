<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class SeedBlogDraftsCommand extends Command
{
    protected $signature = 'blog:seed-drafts {--publish : Hemen yayınla}';

    protected $description = 'blog-drafts klasöründeki markdown dosyalarını blog yazısı olarak ekler';

    public function handle(): int
    {
        $drafts = $this->getDrafts();

        if ($drafts === []) {
            $this->info('Eklenecek yeni taslak yok.');
            return self::SUCCESS;
        }

        foreach ($drafts as $draft) {
            if (BlogPost::where('slug', $draft['slug'])->exists()) {
                $this->warn("Atlanıyor (zaten var): {$draft['slug']}");
                continue;
            }

            BlogPost::create([
                'slug' => $draft['slug'],
                'title' => $draft['title'],
                'excerpt' => $draft['excerpt'],
                'content' => $draft['content'],
                'meta_title' => $draft['meta_title'],
                'meta_description' => $draft['meta_description'],
                'tags' => $draft['tags'],
                'published' => $this->option('publish'),
                'published_at' => $this->option('publish') ? now() : null,
            ]);

            $this->info("Eklendi: {$draft['title']}");
        }

        return self::SUCCESS;
    }

    private function getDrafts(): array
    {
        return [
            $this->hidroforKurulumu(),
        ];
    }

    private function hidroforKurulumu(): array
    {
        return [
            'slug' => 'hidrofor-kurulumu-montaj-rehberi',
            'title' => 'Hidrofor Kurulumu: Adım Adım Montaj ve Bağlantı Rehberi',
            'excerpt' => 'Hidrofor montajı nasıl yapılır? Boru bağlantısı, elektrik tesisatı, presostat ayarı ve ilk çalıştırma adımları. Doğru kurulum ile uzun ömürlü kullanım.',
            'meta_title' => 'Hidrofor Kurulumu: Adım Adım Montaj Rehberi 2026',
            'meta_description' => 'Hidrofor montajı nasıl yapılır? Boru bağlantısı, elektrik tesisatı, presostat ayarı ve ilk çalıştırma adımları. Doğru kurulum ile uzun ömürlü kullanım.',
            'tags' => ['hidrofor', 'kurulum', 'montaj', 'rehber'],
            'content' => <<<'HTML'
<p>Hidrofor sistemi satın aldınız ancak montajı konusunda endişeleriniz mi var? Doğru kurulum, hidroforun verimli çalışması ve uzun ömürlü olması için en kritik adımdır. Bu rehberde ev tipi ve apartman hidroforlarının kurulumunu adım adım anlatıyoruz.</p>

<h2>Hidrofor Kurulumu Öncesi Hazırlık</h2>
<p>Montaja başlamadan önce şu kontrolleri yapmanız gerekir:</p>

<h3>Konum Seçimi</h3>
<ul>
<li><strong>Kuru ve havadar</strong> bir alan tercih edin (bodrum, sığınak veya kapalı balkon)</li>
<li>Doğrudan güneş ışığı ve donma riski olmayan yer</li>
<li>Pompa ile su deposu/şebeke arasındaki mesafe mümkün olduğunca kısa olmalı</li>
<li>Titreşim yalıtımı için lastik paspas veya titreşim takozu kullanın</li>
<li>Bakım için etrafında en az 50 cm boşluk bırakın</li>
</ul>

<h3>Gerekli Malzemeler</h3>
<table>
<thead><tr><th>Malzeme</th><th>Açıklama</th></tr></thead>
<tbody>
<tr><td>Fleksibel bağlantı</td><td>Titreşim iletimini keser</td></tr>
<tr><td>Çekvalf (tek yönlü vana)</td><td>Suyun geri akışını engeller</td></tr>
<tr><td>Manometre</td><td>Basıncı takip etmek için</td></tr>
<tr><td>Küresel vana (2 adet)</td><td>Giriş ve çıkışta bakım kolaylığı</td></tr>
<tr><td>Teflon bant</td><td>Dişli bağlantılarda sızdırmazlık</td></tr>
<tr><td>Uygun çaptaki boru/fitings</td><td>Pompanın giriş-çıkış çapına uygun</td></tr>
</tbody>
</table>

<h3>Elektrik Gereksinimleri</h3>
<ul>
<li>Pompanın etiketindeki voltaj ve amper değerini kontrol edin</li>
<li><strong>Monofaze (220V):</strong> Ev tipi hidroforlar için standart priz yeterli olmayabilir; özel hat çekilmesi önerilir</li>
<li><strong>Trifaze (380V):</strong> Apartman ve sanayi tipi sistemlerde; elektrikçi tarafından bağlanmalı</li>
<li>Topraklama mutlaka yapılmalı — su ve elektrik birlikte çalışıyor</li>
<li>Kaçak akım rölesi (30mA) zorunlu</li>
</ul>

<h2>Adım Adım Hidrofor Montajı</h2>

<h3>Adım 1: Pompa ve Tankın Yerleştirilmesi</h3>
<p>Pompa ünitesini ve basınç tankını seçtiğiniz konuma yerleştirin. <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">Paket hidrofor</a> sistemlerinde pompa ve tank tek gövdede gelir; ayrı parça olarak aldıysanız tank ile pompa arasında esnek bağlantı kullanın.</p>
<p><strong>Dikkat:</strong> Tank yatay mı dikey mi olacak, modeline göre belirlenir. Yatay tankı dik kullanmayın veya tersini yapmayın — membran ömrü kısalır.</p>

<h3>Adım 2: Emme (Giriş) Hattı Bağlantısı</h3>
<ol>
<li>Su kaynağından (depo, kuyu veya şebeke) pompanın emme ağzına boru çekin</li>
<li>Emme hattının en alt noktasına <strong>dip valf (çekvalf)</strong> takın — pompa durduğunda suyun geri akmasını engeller</li>
<li>Boru çapı pompanın emme ağzından küçük olmamalı (genelde 1" veya 1¼")</li>
<li>Emme yüksekliği 8 metreyi geçmemeli (jet pompa hariç)</li>
<li>Emme hattında hava kaçağı olmadığından emin olun — en küçük kaçak pompanın su çekmesini engeller</li>
</ol>

<h3>Adım 3: Basma (Çıkış) Hattı Bağlantısı</h3>
<ol>
<li>Pompanın basma ağzına küresel vana takın</li>
<li>Vanadan sonra tesisat dağıtım hattına bağlayın</li>
<li>Çıkış hattında da çekvalf kullanmanız önerilir (özellikle çok katlı binalarda)</li>
<li>Manometre bağlantısı yapın — basıncı sürekli görebilmeniz gerekir</li>
</ol>

<h3>Adım 4: Basınç Tankı Bağlantısı</h3>
<p>Paket hidroforlarda tank zaten bağlıdır. Ayrı tank kullanıyorsanız:</p>
<ol>
<li>Tankın hava basıncını kontrol edin (genelde pompanın açma basıncının 0.2 bar altında olmalı)</li>
<li><a href="/kategoriler/hidrofor-sistemleri/hidroforlar">Genleşme tankı</a> ile pompa arasındaki bağlantıyı sıkı yapın</li>
<li>Tank membranını kontrol edin — yeni ürünlerde sorun olmaz ama depodan kalan ürünlerde membran kurumuş olabilir</li>
</ol>

<h3>Adım 5: Elektrik Bağlantısı</h3>
<ol>
<li>Pompayı elektriğe bağlamadan önce şalteri kapatın</li>
<li>Kablo kesitini pompanın amperajına göre seçin:
<ul>
<li>0.5-1 HP: 2.5 mm² yeterli</li>
<li>1.5-2 HP: 4 mm² önerilir</li>
<li>3 HP ve üzeri: 6 mm² veya elektrikçi hesaplaması</li>
</ul></li>
<li>Topraklama kablosunu (sarı-yeşil) mutlaka bağlayın</li>
<li>Kaçak akım rölesi devrede olmalı</li>
<li>Presostat kablolarını kontrol edin — fabrika ayarında gelir, genelde dokunmanız gerekmez</li>
</ol>

<h3>Adım 6: İlk Çalıştırma (Priming)</h3>
<p>Bu adım kritiktir. Pompayı kuru çalıştırmak mekanik salmastrayı yakar.</p>
<ol>
<li>Emme hattındaki vanayı açın</li>
<li>Pompanın üzerindeki <strong>priming kapağını</strong> (su doldurma tapası) açın</li>
<li>Pompayı ve emme hattını tamamen su ile doldurun</li>
<li>Hava kabarcığı kalmadığından emin olun</li>
<li>Priming kapağını sıkıca kapatın</li>
<li>Basma tarafındaki vanayı açın</li>
<li>Elektriği verin — pompa çalışmaya başlayacak</li>
<li>Manometreden basınç yükselişini takip edin</li>
<li>Presostat ayarlı basınca ulaştığında pompa otomatik duracak</li>
</ol>
<p><strong>Pompa durmuyorsa:</strong> Hava kaçağı var veya emme hattında sorun var. Elektriği kesin, tekrar priming yapın.</p>

<h3>Adım 7: Presostat Ayarı</h3>
<p>Çoğu <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidrofor</a> fabrika ayarlı gelir:</p>
<ul>
<li><strong>Açma basıncı:</strong> 1.5-2 bar (pompa çalışmaya başlar)</li>
<li><strong>Kapama basıncı:</strong> 3-3.5 bar (pompa durur)</li>
</ul>
<p>Binanızın kat sayısına göre ayar gerekebilir:</p>
<ul>
<li>Her kat ≈ 0.3 bar ek basınç gerektirir</li>
<li>5 katlı bina: kapama basıncı minimum 3.5 bar olmalı</li>
<li>10 katlı bina: minimum 5 bar (çok katlı sistemlerde profesyonel ayar gerekir)</li>
</ul>

<h2>Sık Yapılan Kurulum Hataları</h2>
<table>
<thead><tr><th>Hata</th><th>Sonucu</th><th>Çözüm</th></tr></thead>
<tbody>
<tr><td>Kuru çalıştırma</td><td>Salmastra yanar, pompa arızalanır</td><td>Her zaman priming yapın</td></tr>
<tr><td>İnce emme borusu</td><td>Pompa tam kapasite çalışmaz</td><td>Pompa ağzı ile aynı çap</td></tr>
<tr><td>Hava kaçağı</td><td>Pompa su basmaz veya kesik çalışır</td><td>Tüm bağlantıları teflon ile sıkın</td></tr>
<tr><td>Topraklama yok</td><td>Kaçak akım riski, hayati tehlike</td><td>Mutlaka topraklı hat</td></tr>
<tr><td>Tank hava basıncı yanlış</td><td>Pompa sık sık açılıp kapanır</td><td>Açma basıncının 0.2 bar altı</td></tr>
<tr><td>Çekvalf unutma</td><td>Su geri akar, pompa boşta çalışır</td><td>Emme hattına dip valf takın</td></tr>
</tbody>
</table>

<h2>Ne Zaman Profesyonel Yardım Gerekir?</h2>
<ul>
<li>3 HP üzeri pompalar</li>
<li>Trifaze (380V) sistemler</li>
<li>Çok katlı bina hidroforu (10+ daire)</li>
<li><a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">Frekans kontrollü hidrofor</a> sistemleri</li>
<li>Yangın hidroforu kurulumu</li>
</ul>
<p>Bu durumlar için yetkili teknik servis veya sertifikalı tesisatçı ile çalışmanızı öneririz. <a href="/iletisim">Koşar Ticaret teknik destek ekibimiz</a> kurulum öncesi danışmanlık sağlayabilir.</p>

<h2>Kurulum Sonrası Kontrol Listesi</h2>
<ul>
<li>Tüm bağlantılar sızdırmaz mı?</li>
<li>Pompa presostat basıncında otomatik duruyor mu?</li>
<li>Musluklardan düzenli basınçla su geliyor mu?</li>
<li>Pompa çalışırken anormal ses veya titreşim var mı?</li>
<li>Manometre doğru basınç gösteriyor mu?</li>
<li>Topraklama ve kaçak akım rölesi aktif mi?</li>
</ul>

<h2>Sıkça Sorulan Sorular</h2>

<h3>Hidrofor kurulumu ne kadar sürer?</h3>
<p>Ev tipi paket hidrofor kurulumu ortalama 2-4 saat sürer. Apartman tipi sistemlerde 1 tam gün gerekebilir.</p>

<h3>Hidrofor kurulumunu kendim yapabilir miyim?</h3>
<p>Ev tipi monofaze (220V) sistemleri temel tesisat bilgisi olan kişiler kurabilir. Ancak trifaze ve çok katlı sistemlerde mutlaka profesyonel destek alın.</p>

<h3>Hidrofor nereye konulmalı?</h3>
<p>Kuru, havadar, donmayan ve su kaynağına yakın bir alan ideal. Bodrum katı veya kapalı tesisat odası en yaygın tercihlerdir.</p>

<h3>Emme borusu ne kadar uzun olabilir?</h3>
<p>Yatay mesafede 20-30 metreye kadar çıkılabilir ancak her 10 metre yatay mesafe yaklaşık 1 metre emme yüksekliği kaybettirir. Dikey emme yüksekliği standart pompalarda maksimum 8 metredir.</p>

<h3>Kurulumdan sonra pompa sürekli çalışıyorsa ne yapmalıyım?</h3>
<p>Emme hattında hava kaçağı, çekvalf arızası veya presostat ayar bozukluğu olabilir. <a href="/blog/hidrofor-surekli-calisiyor-7-olasi-sebep-ve-cozum">Hidrofor sürekli çalışıyor rehberimizi</a> inceleyin.</p>

<p><em>İhtiyacınıza uygun hidroforu seçmek için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">Hidrofor Modelleri ve Fiyatları</a> sayfamızı ziyaret edin. Sumak, Pedrollo ve Winpo markalarında yetkili bayi güvencesiyle hizmet veriyoruz.</em></p>
HTML,
        ];
    }
}
