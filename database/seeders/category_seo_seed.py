# -*- coding: utf-8 -*-
"""
Kategori SEO açıklamaları + FAQ verisi yerel SQLite'a yazar.
Çalıştır: python database/seeders/category_seo_seed.py
"""
import sqlite3
import json
import sys
import os

sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

# ─── Her kategori için (id, description_html, faq_json) ───────────────────────
CATEGORIES = [

    # ────────────────────────────────────────────────────────────────
    # 115 — Su Pompaları (Ana Kategori)
    # ────────────────────────────────────────────────────────────────
    (
        115,
        """<h2>Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Su pompası</strong>, konutlardan sanayi tesislerine, tarım arazilerinden otel binalarına kadar suyun güvenli ve verimli taşınmasını sağlayan temel mekanik ekipmandır. Koşar olarak Pedrollo, Sumak ve Ebara gibi önde gelen markaların yetkili distribütörü sıfatıyla <strong>santrifüj pompa, dalgıç pompa, kademeli pompa ve hidrofor sistemleri</strong> dahil 1.000'i aşkın ürünü tek çatı altında sunuyoruz.</p>

<h3>Su Pompası Türleri ve Kullanım Alanları</h3>
<ul>
  <li><strong>Santrifüj Pompalar</strong> — Tek veya çift fanla yüksek debi, düşük-orta basınç gerektiren sulama ve proses uygulamaları için ideal.</li>
  <li><strong>Dalgıç Pompalar</strong> — Keson kuyu, derin kuyu, drenaj, foseptik ve kirli su gibi su altı uygulamalarında kullanılır.</li>
  <li><strong>Kademeli Pompalar</strong> — Yüksek basınç gerektiren bina tesisatı ve endüstriyel proses sistemlerinde tercih edilir.</li>
  <li><strong>Sirkülasyon Pompaları</strong> — Kalorifer, yerden ısıtma ve sıcak su devridaim sistemleri için tasarlanmıştır.</li>
  <li><strong>Özel Amaçlı Pompalar</strong> — Yangın söndürme, havuz, jakuzi ve dizel motorlu uygulamalar için üretilmiştir.</li>
</ul>

<h3>Pompa Seçiminde Dikkat Edilmesi Gerekenler</h3>
<p>Doğru pompayı seçmek için <strong>debi (m³/saat), manometrik yükseklik (metre), sıvı türü</strong> ve çalışma koşullarını belirlemeniz gerekir. Uzman ekibimiz ücretsiz teknik danışmanlık hizmetiyle ihtiyaçlarınıza en uygun ürünü birlikte belirlemenize yardımcı olur.</p>""",
        [
            {
                "q": "Su pompası seçerken nelere dikkat edilmeli?",
                "a": "Su pompası seçiminde dört temel parametre belirleyicidir: <strong>debi (Q)</strong> — saniyede veya saatte iletmek istediğiniz su miktarı; <strong>manometrik yükseklik (H)</strong> — pompanın suyu basması gereken toplam yükseklik; <strong>sıvı tipi</strong> — temiz su, kirli su, kimyasal veya sıcak su; ve <strong>kurulum yeri</strong> — yüzey mi yoksa su altı mı. Bu dört parametreyi netleştirdikten sonra doğru pompa tipini ve gücünü kolayca belirleyebilirsiniz."
            },
            {
                "q": "Santrifüj pompa ile dalgıç pompa arasındaki fark nedir?",
                "a": "Santrifüj pompalar yüzeye monte edilir ve suyu emme yoluyla çeker; sulama, proses ve bina su tesisatı gibi uygulamalarda kullanılır. Dalgıç pompalar ise suya daldırılarak çalışır; keson kuyu, derin kuyu, drenaj ve foseptik gibi su altı uygulamaları için idealdir. Dalgıç pompalar, uzun emme hattı gerektirmediğinden genellikle daha yüksek verimle çalışır."
            },
            {
                "q": "Ev için kaç watt pompa yeterli olur?",
                "a": "Tek aile konutları için genellikle <strong>600 W – 1.500 W</strong> güç aralığında bir santrifüj pompa ya da hidrofor sistemi yeterlidir. Binadaki daire sayısı, boru hattı uzunluğu ve en yüksek kattaki basınç ihtiyacı belirleyici faktörlerdir. Çok katlı binalarda kademeli pompalar veya yüksek basınçlı hidrofor grupları tercih edilmelidir."
            },
            {
                "q": "Hangi pompalar derin kuyu için uygundur?",
                "a": "<strong>Derin kuyu dalgıç pompaları</strong>, su seviyesinin 8 metrenin altında olduğu kuyularda kullanılmak üzere özel olarak tasarlanmıştır. Kuyunun çapına, su derinliğine ve ihtiyaç duyulan debiye göre pompa modeli seçilir. 4 inç ve 6 inç çaplı derin kuyu pompaları en yaygın tercih edilen seçeneklerdir."
            },
            {
                "q": "Su pompasının garantisi ve ömrü ne kadardır?",
                "a": "Koşar'da sattığımız tüm pompalar <strong>2 yıl üretici garantisi</strong> kapsamındadır. Pedrollo, Sumak ve Ebara markalı ürünler yetkili servis ağıyla desteklenir. Düzenli bakım yapıldığında kaliteli bir pompa <strong>10-20 yıl</strong> sorunsuz çalışabilir. Mekanik contalar ve impeller gibi sarf parçaların zamanında değiştirilmesi pompa ömrünü uzatır."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 130 — Dalgıç Pompalar
    # ────────────────────────────────────────────────────────────────
    (
        130,
        """<h2>Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Dalgıç pompa</strong>, motoruyla birlikte suya daldırılarak çalışan; keson kuyu, derin kuyu, drenaj, fosseptik ve kirli su tahliyesi uygulamalarında tercih edilen elektrikli su pompasıdır. Pedrollo, Sumak ve Ebara markalarında <strong>temiz su, kirli su, drenaj, foseptik, sintine ve çamur pompaları</strong> dahil geniş bir yelpazeyi stokta tutuyoruz.</p>

<h3>Dalgıç Pompa Türleri</h3>
<ul>
  <li><strong>Temiz Su Dalgıç Pompa</strong> — Keson ve derin kuyular ile sarnıç transferi için, içme suyu kalitesinde malzeme kullanılır.</li>
  <li><strong>Drenaj Dalgıç Pompa</strong> — Bodrum, şantiye ve tarım alanlarındaki yağmur suyu ve atık su tahliyesinde kullanılır.</li>
  <li><strong>Foseptik Dalgıç Pompa</strong> — Katı madde geçirebilen gövde tasarımıyla fosseptik ve pissu pompalamasında tercih edilir.</li>
  <li><strong>Kirli Su Dalgıç Pompa</strong> — İnce tortu ve askılı partikül içeren suları tahliye etmek için tasarlanmıştır.</li>
  <li><strong>Paslanmaz Drenaj Dalgıç Pompa</strong> — Korozif veya asitli ortamlarda 316 paslanmaz çelik gövdesiyle uzun ömürlü çalışır.</li>
  <li><strong>Derin Kuyu Dalgıç Pompa</strong> — 4" ve 6" çaplarda, 8 metreden derin kuyular için özel olarak tasarlanmıştır.</li>
</ul>

<h3>Montaj ve Teknik Destek</h3>
<p>Dalgıç pompa seçiminde kuyu çapı, su derinliği, statik ve dinamik su seviyeleri ile ihtiyaç duyulan debi değerleri belirleyicidir. Teknik ekibimiz projenize özel pompa seçiminde ve boyutlandırmada <strong>ücretsiz danışmanlık</strong> sunar.</p>""",
        [
            {
                "q": "Dalgıç pompa ile normal pompa arasındaki fark nedir?",
                "a": "Normal (yüzey) pompalar zemin üstüne monte edilerek suyu emme yoluyla çeker; emme derinliği teorik olarak en fazla 8-9 m ile sınırlıdır. Dalgıç pompalar ise motor ve pompa birimi birlikte suya daldırılır; bu sayede çok daha derin kuyulardaki suyu kolayca basabilir. Ayrıca hava kilidi riski bulunmaz ve genel olarak daha sessiz çalışır."
            },
            {
                "q": "Dalgıç pompa için kuyu çapı ne kadar olmalı?",
                "a": "En yaygın derin kuyu dalgıç pompalar <strong>4 inç (DN100)</strong> çaplıdır ve en az 4\" iç çaplı kuyulara uyar. Daha yüksek debili sistemlerde <strong>6 inç (DN150)</strong> ya da daha büyük çaplı pompalar kullanılır. Keson kuyular için genellikle çap kısıtlaması yoktur; boyuta göre özel modeller mevcuttur."
            },
            {
                "q": "Dalgıç pompa ne kadar süre çalışabilir?",
                "a": "Kaliteli bir dalgıç pompa, su içinde sürekli soğutulduğu için <strong>uzun süreli (7/24) çalışmaya</strong> uygundur. Ancak pompanın suyun altında kalması şarttır; kuruya çalıştırılması motoru kısa sürede yakar. Aşırı ısınmayı önlemek için termik koruma özelliği olan modeller tercih edilmelidir."
            },
            {
                "q": "Kirli su için hangi dalgıç pompa kullanılmalı?",
                "a": "İnce tortu ve küçük partiküller içeren sular için <strong>drenaj dalgıç pompaları</strong>, büyük katı maddeler ve fosseptik suyu için <strong>foseptik (bıçaklı) dalgıç pompalar</strong> kullanılır. Kimyasal veya korozif sıvılar için ise <strong>paslanmaz çelik gövdeli dalgıç pompalar</strong> tercih edilmelidir. Geçirmesi gereken parçacık boyutunu ve pH değerini belirterek doğru modeli seçebiliriz."
            },
            {
                "q": "Dalgıç pompa bakımı nasıl yapılır?",
                "a": "Dalgıç pompalar su içinde çalıştığından büyük ölçüde bakım gerektirmez. Yıllık kontrolde <strong>mekanik conta, impeller ve motor yataklarının</strong> durumuna bakılır. Pompanın düzenli olarak kuru çalıştırılmaması, voltaj dalgalanmalarına karşı sigortalanması ve kışın kullanılmıyorsa kuruya çıkarılarak depolanması ömrünü uzatır."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 132 — Santrifüj Pompalar
    # ────────────────────────────────────────────────────────────────
    (
        132,
        """<h2>Santrifüj Pompa Çeşitleri ve Fiyatları</h2>
<p><strong>Santrifüj pompa</strong>, dönen impeller (fan) yardımıyla sıvıya kinetik enerji kazandıran ve bu enerjiyi basınca dönüştüren en yaygın pompa türüdür. Tarımsal sulama, bina su tesisatı, sanayi proses hatları ve havuz sistemlerinde tercih edilen santrifüj pompalar; <strong>tek fanlı, çift fanlı, paslanmaz çelik ve salyangoz</strong> gibi farklı alt tiplerde üretilmektedir.</p>

<h3>Santrifüj Pompa Türleri</h3>
<ul>
  <li><strong>Tek Fanlı Santrifüj Pompa</strong> — Düşük-orta basınç ve yüksek debili uygulamalar; tarım sulama, bina tesisatı.</li>
  <li><strong>Çift Fanlı Santrifüj Pompa</strong> — Daha yüksek basınç gerektiren sistemlerde, tek fanın yetersiz kaldığı durumlarda kullanılır.</li>
  <li><strong>Paslanmaz Pompalar (Kimyasal)</strong> — Asit, baz veya korozif sıvı içeren sanayi proseslerinde 304/316 paslanmaz çelik ile uzun ömürlü çalışma.</li>
  <li><strong>Salyangoz Pompalar</strong> — Büyük çaplı gövdesiyle yüksek debi gerektiren tarımsal sulama ve yangın sistemlerinde tercih edilir.</li>
  <li><strong>Santrifüj Sulama Pompaları</strong> — Tarla, sera ve bahçe sulaması için ekonomik ve yüksek verimli çözüm.</li>
</ul>

<h3>Verim ve Enerji Tasarrufu</h3>
<p>IE3 sınıfı yüksek verimli motorlarla üretilen modern santrifüj pompalar, aynı debide eski nesil pompalara kıyasla <strong>%15-25 daha az enerji</strong> tüketir. Hız kontrollü (inverter) sistemlerle birlikte kullanıldığında bu tasarruf %40'a kadar çıkabilir.</p>""",
        [
            {
                "q": "Santrifüj pompa hangi uygulamalar için idealdir?",
                "a": "Santrifüj pompalar; <strong>tarımsal sulama, bina içme suyu tesisatı, sanayi soğutma devreleri, havuz dolumu ve yangın söndürme</strong> sistemlerinde yaygın olarak kullanılır. Yüksek debi ve düşük-orta basınç ihtiyacı olan her uygulamada tercih edilen pompa tipidir."
            },
            {
                "q": "Santrifüj pompanın debisi ve basınç değerleri ne anlama gelir?",
                "a": "<strong>Debi (Q)</strong>, pompanın birim zamanda ilettiği sıvı miktarıdır (litre/dakika veya m³/saat). <strong>Basma yüksekliği (H)</strong> ise pompanın sıvıyı basabileceği maksimum yüksekliktir. İkisi birlikte pompa karakteristik eğrisini oluşturur; doğru noktada çalışan pompa en yüksek verimi sağlar."
            },
            {
                "q": "Santrifüj pompa kuruda çalışırsa ne olur?",
                "a": "Santrifüj pompalar mekanik contanın su ile soğutulmasına bağlı çalışır. Kuruda çalıştırıldığında <strong>mekanik conta sadece birkaç dakika içinde yanar</strong> ve pompa arıza yapar. Bu nedenle havuz, sarnıç veya boru hattı boşaltma işlemlerinde pompanın dolu olduğundan emin olunmalı ya da kuru çalışma koruma sensörlü modeller tercih edilmelidir."
            },
            {
                "q": "Salyangoz pompa ile normal santrifüj pompa arasındaki fark nedir?",
                "a": "Salyangoz pompa (volute pompa), büyük çaplı ve kavisli gövde tasarımıyla <strong>çok yüksek debi</strong> kapasitesine sahiptir; tarımsal sulama kanalları ve yangın söndürme sistemlerinde kullanılır. Normal (santrifüj) pompa ise daha kompakt yapısıyla orta debi ve basınç değerleri için uygundur. Salyangoz pompalarda salya yolu genellikle daha düşük basınca rağmen daha fazla su geçirir."
            },
            {
                "q": "Kimyasal sıvılar için hangi pompa kullanılmalı?",
                "a": "Asit, baz, solvent veya diğer korozif sıvılar için <strong>paslanmaz çelik (316L) veya plastik gövdeli santrifüj pompalar</strong> kullanılmalıdır. Sıvının pH değeri, sıcaklığı ve içerdiği partikül büyüklüğü pompa malzeme seçimini doğrudan etkiler. Teknik ekibimiz kullanacağınız sıvının özelliklerini belirterek iletişime geçmenizi önerir."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 119 — Hidrofor Sistemleri (Ana Kategori)
    # ────────────────────────────────────────────────────────────────
    (
        119,
        """<h2>Hidrofor Sistemi Çeşitleri ve Fiyatları</h2>
<p><strong>Hidrofor sistemi</strong>, şebeke suyunun yetersiz veya düzensiz geldiği konut, iş yeri ve sanayi tesislerinde <strong>sabit ve yüksek su basıncı</strong> sağlamak için kullanılan otomatik basınç kontrol sistemidir. Pedrollo ve Sumak markalı tek pompali, çok pompalı ve frekans invertörlü hidrofor grupları başta olmak üzere tüm sistem çözümlerini sunuyoruz.</p>

<h3>Hidrofor Sistemi Türleri</h3>
<ul>
  <li><strong>Ev Tipi Hidroforlar</strong> — Tek daire veya küçük konutlar için 24-50 litre genleşme tanklı, kompakt tasarım.</li>
  <li><strong>Hidrofor Grubu</strong> — Çok katlı apartman, iş yeri ve küçük sanayi tesisleri için yüksek kapasiteli çok pompalı sistemler.</li>
  <li><strong>Sıcak Su Hidroforları</strong> — Sıcak su devridaim hatları ve güneş enerjisi sistemleri için ısıya dayanıklı gövde malzemesi.</li>
  <li><strong>Hidromat</strong> — Otomatik basınç regülasyonu ile küçük ölçekli ticari ve konut uygulamaları.</li>
  <li><strong>Pedrollo ve Sumak Hidrofor</strong> — Avrupa üretimi veya Türkiye'de üretilen güvenilir marka seçenekleri.</li>
</ul>

<h3>Hidrofor Sistemi Avantajları</h3>
<p>Doğru boyutlandırılmış bir hidrofor sistemi; su basıncını <strong>sabit tutar</strong>, pompanın gereksiz çalışmasını önler, enerji tüketimini azaltır ve suyun tükendiği durumlarda otomatik olarak durdurur. Uzman ekibimizden binanızın kat sayısı ve günlük su tüketimine göre ücretsiz sistem boyutlandırma desteği alabilirsiniz.</p>""",
        [
            {
                "q": "Hidrofor sistemi ne işe yarar?",
                "a": "Hidrofor sistemi, pompa, basınç tankı ve otomatik kontrol ünitesinden oluşan bir su basıncı yönetim sistemidir. Şebeke basıncının yetersiz olduğu veya hiç olmadığı durumlarda suyu tanklara alarak <strong>sabit ve yeterli basınçta</strong> dağıtır. Çok katlı binalarda üst katların su basıncını düzenlemek, depolardan su dağıtmak ve şebeke kesintilerine karşı yedek su sağlamak için yaygın olarak kullanılır."
            },
            {
                "q": "Hidrofor sistemi seçerken nelere bakılmalı?",
                "a": "Hidrofor seçiminde; <strong>binanın kat sayısı</strong> (her kat için yaklaşık 1 bar ekstra basınç), <strong>eş zamanlı kullanıcı sayısı</strong> (gerekli maksimum debi), <strong>mevcut şebeke veya depo basıncı</strong> ve <strong>sıcak/soğuk su ayrımı</strong> göz önünde bulundurulmalıdır. Tek katlı evler için 24 lt tanklı kompakt hidroforlar yeterli olurken, 10 katlı bir bina için çok pompalı hidrofor grubu gerekebilir."
            },
            {
                "q": "Hidrofor tankının şişmesi ne anlama gelir?",
                "a": "Hidrofor tankı içinde hava yastığı bulunan bir basınç kabıdır. Tank şişmesi (büyük görünmesi) çoğunlukla <strong>iç diyaframın (membrın) yırtılması</strong> anlamına gelir; bu durumda su ve hava karışır, pompa çok sık devreye girer (kısa devre). Membran yırtılınca tank değiştirilmeli veya membran yenilenmelidir. Semptom olarak pompa dakikada birkaç kez açılıp kapanıyorsa teknisyen çağırılmalıdır."
            },
            {
                "q": "Sıcak su için ayrı bir hidrofor gerekli mi?",
                "a": "Evet, sıcak su hatlarında <strong>ısıya dayanıklı malzemeden</strong> üretilmiş sıcak su hidroforları kullanılmalıdır. Standart soğuk su hidroforlarının plastik diyaframları ve contaları 60°C üzerinde bozulabilir. Güneş enerjisi sistemleri, kombiler ve merkezi sıcak su tesisatları için özel olarak tasarlanmış sıcak su hidroforlarımızı inceleyebilirsiniz."
            },
            {
                "q": "Hidrofor sistemi ne kadar sürede kurulur?",
                "a": "Hazır ev tipi hidroforlar genellikle <strong>yarım-1 saat</strong> içinde kurulabilir; teknik bilgisi olan bir kullanıcı kılavuzu okuyarak kendi başına kurulum yapabilir. Çok pompalı ve frekans invertörlü büyük hidrofor grupları ise profesyonel tesisatçı veya yetkili servis tarafından <strong>1-2 günde</strong> devreye alınır. Koşar olarak İstanbul ve çevresi için yetkili servis yönlendirmesi yapabiliyoruz."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 117 — Vantilatörler (Ana Kategori)
    # ────────────────────────────────────────────────────────────────
    (
        117,
        """<h2>Vantilatör Modelleri ve Fiyatları</h2>
<p><strong>Vantilatör</strong>, hava sirkülasyonu ve soğutma sağlamak için elektrik motoruyla dönen pervaneli fan sistemidir. Koşar olarak <strong>sanayi tipi vantilatörler ve ev tipi vantilatörler</strong> kategorilerinde yüksek verimli, dayanıklı ve enerji tasarruflu modeller sunuyoruz.</p>

<h3>Vantilatör Türleri ve Kullanım Alanları</h3>
<ul>
  <li><strong>Sanayi Tipi Vantilatör</strong> — Fabrika, depo, atölye ve sanayi tesislerinde büyük alan havalandırması ve soğutma için yüksek kapasiteli hava akışı sağlar.</li>
  <li><strong>Ev Tipi Vantilatör</strong> — Konut, ofis ve küçük işyerlerinde konforlu hava sirkülasyonu için ekonomik ve sessiz çalışan modeller.</li>
</ul>

<h3>Doğru Vantilatör Seçimi</h3>
<p>Vantilatör seçiminde <strong>alan büyüklüğü (m²), tavan yüksekliği, gerekli hava debisi (m³/saat) ve ses seviyesi (dB)</strong> başlıca kriterlerdir. Sanayi ortamları için IP koruma sınıfı ve motor sınıfı da göz önünde bulundurulmalıdır. Teknik ekibimiz projenize özel vantilatör seçiminde yardımcı olmaktan memnuniyet duyar.</p>""",
        [
            {
                "q": "Sanayi tipi vantilatör ile ev tipi vantilatör arasındaki fark nedir?",
                "a": "Sanayi tipi vantilatörler; <strong>yüksek hava debisi (genellikle 5.000-50.000 m³/saat ve üzeri)</strong>, güçlü motorlar ve dayanıklı metal gövdelerle büyük alanlarda güçlü hava hareketi sağlar. Ev tipi vantilatörler ise daha küçük kapasiteli, sessiz ve estetik tasarımlarıyla konut ve ofis konforunu hedefler. Sanayi vantilatörleri IP koruma ve toz-nem direnci açısından da üstündür."
            },
            {
                "q": "Vantilatör seçerken hava debisi (m³/saat) nasıl hesaplanır?",
                "a": "Gerekli hava debisi; <strong>alan (m²) × tavan yüksekliği (m) × saatteki hava değişim sayısı</strong> formülüyle hesaplanır. Örneğin, 200 m² × 6 m tavan × 10 değişim/saat = 12.000 m³/saat gereklidir. Isı yükü, insan sayısı veya üretim prosesine göre bu değer artırılmalıdır. Teknik ekibimiz hesaplama konusunda destek sağlar."
            },
            {
                "q": "Vantilatörler enerji tasarruflu mu?",
                "a": "Modern vantilatörler, <strong>EC (elektronik kommütatörlü) motorlar ve inverter teknolojisi</strong> sayesinde yük değişkenliğine göre hız ve güç tüketimini otomatik olarak ayarlar. Bu sistemler standart AC motorlu modellere kıyasla <strong>%30-50 enerji tasarrufu</strong> sağlayabilir. Uzun vadeli enerji maliyeti hesaplaması yaparak model seçmenizi öneririz."
            },
            {
                "q": "Vantilatör bakımı nasıl yapılır?",
                "a": "Vantilatör bakımı; <strong>yılda en az 1-2 kez pervane ve motor yüzeyinin toz temizliği, rulman yağlama kontrolü ve bağlantı vidalarının kontrolünden</strong> oluşur. Toz birikmesi motorun aşırı ısınmasına, rulman aşınmasına ve gürültüye yol açar. Sanayi ortamlarında aylık vizüel kontrol ve yılda bir kapsamlı bakım önerilir."
            },
            {
                "q": "Kapalı ortamda vantilatör yeterli mi, yoksa klima mı almalıyım?",
                "a": "Vantilatörler hava <strong>sirkülasyonu ve taşınan ısıyı dağıtma</strong> işlevi görür; ortam ısısını düşürmez. Dış ortam sıcaklığı 28°C'nin altında ve nem oranı makul düzeydeyse vantilatör konfor sağlar. Yüksek sıcaklık ve nemde gerçek soğutma için klima gereklidir. Bununla birlikte sanayi tesislerinde yüksek debili vantilatörler terleme noktasını ve çalışma konforu önemli ölçüde artırır."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 139 — Kademeli Pompalar
    # ────────────────────────────────────────────────────────────────
    (
        139,
        """<h2>Kademeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Kademeli pompa</strong>, birden fazla çark (impeller) kademesini seri bağlayarak standart santrifüj pompalardan çok daha yüksek basınç değerleri elde eden gelişmiş pompa tipidir. Çok katlı bina su tesisatı, kazan besleme hatları, yüksek basınçlı yıkama sistemleri ve endüstriyel proses uygulamalarında vazgeçilmez bir çözümdür.</p>

<h3>Kademeli Pompa Türleri</h3>
<ul>
  <li><strong>Dikey Kademeli Pompalar</strong> — Kompakt dikey gövde tasarımıyla az yer kaplar; bina tesisatı ve endüstriyel proses hatlarında tercih edilir.</li>
  <li><strong>Monoblok Yatay Kademeli</strong> — Motor ve pompa tek gövdede birleştirilmiş, montajı kolay yatay model.</li>
  <li><strong>Norm Tipi Yatay Kademeli</strong> — Standart flanş ölçülerine sahip, büyük sanayi tesislerinde kolay bakım ve parça değişimi imkânı sunar.</li>
  <li><strong>Yatay Kademeli Pompalar</strong> — Orta ve yüksek basınçlı su transfer hatları için ideal.</li>
</ul>

<h3>Uygulama Alanları</h3>
<p>Kademeli pompalar; <strong>çok katlı bina suyu basıncı artırma, yangın söndürme sistemleri, ters ozmoz (RO) ön basıncı, kazan besleme, soğutma kulesi ve tarımsal damlama sulama</strong> sistemlerinde aktif olarak kullanılmaktadır. Frekans invertörü (VFD) ile kullanıldığında sabit çıkış basıncı ve yüksek enerji verimliliği sağlar.</p>""",
        [
            {
                "q": "Kademeli pompa neden daha yüksek basınç sağlar?",
                "a": "Kademeli pompada su, birbirini takip eden birkaç çark (kademe) arasından geçer; her kademede basınç artışı bir öncekinin üzerine eklenir. Örneğin 5 kademeli bir pompa, aynı motorla tek kademeli bir santrifüj pompaya kıyasla <strong>5 kat daha yüksek basınç</strong> üretebilir. Bu sayede 100 metreden yüksek basma yüksekliği gerektiren uygulamalar için idealdir."
            },
            {
                "q": "Kademeli pompa ile santrifüj pompa hangisi daha verimli?",
                "a": "Her ikisi de santrifüj prensiple çalışır; ancak yüksek basınç uygulamalarında kademeli pompalar, aynı çıkış değerini tek kademeli pompaya kıyasla <strong>daha küçük motor gücü ve daha yüksek verimle</strong> üretir. Düşük basınç ve yüksek debi gerektiren uygulamalarda ise standart santrifüj pompalar daha verimlidir. Uygulamaya göre doğru seçim kritiktir."
            },
            {
                "q": "Dikey kademeli pompa ile yatay kademeli pompa arasındaki fark nedir?",
                "a": "<strong>Dikey kademeli pompalar</strong> az yer kaplar, dik konumda montaj yapılır ve küçük makine dairelerinde avantajlıdır. <strong>Yatay kademeli pompalar</strong> ise geniş çaplarda, yüksek debilerde ve bakımı sık gerektiren sanayi hatlarında kullanılmak üzere norm flanş ölçüleriyle standartlaştırılmıştır. Her iki tip de aynı basınç değerlerini üretebilir."
            },
            {
                "q": "Bina su tesisatında kaç kademeli pompa gerekir?",
                "a": "Bina tesisatında gerekli kademe sayısı; <strong>toplam bina yüksekliği, boru kayıpları ve gereken çıkış basıncına</strong> göre hesaplanır. Kaba kural olarak her 10 metre yükseklik için yaklaşık 1 bar basınç gerekmektedir. 10 katlı bir bina için genellikle 40-60 m basma yüksekliği yeterlidir; bu da 3-5 kademeli bir pompayla sağlanabilir."
            },
            {
                "q": "Frekans invertörlü kademeli pompa ne avantaj sağlar?",
                "a": "Frekans invertörü (VFD/sürücü) takılı kademeli pompalar; <strong>kullanım değişimine göre motor hızını ve güç tüketimini otomatik olarak ayarlar</strong>. Bu, enerji tüketiminde %30-50'ye varan tasarruf, pompa ömrünün uzaması ve sabit çıkış basıncı anlamına gelir. Özellikle yük değişkenliğinin fazla olduğu binalar ve proses hatları için yatırım maliyeti hızla geri döner."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 149 — Sirkülasyon Pompaları
    # ────────────────────────────────────────────────────────────────
    (
        149,
        """<h2>Sirkülasyon Pompası Modelleri ve Fiyatları</h2>
<p><strong>Sirkülasyon pompası</strong>, kapalı devre ısıtma ve soğutma sistemlerinde sıvıyı sürekli dolaştırmak için kullanılan düşük basınçlı, yüksek verimli özel pompa tipidir. Merkezi kalorifer sistemleri, yerden ısıtma, güneş enerjisi devreleri ve sıcak su tesisatları için Pedrollo, Sumak markalı <strong>rekorlu, inline ve flanşlı sirkülasyon pompaları</strong> sunuyoruz.</p>

<h3>Sirkülasyon Pompası Türleri</h3>
<ul>
  <li><strong>Rekorlu Dişli Sirkülasyon Pompaları</strong> — Küçük çaplı boru hattına monte edilmek üzere dişli rekora sahip, konut ısıtma sistemleri için ideal.</li>
  <li><strong>Inline Sirkülasyon Pompaları</strong> — Boru hattına doğrudan monte edilir; büyük konutlar ve küçük ticari binalarda yaygın.</li>
  <li><strong>Flanşlı Sirkülasyon Pompaları</strong> — Büyük ticari ve sanayi binalarının merkezi ısıtma/soğutma sistemleri için yüksek kapasiteli çözüm.</li>
  <li><strong>Sıcak Su Pompaları</strong> — 110°C'ye kadar ısıya dayanıklı gövdesiyle merkezi sıcak su hatları için tasarlanmış.</li>
</ul>

<h3>Neden Kaliteli Sirkülasyon Pompası?</h3>
<p>Isıtma sisteminizde sürekli çalışan sirkülasyon pompası, yılda yaklaşık <strong>8.000 saat</strong> çalışır. Düşük kaliteli bir pompa enerji maliyetinizi artırır, arıza nedeniyle ısınmanızı engeller ve uzun vadede servis masrafı yaratır. Yüksek verimli ECM motorlu sirkülasyon pompaları <strong>%80'e varan verim</strong> oranıyla enerji tasarrufu sağlar.</p>""",
        [
            {
                "q": "Sirkülasyon pompası kalorifer sisteminde neden gereklidir?",
                "a": "Kalorifer sisteminde ısınan su, doğal konveksiyonla tüm radyatörlere yeterli hız ve basınçta ulaşamaz. Sirkülasyon pompası, sıcak suyu <strong>sabit bir hızda ve dengeli basınçla</strong> tüm devre boyunca dolaştırır; bu sayede tüm radyatörler eşit ısınır, kazanın verimi artar ve yakıt/enerji tasarrufu sağlanır."
            },
            {
                "q": "Sirkülasyon pompası sesi artarsa ne yapmalıyım?",
                "a": "Sirkülasyon pompasından gelen gürültü genellikle <strong>hava kilidi (hava kabarcıkları), rulman aşınması veya kavitasyon</strong> belirtisidir. İlk yapılması gereken sistemdeki havanın otomatik hava tahliye vanasından alınmasıdır. Hava alındıktan sonra ses devam ediyorsa rulman veya rotor aşınmış olabilir; pompa yetkili serviste kontrol ettirilmelidir."
            },
            {
                "q": "Yerden ısıtma sistemi için hangi sirkülasyon pompası uygundur?",
                "a": "Yerden ısıtma sistemleri, düşük su sıcaklığı (35-50°C) ve yüksek debi gerektiren geniş boru devrelerine sahiptir. Bu sistemler için <strong>değişken hızlı (ECM motorlu veya hız kademeli) sirkülasyon pompaları</strong> tercih edilmelidir. Pompa seçiminde toplam boru uzunluğu, boru çapı ve ısı yükü hesabı yapılmalıdır."
            },
            {
                "q": "Sirkülasyon pompasının ömrü ne kadar?",
                "a": "Kaliteli bir sirkülasyon pompası, düzenli su kalitesi kontrolü ve sistemde hava bulunmaması koşuluyla <strong>10-15 yıl</strong> sorunsuz çalışabilir. Sert su (kireçli su) bölgelerinde iç yüzeylerde kireç birikimi rulman ve rotor ömrünü kısaltabilir; bu nedenle ısıtma suyuna periyodik olarak antifriz veya inhibitör eklenmesi önerilir."
            },
            {
                "q": "Sirkülasyon pompası mı, hidrofor mu; fark nedir?",
                "a": "Sirkülasyon pompası, <strong>kapalı devre</strong> bir sistemde (kalorifer, yerden ısıtma) sabit miktardaki sıvıyı döngüye sokar; basınç ve debi değerleri düşüktür. Hidrofor ise <strong>açık devreli</strong> su tesisatında şebeke veya depodaki suyu basınçlandırarak dağıtır. Birbirlerinin yerine kullanılmazlar; tamamen farklı uygulamalara yöneliktir."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 128 — Hidroforlar (Alt Kategori – Hidrofor Sistemleri)
    # ────────────────────────────────────────────────────────────────
    (
        128,
        """<h2>Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Hidrofor</strong>, pompa, basınç tankı ve otomatik kontrol ünitesinden oluşan; şebeke suyunun yetersiz geldiği ya da hiç bulunmadığı durumlarda sabit su basıncı sağlayan kompakt su sistemidir. Koşar'da Pedrollo, Sumak ve Ebara markalı ev tipi ve ticari hidrofor modellerini uygun fiyat ve hızlı teslimat seçenekleriyle sunuyoruz.</p>

<h3>Hidrofor Seçim Kriterleri</h3>
<ul>
  <li><strong>Tank Kapasitesi:</strong> Tek daire için 24 lt, apartman için 50-100 lt veya daha büyük tank.</li>
  <li><strong>Pompa Gücü:</strong> Bina yüksekliği ve eş zamanlı kullanıcı sayısına göre belirlenir (0,5 – 5 HP).</li>
  <li><strong>Çalışma Basıncı:</strong> Evsel sistemler için 2-5 bar; sanayi için daha yüksek.</li>
  <li><strong>Pompa Sayısı:</strong> Tek pompalı modeller küçük tesisler için, çift ve üçlü pompalı gruplar büyük binalar için uygundur.</li>
</ul>

<h3>Neden Hidrofor Tercih Edilmeli?</h3>
<p>Hidrofor sistemi pompaların kısa devre çalışmasını önler, şebeke arızalarına karşı yedek su sağlar ve üst katlarda sabit basınç garantisi sunar. <strong>Susuz çalışma koruması, manometre ve otomatik presostat</strong> içeren modeller en az bakım gerektiren güvenilir çözümlerdir.</p>""",
        [
            {
                "q": "Hidrofor ile normal pompa arasındaki fark nedir?",
                "a": "Normal bir su pompası, çalıştığında suyu doğrudan basar ve durur; bu sık açılıp kapanma motoru yıpratır. Hidrofor ise bünyesindeki <strong>basınç tankında önceden sıkıştırılmış hava</strong> sayesinde küçük su çekimlerinde pompa çalışmadan suyu dağıtır. Bu sayede pompa devreye girme sıklığı azalır, motor ömrü uzar ve sabit basınç sağlanır."
            },
            {
                "q": "Evim için hangi kapasitede hidrofor almalıyım?",
                "a": "Tek daire için genellikle <strong>0,5-1 HP motor gücü ve 24 litre tank</strong> yeterlidir. 2-5 katlı müstakil ev için 1-1,5 HP ve 50 litre tank önerilir. Apartman veya iş yerleri için ise <strong>çok pompalı hidrofor grupları</strong> tercih edilmelidir. Doğru boyutlandırma için kat sayısını, boru çapını ve eş zamanlı kullanıcı sayısını bildirmeniz yeterlidir."
            },
            {
                "q": "Hidrofor pompası neden sürekli çalışıyor?",
                "a": "Hidrofor pompasının sürekli çalışması genellikle <strong>basınç tankı membrın yırtılması, presostat ayarı kayması veya sistemde su kaçağı</strong> olduğuna işaret eder. Membrın yırtılırsa tank içinde su birikir, hava yastığı kalmaz ve pompa sürekli devre yapar. Sistemi kapatıp servis çağırmanız önerilir."
            },
            {
                "q": "Hidrofor tankı ne sıklıkla bakım gerektiriyor?",
                "a": "Hidrofor tankı yılda <strong>1-2 kez</strong> basınç ve membran kontrolü gerektirir. Tank ön yüz basıncı (hava tarafı) genellikle 1,5-2 bar'da tutulur; bu değer her sezon kontrol edilmelidir. Membran durumu ve bakteri üremesini önlemek için tankın yılda bir kez boşaltılıp temizlenmesi önerilir."
            },
            {
                "q": "Pedrollo mu Sumak Hidrofor mu daha iyi?",
                "a": "<strong>Pedrollo</strong> İtalya kaynaklı bir marka olup Avrupa üretim standartlarıyla yüksek kalite ve uzun ömür sunar; fiyatı görece daha yüksektir. <strong>Sumak</strong> ise Türkiye'de üretilen, yaygın servis ağı ve uygun fiyatıyla konut segmentinde çok tercih edilen güvenilir bir markadır. Her iki markanın da garantili yetkili servis ağına sahibiz; ihtiyacınıza ve bütçenize göre doğru modeli belirlemenize yardımcı olabiliriz."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 118 — Sanayi Tipi Vantilatör
    # ────────────────────────────────────────────────────────────────
    (
        118,
        """<h2>Sanayi Tipi Vantilatör Modelleri ve Fiyatları</h2>
<p><strong>Sanayi tipi vantilatör</strong>, fabrika, depo, üretim tesisi, atölye ve büyük ticari alanlarda yüksek hava debisi ile etkili havalandırma ve soğutma sağlamak için tasarlanmış endüstriyel fan sistemidir. Dayanıklı metal gövde, güçlü AC/EC motorlar ve yüksek IP koruma sınıfıyla zorlu sanayi koşullarına uygun olan modellerimizi inceleyebilirsiniz.</p>

<h3>Sanayi Vantilatörü Seçim Kriterleri</h3>
<ul>
  <li><strong>Hava Debisi (m³/saat):</strong> Alan büyüklüğü ve gerekli hava değişim sayısına göre belirlenir.</li>
  <li><strong>Pervane Çapı:</strong> Yüksek debi için büyük çaplı pervane; düşük gürültü için büyük çap + düşük devir tercih edilir.</li>
  <li><strong>Motor Tipi:</strong> AC standart veya EC (inverter kontrolü) motor; enerji verimliliği için EC önerilir.</li>
  <li><strong>IP Koruma Sınıfı:</strong> Tozlu ve nemli ortamlar için en az IP44, açık alan kullanımı için IP55 ve üzeri.</li>
  <li><strong>Montaj Tipi:</strong> Zemin, duvar, tavan veya sütun montajlı seçenekler.</li>
</ul>

<h3>Sektörel Kullanım Alanları</h3>
<p>Tekstil, gıda, metal işleme, kimya ve lojistik sektörlerinde <strong>ısı yükü azaltma, nem kontrolü ve kötü koku/duman tahliyesi</strong> için kullanılan sanayi vantilatörleri, çalışan sağlığı ve ürün kalitesi açısından kritik önem taşımaktadır.</p>""",
        [
            {
                "q": "Sanayi tipi vantilatör ne kadar alan soğutabilir?",
                "a": "Bir sanayi vantilatörü doğrudan ortam sıcaklığını düşürmez; ancak <strong>hava hareketi sayesinde çalışanların hissettikleri sıcaklık 3-8°C azalabilir</strong>. Büyük bir pervane (örn. 125 cm çap) 1.000-1.500 m²'lik alanda etkili hava hareketi sağlayabilir. Gerçek soğutma için sanayi tipi klima veya evaporatif soğutucular kullanılmalıdır."
            },
            {
                "q": "Sanayi vantilatörü için gereken elektrik gücü nedir?",
                "a": "Sanayi tipi vantilatörlerin motor güçleri genellikle <strong>0,37 kW – 15 kW</strong> arasında değişir. Küçük atölye vantilatörleri 0,37-0,75 kW ile yetinirken, büyük depo ve fabrikalar için 3-15 kW motor gücü gerekebilir. EC motorlu modeller, aynı performansı AC motorlu modellere kıyasla %30-40 daha az enerji tüketerek sağlar."
            },
            {
                "q": "IP koruma sınıfı neden önemlidir?",
                "a": "IP (Ingress Protection) koruma sınıfı, vantilatör motorunun toz ve neme karşı direncini gösterir. <strong>IP44</strong>: iri toz ve su sıçramasına karşı korumalı (kapalı fabrika içi). <strong>IP55</strong>: her yönden su püskürtmesine ve toza karşı korumalı (açık alan veya yıkama uygulamaları). Doğru IP sınıfı seçimi, motor arızasını ve bakım maliyetini önemli ölçüde azaltır."
            },
            {
                "q": "Sanayi vantilatörü ne sıklıkla bakım yapılmalı?",
                "a": "Sanayi ortamlarında <strong>ayda bir</strong> pervane ve motor yüzeyinin toz temizliği, <strong>6 ayda bir</strong> rulman yağlama ve bağlantı kontrolü, <strong>yılda bir</strong> kapsamlı bakım (motor yatağı, kablo bağlantıları, titreşim ölçümü) yapılması önerilir. Zamanında bakım yapılan bir sanayi vantilatörü 15-20 yıl sorunsuz çalışabilir."
            },
            {
                "q": "Sanayi tipi vantilatör nasıl monte edilir?",
                "a": "Sanayi vantilatörleri <strong>zemin, duvar, tavan veya sütun/kolon</strong> montajlı olarak temin edilebilir. Zemin montajlı modeller yüksek esneklik sunarken, tavan montajlı modeller yer tasarrufu sağlar. Montaj öncesinde taşıyıcı yapının yük kapasitesi kontrol edilmeli, titreşim yalıtım takozları kullanılmalıdır. Elektrik bağlantısı mutlaka yetkili elektrikçi tarafından yapılmalıdır."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 136 — Özel Amaçlı Pompalar
    # ────────────────────────────────────────────────────────────────
    (
        136,
        """<h2>Özel Amaçlı Pompa Çeşitleri ve Fiyatları</h2>
<p><strong>Özel amaçlı pompalar</strong>, standart su pompalarının yetersiz kaldığı spesifik uygulama gereksinimlerine yönelik özel tasarım ve malzeme özellikleriyle üretilen pompa grubudur. Yangın söndürme sistemleri, havuz ve jakuzi tesisatı, dizel motorlu mobil pompaj ve klapeli atık su transferi gibi farklı ihtiyaçlar için uzman çözümler sunuyoruz.</p>

<h3>Özel Amaçlı Pompa Türleri</h3>
<ul>
  <li><strong>Yangın Pompaları</strong> — EN 12845 standardına uygun, yüksek güvenilirlikte ve yedekli sistemlerde kullanılmak üzere tasarlanmış.</li>
  <li><strong>Havuz Pompaları (Ön Filtreli)</strong> — Havuz sirkülasyonu için entegre sepet filtreli, korozyona dayanıklı gövde.</li>
  <li><strong>Jakuzi Pompası</strong> — Düşük gürültülü ve yüksek debili hava/su karışımı için tasarlanmış.</li>
  <li><strong>Klapeli Pompalar</strong> — Pis su ve atık su geri akışını önleyen klapeli (çek valf entegreli) model.</li>
  <li><strong>Dizel Su Motorları</strong> — Şebeke elektriğinin ulaşamadığı tarım ve şantiye alanları için akaryakıt bağımsız mobil pompa.</li>
  <li><strong>Yağmur Suyu Tahliye Pompası</strong> — Yoğun yağış sonrası birikim noktaları için yüksek debili tahliye.</li>
  <li><strong>Foseptik Tahliye Cihazı</strong> — Çok katlı binalarda zemine altındaki tuvalet/banyo atık suyunu zorla yukarı pompalayan özel sistem.</li>
</ul>

<p>İhtiyacınıza uygun özel amaçlı pompa seçimi için uzman ekibimizle iletişime geçebilirsiniz.</p>""",
        [
            {
                "q": "Yangın pompası hangi standartlara uygun olmalı?",
                "a": "Yangın söndürme sistemlerinde kullanılan pompalar, Türkiye'de <strong>TS EN 12845 (sabit yangın söndürme sistemleri standardı)</strong> ve yerel itfaiye/sigorta şirketi gerekliliklerine göre seçilmelidir. Elektrikli ana pompa, dizel yedek pompa ve küçük jockey (basınç takip) pompasından oluşan üçlü sistem en yaygın konfigürasyondur. Yangın pompası seçiminde yetkili yangın tesisatçısından teknik destek alınması zorunludur."
            },
            {
                "q": "Havuz pompası seçiminde nelere bakılır?",
                "a": "Havuz pompası seçiminde <strong>havuz hacmi (m³), filtre türü ve boru çapı</strong> belirleyicidir. Genel kural olarak havuz suyunun 6-8 saatte bir tam devresinin tamamlanması gerekir; buna göre gerekli debi (m³/saat) hesaplanır. Ayrıca korozif havuz kimyasallarına (klor, tuz) karşı dayanıklı <strong>plastik gövde veya paslanmaz çelik impeller</strong> tercih edilmelidir."
            },
            {
                "q": "Dizel su motoru ne zaman tercih edilmeli?",
                "a": "Dizel (mazot) pompa motorları; <strong>elektriğin ulaşamadığı tarım arazileri, şantiyelere geçici su temini, doğal afet ve şebeke kesintisi</strong> gibi durumlarda tercih edilir. Taşınabilir modeller, acil durum pompalama ve yağmur suyu hasadı için de kullanılır. Akaryakıt tüketimi ve bakım maliyeti dikkate alınarak elektrikli sistemlerin mümkün olduğu yerlerde elektrikli pompalar tercih edilmelidir."
            },
            {
                "q": "Foseptik tahliye cihazı ne işe yarar?",
                "a": "Foseptik tahliye cihazı (macerator/lifting station), bodrum veya zemin altındaki tuvalet, duş ve lavabonun <strong>yer çekimine karşı yukarıya doğru pompalanmasını</strong> sağlar. Özellikle bodrum kata banyo veya tuvalet eklenmek istenen binalarda, ana kanalizasyon hattının üstünde kalan yapılarda vazgeçilmezdir. Küçük boyutu ve kolay montajıyla ciddi tadilat maliyetlerini ortadan kaldırır."
            },
            {
                "q": "Jakuzi pompası ile normal su pompası aynı mı?",
                "a": "Hayır. Jakuzi pompası, su ve hava karışımını boru hatları aracılığıyla jakuzi memlerine ileten özel tasarımlı bir sistemdir; <strong>düşük gürültü, titreşim yalıtımı ve sürekli çalışmaya uygun termal koruma</strong> içerir. Normal su pompaları, jakuzi uygulamalarındaki titreşim, nem ve havuz kimyasallarına uzun süre dayanacak şekilde tasarlanmamıştır."
            }
        ]
    ),

    # ────────────────────────────────────────────────────────────────
    # 124 — Hidrofor Grubu
    # ────────────────────────────────────────────────────────────────
    (
        124,
        """<h2>Hidrofor Grubu Modelleri ve Fiyatları</h2>
<p><strong>Hidrofor grubu</strong>, iki veya daha fazla pompanın paralel bağlandığı; yüksek su talebi olan çok katlı apartmanlar, rezidanslar, oteller ve küçük sanayi tesisleri için tasarlanmış profesyonel basınç artırma sistemidir. Yedek pompa desteği, otomatik sıra değiştirme ve akıllı kontrol panosuyla kesintisiz ve güvenilir su basıncı sağlar.</p>

<h3>Hidrofor Grubu Özellikleri</h3>
<ul>
  <li><strong>Çift ve Üçlü Pompa Konfigürasyonu</strong> — Yük paylaşımı ve yedekleme sayesinde yüksek güvenilirlik.</li>
  <li><strong>Otomatik Pompa Sıralama</strong> — Pompalar eşit çalışma saati için otomatik dönüşümlü çalışır; ömür uzar.</li>
  <li><strong>Frekans İnvertörlü (VFD) Modeller</strong> — Anlık su talebine göre pompa hızını ve enerji tüketimini optimize eder.</li>
  <li><strong>Paslanmaz Çelik Manifold</strong> — Uzun ömürlü ve hijienik su dağıtımı için.</li>
  <li><strong>Akıllı Kontrol Paneli</strong> — Arıza alarmı, kuru çalışma koruması ve basınç izleme.</li>
</ul>

<h3>Uygulama Alanları</h3>
<p>10 kat ve üzeri apartmanlar, oteller, hastaneler, alışveriş merkezleri ve küçük sanayi tesisleri için vazgeçilmez olan hidrofor grupları; <strong>tek pompalı sistemlerin yetersiz kaldığı</strong> her durumda tercih edilmelidir.</p>""",
        [
            {
                "q": "Hidrofor grubu ile tek pompalı hidrofor arasındaki fark nedir?",
                "a": "Tek pompalı hidrofor, küçük ve orta ölçekli tesisler için yeterlidir; ancak pompa arıza yaptığında sistem tamamen devre dışı kalır. Hidrofor grubu ise <strong>en az iki pompanın paralel çalıştığı</strong> sistemdir; yük paylaşımı sayesinde pompalar daha az yorulur ve bir pompa arıza yaptığında diğeri devreye girer. Bu kesintisiz hizmet otel, hastane ve çok katlı apartmanlar için kritiktir."
            },
            {
                "q": "Kaç katlı bina için hidrofor grubu gerekli?",
                "a": "Genel kural olarak <strong>8-10 kattan</strong> fazla yapılar ve yüksek eş zamanlı kullanım gerektiren tesislerde çok pompalı hidrofor grupları tercih edilmelidir. Tek pompalı sistemler 6-8 kata kadar yeterli olabilir; ancak kullanıcı sayısı, boru hattı kaybı ve güvenilirlik gereksinimi bu eşiği aşağı çekebilir."
            },
            {
                "q": "Frekans invertörlü (VFD) hidrofor grubu ne avantaj sağlar?",
                "a": "VFD (değişken frekanslı sürücü) kontrollü hidrofor grupları; <strong>kullanım miktarına göre pompa hızını otomatik ayarlayarak enerji tüketimini %30-50 azaltır</strong>. Sabit hızlı gruba kıyasla mekanik yıpranma azalır, basınç darbesi (water hammer) riski düşer ve pompalar daha uzun ömürlü olur. Yatırım maliyeti 2-4 yıl içinde enerji tasarrufu ile geri dönebilir."
            },
            {
                "q": "Hidrofor grubu kurulumu ne kadar sürer?",
                "a": "Hazır (fabrikasyon) hidrofor grupları, temel hazırlık ve boru bağlantıları dahil genellikle <strong>1-3 gün</strong> içinde devreye alınabilir. Büyük ve özelleştirilmiş sistemler daha uzun sürebilir. Koşar olarak yetkili tesisat ekipleriyle proje bazlı kurulum desteği sağlıyoruz; sisteme özel teknik hesaplama ve devreye alma belgeleri de sunulur."
            },
            {
                "q": "Hidrofor grubu bakımı ne sıklıkla yapılmalıdır?",
                "a": "Profesyonel bir hidrofor grubu için önerilen bakım takvimi: <strong>aylık</strong> basınç ve çalışma saati kontrolü; <strong>6 ayda bir</strong> mekanik conta, vana, manometre ve elektrik bağlantısı kontrolü; <strong>yılda bir</strong> kapsamlı bakım (pompa sökümü, impeller kontrolü, kontrol paneli kalibrasyonu). Düzenli bakım arıza riskini minimize eder ve pompanın 15+ yıl çalışmasını sağlar."
            }
        ]
    ),

]

# ─── Veritabanına yaz ─────────────────────────────────────────────────────────
updated = 0
for cat_id, description, faq_list in CATEGORIES:
    faq_json = json.dumps(faq_list, ensure_ascii=False)
    cur.execute(
        "UPDATE categories SET description=?, faq=? WHERE id=?",
        (description, faq_json, cat_id)
    )
    if cur.rowcount:
        updated += 1
        print(f"  ✓ id={cat_id} güncellendi")
    else:
        print(f"  ✗ id={cat_id} BULUNAMADI")

conn.commit()
conn.close()
print(f"\nToplam {updated} kategori güncellendi.")
