# -*- coding: utf-8 -*-
import sqlite3, json, sys, os
sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

def faq(items):
    return json.dumps([{"q": q, "a": a} for q, a in items], ensure_ascii=False)

CATS = [
    (116,
     """<h2>Elektrik ve Aydınlatma Ürünleri</h2>
<p><strong>Elektrik ve aydınlatma</strong> ürünleri; pompa, hidrofor, vantilatör ve sanayi ekipmanlarının güvenli çalışması için gerekli tamamlayıcı ekipmanları kapsar. Motor koruma şalteri, kontaktör, sigorta, kablo bağlantı ekipmanı, LED armatür ve pano içi aksesuarlar doğru seçilmediğinde pompa motorlarında yanma, faz kaybı ve güvenlik riski oluşabilir.</p>
<h3>Pompa Sistemlerinde Elektrik Güvenliği</h3>
<ul>
  <li><strong>Motor koruma şalteri:</strong> aşırı akım ve kısa devreye karşı motoru korur</li>
  <li><strong>Kontaktör:</strong> motorun güvenli devreye alınmasını sağlar</li>
  <li><strong>Faz koruma rölesi:</strong> üç fazlı sistemlerde faz kaybı ve faz sırası hatasını önler</li>
  <li><strong>Kaçak akım rölesi:</strong> özellikle <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompa</a> uygulamalarında can güvenliği için kritiktir</li>
</ul>
<p><a href="/marka/horoz-electric">Horoz Electric</a> ürünleri, pompa tesisatı ve endüstriyel elektrik altyapısında yaygın kullanılan ekonomik ve güvenilir çözümler sunar. Pompa seçiminin yanında doğru elektrik koruması için <a href="/kategoriler/su-pompalari">su pompaları</a> kategorimizi de inceleyebilirsiniz.</p>""",
     faq([
         ("Pompa için motor koruma şalteri gerekli mi?", "Evet. Motor koruma şalteri, pompayı aşırı akım, kısa devre ve sıkışma kaynaklı motor yanmasına karşı korur. Özellikle üç fazlı pompalar motor koruma olmadan çalıştırılmamalıdır."),
         ("Kontaktör ile sigorta aynı şey mi?", "Hayır. Sigorta devreyi korur, kontaktör ise motoru uzaktan veya otomasyonla açıp kapatır. Büyük pompa sistemlerinde sigorta, kontaktör ve termik röle birlikte kullanılır."),
         ("Dalgıç pompa için kaçak akım rölesi gerekir mi?", "Kesinlikle gerekir. Su içinde çalışan elektrikli ekipmanlarda kaçak akım rölesi can güvenliği açısından zorunlu kabul edilmelidir."),
         ("LED aydınlatma sanayi ortamında nasıl seçilmeli?", "Tozlu ve nemli ortamlarda IP65 veya üzeri koruma sınıfı; yüksek tavanlı depolarda lens açısı ve lümen değeri doğru seçilmelidir."),
         ("Pompa panosunda faz koruma rölesi neden kullanılır?", "Faz kaybı veya faz sırası hatası üç fazlı motoru kısa sürede yakabilir. Faz koruma rölesi bu durumda motoru otomatik durdurur.")
     ]),
     "Elektrik ve Aydınlatma Ürünleri | Koşar Ticaret",
     "Pompa ve sanayi sistemleri için motor koruma, kontaktör, sigorta, pano ekipmanı ve aydınlatma ürünleri. Horoz Electric çözümleri."),

    (123,
     """<h2>Hidromat Modelleri ve Fiyatları</h2>
<p><strong>Hidromat</strong>, pompa çıkışına bağlanarak su basıncını otomatik kontrol eden elektronik basınç şalteridir. Klasik hidrofor tankı ve presostat kullanımına alternatif olarak küçük konut, bahçe ve basit basınç artırma sistemlerinde pompayı musluk açıldığında çalıştırır, musluk kapandığında durdurur.</p>
<p>Hidromat, özellikle küçük <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidrofor</a> sistemlerinde yerden tasarruf sağlar. Daha stabil basınç ve uzun motor ömrü için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">tanklı hidroforlar</a> veya <a href="/kategoriler/hidrofor-sistemleri/pedrollo-hidrofor">Pedrollo inverterli hidroforlar</a> da değerlendirilebilir.</p>""",
     faq([
         ("Hidromat ne işe yarar?", "Hidromat, pompayı otomatik açıp kapatan elektronik kontrol ünitesidir. Musluk açılınca akışı algılar ve pompayı çalıştırır; akış bitince pompayı durdurur."),
         ("Hidromat basınç tankının yerine geçer mi?", "Küçük sistemlerde kısmen geçer; ancak basınç tankı kadar su depolamadığı için pompa daha sık çalışabilir. Yoğun kullanımda tanklı hidrofor daha sağlıklıdır."),
         ("Hidromat kuru çalışma koruması yapar mı?", "Birçok hidromat modelinde kuru çalışma koruması vardır. Su gelmediğini algıladığında pompayı durdurur ve motorun yanmasını önler."),
         ("Hidromat hangi pompalarla çalışır?", "Santrifüj, jet ve preferikal yüzey pompalarıyla kullanılabilir. Pompanın basıncı hidromatın minimum çalışma basıncını karşılamalıdır."),
         ("Hidromat arızası nasıl anlaşılır?", "Pompa hiç çalışmıyor, sürekli çalışıyor veya musluk kapalıyken sık devreye giriyorsa hidromat sensörü, çekvalf veya basınç ayarı kontrol edilmelidir.")
     ]),
     "Hidromat Fiyatları | Elektronik Pompa Kontrolü | Koşar Ticaret",
     "Pompa ve küçük hidrofor sistemleri için hidromat elektronik basınç kontrol cihazları. Kuru çalışma koruması ve otomatik pompa yönetimi."),

    (126,
     """<h2>Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Su pompası</strong>, temiz suyun kuyu, depo, sarnıç, şebeke veya göletten alınarak istenen noktaya basınçla taşınmasını sağlayan temel mekanik ekipmandır. Evsel kullanımda bahçe sulama, depo besleme ve hidrofor uygulamaları; sanayide ise proses suyu, soğutma devresi ve transfer hatları için kullanılır.</p>
<p>İhtiyaca göre <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a>, <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompa</a>, <a href="/kategoriler/su-pompalari/kademeli-pompalar">kademeli pompa</a> veya <a href="/kategoriler/hidrofor-sistemleri">hidrofor sistemi</a> tercih edilir. Marka olarak <a href="/marka/pedrollo">Pedrollo</a>, <a href="/marka/sumak">Sumak</a>, <a href="/marka/kaysu-pompa">Kaysu</a> ve <a href="/marka/winpo">Winpo</a> seçenekleri bulunur.</p>""",
     faq([
         ("Su pompası seçerken en önemli iki değer nedir?", "Debi ve basma yüksekliği. Debi pompanın ne kadar su taşıyacağını, basma yüksekliği ise suyu ne kadar yükseğe veya ne kadar basınçla göndereceğini gösterir."),
         ("Ev için hangi su pompası uygundur?", "Bahçe ve depo besleme için santrifüj veya jet pompa; kuyu içinde çalışma için dalgıç pompa; sabit basınçlı kullanım için hidrofor sistemi uygundur."),
         ("Su pompası kuru çalışırsa ne olur?", "Kuru çalışma mekanik contayı yakar ve motoru aşırı ısıtır. Pompa mutlaka su dolu çalışmalı, mümkünse kuru çalışma koruması kullanılmalıdır."),
         ("Pompa gücü arttıkça daha mı iyi olur?", "Hayır. Gereğinden büyük pompa enerji harcar, tesisatta basınç darbesi oluşturur ve daha kısa ömürlü olabilir. Doğru boyutlandırma gerekir."),
         ("Su pompası garanti süresi ne kadar?", "Koşar Ticaret'te satılan markalı pompalar genel olarak 2 yıl garanti kapsamındadır. Garanti koşulları markaya ve kullanım hatasına göre değişir.")
     ]),
     "Su Pompası Fiyatları ve Modelleri | Koşar Ticaret",
     "Ev, tarım ve sanayi için su pompası modelleri. Santrifüj, dalgıç, kademeli ve hidrofor seçenekleri. Pedrollo, Sumak, Kaysu ve Winpo."),

    (141,
     """<h2>Çift Fanlı Santrifüj Pompa Modelleri</h2>
<p><strong>Çift fanlı santrifüj pompa</strong>, iki impellerin seri çalışmasıyla tek fanlı modellere göre daha yüksek basınç üreten santrifüj pompa tipidir. Debi ihtiyacı orta seviyede kalırken basma yüksekliği arttığında, örneğin çok katlı bina besleme, uzun boru hattı veya bahçe sulamasında tercih edilir.</p>
<p>Daha düşük basınçlı yüksek debi için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/tek-fanli-santrifuj-pompa">tek fanlı santrifüj pompalar</a>, çok daha yüksek basınç için <a href="/kategoriler/su-pompalari/kademeli-pompalar">kademeli pompalar</a> doğru alternatiftir.</p>""",
     faq([
         ("Çift fanlı pompa ne avantaj sağlar?", "İki fan seri çalıştığı için basınç artar. Tek fanlı pompanın yetersiz kaldığı orta debi-yüksek basınç uygulamalarında kullanılır."),
         ("Çift fanlı pompa daha çok elektrik yakar mı?", "Aynı debide daha yüksek basınç ürettiği için motor gücü genellikle daha yüksektir; ancak doğru noktada çalışırsa verimli olabilir."),
         ("Bahçe sulamasında çift fanlı pompa kullanılır mı?", "Evet, özellikle uzun mesafeli boru hattı veya eğimli arazide basınç kaybı fazlaysa çift fanlı pompa uygundur."),
         ("Çift fanlı pompa hidrofor yapılabilir mi?", "Basınç tankı ve presostatla hidrofor sistemi kurulabilir. Ancak sürekli konut basıncı için kademeli veya inverterli sistemler daha konforlu olabilir."),
         ("Tek fanlı yerine çift fanlı seçmek her zaman doğru mu?", "Hayır. Sadece yüksek basınç ihtiyacı varsa doğru olur. Gereksiz yüksek basınç tesisata zarar verebilir.")
     ]),
     "Çift Fanlı Santrifüj Pompa Fiyatları | Koşar Ticaret",
     "Orta debi ve yüksek basınç gerektiren uygulamalar için çift fanlı santrifüj pompalar. Sulama, bina ve hidrofor sistemleri için teknik destek."),

    (163,
     """<h2>Yatay Kademeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Yatay kademeli pompa</strong>, birden fazla impeller kademesinin yatay eksende sıralandığı, yüksek basınç ihtiyacını kompakt ve servis edilebilir bir yapıyla karşılayan pompadır. Bina basınç artırma, RO ters ozmoz, küçük kazan besleme, yıkama sistemleri ve proses suyu hatlarında yaygın kullanılır.</p>
<p>Kompakt paket çözüm için <a href="/kategoriler/su-pompalari/kademeli-pompalar/monoblok-yatay-kademeli">monoblok yatay kademeli</a>, ağır sanayi için <a href="/kategoriler/su-pompalari/kademeli-pompalar/norm-tipi-yatay-kademeli">norm tipi yatay kademeli</a>, yer tasarrufu için <a href="/kategoriler/su-pompalari/kademeli-pompalar/dikey-kademeli-pompalar">dikey kademeli</a> seçenekleri incelenmelidir.</p>""",
     faq([
         ("Yatay kademeli pompa neden kullanılır?", "Tek kademeli pompanın basıncı yetmediğinde, birden fazla impeller seri bağlanarak yüksek basınç elde edilir. Yatay yapı bakım erişimini kolaylaştırır."),
         ("Yatay kademeli pompa RO sisteminde kullanılır mı?", "Evet. Ters ozmoz membranları belirli giriş basıncı istediği için yatay veya dikey kademeli pompalar sık kullanılır."),
         ("Dikey mi yatay kademeli mi daha iyi?", "Alan darsa dikey; servis erişimi ve yatay boru hattı uyumu önemliyse yatay daha avantajlıdır."),
         ("Kademeli pompa ses yapar mı?", "Doğru seçildiğinde sessiz çalışır. Kavitasyon, rulman aşınması veya yanlış çalışma noktası ses ve titreşim yapabilir."),
         ("Yatay kademeli pompa sıcak su basar mı?", "Model malzeme ve conta uygun ise belirli sıcaklıklara kadar basabilir. Standart modeller için üretici sıcaklık sınırı kontrol edilmelidir.")
     ]),
     "Yatay Kademeli Pompa Fiyatları | Koşar Ticaret",
     "Bina, RO ve proses hatları için yatay kademeli pompalar. Monoblok ve norm tipi seçenekler, yüksek basınç ve teknik seçim desteği."),

    (154,
     """<h2>Sintine Pompası Modelleri ve Fiyatları</h2>
<p><strong>Sintine pompası</strong>, tekne, yat, balıkçı teknesi ve marin uygulamalarda tekne tabanında biriken sintine suyunu otomatik veya manuel olarak tahliye eden kompakt pompadır. Tuzlu suya, yağlı suya ve dar alan montajına dayanıklı olacak şekilde plastik veya paslanmaz malzemeyle üretilir.</p>
<p>Genel drenaj için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompası</a>, tuzlu ve korozif ortamlar için <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj pompaları</a> incelenebilir.</p>""",
     faq([
         ("Sintine pompası otomatik çalışır mı?", "Şamandıra flatörü veya elektronik seviye sensörüyle otomatik çalışabilir. Su belirli seviyeye çıkınca devreye girer."),
         ("Tekne için kaç GPH sintine pompası gerekir?", "Küçük teknelerde 500-800 GPH, orta boy teknelerde 1100-2000 GPH, büyük teknelerde birden fazla pompa önerilir."),
         ("Sintine pompası tuzlu suya dayanır mı?", "Marin sınıfı modeller tuzlu suya dayanıklı plastik veya paslanmaz bileşenlerden üretilir. Kullanım sonrası tatlı suyla durulama ömrü artırır."),
         ("Sintine pompası yağlı suyu basabilir mi?", "Hafif yağlı sintine suyunu basabilir; ancak çevre mevzuatı gereği yağlı sintine suyu doğrudan denize boşaltılmamalıdır."),
         ("Sintine pompası neden sık tıkanır?", "Saç, ip, plastik parça ve tortu emiş ızgarasını tıkayabilir. Düzenli temizlik ve emiş filtresi kullanımı gerekir.")
     ]),
     "Sintine Pompası Fiyatları | Koşar Ticaret",
     "Tekne ve marin uygulamalar için otomatik sintine pompaları. Tuzlu suya dayanıklı, şamandıralı ve kompakt modeller."),

    (164,
     """<h2>Yağmur Suyu Tahliye Pompası Modelleri</h2>
<p><strong>Yağmur suyu tahliye pompası</strong>, bodrum, garaj, bahçe çukuru, asansör kuyusu ve yağmur suyu toplama haznelerinde biriken suyu otomatik olarak uzaklaştırmak için kullanılan dalgıç pompa sistemidir. Ani sağanaklarda hızlı debiyle çalışması, otomatik şamandıra ve geri akış önleyici çekvalf kullanımı kritik önemdedir.</p>
<p>Hafif kirli su için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj pompaları</a>, çamurlu su için <a href="/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa">kirli su dalgıç pompaları</a> tercih edilmelidir.</p>""",
     faq([
         ("Yağmur suyu pompası otomatik çalışır mı?", "Şamandıralı modeller su seviyesi yükseldiğinde otomatik devreye girer. Bodrum ve garaj için otomatik model önerilir."),
         ("Bodrum taşmasını önlemek için kaç pompa gerekir?", "Risk yüksekse biri ana, biri yedek olmak üzere iki pompa önerilir. Elektrik kesintisine karşı UPS veya jeneratör düşünülmelidir."),
         ("Yağmur suyu pompası çamurlu suda çalışır mı?", "Hafif tortuda çalışabilir; yoğun çamur veya iri parça varsa kirli su pompası seçilmelidir."),
         ("Çekvalf gerekli mi?", "Evet, basma hattındaki suyun geri dönmesini önlemek için çekvalf tavsiye edilir. Aksi halde pompa aynı suyu tekrar tekrar basabilir."),
         ("Pompa kuyusu nasıl olmalı?", "Pompanın rahat yerleşeceği, tortunun çökeceği ve şamandıranın takılmadan hareket edeceği yeterli hacimde olmalıdır.")
     ]),
     "Yağmur Suyu Tahliye Pompası | Koşar Ticaret",
     "Bodrum, garaj ve bahçe çukurları için otomatik yağmur suyu tahliye pompaları. Şamandıralı, çekvalfli ve hızlı debili çözümler."),

    (159,
     """<h2>Karıştırıcılı Çamur Pompası Modelleri</h2>
<p><strong>Karıştırıcılı çamur pompası</strong>, dipte çöken yoğun çamur, kum ve tortuyu pompalama öncesinde karıştırarak akışkan hale getiren agitator yapılı dalgıç pompadır. Standart drenaj pompaları çökelmiş tortuyu ememezken karıştırıcılı modeller maden, şantiye, arıtma, hafriyat ve çamur havuzlarında ağır hizmet için kullanılır.</p>
<p>Daha hafif kirli su için <a href="/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa">kirli su dalgıç pompa</a>, fosseptik için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik pompalar</a> daha uygundur.</p>""",
     faq([
         ("Karıştırıcılı çamur pompası ne fark eder?", "Altındaki karıştırıcı bıçak, çöken tortuyu kaldırıp sıvıyla karıştırır. Böylece yoğun çamur pompalanabilir hale gelir."),
         ("Bu pompalar kumlu suda çalışır mı?", "Evet, aşınmaya dayanıklı impeller ve gövdeyle kumlu/çamurlu suda çalışır. Ancak aşırı iri taşlar pompayı zorlayabilir."),
         ("Arıtma tesisinde kullanılabilir mi?", "Evet, çamur havuzları ve tortu transferlerinde kullanılır. Sıvı içeriğine göre malzeme ve conta seçimi yapılmalıdır."),
         ("Karıştırıcı sürekli çalışır mı?", "Pompa çalışırken karıştırıcı da döner. Bu yapı motor yükünü artırdığı için doğru güç seçimi önemlidir."),
         ("Bakımı nasıl yapılır?", "Karıştırıcı bıçak, impeller ve mekanik conta sık kontrol edilmelidir. Aşındırıcı çamurda bakım aralığı standart pompalara göre daha kısadır.")
     ]),
     "Karıştırıcılı Çamur Pompası Fiyatları | Koşar Ticaret",
     "Şantiye, maden ve arıtma çamuru için karıştırıcılı dalgıç çamur pompaları. Ağır hizmet, aşınmaya dayanıklı yapı ve teknik seçim desteği."),

    (165,
     """<h2>Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Dalgıç pompa</strong>, motor ve pompa gövdesiyle birlikte suya tamamen daldırılarak çalışan, temiz su, drenaj, kirli su, fosseptik, sintine ve derin kuyu uygulamalarında kullanılan geniş ürün grubudur. Yüzey pompalarına göre emiş problemi yaşamaz; pompa doğrudan suyun içinde olduğu için suyu iterek daha verimli çalışır.</p>
<p>İhtiyaca göre <a href="/kategoriler/su-pompalari/dalgic-pompalar/temiz-su-dalgic-pompasi">temiz su dalgıç pompası</a>, <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a>, <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj pompası</a>, <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik pompası</a> veya <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompa</a> seçilmelidir.</p>""",
     faq([
         ("Dalgıç pompa hangi durumlarda kullanılır?", "Kuyu, depo, sarnıç, drenaj çukuru, fosseptik ve derin kuyu gibi pompanın su içinde çalışması gereken uygulamalarda kullanılır."),
         ("Dalgıç pompa suyun dışında çalışır mı?", "Hayır. Motor soğutması suyla sağlandığı için dışarıda veya kuru çalıştırılmamalıdır. Kuru çalışma motoru yakabilir."),
         ("Temiz su ve kirli su dalgıç pompası farkı nedir?", "Temiz su pompası küçük partiküle uygundur; kirli su pompası tortu ve daha büyük parçacıkları geçirebilir. Yanlış tip seçimi tıkanma veya aşınma yapar."),
         ("Dalgıç pompa için şamandıra gerekli mi?", "Otomatik çalışması ve kuru çalışma riskinin önlenmesi için şamandıra önerilir. Özellikle drenaj ve yağmur suyu tahliyesinde çok önemlidir."),
         ("Dalgıç pompa markası seçerken nelere bakmalı?", "Uygulama tipi, motor gücü, basma yüksekliği, parçacık geçiş çapı, garanti ve servis ağı değerlendirilmelidir. Pedrollo ve Sumak yaygın servis avantajı sunar.")
     ]),
     "Dalgıç Pompa Fiyatları ve Çeşitleri | Koşar Ticaret",
     "Temiz su, drenaj, kirli su, foseptik, bıçaklı ve derin kuyu dalgıç pompa modelleri. Pedrollo, Sumak ve Winpo seçenekleri."),
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
print(f"\nPart-5: {updated} kategori guncellendi.")
