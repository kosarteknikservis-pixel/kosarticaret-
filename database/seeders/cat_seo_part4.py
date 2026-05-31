# -*- coding: utf-8 -*-
import sqlite3, json, sys, os
sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

def faq(items):
    return json.dumps([{"q": q, "a": a} for q, a in items], ensure_ascii=False)

CATS = [
    (134,
     """<h2>Paslanmaz Pompa (Kimyasal Pompa) Modelleri ve Fiyatları</h2>
<p><strong>Paslanmaz pompa</strong>, kimyasal sıvılar, tuzlu su, klorlu havuz suyu, gıda proses sıvıları ve hafif korozif akışkanlar için AISI 304 veya AISI 316L paslanmaz çelik gövdeyle üretilen santrifüj pompa tipidir. Standart döküm pompalarda korozyon, impeller aşınması ve conta bozulması görülen ortamlarda paslanmaz kimyasal pompalar güvenli ve uzun ömürlü çözüm sunar.</p>
<h3>Hangi Sıvılar İçin Uygundur?</h3>
<ul>
  <li><strong>AISI 304:</strong> temiz su, gıda sıvıları, düşük klorlu proses suyu</li>
  <li><strong>AISI 316L:</strong> tuzlu su, klorlu havuz suyu, zayıf asit ve baz çözeltileri</li>
  <li><strong>EPDM / Viton / PTFE conta:</strong> sıvı sıcaklığı ve pH değerine göre seçilir</li>
</ul>
<p>Yoğun kimyasal ortamlar için <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj pompaları</a>, genel su transferi için <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a> seçeneklerini de inceleyebilirsiniz. Marka karşılaştırması için <a href="/marka/pedrollo">Pedrollo</a> ve <a href="/marka/sumak">Sumak</a> sayfalarına bakabilirsiniz.</p>""",
     faq([
         ("AISI 304 ve 316L paslanmaz pompa arasındaki fark nedir?", "AISI 316L, molibden içerdiği için klorür ve tuzlu suya karşı AISI 304'e göre daha dayanıklıdır. İçme suyu ve gıda hatlarında 304 yeterli olabilir; deniz suyu, havuz suyu veya kimyasal proseslerde 316L tercih edilmelidir."),
         ("Kimyasal pompa seçerken sadece gövde paslanmaz olması yeterli mi?", "Hayır. Gövde kadar <strong>mekanik conta, O-ring, impeller ve mil malzemesi</strong> de önemlidir. Örneğin asidik sıvıda EPDM yerine Viton veya PTFE conta gerekebilir. Sıvının pH değeri, sıcaklığı ve yoğunluğu seçimden önce bilinmelidir."),
         ("Paslanmaz pompa gıda üretiminde kullanılabilir mi?", "Gıda proseslerinde kullanılacak pompada gıda temasına uygun yüzey kalitesi, uygun conta malzemesi ve mümkünse FDA/NSF uyumluluğu aranmalıdır. Süt, meyve suyu veya içme suyu hatlarında standart sanayi tipi paslanmaz pompa yerine hijyenik tasarımlı model tercih edilmelidir."),
         ("Paslanmaz pompa tuzlu suda paslanır mı?", "AISI 304 paslanmaz tuzlu suda çukur korozyonuna uğrayabilir. Tuzlu su veya deniz suyunda 316L paslanmaz çelik ve uygun conta malzemesi kullanılmalıdır. Kullanım sonrası tatlı suyla durulama pompa ömrünü uzatır."),
         ("Kimyasal pompa fiyatları neden standart pompadan yüksek?", "Paslanmaz gövde, özel conta ve daha hassas işleme maliyeti fiyatı artırır. Ancak korozif ortamda standart pompa kısa sürede arızalanırken doğru paslanmaz pompa yıllarca çalışabilir; toplam sahip olma maliyeti daha düşüktür.")
     ]),
     "Paslanmaz Kimyasal Pompa Fiyatları | Koşar Ticaret",
     "Kimyasal sıvılar, havuz ve tuzlu su için AISI 304/316L paslanmaz santrifüj pompalar. Uygun conta seçimi, teknik destek ve garantili ürünler."),

    (146,
     """<h2>Monoblok Yatay Kademeli Pompa Modelleri</h2>
<p><strong>Monoblok yatay kademeli pompa</strong>, motor ve pompa gövdesinin tek kompakt ünite halinde bağlandığı, birden fazla impeller kademesiyle yüksek basınç üreten yatay tip pompadır. Dikey kademeli pompalara göre servis erişimi kolay, montajı pratik ve küçük-orta ölçekli hidrofor sistemleri için ekonomiktir.</p>
<h3>Kullanım Alanları</h3>
<ul>
  <li>Villa, apartman ve küçük ticari bina basınç artırma</li>
  <li>Ters ozmoz ve filtrasyon ön basınç sistemleri</li>
  <li>Küçük kazan besleme ve proses suyu hatları</li>
  <li>Bahçe ve sera sulamasında yüksek basınç ihtiyacı</li>
</ul>
<p>Daha kompakt dikey çözüm için <a href="/kategoriler/su-pompalari/kademeli-pompalar/dikey-kademeli-pompalar">dikey kademeli pompalar</a>, büyük sanayi hatları için <a href="/kategoriler/su-pompalari/kademeli-pompalar/norm-tipi-yatay-kademeli">norm tipi yatay kademeli pompalar</a> sayfasını inceleyebilirsiniz.</p>""",
     faq([
         ("Monoblok yatay kademeli pompa ne avantaj sağlar?", "Motor ve pompa aynı gövdede olduğu için kaplin ayarı gerekmez, montaj süresi kısalır ve hizalama hatası riski azalır. Bu yapı küçük makine daireleri ve paket hidrofor sistemleri için pratiktir."),
         ("Yatay kademeli pompa ile dikey kademeli pompa farkı nedir?", "Yatay model servis ve sökme açısından daha kolaydır; dikey model daha az yer kaplar. Debi ve basınç ihtiyacı benzer olabilir, seçim genellikle montaj alanı ve bakım erişimine göre yapılır."),
         ("Monoblok pompa inverter ile kullanılabilir mi?", "Evet. Uygun motor izolasyon sınıfına sahip monoblok kademeli pompalar frekans invertörüyle çalıştırılabilir. Bu sayede basınç sabit tutulur, enerji tüketimi düşer ve start-stop sayısı azalır."),
         ("Bu pompa hidrofor olarak kullanılabilir mi?", "Evet, basınç tankı ve presostat veya elektronik kontrol ünitesiyle hidrofor sistemi haline getirilebilir. Çok katlı bina yerine villa, küçük apartman ve işyeri uygulamalarında daha uygundur."),
         ("Kademeli pompa kuru çalışırsa ne olur?", "Kuru çalışma mekanik contayı hızla yakar ve impellerleri hasara uğratır. Mutlaka susuz çalışma koruması, seviye flatörü veya basınç sensörü kullanılması önerilir.")
     ]),
     "Monoblok Yatay Kademeli Pompa | Koşar Ticaret",
     "Konut, hidrofor ve proses hatları için monoblok yatay kademeli pompalar. Kompakt yapı, yüksek basınç, kolay montaj ve teknik destek."),

    (157,
     """<h2>Norm Tipi Yatay Kademeli Pompa Modelleri</h2>
<p><strong>Norm tipi yatay kademeli pompa</strong>, standart flanş ve kaplin bağlantı ölçülerine sahip, motor ve pompa gövdesi ayrı şase üzerinde hizalanan ağır hizmet tipi yüksek basınç pompasıdır. Büyük sanayi tesisleri, enerji santralleri, kazan besleme hatları ve yüksek basınçlı proses suyu transferinde tercih edilir.</p>
<h3>Neden Norm Tipi?</h3>
<ul>
  <li>Standart motorla değiştirilebilir yapı</li>
  <li>Kaplinli bağlantı sayesinde ağır hizmete uygunluk</li>
  <li>Yüksek basınç ve yüksek debiyi birlikte sağlayabilme</li>
  <li>Bakımda motor veya pompa tarafının bağımsız sökülebilmesi</li>
</ul>
<p>Daha küçük uygulamalar için <a href="/kategoriler/su-pompalari/kademeli-pompalar/monoblok-yatay-kademeli">monoblok yatay kademeli</a>, yer tasarrufu için <a href="/kategoriler/su-pompalari/kademeli-pompalar/dikey-kademeli-pompalar">dikey kademeli pompa</a> alternatiflerini inceleyebilirsiniz.</p>""",
     faq([
         ("Norm tipi pompa ne demek?", "Norm tipi, pompanın flanş, mil, kaplin ve motor bağlantı ölçülerinin endüstriyel standartlara uygun olduğu anlamına gelir. Bu sayede motor değişimi, kaplin yenileme ve servis süreçleri daha kolay yönetilir."),
         ("Kaplin ayarı neden önemlidir?", "Motor ve pompa milleri aynı eksende değilse rulman, mekanik conta ve kaplin hızla aşınır. İlk montajda lazer hizalama veya komparatörle hassas kaplin ayarı yapılmalıdır."),
         ("Norm tipi kademeli pompa nerelerde kullanılır?", "Kazan besleme, ters ozmoz, yüksek basınçlı yıkama, maden prosesleri, sanayi soğutma ve merkezi basınç artırma sistemlerinde kullanılır."),
         ("Bu pompalar sürekli çalışmaya uygun mu?", "Doğru seçilmiş norm tipi pompalar 7/24 endüstriyel çalışmaya uygundur. Düzenli rulman, conta ve titreşim bakımı yapılması gerekir."),
         ("Yedek motor takılabilir mi?", "Evet. Standart IEC motor yapısı sayesinde aynı güç, devir ve flanş ölçüsüne sahip motorla değişim yapılabilir.")
     ]),
     "Norm Tipi Yatay Kademeli Pompa | Koşar Ticaret",
     "Sanayi, kazan besleme ve yüksek basınç prosesleri için norm tipi yatay kademeli pompalar. Kaplinli ağır hizmet yapısı ve teknik seçim desteği."),

    (153,
     """<h2>Flanşlı Sirkülasyon Pompası Modelleri</h2>
<p><strong>Flanşlı sirkülasyon pompası</strong>, merkezi ısıtma, soğutma ve sıcak su devridaim hatlarında yüksek debiyle çalışan, boru hattına PN10/PN16 flanş bağlantısıyla bağlanan endüstriyel sirkülasyon pompasıdır. Rekorlu dişli modellere göre daha yüksek debi ve basınç kapasitesi sunar.</p>
<p>Otel, hastane, okul, AVM, fabrika ve büyük apartman gibi tesislerde kalorifer kazanı ile tesisat arasında suyun kesintisiz dolaşmasını sağlar. Küçük konutlar için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/rekorlu-disli-sirkulasyon-pompalari">rekorlu sirkülasyon pompaları</a>, boru hattı üstü büyük sistemler için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/inline-sirkulasyon-pompalari">inline sirkülasyon pompaları</a> daha doğru olabilir.</p>""",
     faq([
         ("Flanşlı sirkülasyon pompası hangi sistemlerde kullanılır?", "Büyük merkezi ısıtma, soğutma, kazan, eşanjör ve sıcak su devridaim hatlarında kullanılır. DN40 ve üzeri boru çaplarında rekorlu pompa yerine flanşlı model seçilir."),
         ("PN10 ve PN16 flanş farkı nedir?", "PN değeri flanşın basınç sınıfını gösterir. PN10 10 bar, PN16 16 bar nominal basınca uygundur. Mevcut tesisattaki flanş standardıyla aynı pompa seçilmelidir."),
         ("Flanşlı pompa yatay mı dikey mi monte edilir?", "Model tasarımına göre değişir. Inline flanşlı modeller boru hattı üzerinde yatay veya dikey konumda çalışabilir; yataklı büyük modeller genellikle yatay şaseye monte edilir."),
         ("Sirkülasyon pompası inverterle çalışır mı?", "Evet, büyük tesislerde inverter kullanımı enerji tasarrufu sağlar. Debi ihtiyacı azaldığında motor hızı düşer ve pompa gereksiz enerji harcamaz."),
         ("Flanşlı pompa ses yapıyorsa sebep ne olabilir?", "Sistemde hava, kavitasyon, rulman aşınması veya yanlış pompa seçimi ses yapabilir. İlk kontrol hava tahliyesi ve tesisat basıncı olmalıdır.")
     ]),
     "Flanşlı Sirkülasyon Pompası Fiyatları | Koşar Ticaret",
     "Merkezi ısıtma ve soğutma hatları için flanşlı sirkülasyon pompaları. DN40 üzeri tesisatlar, PN10/PN16 bağlantı ve teknik destek."),

    (156,
     """<h2>Sıcak Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Sıcak su pompası</strong>, ısıtma, boyler, güneş enerjisi, merkezi sıcak su ve proses hatlarında 60-110°C aralığındaki sıcak suyu devridaim ettirmek veya transfer etmek için üretilmiş özel pompadır. Standart soğuk su pompalarında kullanılan conta ve plastik parçalar yüksek sıcaklıkta bozulabileceği için sıcak su pompalarında EPDM, Viton, seramik-karbon mekanik conta ve ısıya dayanıklı gövde malzemeleri kullanılır.</p>
<p>Kapalı devre dolaşım için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari">sirkülasyon pompaları</a>, basınç artırma amacıyla <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforu</a> seçenekleri incelenmelidir.</p>""",
     faq([
         ("Sıcak su pompası kaç dereceye dayanır?", "Modeline göre 90°C, 110°C veya 120°C sürekli çalışma sıcaklığına dayanabilir. Seçim yapılırken maksimum sıvı sıcaklığı ve çalışma basıncı birlikte değerlendirilmelidir."),
         ("Soğuk su pompası sıcak suda kullanılabilir mi?", "Önerilmez. Standart contalar ve plastik parçalar yüksek sıcaklıkta bozulur. Sıcak su için EPDM/Viton conta ve uygun gövde malzemesine sahip model gerekir."),
         ("Sıcak su pompası kombi hattına bağlanır mı?", "Kombi veya merkezi sıcak su devridaim hattında kullanılabilir; ancak pompa tipi sistemin açık veya kapalı devre olmasına göre seçilmelidir."),
         ("Sıcak su pompasında kireçlenme nasıl azaltılır?", "Sert su bölgelerinde su yumuşatma, filtre ve düzenli sistem temizliği gerekir. Kireç, mekanik contayı aşındırır ve pompa verimini düşürür."),
         ("Sıcak su pompası ile sıcak su hidroforu aynı mı?", "Hayır. Sıcak su pompası çoğunlukla devridaim veya transfer yapar; sıcak su hidroforu ise açık devrede basınç artırır.")
     ]),
     "Sıcak Su Pompası Fiyatları | Koşar Ticaret",
     "Boyler, güneş enerjisi ve merkezi sıcak su hatları için ısıya dayanıklı sıcak su pompaları. EPDM/Viton conta ve teknik seçim desteği."),

    (160,
     """<h2>Jakuzi Pompası Modelleri ve Fiyatları</h2>
<p><strong>Jakuzi pompası</strong>, spa, küvet ve hidromasaj sistemlerinde suyu yüksek debiyle memelere basarak masaj etkisi oluşturan özel tasarımlı sirkülasyon pompasıdır. Standart su pompalarından farklı olarak düşük gürültü, titreşim yalıtımı, neme dayanıklı motor ve sürekli devridaim için optimize edilmiş hidrolik yapıya sahiptir.</p>
<p>Havuz sirkülasyonu için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi">ön filtreli havuz pompaları</a>, özel uygulamalar için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar">özel amaçlı pompalar</a> sayfasını inceleyebilirsiniz.</p>""",
     faq([
         ("Jakuzi pompası normal pompa yerine kullanılabilir mi?", "Jakuzi pompası hidromasaj için tasarlanmıştır; genel su transferinde kullanılabilir gibi görünse de verim ve bağlantı tipi uygun olmayabilir. Normal su pompası ise jakuzi sisteminde yüksek gürültü ve titreşim yapabilir."),
         ("Jakuzi pompası neden hava yapar?", "Emiş hattında kaçak, düşük su seviyesi veya filtre tıkanıklığı pompanın hava yapmasına neden olur. Su seviyesi emiş ağzının üzerinde olmalı ve sistemde hava tahliyesi yapılmalıdır."),
         ("Jakuzi pompası kaç HP olmalı?", "Küçük ev tipi jakuzilerde 0,75-1 HP, çok memeli büyük spa sistemlerinde 1,5-3 HP arası pompalar kullanılır. Meme sayısı ve boru hattı kayıpları seçimi belirler."),
         ("Jakuzi pompası sessiz model olur mu?", "Evet. Kaliteli rulman, iyi balanslanmış impeller ve titreşim takozu olan modeller daha sessiz çalışır. Montaj zemini de sesi doğrudan etkiler."),
         ("Jakuzi pompasının contası neden bozulur?", "Kuru çalışma, kimyasal dengesizlik ve yüksek sıcaklık mekanik contayı bozar. Su seviyesi ve kimyasal pH değerleri düzenli kontrol edilmelidir.")
     ]),
     "Jakuzi Pompası Fiyatları | Koşar Ticaret",
     "Spa ve hidromasaj sistemleri için sessiz, yüksek debili jakuzi pompaları. Ev ve ticari spa uygulamalarına uygun garantili modeller."),

    (147,
     """<h2>Foseptik Tahliye Cihazı Modelleri ve Fiyatları</h2>
<p><strong>Foseptik tahliye cihazı</strong> (lifting station / atık su terfi ünitesi), bodrum katı, zemin altı banyo, WC, mutfak veya çamaşır odasındaki atık suyu yer çekiminin yetmediği durumlarda kanalizasyon kotuna pompalayan kompakt sistemdir. Pompa, kesici veya vortex mekanizma, toplama haznesi ve otomatik şamandıra tek gövdede bulunur.</p>
<p>Büyük fosseptik kuyuları için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a>, kesici sistemler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompalar</a> sayfalarını inceleyebilirsiniz.</p>""",
     faq([
         ("Foseptik tahliye cihazı ne işe yarar?", "Kanalizasyon seviyesinin altında kalan WC, duş veya lavabonun atık suyunu yukarı pompalayarak ana hatta bağlar. Bodrum kata banyo veya tuvalet eklemek için pratik çözümdür."),
         ("Bu cihaz koku yapar mı?", "Doğru havalandırma ve çekvalf montajıyla koku yapmaz. Hazne kapağı contalı olmalı, havalandırma hattı uygun şekilde dışarı verilmelidir."),
         ("Tuvalet kağıdı ve ıslak mendil geçer mi?", "Tuvalet kağıdı çoğu cihazda sorun yaratmaz; ıslak mendil ve bez gibi lifli atıklar tıkanma yapabilir. Kesicili model seçilse bile plastik ve tekstil atıklar kullanılmamalıdır."),
         ("Cihaz elektrik kesilince çalışır mı?", "Standart modeller elektrik kesildiğinde çalışmaz. Kritik kullanımda UPS veya jeneratör destekli kurulum önerilir."),
         ("Foseptik tahliye cihazı bakımı zor mu?", "Hazne ve bıçak/impeller bölgesi yılda 1-2 kez temizlenmelidir. Yağlı mutfak atığı bağlanıyorsa yağ tutucu kullanmak tıkanmayı azaltır.")
     ]),
     "Foseptik Tahliye Cihazı Fiyatları | Koşar Ticaret",
     "Bodrum WC, duş ve lavabo atık suyu için foseptik tahliye cihazları. Kesicili veya vortex sistem, kompakt hazne ve otomatik çalışma."),

    (161,
     """<h2>Klapeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Klapeli pompa</strong>, geri akışı önleyen entegre çekvalf (klape) yapısına sahip atık su veya drenaj pompasıdır. Pompa durduğunda basma hattındaki suyun geri dönmesini engeller; bu sayede pompa tekrar devreye girdiğinde aynı suyu yeniden basmak zorunda kalmaz ve çukurda taşma riski azalır.</p>
<p>Drenaj uygulamaları için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompalar</a>, fosseptik uygulamaları için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a> sayfası da incelenmelidir.</p>""",
     faq([
         ("Klape ne işe yarar?", "Klape, pompa durduğunda basma borusundaki suyun geri akmasını engelleyen tek yönlü valftir. Taşma riskini azaltır ve pompanın sık çalışmasını önler."),
         ("Klapeli pompa her yerde gerekli mi?", "Geri akış riski olan dik basma hatlarında, bodrum su tahliyesinde ve fosseptik uygulamalarında çok faydalıdır. Kısa yatay tahliyede şart olmayabilir."),
         ("Klape tıkanırsa ne olur?", "Klape kapanmazsa su geri akar ve pompa sürekli devreye girer. Açılmazsa pompa basamaz. Periyodik temizlik gerekir."),
         ("Dışarıdan çekvalf takmak yerine klapeli pompa alınır mı?", "Entegre klapeli model montajı kolaylaştırır; fakat büyük sistemlerde dış tip çekvalf bakım açısından daha erişilebilir olabilir."),
         ("Klapeli pompa pis suda çalışır mı?", "Pompanın impeller tipine bağlıdır. Vortex veya foseptik tipi klapeli pompalar pis suda çalışabilir; standart drenaj tipi iri katı maddeye uygun değildir.")
     ]),
     "Klapeli Pompa Fiyatları | Koşar Ticaret",
     "Geri akışı önleyen entegre çekvalfli klapeli pompalar. Bodrum, drenaj ve atık su tahliyesi için güvenli pompalama çözümleri."),

    (137,
     """<h2>Keson Kuyu Pompası Modelleri ve Fiyatları</h2>
<p><strong>Keson kuyu pompası</strong>, geniş çaplı beton halka kuyulardan su çekmek için kullanılan dalgıç veya yüzey tipi pompadır. Keson kuyular genellikle 3-20 metre derinliğe sahip olduğu için derin kuyu sondaj pompalarından farklı seçim yapılır. Su seviyesi değişken ise dalgıç pompa, su seviyesi yüzeye yakınsa jet veya santrifüj yüzey pompası tercih edilebilir.</p>
<p>Daha derin sondaj kuyuları için <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompaları</a>, temiz su kullanımı için <a href="/kategoriler/su-pompalari/dalgic-pompalar/temiz-su-dalgic-pompasi">temiz su dalgıç pompaları</a> sayfalarını inceleyebilirsiniz.</p>""",
     faq([
         ("Keson kuyu için dalgıç pompa mı yüzey pompası mı?", "Su seviyesi 7-8 metreden daha derindeyse dalgıç pompa daha doğru seçimdir. Su seviyesi yüzeye yakın ve bakım erişimi önemliyse yüzey tipi jet veya santrifüj pompa kullanılabilir."),
         ("Keson kuyu pompası kaç metreye su basar?", "Modeline bağlıdır. Temiz su dalgıç pompaları 20-60 m, kademeli dalgıçlar 100 m üzeri basma yüksekliği sağlayabilir."),
         ("Kuyu suyu kumluysa hangi pompa gerekir?", "Kumlu su impelleri aşındırır. Kum toleranslı dalgıç pompa ve kuyu filtresi kullanılmalı, ilk çalıştırmada kuyu temiz su gelene kadar boşaltılmalıdır."),
         ("Keson kuyuda şamandıra gerekli mi?", "Evet. Su seviyesi düştüğünde pompanın kuru çalışmasını önlemek için seviye flatörü veya elektrotlu seviye kontrolü önerilir."),
         ("Keson kuyu pompası içme suyu için kullanılabilir mi?", "Gıda uyumlu malzemeden üretilmiş temiz su dalgıç pompası tercih edilmelidir. Kuyu suyunun içilebilirliği için ayrıca analiz yapılmalıdır.")
     ]),
     "Keson Kuyu Pompası Fiyatları | Koşar Ticaret",
     "Beton halka ve geniş çaplı kuyular için keson kuyu pompaları. Dalgıç veya yüzey tipi seçenekler, kuru çalışma koruması ve teknik destek."),
]

updated = 0
for cat_id, desc, faq_json, title, meta in CATS:
    cur.execute("UPDATE categories SET description=?, faq=?, meta_title=?, meta_description=? WHERE id=?", (desc, faq_json, title, meta, cat_id))
    if cur.rowcount:
        updated += 1
        print(f"  ✓ id={cat_id}")
    else:
        print(f"  ✗ id={cat_id} BULUNAMADI")

conn.commit()
conn.close()
print(f"\nPart-4: {updated} kategori guncellendi.")
