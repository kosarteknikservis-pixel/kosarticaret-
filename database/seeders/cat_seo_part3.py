# -*- coding: utf-8 -*-
# Part 3: Hidrofor alt + Ozel amacli alt + Vantilatör alt + diger
import sqlite3, json, sys, os
sys.stdout.reconfigure(encoding='utf-8')
DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

CATS = [

# ── 120 Pedrollo Hidrofor ─────────────────────────────────────────────────────
(120,
"""<h2>Pedrollo Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Pedrollo hidrofor sistemleri</strong>, İtalya'nın Verona bölgesinde üretilen ve Türkiye pazarında yüksek kalite standardının simgesi haline gelmiş profesyonel basınç artırma ürünleridir. Konut, ticari bina ve küçük sanayi tesislerinde sabit su basıncı sağlamak için tasarlanan Pedrollo hidrofor serileri iki ana gruba ayrılır: geleneksel tanklı sistemler ve akıllı inverter kontrollü sistemler.</p>
<h3>Pedrollo Hidrofor Serileri</h3>
<ul>
  <li><strong>Easy serisi</strong> — Presostat, manometre ve basınç tankı entegre; 0,5-1,1 kW, 24-60 lt tank. Konut ve küçük iş yerleri için ekonomik çözüm.</li>
  <li><strong>Easy Tech serisi</strong> — Elektronik inverter kontrollü, baskı tankı gerektirmeyen (2 lt akümülatör) akıllı sistem; sabit basınç, enerji tasarrufu, kuru çalışma koruması entegre.</li>
</ul>
<p><a href="/kategoriler/hidrofor-sistemleri">Tüm hidrofor sistemleri</a> | <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak hidrofor karşılaştırması</a> | <a href="/marka/pedrollo">Pedrollo marka sayfası</a></p>""",
[
{"q":"Pedrollo Easy ile Easy Tech serisi arasındaki fark nedir?",
 "a":"<strong>Easy serisi</strong> klasik presostat + basınç tankı kombinasyonu; pompa belirli basıncın altına düşünce devreye girer, üstüne çıkınca durur. Basit ve güvenilirdir. <strong>Easy Tech</strong> ise entegre elektronik inverter sürücüsüyle çalışır; pompa hızını anlık su talebine göre kademesiz ayarlar, sabit basınç sağlar, start-stop şoku yoktur, kuru çalışma koruması otomatiktir. Enerji tasarrufu Easy Tech'te %30-50 daha fazladır; kurulum maliyeti ise daha yüksektir."},
{"q":"Pedrollo Easy hidrofor 3 katlı ev için yeterli mi?",
 "a":"Pedrollo Easy 0,75 kW ve 24 lt tanklı bir model, <strong>2-3 katlı müstakil ev</strong> için genellikle yeterlidir; 30-35 m basma yüksekliği sağlar. 4-5 katlı binalar için 1,1 kW ve 50-60 lt tank önerilir. Eğer eş zamanlı kullanım yoğunsa (birden fazla banyo+mutfak aynı anda) daha yüksek debili ve büyük tanklı modeller tercih edilmelidir."},
{"q":"Pedrollo Easy Tech de arıza oluşursa kendi kendine kurtarabilir mi?",
 "a":"Easy Tech'in elektronik kontrol ünitesi; <strong>kuru çalışma, aşırı sıcaklık, aşırı akım ve düşük voltaj</strong> durumlarında pompayı otomatik durdurur ve LED ekranda hata kodu gösterir. Sorunu giderdikten sonra reset tuşuna basılarak sistem yeniden devreye alınır. Hata kodları kullanım kılavuzunda açıklanmaktadır; teknik ekibimiz telefon desteği de sağlamaktadır."},
{"q":"Pedrollo hidroforu kirlenmez kullanımda ne sıklıkla bakım gerektirir?",
 "a":"Pedrollo hidroforlar düşük bakım gerektiren ürünlerdir. Yılda bir: <strong>basınç tankı ön basıncı kontrolü</strong> (hava tarafı; Easy serisi genellikle 1,5 bar olmalı), membrın durumu gözlem, presostat kontak temizliği. Easy Tech için yılda bir: hata log kontrolü ve sensör kalibrasyonu. Kireçli bölgelerde her 2-3 yılda bir pompa kasasının temizlenmesi önerilir."},
{"q":"Pedrollo hidrofor fiyatı neden Sumak'tan yüksek?",
 "a":"Pedrollo İtalya'da üretilmektedir; malzeme ve işleme standartları Avrupa normlarındadır. Motor sargıları Klasse F (155°C) termal sınıfında olup daha yüksek aşırı yük toleransı sağlar. Pompa gövdesi ve impeller ölçü toleransları daha dar tutulduğundan verim ve uzun ömür açısından üstündür. Sonuç olarak Pedrollo daha uzun süre değiştirilmeden çalıştığında toplam maliyet analizi Sumak ile yarışabilir hale gelir."}
],
"Pedrollo Hidrofor Fiyatları | Easy ve Easy Tech Serisi | Koşar Ticaret",
"Pedrollo Easy ve Easy Tech hidrofor sistemleri. İtalyan kalitesi, inverter kontrollü akıllı "
"modeller ve klasik tanklı seçenekler. Koşar Ticaret yetkili Pedrollo satıcısı."),

# ── 125 Sumak Hidrofor ────────────────────────────────────────────────────────
(125,
"""<h2>Sumak Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Sumak hidrofor sistemleri</strong>, Türkiye'de en yaygın kullanılan yerli marka olarak milyonlarca konut ve küçük ticari tesiste sabit ve güvenilir su basıncı sağlamaktadır. Geniş servis ağı, uygun yedek parça fiyatı ve rekabetçi satış fiyatıyla Sumak, özellikle konut segmentinin birinci tercihi olmaya devam etmektedir.</p>
<h3>Sumak Hidrofor Serileri</h3>
<ul>
  <li><strong>SKS serisi</strong> — Tek pompalı, presostat ve manometre entegre; 0,5-1,5 kW, 24-100 lt tank. Ev ve küçük konut için standart model.</li>
  <li><strong>SKT serisi</strong> — Çift pompalı, daha büyük tanklı; çok katlı bina ve orta ölçekli işyerleri için.</li>
  <li><strong>Elektronik presostat versiyonları</strong> — Kademesiz basınç ayarlı, dijital göstergeli gelişmiş modeller.</li>
</ul>
<p><a href="/kategoriler/hidrofor-sistemleri">Tüm hidrofor sistemleri</a> | <a href="/kategoriler/hidrofor-sistemleri/pedrollo-hidrofor">Pedrollo hidrofor karşılaştırması</a> | <a href="/marka/sumak">Sumak marka sayfası</a></p>""",
[
{"q":"Sumak SKS serisi tek başına apartmana yeterli mi?",
 "a":"Sumak SKS serisi <strong>tek daire veya 2-3 katlı müstakil ev</strong> kullanımı için tasarlanmıştır. Eğer bir aparman veya çok katlı bina söz konusuysa SKT çift pompalı seri ya da Sumak'ın çok pompalı hidrofor grupları daha uygun olacaktır. SKS ile apartman beslemesi teknik olarak mümkün ancak eş zamanlı kullanımda basınç düşüşü yaşanacaktır."},
{"q":"Sumak hidrofor kaç yıllık garantisi var?",
 "a":"Koşar Ticaret üzerinden satın alınan Sumak hidroforlar <strong>2 yıl satıcı garantisi</strong> kapsamındadır. Garanti; üretim ve malzeme hatalarını kapsar. Pompa kasasına kum girişi, yanlış elektrik bağlantısı veya su kesintisinde kuru çalıştırma garanti dışıdır. Arızada önce Koşar Ticaret ile iletişime geçilmesi, gerekirse yetkili Sumak servisine yönlendirme yapılması en hızlı çözüm yoludur."},
{"q":"Sumak hidrofor düşük basınç ayarı nasıl yapılır?",
 "a":"Sumak hidroforun presostat kapağı açıldığında içinde iki yay görülür: büyük yay <strong>cut-off basıncını (pompanın durduğu basınç)</strong>, küçük yay ise cut-in ve cut-off arasındaki farkı (diferansiyel) ayarlar. Büyük yayı saat yönünde çevirmek cut-off basıncını artırır. Tipik ayar: cut-in 1,5 bar, cut-off 2,5-3 bar. Ayar yaparken sistem altında manometre değerini gözlemleyin."},
{"q":"Sumak hidrofor neden aşırı sık açılıp kapanıyor?",
 "a":"Pompanın çok sık start-stop yapması (kısa devre/short cycling) genellikle <strong>basınç tankı membrının yırtılmasından</strong> kaynaklanır. Membrın yırtılırsa tankın hava yastığı işlevi ortadan kalkar ve pompa her küçük su talebiyle devreye girer. Test için: sistemi durdurun, tank üzerindeki hava valinden şişirme yapın — hava yerine su geliyorsa membran yırtılmış demektir. Membran değişimi veya tank yenileme gerekir."},
{"q":"Sumak hidrofor tankının ön basıncı nasıl kontrol edilir?",
 "a":"Sistemi kapatın ve su basıncını sıfırlayın (tüm muslukları açın). Basınç tankı üzerindeki <strong>Schrader valfi (lastik teker şişirme valfi gibi)</strong>'ni bisiklet pompa manometresiyle ölçün. Ön basınç genellikle pompanın cut-in basıncının <strong>%90'ı kadar</strong> olmalı; örneğin cut-in 1,5 bar ise ön basınç ~1,35-1,4 bar. Gerekirse bisiklet pompasıyla hava ekleyin veya valfi açarak hava verin."}
],
"Sumak Hidrofor Fiyatları | SKS ve SKT Serileri | Koşar Ticaret",
"Sumak SKS ve SKT serisi hidrofor sistemleri. Türkiye'nin en çok tercih edilen yerli markası, "
"uygun fiyat ve geniş servis ağı. Koşar Ticaret yetkili Sumak satıcısı."),

# ── 129 Ev Tipi Hidroforlar ───────────────────────────────────────────────────
(129,
"""<h2>Ev Tipi Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Ev tipi hidrofor</strong>, tek aile konutu, daire veya küçük işyerinin su tesisatına bağlanarak düşük veya düzensiz şebeke basıncını otomatik olarak artıran kompakt basınç artırma sistemidir. Pompa + basınç tankı + otomatik presostat üçlüsünden oluşan paket sistem olarak teslim edilir; kurulumu kolay ve bakım gerektirme sıklığı düşüktür.</p>
<h3>Ev Tipi Hidrofor Seçim Kriterleri</h3>
<ul>
  <li><strong>Motor gücü:</strong> 1 daire için 0,5-0,75 kW, müstakil 2-3 katlı ev için 1-1,5 kW</li>
  <li><strong>Tank kapasitesi:</strong> Küçük konut için 24 lt, orta büyüklükte 50 lt yeterli</li>
  <li><strong>Maksimum basınç:</strong> Çoğu ev tipi model 3-4 bar cut-off</li>
</ul>
<p><a href="/kategoriler/hidrofor-sistemleri/pedrollo-hidrofor">Pedrollo ev tipi hidrofor</a> | <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak ev tipi hidrofor</a> | <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">Apartman için hidrofor grubu</a></p>""",
[
{"q":"Ev tipi hidrofor şebeke suyu olan bir evde gerekli mi?",
 "a":"Şebeke basıncı <strong>sürekli ve yeterli (2-3 bar)</strong> olan evlerde hidrofor zorunlu değildir. Ancak şebeke basıncı zaman zaman düşüyorsa, üst katlarda yetersiz su geliyorsa ya da depodan su kullanıyorsanız hidrofor çok faydalı olur. Şebeke basıncını önce basit bir dijital manometre ile ölçün; düşükse hidrofor yatırımı kendini kısa sürede geri öder."},
{"q":"Ev tipi hidrofor kurulumunu kendim yapabilir miyim?",
 "a":"Elektrik bağlantısını mutlaka yetkili elektrikçiye yaptırın — bu kural garantinin korunması ve güvenliğiniz için zorunludur. Boru bağlantısını ise temel tesisat bilgisine sahip kişi yapabilir: giriş ve çıkış borusu bağlantısı, presostat ayarı, manometre takibi. Kurulum kılavuzu kutuda yer alır; Türkçe anlatım mevcuttur."},
{"q":"Ev tipi hidrofor kombi ile uyumlu mu?",
 "a":"Evet. Hidrofor sistemi, kombi veya şofben girişinden önce tesisata bağlandığında kombiye gelen su basıncını artırarak kombinin sorunsuz çalışmasını sağlar. Pek çok kombinin minimum çalışma basıncı 0,5-1 bar'dır; şebeke basıncı bu değerin altında ise kombi hata verir. Hidrofor bu sorunu kalıcı olarak çözer."},
{"q":"Hidrofor tankının hacmi neden önemli?",
 "a":"Daha büyük tank, pompanın daha az start-stop yapması anlamına gelir: 24 lt tank yaklaşık 5-8 litre kullanışlı su depolayabilirken 50 lt tank 12-16 litre depolayabilir. Az kullanım (tek musluk) durumunda 24 lt yeterlidir; aynı anda birden fazla kullanım noktası aktifse (duş + mutfak) 50 lt veya üstü tercih edilmelidir. Daha büyük tank motor ömrünü uzatır."},
{"q":"Ev tipi hidroforu kışın dışarıda bırakmak zararlı mı?",
 "a":"Evet. Sıfırın altında sıcaklıkta su içinde kalan hidrofor pompası, basınç tankı ve boru bağlantı parçaları <strong>donarak çatlayabilir</strong>. Dışarıda kullanmak zorundaysanız pompayı ısıtılmış bir dolap içine alın veya her gece sistemi boşaltın. Kışlık (frost protection) modeli yoksa hidroforun yalıtımlı bir iç mekâna kurulması şiddetle önerilir."}
],
"Ev Tipi Hidrofor Fiyatları | Pedrollo, Sumak | Koşar Ticaret",
"Konut ve daire için kompakt ev tipi hidrofor sistemleri. 24-50 lt tank, 0,5-1,5 kW motor. "
"Pedrollo ve Sumak markalı, garantili. Koşar Ticaret'ten uygun fiyat."),

# ── 166 Ev Tipi Vantilatör ────────────────────────────────────────────────────
(166,
"""<h2>Ev Tipi Vantilatör Modelleri ve Fiyatları</h2>
<p><strong>Ev tipi vantilatör</strong>, konut, ofis ve küçük işyerlerinde konforlu hava sirkülasyonu sağlamak için tasarlanmış; masa, ayaklı, pencere veya tavan tipi seçenekleriyle sunulan elektrikli havalandırma ürünüdür. Klima sistemi olmayan veya enerji tasarrufu hedefleyen mekânlarda hissedilen sıcaklığı <strong>3-7°C</strong> aşağı çekerek termal konfor sağlar; enerji tüketimi 40-100 W ile klimaya kıyasla çok ekonomiktir.</p>
<h3>Ev Tipi Vantilatör Türleri</h3>
<ul>
  <li><strong>Masa vantilatörü:</strong> Kompakt, taşınabilir; kişisel soğutma için ideal</li>
  <li><strong>Ayaklı (sütun) vantilatör:</strong> Yüksek konumdan geniş alan taraması</li>
  <li><strong>Tavan vantilatörü:</strong> Yüksek tavanlarda sessiz ve geniş alanlı hava hareketi</li>
  <li><strong>Pencere/çerçeve vantilatörü:</strong> Dışarıdan hava alarak ventilasyon sağlar</li>
</ul>
<p>Büyük sanayi ve depo havalandırması için <a href="/kategoriler/vantilatorler/sanayi-tipi-vantilator">sanayi tipi vantilatörler</a> sayfamıza bakınız. <a href="/kategoriler/vantilatorler">Tüm vantilatör çeşitleri</a></p>""",
[
{"q":"Ev tipi vantilatör enerji tüketimi ne kadar?",
 "a":"Ev tipi vantilatörler güçlerine göre <strong>25-100 W</strong> arasında tüketir. Ortalama 60 W bir ayaklı vantilatör günde 8 saat çalıştırıldığında aylık ~15 kWh tüketir; bu yaklaşık 3-5 TL'ye karşılık gelir. Aynı alan için kullanılan bir split klima <strong>800-1500 W</strong> güçte çalışır. Vantilatör gerçek soğutma yapmaz (ortam sıcaklığını düşürmez); sadece hava hareketi ile hissedilen sıcaklığı azaltır."},
{"q":"Vantilatör gece kullanımında rahatsız edici gürültü çıkarıyor mu?",
 "a":"Kaliteli ev tipi vantilatörler en düşük hız kademesinde <strong>35-40 dB(A)</strong> ses seviyesinde çalışır; bu değer fısıltıya yakın bir sese denk gelir. Ucuz modellerde bıçak dengesizliği veya motor yatak kalitesizliği nedeniyle titreşim ve ıslık sesi oluşabilir. Sessiz gece kullanımı için EC motorlu veya DC motorlu (ayarlanabilir hız) modeller tercih edilmelidir."},
{"q":"Tavan vantilatörü ile ayaklı vantilatör hangisi daha etkili?",
 "a":"<strong>Tavan vantilatörü</strong>, tepeden aşağı hava akışıyla oda hacminde homojen hava sirkülasyonu sağlar; büyük odalar için daha etkilidir. <strong>Ayaklı vantilatör</strong> yönlendirilebilir olması sayesinde kişiye veya belirli bir noktaya hava akışı sağlar; daha hızlı ve yoğun hava hareketi üretir. Tavan vantilatörleri genellikle daha sessizdir; ayaklı modeller daha taşınabilir ve daha ucuzdur."},
{"q":"Vantilatörü pencere önüne koymak daha mı etkili?",
 "a":"Dışarısı içeriden daha seyrelse (geceleri veya sabah erken) vantilatörü pencerenin içe bakacak şekilde yerleştirmek <strong>serin dış havayı içeri çeker</strong> ve etkili ventilasyon sağlar. Dışarısı sıcaksa vantilatörü dışarı bakacak şekilde kurmak içerideki sıcak havayı dışarı atar. Çapraz hava akışı (karşılıklı iki pencere) için bir vantilatör yeterlidir."},
{"q":"Çocuk odası için hangi vantilatör güvenli?",
 "a":"Çocuk odası için koruma kafesi sıkı ve küçük parmakların giremeyeceği şekilde tasarlanmış; gürültü seviyesi düşük (tercihen 35 dB altında) modeller tercih edilmelidir. Devrilmeye karşı <strong>geniş taban ve otomatik devrilme kapama sensörü</strong> olan modeller güvenliği artırır. Tavan vantilatörü çocuk odasında da iyi bir seçenektir çünkü çocuğun erişemeyeceği konumdadır."}
],
"Ev Tipi Vantilatör Fiyatları | Masa, Ayaklı ve Tavan | Koşar Ticaret",
"Konut ve ofis için masa, ayaklı ve tavan vantilatörleri. 40-100W, sessiz çalışma, "
"taşınabilir seçenekler. Koşar Ticaret'ten uygun fiyat ve hızlı teslimat."),

# ── 162 Dizel Su Motorlari ────────────────────────────────────────────────────
(162,
"""<h2>Dizel Su Motoru (Motorlu Pompa) Modelleri ve Fiyatları</h2>
<p><strong>Dizel su motoru</strong> (dizel motorlu pompa / motopomp), akaryakıt motoruyla çalışan; elektrik şebekesi olmayan tarım arazileri, şantiyeler, su kuyuları ve afet/acil durum pompalama için vazgeçilmez mobil su pompası ünitesidir. Santrifüj veya dalgıç pompanın dizel yakıtlı içten yanmalı motor ile aynı şasiye entegre edilmesiyle oluşur. Türkiye'de tarımsal sulama sektöründe <strong>elektriksiz uzak alanlarda</strong> en yaygın kullanılan pompalama çözümüdür.</p>
<h3>Uygulama Alanları</h3>
<ul>
  <li>Elektrik altyapısı bulunmayan tarım arazisi sulaması</li>
  <li>Şantiye ve kazı alanlarında geçici su temin veya tahliye</li>
  <li>Yangın söndürme yardımcı ünitesi (mobil)</li>
  <li>Afet ve doğal felaket durumlarında acil pompalama</li>
</ul>
<p><a href="/kategoriler/su-pompalari/ozel-amacli-pompalar">Özel amaçlı pompalar</a> | <a href="/kategoriler/su-pompalari/santrifuj-pompalar/santrifuj-pompalar-sulama">Sulama santrifüj pompaları</a> | <a href="/kategoriler/su-pompalari/dalgic-pompalar">Dalgıç pompalar</a></p>""",
[
{"q":"Dizel su motoru ne kadar yakıt harcar?",
 "a":"Dizel motorlu pompaların yakıt tüketimi motor gücüne doğrudan bağlıdır. Kaba referans: <strong>5 HP (3,7 kW) dizel pompa</strong> tam yükte saatte yaklaşık 1-1,5 litre mazot tüketir; 10 HP model 2-3 litre, 20 HP model 4-6 litre. Kısmi yükte tüketim orantılı düşer. Yakıt deposu kapasitesi modele göre 3-20 litre arasında değişir; büyük arazilerde ek yakıt tankı taşınması önerilir."},
{"q":"Dizel pompa ile elektrik pompası arasında uzun vadede maliyet nasıl karşılaştırılır?",
 "a":"Uzak arazilerde elektrik hattı çekmek önemli bir altyapı yatırımı gerektirirken dizel pompa sıfır altyapı ile çalışmaya başlar. Ancak <strong>işletme maliyeti açısından</strong> dizel yakıt maliyeti elektrik faturasının üzerinde kalabilir. Elektrik erişimi mümkünse ve sabit bir sulama ihtiyacı varsa elektrikli pompa uzun vadede her zaman daha ekonomiktir. Dizel pompa; geçici, mobil veya yedek çözüm olarak en rasyonel tercihdir."},
{"q":"Dizel motorlu pompanın bakımı nasıl yapılır?",
 "a":"Dizel pompa bakımı iki bölümden oluşur: <strong>motor bakımı ve pompa bakımı</strong>. Motor için: her 100-200 çalışma saatinde yağ değişimi, hava filtresi temizliği, yakıt filtresi kontrolü ve soğutma suyu seviyesi. Pompa için: conta ve impeller yıllık kontrolü, sezonda uzun süre kullanılmayacaksa yakıt deposunu boşaltın ve pompa kasasındaki suyu tahliye edin. Kışın depolama öncesi antifriz veya tam boşaltma yapın."},
{"q":"Dizel pompa kaçak akım koruma gerektiriyor mu?",
 "a":"Dizel pompa elektrikten bağımsız çalıştığından <strong>şebeke kaçak akım korumaya ihtiyaç duymaz</strong>. Ancak elektrik marşı (starter motor) veya akü şarjı olan modellerde 12V DC elektrik sistemi bulunur; bu sistem araç bataryasına bağlanır. Güvenlik açısından pompa çalışırken yakıt dolumu yapılmamalı, egzoz gazından uzak durulmalı ve pompa stabli bir yüzeye yerleştirilmelidir."},
{"q":"Dizel pompa satın alırken nelere bakmalıyım?",
 "a":"Temel seçim kriterleri: <strong>(1) Debi ve basma yüksekliği</strong> (sulama ihtiyacınıza göre); <strong>(2) Motor gücü HP/kW</strong>; <strong>(3) Başlatma tipi</strong> (elektrik marşlı mı, ipli mi); <strong>(4) Yakıt deposu kapasitesi</strong>; <strong>(5) Şasi tasarımı</strong> (tekerlekli mi, portatif mi); <strong>(6) Yedek parça erişimi</strong>. Türkiye'de yaygın motor markaları (Honda, Yanmar, Lombardini) için yedek parça bulmak daha kolaydır."}
],
"Dizel Su Motoru (Motorlu Pompa) Fiyatları | Koşar Ticaret",
"Elektriksiz tarım ve şantiye için dizel motorlu su pompaları. 5-30 HP seçenekler, taşınabilir "
"şasi, mazotlu çalışma. Koşar Ticaret'ten garantili, hızlı teslimat."),

# ── 151 On Filtreli Havuz Pompasi ─────────────────────────────────────────────
(151,
"""<h2>Ön Filtreli Havuz Pompası Modelleri ve Fiyatları</h2>
<p><strong>Ön filtreli havuz pompası</strong>, entegre sepet filtresi ile büyük yabancı maddeleri (yaprak, kıl, çöp) pompaya ulaşmadan tutan; yüzme havuzunun su sirkülasyonu, filtrasyon ve kimyasal dağıtımını sağlamak için özel tasarlanmış self-priming santrifüj pompasıdır. Pompa gövdesi havuz suyundaki klor, pH düzenleyiciler ve tuz gibi kimyasallara dayanıklı <strong>cam elyaf takviyeli polipropilen veya ABS</strong> malzemeden üretilir.</p>
<h3>Havuz Pompası Boyutlandırması</h3>
<p>Havuz pompası; havuz hacmini (m³) <strong>6-8 saat içinde tam devrettirmelidir</strong>. Örneğin 50 m³ havuz için gerekli debi: 50/6 = ~8 m³/s. Bu debiye ve filtreye göre pompa seçilir. Küçük ev havuzları için 0,5-0,75 kW, büyük olimpik havuzlar için 5-15 kW pompalar kullanılır.</p>
<p><a href="/kategoriler/su-pompalari/ozel-amacli-pompalar">Özel amaçlı pompalar</a> | <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/jakuzi-pompasi">Jakuzi pompalar</a></p>""",
[
{"q":"Havuz pompası ne kadar süre çalışmalı?",
 "a":"Havuz suyunun tam filtrasyon için günde <strong>1 tam devre</strong> tamamlaması gerekir; bu da genellikle 6-8 saatlik pompa çalışması demektir. Yoğun kullanım dönemlerinde (yaz ortası, çok misafirli günler) 10-12 saat çalıştırılabilir. Gece gündüz arası güneş ışığından yoksun saatlerde (23:00-07:00) çalıştırmak enerji maliyetini düşürür."},
{"q":"Havuz pompasında sepet filtre ne sıklıkla temizlenmeli?",
 "a":"Yüzme sezonu boyunca havuz yoğun kullanılıyorsa sepet filtresi <strong>haftada 1-2 kez</strong> kontrol edilmeli ve gerektiğinde temizlenmelidir. Sepet tıkandığında pompa verimli çalışamaz, aşırı ısınarak hasara uğrayabilir. Yaprağın yoğun düştüğü sonbahar döneminde günlük kontrol gerekebilir."},
{"q":"Havuz pompasında sızıntı var, ne yapmalıyım?",
 "a":"Havuz pompasındaki sızıntı genellikle <strong>mekanik conta arızasından veya pompa kapağı O-ring'inin bozulmasından</strong> kaynaklanır. Mekanik conta, pompa milini su dolu gövdeden ayıran sızdırmazlık elemanıdır. Kuruyarak veya aşınarak sızıntı oluşabilir. Conta veya O-ring değişimi çoğunlukla pompa sökülerek yapılır; yetkili servis veya deneyimli tesisatçı gerektirir."},
{"q":"Havuz pompasını kışın ne yapmalıyım?",
 "a":"Kış kapanışında pompayı mutlaka <strong>sistemi boşaltarak ve ağzı kapatarak</strong> depolayın; donma, pompa gövdesinde kalıcı çatlama yaratır. Pompayı kuru ve ısıtmalı bir mekânda muhafaza edin. Yeniden açılışta conta ve O-ring kontrolü yapılması ve pompanın doğru priming ile doldurulması gerekmektedir."},
{"q":"Havuz pompasının klordan zarar görmemesi için ne yapmalıyım?",
 "a":"İyi kaliteli havuz pompaları polipropilen veya ABS gövde, paslanmaz çelik veya bronz impellerle üretilmiştir; bu malzemeler standart havuz kimyasallarına (klor 1-3 ppm) dayanıklıdır. Aşırı klor konsantrasyonu (>10 ppm) veya pH'ın <7 olması conta malzemelerini bozabilir. Havuz kimyasallarını pompanın hemen çıkışından değil, sirkülasyon yapılırken sistemin uygun noktasına ekleyin."}
],
"Ön Filtreli Havuz Pompası Fiyatları | Koşar Ticaret",
"Yüzme havuzu sirkülasyonu için sepet filtreli havuz pompaları. Klora dayanıklı PP/ABS gövde, "
"self-priming tasarım. Koşar Ticaret'ten uygun fiyat ve hızlı teslimat."),

# ── 127 Sicak Su Hidroforu ────────────────────────────────────────────────────
(127,
"""<h2>Sıcak Su Hidroforu Modelleri ve Fiyatları</h2>
<p><strong>Sıcak su hidroforu</strong>, güneş enerjisi sistemleri, merkezi sıcak su tesisatı ve kalorifer hatlarında <strong>60-90°C'ye kadar sıcak suyu</strong> basınçla dağıtmak için özel malzeme ve conta ile üretilmiş hidrofor sistemidir. Standart soğuk su hidroforlarında kullanılan kauçuk diyafram ve plastik parçalar bu sıcaklık aralığında bozulacağından sıcak su hidroforlarında <strong>EPDM veya silikon membran, pirinç veya paslanmaz gövde</strong> kullanılmaktadır.</p>
<h3>Kullanım Alanları</h3>
<ul>
  <li>Merkezi sıcak su dağıtım tesisatı (apartman, otel, yurt)</li>
  <li>Güneş enerjisi kolektörü çıkışında sıcak su transferi</li>
  <li>Isı pompası ve kombili sistemlerde devridaim</li>
</ul>
<p><a href="/kategoriler/hidrofor-sistemleri">Tüm hidrofor sistemleri</a> | <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/sicak-su-pompalari">Sıcak su sirkülasyon pompaları</a></p>""",
[
{"q":"Sıcak su hidroforu ile sirkülasyon pompası arasındaki fark nedir?",
 "a":"<strong>Sirkülasyon pompası</strong> kapalı devrede (kalorifer, güneş enerjisi) sabit miktardaki suyu devamlı dolaştırır; basınç üretmez. <strong>Sıcak su hidroforu</strong> ise açık devrede (musluktan akan sıcak su tesisatı) basınç tankı ile su basıncını artırır ve pompayı azaltır. Merkezi sıcak su depolarından birden fazla kata su dağıtımında hem sirkülasyon pompası (devridaim) hem de hidrofor (basınç artırma) birlikte kullanılabilir."},
{"q":"Normal soğuk su hidroforu yerine sıcak su hidroforu neden gerekli?",
 "a":"60°C üzerindeki sularda standart hidrofor membranları (genellikle NBR kauçuk) hızla bozulur, presostat iletkenlerinin plastik parçaları yumuşar ve şekil değiştirir. <strong>EPDM veya silikon membranlı sıcak su hidroforları</strong> 90°C'ye kadar çalışmaya uygundur. Soğuk su hidroforunu sıcak suda kullanmak hem arızaya hem de mem­bran parçacıklarının su tesisatına karışmasına yol açar."},
{"q":"Güneş enerjisi sistemi için hangi kapasitede sıcak su hidroforu seçilmeli?",
 "a":"Güneş enerjisi sisteminde hidrofora ihtiyaç duyulması, deponun bina tesisatından daha alçak konumda olduğu veya basıncın yetersiz kaldığı durumlarda söz konusudur. Seçimde: <strong>günlük sıcak su tüketimi (kişi başı 50-80 lt), depo kapasitesi ve boru kayıpları</strong> belirleyicidir. 4-6 kişilik hane için 0,5-0,75 kW ve 8-24 lt tanklı sıcak su hidroforu genellikle yeterlidir."},
{"q":"Sıcak su hidroforunun bakımı nasıl yapılır?",
 "a":"Yılda bir: membran ön basıncını kontrol edin (sistem soğukken ve boşaltılmışken), presostat kontak temizliği yapın, boru bağlantılarında sızıntı kontrolü gerçekleştirin. Sert su (kireçli su) bölgelerinde her 2-3 yılda bir tank iç yüzeyini kontrol edin; kireç birikimi tanktaki su kalitesini olumsuz etkiler. Merkezi tesislerde yetkili servis bakımı zorunludur."},
{"q":"Sıcak su hidroforu donmadan korunmalı mı?",
 "a":"Evet. Dış ortamda veya soğuk mekânda kurulu sıcak su hidroforları, sistem boşaltılmadan uzun süre pasif bırakıldığında donma riski taşır. Boru bağlantıları ve tank özellikle savunmasızdır. Çözüm olarak ısıtma kablosu (heat trace) veya ısıtmalı dolap kullanılmalı ya da sistem sezon kapanışında tam boşaltılmalıdır."}
],
"Sıcak Su Hidroforu Fiyatları | Koşar Ticaret",
"Güneş enerjisi ve merkezi sıcak su tesisatı için EPDM membranlı sıcak su hidroforları. "
"90°C'ye dayanıklı özel yapı. Koşar Ticaret'ten garantili uygun fiyat."),

# ── 143 Bicakli Dalgic Pompa ──────────────────────────────────────────────────
(143,
"""<h2>Bıçaklı Dalgıç Pompa (Macerator) Modelleri ve Fiyatları</h2>
<p><strong>Bıçaklı dalgıç pompa</strong> (macerator pompa / kesici bıçaklı pompa), pissu ve fosseptik sistemlerinde tuvalet kâğıdı, peçete, bez ve diğer katı organik atıkları dönen kesici bıçak sistemiyle parçalayarak (macerate) küçültüp normal çaplı borulardan iletebilen özel yapılı daldırmalı elektrik pompasıdır. 50-100 mm'lik büyük katı atıkları <strong>3-5 mm parçacıklara</strong> indirgeyen kesici sistemi sayesinde çok daha küçük çaplı boru hatlarında pissu tahliyesi mümkün olur.</p>
<h3>Avantajları</h3>
<ul>
  <li>İnce boru hattıyla (DN32-DN50) pissu tahliyesi; büyük altyapı değişikliği gerekmez</li>
  <li>Tıkanma riskinin çok düşük olması</li>
  <li>Bodrum ve zemin altı WC tesisatı için ideal (<a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/foseptik-tahliye-cihazi">foseptik tahliye cihazı</a> ile birlikte kullanım)</li>
</ul>
<p><a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">Foseptik dalgıç pompalar</a> | <a href="/kategoriler/su-pompalari/dalgic-pompalar">Tüm dalgıç pompalar</a></p>""",
[
{"q":"Bıçaklı pompa mı, vortex pompa mı tercih edilmeli?",
 "a":"<strong>Vortex (girdap) impellerli pompa</strong>, katı maddeleri doğrudan temas etmeden geçirir; tıkanma riski çok düşük ama büyük katı maddeler geçiremez. <strong>Bıçaklı macerator pompa</strong> ise büyük katı atıkları keserek küçültür, çok daha ince boru hattında çalışmayı mümkün kılar; ancak kesici bıçaklar zamanla aşınır ve bakım gerektirir. Yoğun organik katı atık içeren sistemlerde bıçaklı tercih edilir; standart fosseptik için vortex yeterlidir."},
{"q":"Bıçaklı pompa bezi veya peçeteyi geçirir mi?",
 "a":"Bıçaklı pompalar ıslak mendil ve peçete gibi lifleri <strong>parçalayabilir</strong>; ancak modern dayanıklı ıslak mendiller (özellikle 'flushable' olmayan) hâlâ tıkanmaya yol açabilir. Üretici talimatlarında genellikle yalnızca biyolojik olarak parçalanabilir ıslak mendillerin kullanılması önerilir. Sentetik lifli bez, plastik ve büyük yabancı cisimler bıçakları kırar veya tıkar."},
{"q":"Bıçaklar ne zaman değiştirilmeli?",
 "a":"Bıçak aşınması genellikle <strong>3-5 yılda bir</strong> kontrol gerektirir; ancak kullanım yoğunluğuna ve pompalanan atığın özelliğine göre bu süre değişir. Bıçak körelince pompa daha fazla enerji çekmeye başlar, gürültü artar ve büyük parçacıklar geçiremez hale gelir. Bazı modellerde bıçak değişimi pompa sökülerek kullanıcı tarafından yapılabilir; ancak çoğunlukla yetkili servis önerilir."},
{"q":"Bıçaklı pompa hangi boru çapıyla çalışır?",
 "a":"Kesici sistemi sayesinde atıkları küçülten bıçaklı pompalar, standart fosseptik sisteminin gerektirdiği DN100 (4 inç) yerine <strong>DN32-DN50 (1,25-2 inç)</strong> çaplı borularla çalışabilir. Bu özellik özellikle mevcut tesisatta büyük boru değişikliği yapmadan bodrum katına tuvalet veya banyo eklenmesini mümkün kılar."},
{"q":"Bıçaklı pompa gürültülü çalışıyorsa ne yapmalıyım?",
 "a":"Anormal gürültü nedenleri: <strong>kesici bıçaklar arasına sıkışmış yabancı cisim</strong> (plastik, tel, bez), bıçak körlüğü veya rulman arızası. İlk adım: pompayı durdurun, devresini kesin, pompayı çıkarın ve bıçak bölümünü kontrol edin. Plastik ya da yabancı cisim sıkışmışsa dikkatle temizleyin. Temizlik sonrası sorun devam ediyorsa bıçak veya rulman değişimi için servis çağırın."}
],
"Bıçaklı Dalgıç Pompa (Macerator) Fiyatları | Koşar Ticaret",
"Fosseptik ve pissu tahliyesi için bıçaklı kesici macerator pompalar. İnce boru hattında "
"çalışma, katı atık parçalama. Koşar Ticaret'ten garantili uygun fiyat."),

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
print(f"\nPart-3: {updated} kategori guncellendi.")
