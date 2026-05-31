# -*- coding: utf-8 -*-
# Part 2: Dalgic kalan + Santrifuj alt + Kademeli alt + Sirkulasyon alt
import sqlite3, json, sys, os
sys.stdout.reconfigure(encoding='utf-8')
DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

CATS = [

# ── 148 Kirli Su Dalgic Pompa ─────────────────────────────────────────────────
(148,
"""<h2>Kirli Su Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Kirli su dalgıç pompası</strong>, ince çamur, kum, tortu ve askılı madde içeren suları boşaltmak için kullanılan güçlendirilmiş daldırmalı elektrik pompasıdır. Standart drenaj pompasından farklı olarak daha büyük parçacık geçiş kapasitesine (20-35 mm) sahiptir ve aşınmaya dayanıklı malzemeden impeller üretilir. Şantiye, kazı alanı, zemin altı taşkınları ve hafif sanayi atık suyu tahliyesinde yaygın kullanılır.</p>
<h3>Ne Zaman Kirli Su Pompası Seçilmeli?</h3>
<ul>
  <li>Kum, çakıl ve ince taş içeren sular için (şantiye, inşaat)</li>
  <li>Çiftlik ve ahır atık sularında (saman kırıntısı, gübre sıvısı)</li>
  <li>Seller ve taşkın sularında (yoğun tortu)</li>
  <li>Hafif sanayi proseslerindeki bulanık atık sularda</li>
</ul>
<p>Katı madde boyutu 35 mm'yi geçiyorsa <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompa</a>, kanallar ve tarımsal drenaj için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompa</a> sayfamıza bakınız.</p>""",
[
{"q":"Kirli su pompası ile drenaj pompası arasındaki fark nedir?",
 "a":"Drenaj pompası genellikle 10-15 mm parçacık geçiş kapasitesine sahipken kirli su pompası <strong>20-35 mm</strong> katı madde geçirebilir. Kirli su pompasının impeller malzemesi (genellikle yüksek krom veya sertleştirilmiş çelik) drenaj pompasına kıyasla aşınmaya çok daha dayanıklıdır. Pompayı sürekli yoğun tortu içeren sularda kullanıyorsanız kirli su modeli daha uzun ömürlüdür."},
{"q":"Kirli su pompası temiz su için de kullanılabilir mi?",
 "a":"Evet, teknik olarak kullanılabilir; ancak temiz su içme veya sulama sistemlerinde <strong>gıda uyumlu (NSF) malzeme belgesi olmayan</strong> kirli su pompasının kullanılması önerilmez. Kirli su pompasının impeller ve conta malzemeleri temiz suya zarar vermez; ancak içme suyu tesisatı için ayrıca sertifikalı temiz su modelleri tercih edilmelidir."},
{"q":"Şantiyede kirli su pompasını yağmurlu havada kullanmak güvenli mi?",
 "a":"IP68 sertifikalı kirli su dalgıç pompaları <strong>sürekli su altında çalışmak</strong> üzere tasarlanmıştır; yağmurlu hava herhangi bir ek risk yaratmaz. Dikkat edilmesi gereken nokta pompanın elektrik kablosu ve prize bağlantısıdır: kablo hasarı veya düzgün topraklanmamış elektrik bağlantısı ciddi elektrik çarpma tehlikesi yaratır. Şantiyede daima kaçak akım rölesi (RCD/RCCB) kullanılmalıdır."},
{"q":"Kirli su pompasında impeller aşınmasını nasıl azaltabilirim?",
 "a":"Aşınmayı en aza indirmenin yolu doğru pompa seçimidir: <strong>yüksek krom çelik veya sertleştirilmiş döküm impeller</strong> standart plastik/bronz impellere göre 3-5 kat daha uzun süre dayanır. Kum ve çakıl konsantrasyonu yüksek sularda pompa çalışma süresini kısaltmak (küçük partilerle boşaltım) da aşınmayı azaltır. Mümkünse suyun içindeki iri taşları filtreleyecek ızgara kullanılmalıdır."},
{"q":"Kirli su pompası motorunun korunması için ne yapmalıyım?",
 "a":"Motor koruması için: <strong>termik-manyetik motor koruma şalteri</strong> (Horoz Electric gibi markalarda bulabilirsiniz) ve <strong>kaçak akım rölesi (RCD)</strong> kullanın. Kablo hasarını önlemek için kablo zırhlanmış (armored) veya kablo kılıfı çarpma dayanımlı olmalıdır. Pompayı taşırken ve kaldırırken kablodan değil pompa gövdesinden veya kulpundan tutun — kabloya yapılan baskı iç iletkenlerini kopuşa götürür."}
],
"Kirli Su Dalgıç Pompa Fiyatları | Koşar Ticaret",
"Şantiye, kazı alanı ve çamurlu su tahliyesi için kirli su dalgıç pompaları. 20-35 mm parçacık "
"geçişi, aşınmaya dayanıklı impeller. Koşar Ticaret'ten garantili fiyatlarla."),

# ── 135 Paslanmaz Drenaj Dalgic Pompa ────────────────────────────────────────
(135,
"""<h2>Paslanmaz Drenaj Dalgıç Pompa Fiyatları ve Modelleri</h2>
<p><strong>Paslanmaz çelik drenaj dalgıç pompası</strong>, AISI 304 veya AISI 316L paslanmaz çelik gövde ve impellerle üretilen; tuzlu su, havuz suyu, hafif asidik/bazik sıvılar ve kimyasal atık suların tahliyesinde kullanılan korozyona karşı üstün dirençli daldırmalı pompadır. Standart döküm demir veya plastik gövdeli drenaj pompalarının kısa sürede paslanarak bozulduğu ortamlarda paslanmaz modeller <strong>5-10 kat daha uzun ömür</strong> sunar.</p>
<h3>Tercih Edildiği Ortamlar</h3>
<ul>
  <li>Yüzme havuzu ve termal havuz suyu tahliyesi (klorlu su)</li>
  <li>Deniz suyu veya tuzlu göl suyu pompalama</li>
  <li>Gıda ve içecek sanayi atık suları</li>
  <li>pH 3-11 aralığındaki endüstriyel prosesler</li>
</ul>
<p>Kimyasal proses pompalama için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal">paslanmaz santrifüj pompalar</a>, genel drenaj için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompalar</a> sayfamıza da bakınız.</p>""",
[
{"q":"AISI 304 ve AISI 316 paslanmaz çelik arasındaki fark nedir?",
 "a":"Her ikisi de paslanmaz çelik olmakla birlikte <strong>AISI 316L</strong>, molibden içeriği sayesinde klorit (deniz suyu, havuz kimyasalları) ve asitlere karşı çok daha yüksek direnç gösterir. AISI 304, genel kullanım (tatlı su, gıda, tıbbi) için yeterlidir. Tuzlu su veya klor yoğun ortamlarda kesinlikle 316L tercih edilmelidir; 304 bu ortamlarda 'paslanmaz' olarak tanımlanmasına rağmen çukur korozyonuna uğrayabilir."},
{"q":"Paslanmaz drenaj pompası havuz suyu tahliyesi için uygun mu?",
 "a":"Evet. Havuz suyundaki klor ve pH dengeleyici kimyasallar standart plastik veya demir pompalar için korozif olmakla birlikte <strong>316L paslanmaz çelik gövde ve impellerli pompalar</strong> bu ortama idealdir. Havuz tahliyesinde ayrıca pompanın havuz dibindeki kum filtresini karıştırmayacak şekilde konumlandırılmasına dikkat edilmeli; filtre çamuru pompayı tıkayabilir."},
{"q":"Paslanmaz pompa bakımı standart pompadan farklı mı?",
 "a":"Genel bakım prosedürü aynıdır: conta, rulman kontrolü ve temizlik. Ancak paslanmaz pompalar birleşme yüzeylerinde 'seize' (metal yüzeylerin birbirine yapışması) riski taşır. Sökme-takma işlemi sırasında <strong>gıda uyumlu koper paste veya anti-seize bileşik</strong> kullanılmalıdır. Tuzlu su ortamında kullanım sonrası her zaman tatlı suyla durulama yapılması ömrü uzatır."},
{"q":"Gıda sanayii için paslanmaz pompa alırken nelere dikkat edilmeli?",
 "a":"Gıda ve içecek sanayiinde kullanılacak pompaların <strong>FDA, NSF/ANSI 61 veya EHEDG (Avrupa Gıda Ekipmanları Tasarımı Grubu)</strong> belgesi olması zorunludur. Bu belgeler pompa malzemelerinin gıda temaslı yüzeylerde güvenli olduğunu kanıtlar. Contalar da gıda uyumlu EPDM veya PTFE malzemeden olmalıdır; standart NBR kauçuk conta içme suyu sistemlerinde kullanılamaz."},
{"q":"Paslanmaz drenaj pompasının fiyatı neden standart pompadan çok yüksek?",
 "a":"316L paslanmaz çeliğin hammadde maliyeti, döküm demir veya polipropilene göre <strong>4-6 kat</strong> daha yüksektir. Ayrıca işleme (talaşlı imalat, kaynak, yüzey işlemi) maliyeti de çok fazladır. Buna karşın paslanmaz pompa korozif ortamlarda 8-15 yıl dayanabilirken standart pompa aynı ortamda 1-3 yılda değiştirilmek zorunda kalabilir; uzun vadede toplam maliyet paslanmaz pompada genellikle daha düşük çıkar."}
],
"Paslanmaz Drenaj Dalgıç Pompa Fiyatları | Koşar Ticaret",
"Havuz, deniz suyu ve kimyasal atık su için AISI 304/316L paslanmaz drenaj dalgıç pompaları. "
"Korozyona dayanıklı, uzun ömürlü. Koşar Ticaret'ten garantili uygun fiyat."),

# ── 158 Salyangoz Pompalar ────────────────────────────────────────────────────
(158,
"""<h2>Salyangoz Pompa (Volut Pompası) Modelleri ve Fiyatları</h2>
<p><strong>Salyangoz pompa</strong> (volut / snail pump), geniş dairesel gövdesi ve büyük çaplı impelleriyle <strong>çok yüksek debi, düşük-orta basınç</strong> kombinasyonu sağlayan yatay eksenli santrifüj pompadır. "Salyangoz" adını aldığı karakteristik spiral (volut) gövde tasarımı, sıvının yüksek hızdan düşük hıza yavaşça dönüştürülmesini sağlar; bu da basınç kazancını optimize eder. Tarımsal sulama kanalları, yangın söndürme rezervuarları, büyük bina soğutma devreleri ve sanayi proses hatlarında tercih edilir.</p>
<h3>Tipik Uygulama Değerleri</h3>
<ul>
  <li>Debi: 20 – 5.000 m³/s arasında geniş yelpazeye sahip</li>
  <li>Basma yüksekliği: 5 – 40 m (tek kademede)</li>
  <li>Büyük çaplı boru bağlantıları (DN80 – DN400 ve üzeri)</li>
</ul>
<p>Daha yüksek basınç gerektiren uygulamalar için <a href="/kategoriler/su-pompalari/kademeli-pompalar">kademeli pompalar</a>, normal debi sulama için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/tek-fanli-santrifuj-pompa">tek fanlı santrifüj pompalar</a> sayfamıza bakınız. <a href="/marka/pedrollo">Pedrollo</a> markasında salyangoz modeller mevcuttur.</p>""",
[
{"q":"Salyangoz pompa neden bu kadar büyük debi sağlayabilir?",
 "a":"Salyangoz pompanın yüksek debi kapasitesi iki faktörden kaynaklanır: büyük çaplı ve geniş kanallı impeller ile volut (salyangoz spiral) gövde tasarımı. Büyük impeller çapı daha fazla sıvıyı döngüye sokabilirken volut gövde, sıvıyı yavaşlatarak kinetik enerjiyi basınca dönüştürür — tüm bu süreç boyunca akış yolunu daraltmadan genişleterek çok yüksek debi akışına izin verir."},
{"q":"Salyangoz pompa yangın söndürme sistemleri için uygun mu?",
 "a":"Evet, büyük yangın söndürme rezervuarlarından yangın hatlarına yüksek debide su aktarmak için salyangoz pompalar idealdir. Ancak <strong>EN 12845 veya NFPA 20 standardı</strong> kapsamındaki bina yangın söndürme sistemleri özel sertifikalı yangın pompaları gerektirmektedir. Salyangoz pompa yangın rezervuarından ana hat tankına besleme (besleme pompası) olarak kullanılabilir; ancak son deşarj hattında lisanslı yangın pompasına ihtiyaç duyulur."},
{"q":"Salyangoz pompa büyük tarla sulaması için ne kadar verimli?",
 "a":"Sulama kanalından büyük tarım arazisine su aktarmak için salyangoz pompa mükemmel bir tercihtir. Tipik bir tarımsal uygulamada <strong>500-2.000 m³/saat debi ile 10-30 m basma yüksekliği</strong> sağlanır. Enerji verimliliği açısından doğru boyutlandırılmış bir salyangoz pompa yüksek verim noktasında (%80-88) çalışabilir. Boyutlandırma için toplam sulama alanı, sulama süresi ve boru hattı kayıpları hesabı gereklidir."},
{"q":"Salyangoz pompanın bakımı zor mu?",
 "a":"Salyangoz pompaların gövde tasarımı servis erişimini kolaylaştırır: büyük çaplı impeller ve geniş gövde, iç bileşenlere ulaşmayı standart santrifüj pompalara kıyasla daha basit kılar. Rutin bakımda <strong>mekanik conta, doldurma halkası veya salmastra, rulmanlar ve kopling bağlantısı</strong> kontrol edilir. Büyük güçlü modellerde (30 kW+) titreşim ölçümü ve balans kontrolü önerilir."},
{"q":"Salyangoz pompa dikey mi yatay mı konumlandırılmalı?",
 "a":"Standart salyangoz pompalar <strong>yatay eksenli</strong> tasarımdadır ve yatay zemine monte edilir. Dikey eksenli varyantları da mevcuttur (dikey volut pompa) ancak çok daha büyük tesisler için kullanılır. Yatay montajda pompanın altına sağlam beton temel veya çerçeve gereklidir; titreşim yalıtım takozu kullanılması hem gürültüyü azaltır hem de boru bağlantılarına binen yükü hafifletir."}
],
"Salyangoz Pompa (Bol Su Veren) Fiyatları | Koşar Ticaret",
"Tarım sulaması, yangın rezervuarı ve sanayi için salyangoz volut pompalar. Yüksek debi, "
"DN80-DN400 bağlantı, geniş kapasite aralığı. Koşar Ticaret'te uygun fiyat."),

# ── 140 Dikey Kademeli Pompalar ───────────────────────────────────────────────
(140,
"""<h2>Dikey Kademeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Dikey kademeli pompa</strong> (dikey multistage pompa), birden fazla çarkın dikey eksen üzerinde seri bağlandığı; dar ve yüksek sütun profiliyle az yer kaplayan yüksek basınçlı pompa tipidir. Çok katlı bina su tesisatı, sanayi proses hatları ve baskı artırma sistemlerinde kompakt kurulum gerektiren her uygulamada tercih edilir. Bir ünite aynı zemin alanında 8-20 bağımsız çark barındırabilir.</p>
<h3>Teknik Avantajlar</h3>
<ul>
  <li><strong>Küçük taban alanı:</strong> Motor ve pompa aynı dikey eksende; ayrı bağlantı gerekmiyor</li>
  <li><strong>Yüksek basınç:</strong> 20 ila 200 m basma yüksekliği tek üniteyle</li>
  <li><strong>Gürültüsüz:</strong> Bağlantı parçası olmadığından titreşim azalır</li>
  <li><strong>Kolay montaj:</strong> In-line (boru hattı üstü) kurulum seçeneği</li>
</ul>
<p><a href="/kategoriler/su-pompalari/kademeli-pompalar">Tüm kademeli pompa çeşitleri</a> | <a href="/kategoriler/su-pompalari/kademeli-pompalar/monoblok-yatay-kademeli">Monoblok yatay kademeli pompalar</a> | <a href="/marka/pedrollo">Pedrollo 3/4CRm serisi</a></p>""",
[
{"q":"Dikey kademeli pompa kaç katlı binaya uygun?",
 "a":"Dikey kademeli pompalar genellikle <strong>5 ile 30+ katlı</strong> binalarda kullanılır. Kaba hesap olarak her kat için 0,3-0,4 bar ek basınç gerekir; 10 katlı bir bina için yaklaşık 35-40 m basma yüksekliği (verimli çalışma noktasında) yeterlidir. Kademe sayısı (3 kademe, 5 kademe, 8 kademe vb.) ve motor gücü binanın yüksekliğine ve eş zamanlı kullanıcı sayısına göre seçilir."},
{"q":"Dikey kademeli pompa ile yatay kademeli pompa arasındaki fark nedir?",
 "a":"<strong>Dikey kademeli pompa</strong> düşey monte edilir; giriş ve çıkış aynı eksen üzerindedir (in-line), az yer kaplar ve motor bağlantısı yoktur. <strong>Yatay kademeli pompa</strong> zemine yatık konumlandırılır; daha yüksek debilerde kullanılır ve kopling bağlantısı gerektiren modeller mevcuttur. Küçük makine daireleri için dikey, büyük debili proses hatları için yatay kademeli tercih edilir."},
{"q":"Dikey kademeli pompa neden titreşim yapıyor?",
 "a":"Titreşimin başlıca nedenleri: <strong>hidrolik dengesizlik (kavitasyon veya yüksüz çalışma), rulman aşınması veya pompa milinin doğrusal hizasından sapması</strong>. İlk yapılacak iş basınç ve debi değerlerinin tasarım aralığında olduğunu doğrulamaktır. Pompanın yeterli emme basıncı (NPSH) sağlandığından emin olun; kavitasyon çok yüksek frekanslı titreşim ve gürültü yapar. Rulman arızası ise düşük frekanslı titreşim üretir."},
{"q":"Dikey kademeli pompa inverter (VFD) ile kullanılabilir mi?",
 "a":"Evet ve önerilir. VFD kontrollü dikey kademeli pompa, <strong>anlık su talebine göre motor hızını ve dolayısıyla debisi ile basıncını otomatik ayarlar</strong>. Sabit hızlı pompalarda başarılabilir enerji tasarrufu inverter ile %30-50'ye ulaşır. Özellikle değişken kullanım profili olan konutlarda (gündüz az, sabah-akşam yoğun) inverter entegrasyonu yatırım maliyetini 2-4 yılda geri öder."},
{"q":"Dikey kademeli pompa için montaj zemini nasıl hazırlanmalı?",
 "a":"Dikey kademeli pompalar ya <strong>boru hattı üstüne doğrudan (in-line)</strong> ya da ayrı bir beton/çelik tabla üstüne monte edilir. Boru hattı montajında bağlantı flanşlarının standarda (PN10 veya PN16) uygunluğu, boru hattının pompanın ağırlığını taşıyacak şekilde desteklenmesi ve titreşim yalıtım bağlantı parçalarının kullanılması gerekir. Beton tabla montajında pompa altına sünger esaslı titreşim takozları konulmalıdır."}
],
"Dikey Kademeli Pompa Fiyatları | Koşar Ticaret",
"Çok katlı bina ve sanayi için in-line dikey kademeli pompalar. 20-200m basma yüksekliği, "
"kompakt dikey montaj. Pedrollo, Sumak garantisi. Koşar Ticaret yetkili satıcısı."),

# ── 150 Rekorlu Disli Sirkulasyon Pompalari ───────────────────────────────────
(150,
"""<h2>Rekorlu Dişli Sirkülasyon Pompası Modelleri ve Fiyatları</h2>
<p><strong>Rekorlu dişli sirkülasyon pompası</strong>, boru hattına dişli (rekor) bağlantıyla monte edilen, kalorifer, yerden ısıtma ve sıcak su devridaim hatlarına yönelik kompakt rotorlu pompadır. Birleşik gövdesi içinde dönen ıslak rotor, biyaringa veya harici yataklamaya gerek duymadan çalışır; bu da bakım ihtiyacını minimuma indirir. Türkiye'deki konut ve küçük ticari ısıtma tesisatlarının <strong>%80'inden fazlasında</strong> bu pompa tipi kullanılır.</p>
<h3>Özellikler</h3>
<ul>
  <li>Bağlantı: 1\\" – 2\\" BSP/ISO dişli rekor</li>
  <li>Basma yüksekliği: 2-8 m (düşük basınçlı kapalı devre uygulaması)</li>
  <li>Güç tüketimi: 25-180 W (enerji verimli ECM motor seçenekleri mevcuttur)</li>
  <li>Çalışma sıcaklığı: 2-110°C</li>
</ul>
<p><a href="/kategoriler/su-pompalari/sirkulasyon-pompalari">Tüm sirkülasyon pompaları</a> | <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/inline-sirkulasyon-pompalari">Inline sirkülasyon pompalar</a> | <a href="/marka/pedrollo">Pedrollo TOP/PD serisi</a> | <a href="/marka/sumak">Sumak sirkülasyon modelleri</a></p>""",
[
{"q":"Rekorlu sirkülasyon pompası hangi boru çaplarına uyuyor?",
 "a":"Standart rekorlu sirkülasyon pompaları <strong>DN15 (1/2\"), DN20 (3/4\"), DN25 (1\"), DN32 (1¼\") ve DN40 (1½\") ve DN50 (2\")</strong> dişli bağlantıya uyar. Satın almadan önce tesisatınızdaki boru çapını veya mevcut pompa rekorunun dişli boyutunu ölçmeniz gerekir. Mevcut pompanın bağlantı boyutu genellikle pompa gövdesi üzerinde yazılıdır."},
{"q":"Sirkülasyon pompasının hız kademesi ne anlama gelir?",
 "a":"Çoğu rekorlu sirkülasyon pompasında <strong>3 hız kademesi</strong> bulunur (I, II, III). I. kademe en düşük devir ve enerji tüketimi, III. kademe en yüksek. Isıtma sezonunun başında veya soğuk havalarda III. kademe, ılık havalarda I. veya II. kademe tercih edilir. Modern ECM motorlu (A sınıfı) pompalar ise kademelere gerek duymadan otomatik verim optimizasyonu yapar."},
{"q":"Sirkülasyon pompası hava kabarcığı nasıl giderilir?",
 "a":"Pompa üzerinde bulunan <strong>hava tahliye vidası</strong> (genellikle pompa üst kapağında bir küçük tork başlı vida veya kelebek vana) saat yönünün tersine yarım-bir tur döndürülerek açılır. Hava çıkarken ıslak bir bez hazır bulundurun; su gelmeye başladığında vana yavaşça kapatılır. İşlem birkaç saniye içinde biter. Sistem devreye alındıktan sonraki ilk 24-48 saatte bu işlemin tekrar edilmesi tavsiye edilir."},
{"q":"Sirkülasyon pompası ne kadar elektrik harcıyor?",
 "a":"Standart sabit hızlı rekorlu sirkülasyon pompaları <strong>40-120 W</strong> arasında çalışır. Yılda 8.000 saat çalıştığında 40 W pompa yılda ~320 kWh, 120 W pompa ise ~960 kWh tüketir. Günümüzün A+++ sınıfı ECM motorlu pompaları <strong>5-25 W</strong> tüketimiyle aynı performansı sağlar; enerji faturasında yılda 200-400 TL tasarruf edebilir ve 2-3 yılda kendini amorti eder."},
{"q":"Rekorlu sirkülasyon pompası teslimatta ne kontrol edilmeli?",
 "a":"Kutuda şunların olduğunu kontrol edin: <strong>pompa gövdesi, rekor bağlantı seti (genellikle 2 adet rekor + conta), şalt kutusu/elektrik bağlantısı ve kullanım kılavuzu</strong>. Pompa milini elle döndürerek (rotor kapağı kaldırıldığında) serbestçe döndüğünü doğrulayın. Nakliye sırasında donmuş veya bloklayan mil arıza işaretidir. Kurulumdan önce boru hattını yıkayarak kum ve tortuyu temizlemek conta ömrünü uzatır."}
],
"Rekorlu Dişli Sirkülasyon Pompası Fiyatları | Koşar Ticaret",
"Kalorifer ve yerden ısıtma için rekorlu sirkülasyon pompaları. 1/2\"-2\" dişli bağlantı, "
"ECM enerji tasarruflu seçenekler. Pedrollo ve Sumak, Koşar Ticaret'ten garantili."),

# ── 152 Inline Sirkulasyon Pompalari ──────────────────────────────────────────
(152,
"""<h2>Inline Sirkülasyon Pompası Modelleri ve Fiyatları</h2>
<p><strong>Inline sirkülasyon pompası</strong>, giriş ve çıkış flanşları aynı boru ekseninde karşılıklı konumlandırılmış; boru hattına doğrudan eklenebildiği için özel pompa dairesi veya bypass gerektirmeyen rotorlu sirkülasyon pompasıdır. Büyük konut kompleksleri, oteller, hastaneler ve ticari binalardaki <strong>merkezi ısıtma, soğutma ve sıhhi sıcak su devridaim</strong> sistemlerinde DN40 – DN150 flanşlı bağlantıyla kullanılır.</p>
<h3>Rekorlu Pompadan Farkı</h3>
<ul>
  <li>Daha büyük debi kapasitesi (5-200 m³/s)</li>
  <li>Flanşlı bağlantı ile kolay değiştirilebilirlik</li>
  <li>Yüksek basınçlı sistemlere (PN16, PN25) uyumluluk</li>
  <li>Genellikle dıştan yataklı (external bearing) motor</li>
</ul>
<p><a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/rekorlu-disli-sirkulasyon-pompalari">Rekorlu sirkülasyon pompalar</a> | <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/flansli-sirkulasyon-pompalari">Flanşlı sirkülasyon pompalar</a> | <a href="/marka/pedrollo">Pedrollo inline serisi</a></p>""",
[
{"q":"Inline pompa ile rekorlu pompa hangisini seçmeliyim?",
 "a":"<strong>Rekorlu pompa</strong> küçük konut ısıtma sistemleri (1/2\"-2\" boru) için idealdir; montajı basit ve ekonomiktir. <strong>Inline pompa</strong> daha büyük sistemlerde (DN40 ve üzeri), yüksek debi gerektiren binalarda ve flanşlı boru hatlarında kullanılır. Genellikle mevcut boru hattına uyacak standart ölçüde flanşlı bağlantı ile kolay değiştirilebilirlik önemli bir avantajdır."},
{"q":"Inline sirkülasyon pompasının basınç kaybı nasıl hesaplanır?",
 "a":"Kapalı devre ısıtma sistemlerinde sirkülasyon pompasının sağlaması gereken basınç, <strong>sistemin toplam hidrolik direncine (basınç düşüşüne)</strong> eşit olmalıdır. Hesaplama: her metre boru için 1-3 mbar kayıp, her dirsek için 0,5-1 m eşdeğer boru uzunluğu, vana kayıpları. Bu değerlerin toplamı pompanın basma yüksekliğini verir. Büyük sistemlerde hidrolik hesap uzman mühendise yaptırılmalıdır."},
{"q":"Inline pompada motor değiştirilebilir mi?",
 "a":"Evet. Standart inline pompalarda motor bileşeni (IEC standart flanşlı motor) pompanın hidrolik kısmından bağımsız olarak değiştirilebilir. Bu durum özellikle motor arızasında (<strong>sargı yaması, rulman arızası</strong>) pompa gövdesini değiştirmeden sadece motoru yenileme imkânı tanır ve tamir maliyetini önemli ölçüde düşürür. Motoru değiştirirken aynı güç ve devir sayısına sahip standart IEC motor seçilmesi gerekir."},
{"q":"Inline pompa otomatik devreye girme özelliği nasıl sağlanır?",
 "a":"Otomasyon için pompa; <strong>dış sıcaklık sensörü, bina otomasyon sistemi (BMS) veya termostat</strong> ile entegre edilir. Dış sıcaklık düşünce ısıtma devresi otomatik başlar; yeterli sıcaklıkta durur. Büyük binalarda genellikle iki pompa paralel kurulur (biri yedek); biri arıza yaptığında diğeri otomatik devreye girer (otomatik yedekleme)."},
{"q":"Inline pompa sesleniyorsa ne yapmalıyım?",
 "a":"Inline pompadan gelen anormal sesler şu nedenlere bağlıdır: <strong>kavitasyon sesi (ıslık gibi yüksek frekanslı)</strong> — sisteme hava girmiş veya emme basıncı yetersiz; <strong>rulman sesi (gıcırtı)</strong> — rulman değişimi gerekiyor; <strong>metalik ses</strong> — impeller ile gövde arasında temas. Hava kilidi durumunda havayı tahliye edin; rulman veya impeller sorununda yetkili servisi arayın."}
],
"Inline Sirkülasyon Pompası Fiyatları | Koşar Ticaret",
"Otel, hastane ve büyük bina ısıtma sistemleri için inline sirkülasyon pompaları. DN40-DN150 "
"flanşlı bağlantı, yüksek debi kapasitesi. Pedrollo garantisi, Koşar Ticaret."),

# ── 155 Yangin Pompalari ──────────────────────────────────────────────────────
(155,
"""<h2>Yangın Pompası Modelleri ve Fiyatları</h2>
<p><strong>Yangın pompası</strong>, binalardaki sabit yangın söndürme sistemleri için yangın deposundan veya şebekeden gereken basınç ve debide su besleyen, yüksek güvenilirlik ve hızlı devreye girme özelliğine sahip özel amaçlı sanayi pompasıdır. Türkiye'de binaların yangın söndürme sistemleri <strong>TS EN 12845 — Sabit Yangın Söndürme Sistemleri standardı</strong> kapsamında tasarlanmak zorundadır.</p>
<h3>Yangın Pompası Sisteminin Bileşenleri</h3>
<ul>
  <li><strong>Elektrikli ana pompa:</strong> Şebeke elektriğiyle çalışır; normal operasyon için</li>
  <li><strong>Dizel yedek pompa:</strong> Elektrik kesintisinde otomatik devreye girer; zorunlu</li>
  <li><strong>Jockey (basınç takip) pompası:</strong> Hat basıncını sürekli izler, küçük sızıntıları telafi eder</li>
  <li><strong>Kontrol paneli:</strong> UL/FM sertifikalı otomatik start, arıza alarm sistemi</li>
</ul>
<p><a href="/kategoriler/su-pompalari/ozel-amacli-pompalar">Özel amaçlı pompalar</a> | <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/dizel-su-motorlari">Dizel su motorları</a> | <a href="/marka/pedrollo">Pedrollo yangın pompa serileri</a></p>""",
[
{"q":"Yangın pompası sistemi zorunlu mu? Hangi binalarda gerekli?",
 "a":"Evet. Türk Yapı Yönetmeliğine (TBDY) ve <strong>Binaların Yangından Korunması Hakkında Yönetmelik</strong>'e göre yüksek binalarda (30 m üzeri), kapalı otoparklar, hastaneler, okullar, AVM ve büyük endüstriyel tesislerde sabit yangın söndürme sistemi ve dolayısıyla yangın pompası zorunludur. Hangi bina tiplerinin kapsama girdiği konusunda yetkili bir yangın güvenlik firmasından teknik görüş alınması gerekir."},
{"q":"Yangın pompasının elektrikli ve dizel olarak birlikte kullanılması neden zorunlu?",
 "a":"Yangın senaryolarında bina elektriği büyük olasılıkla kesilir ya da devre kesiciler devreye girer. Bu nedenle <strong>EN 12845 ve NFPA 20</strong> standartları, yedek enerji kaynağına sahip dizel pompa kullanımını zorunlu kılmaktadır. Dizel pompa şebeke bağımsız olarak çalışır, otomatik start özelliğine sahiptir ve kesintisiz yakıt tankı ile 6-8 saatlik sürekli çalışma sağlar."},
{"q":"Yangın pompası yıllık bakımı nasıl yapılır?",
 "a":"EN 12845 standardı; yangın pompasının <strong>haftada bir test çalıştırması</strong> (en az 10 dakika yüksüz), <strong>üç ayda bir</strong> yük altında test ve <strong>yılda bir kapsamlı test ve bakım</strong> yapılmasını öngörür. Bakım kayıtları, standartlara uyum için muhafaza edilmeli ve yetkili yangın sistemleri firması tarafından imzalanmalıdır. Türkiye'de sigorta şirketleri bu bakım kayıtlarını poliçe kapsamı için zorunlu koşul olarak isteyebilir."},
{"q":"Yangın pompası için ne kadar su deposu gerekmez?",
 "a":"Depo kapasitesi, sprinkler sisteminin tasarım debisine ve gerekli yangın söndürme süresine (genellikle <strong>60-90 dakika</strong>) göre hesaplanır. EN 12845'e göre Hazard Sınıfı I binalar için tipik değer 70-182 m³, Sınıf II-III için 70-360 m³ civarındadır. Hesap bina riskleri sınıfı ve sprinkler konfigürasyonuna göre yapılmakta olup yetkili yangın mühendisi tarafından onaylanmalıdır."},
{"q":"Yangın pompasını kim kurabilir?",
 "a":"Türkiye'de yangın söndürme sistemleri kurulumu <strong>Yangın Güvenlik Sistemleri Montaj Yeterlilik Belgesi</strong>'ne sahip firmalar tarafından gerçekleştirilebilir. Koşar Ticaret olarak yangın pompası ürünü satışı yapıyoruz; kurulum için bölgenizdeki yetkili yangın sistemleri firması ile çalışmanızı öneriyoruz. Pompa seçimi konusunda teknik desteğimizden ücretsiz faydalanabilirsiniz."}
],
"Yangın Pompası Fiyatları | Koşar Ticaret",
"EN 12845 uyumlu yangın pompaları: elektrikli ana pompa, dizel yedek, jockey pompa. "
"Bina yangın söndürme sistemleri için teknik danışmanlık. Koşar Ticaret yetkili satıcısı."),

]

updated = 0
for row in CATS:
    cat_id, desc, faq_list, meta_title, meta_desc = row
    faq_json = json.dumps(faq_list, ensure_ascii=False)
    cur.execute(
        "UPDATE categories SET description=?, faq=?, meta_title=?, meta_description=? WHERE id=?",
        (desc, faq_json, meta_title, meta_desc, cat_id)
    )
    if cur.rowcount:
        updated += 1
        print(f"  ✓ id={cat_id}")
    else:
        print(f"  ✗ id={cat_id} BULUNAMADI")

conn.commit()
conn.close()
print(f"\nPart-2: {updated} kategori guncellendi.")
