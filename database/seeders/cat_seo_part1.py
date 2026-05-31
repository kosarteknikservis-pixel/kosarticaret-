# -*- coding: utf-8 -*-
# Part 1: Dalgic + Jet + Preferikal + Santrifuj alt kategorileri
import sqlite3, json, sys, os
sys.stdout.reconfigure(encoding='utf-8')
DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

CATS = [

# ── 138 Derin Kuyu Dalgic Pompa ──────────────────────────────────────────────
(138,
"""<h2>Derin Kuyu Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Derin kuyu dalgıç pompası</strong>, statik su seviyesi 8 metrenin altında olan artezyen ve sondaj kuyularından içme suyu, sulama suyu veya sanayi suyu temin etmek için kuyunun içine daldırılarak çalıştırılan özel elektrik motorlu pompasıdır. Motor ve pompa birimi birleşik olup tamamen su içinde çalışır; su motorun soğutulmasını sağlar.</p>
<h3>Teknik Özellikler ve Boyutlandırma</h3>
<ul>
  <li><strong>4" (DN100)</strong> kuyu çapı — en yaygın konut/tarım derin kuyu standardı</li>
  <li><strong>6" (DN150) ve üzeri</strong> — yüksek debili tarım ve sanayi kuyuları</li>
  <li>Basma yüksekliği: 20 m'den 300 m'ye kadar model bazlı değişir</li>
  <li>Debi: 1 m³/s'den 150 m³/s'ye kadar (0,37 kW – 75 kW)</li>
</ul>
<h3>Marka Seçenekleri</h3>
<p><a href="/marka/pedrollo"><strong>Pedrollo 4SR/6SR serisi</strong></a>, paslanmaz çelik gövde ve aşamalı kademeli tasarımıyla Türkiye'de en çok tercih edilen derin kuyu pompasıdır. <a href="/marka/sumak"><strong>Sumak</strong></a> ise daha ekonomik bütçe alternatifleri sunar. <a href="/kategoriler/su-pompalari/dalgic-pompalar">Tüm dalgıç pompa çeşitleri</a> için ana kategori sayfamızı inceleyebilirsiniz.</p>""",
[
{"q":"Derin kuyu pompası için kuyu sondaj çapı ne kadar olmalı?",
 "a":"Standart 4\" (100 mm) derin kuyu dalgıç pompalar için kuyu iç çapının en az <strong>110 mm</strong> olması gerekir (pompa dış çapı + 5 mm minimum boşluk). 6\" pompa için en az 160 mm iç çap gerekir. Türkiye'de çoğu konut sondajı 110-125 mm çapında açılmaktadır; pompa seçimi yapılmadan önce kuyu test raporunun alınması tavsiye edilir."},
{"q":"Derin kuyu pompasını kuyuya nasıl indiririm?",
 "a":"Pompa, <strong>paslanmaz çelik veya galvanizli çelik boru</strong> ve stainless çelik askı halatıyla kuyuya indirilir. Pompa üstüne basınçlı PVC veya çelik iletim borusu bağlanır, elektrik kablosu boruya bantlanarak çıkar. Kuyu başlığı montajından sonra sisteme elektrik verilir. İlk devrede kuyu içindeki çamur suyunun temizlenmesi beklenir; genellikle 30-60 dakika sürer. Montajı mutlaka kuyu açma firması veya yetkili teknisyen yapmalıdır."},
{"q":"Derin kuyu pompası için kaç kW güç gerekir?",
 "a":"Gerekli güç; <strong>debi (Q), basma yüksekliği (H) ve verim (η)</strong> değerlerine göre hesaplanır. Kaba formül: P(kW) = (Q × H × ρ × g) / (3600 × η). Örneğin 5 m³/saat debide 80 metre basma yüksekliği için yaklaşık <strong>1,5-2,2 kW</strong> motor gücü yeterlidir. Daha derin kuyular (150m+) için 4-7,5 kW gerekebilir. Teknik ekibimiz kuyu raporunuzu değerlendirerek doğru gücü belirler."},
{"q":"Derin kuyu pompam suyu neden yavaş çekiyor?",
 "a":"Yavaş su çekiminin başlıca nedenleri: <strong>kuyu veriminin düşmesi</strong> (kuyu içindeki su miktarının azalması), impeller aşınması, boru içinde kireç tıkanması veya pompanın gereğinden yüksek derinliğe kurulması. İlk yapılacak iş kuyu su seviyesini ölçmektir. Kuyu verimi yeterliyse pompanın sökülüp kontrol edilmesi gerekir."},
{"q":"Derin kuyu pompasında susuzluk (kuru çalışma) ne kadar süre hasara yol açar?",
 "a":"Derin kuyu dalgıç pompaları, motorun su ile soğutulmasına dayalı çalışır. Kuru (susuz) çalışmada motor <strong>30 saniye ile 3 dakika</strong> içinde aşırı ısınarak mekanik conta ve rotor sargılarında kalıcı hasar oluşur. Bu nedenle sistem mutlaka <strong>seviye şamandırası veya basınç sensörü</strong> ile korunmalıdır. Pedrollo Easy Tech dahil akıllı sistemlerde kuru çalışma koruması entegre gelir."}
],
"Derin Kuyu Dalgıç Pompa Fiyatları | Koşar Ticaret",
"Pedrollo 4SR, 6SR ve Sumak markalı derin kuyu dalgıç pompaları. 4\" ve 6\" kuyu çapı seçenekleri, "
"20-300m basma yüksekliği. Yetkili satıcı Koşar Ticaret'ten uygun fiyat ve teknik destek."),

# ── 131 Temiz Su Dalgic Pompasi ──────────────────────────────────────────────
(131,
"""<h2>Temiz Su Dalgıç Pompası Modelleri ve Fiyatları</h2>
<p><strong>Temiz su dalgıç pompası</strong>, keson kuyu, su deposu (sarnıç) veya gölet gibi kaynakların içine daldırılarak içme suyu, sulama suyu veya binaya dağıtım suyu temin eden daldırmalı elektrik pompasıdır. Pompanın gövde, impeller ve bağlantı parçaları <strong>NSF/WRAS belgeli gıda uyumlu plastik veya 304 paslanmaz çelik</strong> malzemeden üretilir; bu sayede suyun kimyasal bileşimini değiştirmez.</p>
<h3>Kullanım Alanları</h3>
<ul>
  <li>Keson ve halka beton kuyular (3-15 m derinlik)</li>
  <li>Su depoları (sarnıç) ve çiftlik havuzlarından bina dağıtımı</li>
  <li><a href="/kategoriler/hidrofor-sistemleri">Hidrofor sistemleri</a> ile birlikte sabit basınçlı su dağıtımı</li>
  <li>Tarım arazisine gölet veya kanaldan sulama</li>
</ul>
<p><a href="/marka/pedrollo">Pedrollo</a> ve <a href="/marka/sumak">Sumak</a> markalı temiz su dalgıç pompalar stokta hazır. <a href="/kategoriler/su-pompalari/dalgic-pompalar">Dalgıç pompa</a> ana kategorisinde tüm dalgıç çeşitlerini karşılaştırabilirsiniz.</p>""",
[
{"q":"Temiz su dalgıç pompası ile normal drenaj pompası arasındaki fark nedir?",
 "a":"Temiz su dalgıç pompası, <strong>gıda uyumlu (NSF/WRAS belgeli) malzemelerden</strong> üretilir ve içme suyu kalitesini korur; sadece temiz suda çalıştırılabilir (parçacık boyutu genellikle maks. 0,5 mm). Drenaj pompası ise ince tortu ve küçük partiküller içeren sularla çalışmak üzere tasarlanmış olup gıda belgesi yoktur. Temiz su pompasını drenaj amacıyla kullanmak impelleri hızla aşındırır."},
{"q":"Keson kuyum için hangi güçte temiz su dalgıç pompası seçmeliyim?",
 "a":"Keson kuyu için pompa seçiminde <strong>statik su derinliği + bina yüksekliği + boru kayıpları</strong> toplamı basma yüksekliğini verir. 10 m kuyudan 2 katlı bir binaya (8m) su basmak için toplam ~20-25 m, yani <strong>0,5-0,75 kW</strong> bir pompa yeterlidir. Kuyunun verimi (saatteki dolum hızı) da dikkate alınmalıdır; verimden yüksek debi çekmek kuyuyu kurutur."},
{"q":"Sarnıçtaki suyu binaya basmak için submersible mi, üstten pompa mı daha iyi?",
 "a":"Sarnıç zemini tabana yakınsa <strong>dalgıç pompa</strong> her zaman üstündür: emme borusu gerektirmez, hava kilidi riski yoktur ve deponun her noktasındaki suyu kullanabilir. Üstten monte edilen yüzey pompası maks. 7-8 m emme yüksekliğiyle sınırlıdır; sarnıç zemin altındaysa emme sorunları yaşanır. Yüzey pompası avantajı ise bakım ve erişim kolaylığıdır."},
{"q":"Temiz su dalgıç pompam kireç bağlıyor, ne yapmalıyım?",
 "a":"Sert su bölgelerinde (sertlik > 20 Fr) dalgıç pompalarda impeller, gövde ve boru içinde kireç (CaCO₃) birikmesi olabilir. Yılda bir <strong>sitrik asit çözeltisiyle temizleme</strong> (pompa devreden çıkarılıp asit+su karışımı kuyuya verilip beklenmesi) önerilir. Uzun vadede yumuşatma filtresi kullanmak en kalıcı çözümdür. Paslanmaz çelik gövdeli pompalar plastik gövdelilere göre kireçten daha az etkilenir."},
{"q":"Dalgıç pompa ne kadar süre suda bekleyebilir?",
 "a":"Kaliteli bir temiz su dalgıç pompası, <strong>sürekli olarak su içinde bekleyebilir</strong>; IP68 koruma sınıfı bunu garanti eder. Uzun süre kullanılmayacaksa (kış ayları gibi) kuyudan çıkarılarak temiz su ile durulayıp kuru yerde muhafaza etmek motor ve contaların ömrünü uzatır. Donma riski olan kuyularda pompa düzeyinin buz çizgisi altında kalmasına dikkat edilmelidir."}
],
"Temiz Su Dalgıç Pompası Fiyatları | Koşar Ticaret",
"Keson kuyu ve sarnıç için NSF uyumlu temiz su dalgıç pompaları. Pedrollo ve Sumak markalı, "
"gıda sınıfı malzeme, garantili. Koşar Ticaret'ten uygun fiyat ve hızlı teslimat."),

# ── 145 Drenaj Dalgic Pompa ──────────────────────────────────────────────────
(145,
"""<h2>Drenaj Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Drenaj dalgıç pompası</strong>, yağmur suyu, birikim suyu ve hafif askılı partikül içeren atık suyu tahliye etmek için su içine daldırılarak çalıştırılan elektrikli pompasıdır. Bodrum katı taşkınları, şantiye su boşaltımı, tarımsal drenaj ve zemin altı su birikim noktaları için yaygın kullanılır.</p>
<h3>Teknik Özellikler</h3>
<ul>
  <li>Geçirebileceği parçacık boyutu: genellikle <strong>10-20 mm</strong></li>
  <li>Motor gücü: 0,25 kW – 7,5 kW arasında geniş aralık</li>
  <li>IP68 korumalı motor, tamamen suya dayanıklı</li>
  <li>Otomatik float şamandırası seçeneği ile el müdahalesi gerektirmez</li>
</ul>
<h3>İlgili Kategoriler</h3>
<p>Katı madde içeren atık su için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompa</a>, kimyasal ortamlar için <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj pompası</a>, tüm seçenekler için <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompa</a> sayfamıza bakabilirsiniz.</p>""",
[
{"q":"Drenaj pompası ile kirli su pompası arasındaki fark nedir?",
 "a":"<strong>Drenaj pompası</strong>, ince tortu ve küçük parçacıklar (10-20 mm) içeren hafif kirli suları tahliye eder; çoğunlukla kanalı impeller tasarımına sahiptir. <strong>Kirli su dalgıç pompası</strong> ise daha büyük parçacıklar (25-50 mm) ile kısmen tıkanan sıvıları geçirebilir. <strong>Foseptik/pissu pompası</strong> ise 50 mm+ katı madde içeren fosseptik ve kanalizasyon suyunda çalışmak için gömlek veya vortex impellerle üretilir. Uygulamaya göre doğru tipi seçmek hem pompa ömrünü uzatır hem de tıkanma riskini azaltır."},
{"q":"Bodrumumdaki su için hangi güçte drenaj pompası yeterli?",
 "a":"Tipik bir bodrum su tahliyesi için <strong>0,37-0,75 kW</strong> güçlü bir drenaj pompası yeterlidir. Eğer su hızla birikiyorsa (yoğun yağış, su borusu patlaması) 1-1,5 kW tercih edilir. Önemli olan suyun tahliye edileceği mesafe ve yükseklik: her 10 m yükseklik için yaklaşık 1 bar ek basınç gerekir. Float şamandırası eklenmesi, su belirli seviyeye ulaşınca pompayı otomatik çalıştırır."},
{"q":"Drenaj pompasını bahçe havuzunu boşaltmak için kullanabilir miyim?",
 "a":"Evet, temiz veya hafif kirli havuz suyu için drenaj pompaları uygundur. Havuz suyu genellikle klor ve kimyasal madde içerdiğinden <strong>klora dayanıklı impeller ve conta</strong> olan modeller tercih edilmelidir. Yüksek konsantrasyonlu havuz kimyasalları için ise paslanmaz çelik gövdeli drenaj pompası seçilmesi önerilir; standart plastik gövdeli modeller zamanla bozulabilir."},
{"q":"Drenaj pompası şamandıralı mı almalıyım?",
 "a":"Şamandıralı (float switch) pompa, su belirli bir seviyeye yükseldiğinde <strong>otomatik devreye girip</strong> su azaldığında durur; kullanıcının sürekli kontrol etmesi gerekmez. Bodrum, sığınak veya gözetimsiz bölgeler için şamandıralı model şiddetle önerilir. Manuel modeller daha ucuzdur ama gözetimsiz bırakıldığında kuru çalışma riski taşır."},
{"q":"Drenaj pompasının ömrünü uzatmak için ne yapmalıyım?",
 "a":"Drenaj pompası bakımı: <strong>kullanım sonrası mutlaka temiz suyla durulayın</strong> (kum ve tortu impelleri aşındırır), uzun süre kullanılmayacaksa kuru yerde muhafaza edin, her sezonda şamandıra float'ının serbestçe hareket ettiğini kontrol edin, kablo ve fişi nem ve ezilmeye karşı koruyun. Pompanın depodan çektiği suyun içine iri taş veya plastik atık girmemesi için ızgara kullanın."}
],
"Drenaj Dalgıç Pompa Fiyatları | Koşar Ticaret",
"Bodrum, şantiye ve tarım drenajı için dalgıç drenaj pompaları. Float şamandıralı, 0,25-7,5 kW "
"model seçenekleri. Pedrollo ve Sumak garantisi, uygun fiyat, hızlı teslimat."),

# ── 142 Foseptik Dalgic Pompa ─────────────────────────────────────────────────
(142,
"""<h2>Foseptik Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Foseptik dalgıç pompası</strong> (pissu pompası / sewage pump), tuvalet atığı, kanalizasyon suyu ve büyük katı parçacıklar içeren pis suların fosseptik çukurdan veya toplayıcı depodan tahliye edilmesi için tasarlanmış özel yapılı daldırmalı elektrik pompasıdır. Standart drenaj pompalarından farklı olarak <strong>30-80 mm büyüklüğündeki katı parçacıkları</strong> çözündürmeden geçirebilir.</p>
<h3>İmpeller Tipleri</h3>
<ul>
  <li><strong>Vortex (girdap) impeller</strong> — Sıvı ile temas minimumdur; tıkanma riski çok düşük.</li>
  <li><strong>Kanal impeller</strong> — Daha yüksek verim, orta düzey katı madde geçirgenliği.</li>
  <li><strong>Bıçaklı (macerator) impeller</strong> — Katı maddeleri keserek küçültür; <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompa</a> sayfasına bakınız.</li>
</ul>
<p><a href="/kategoriler/su-pompalari/dalgic-pompalar">Tüm dalgıç pompa çeşitleri</a> | <a href="/marka/pedrollo">Pedrollo foseptik pompalar</a> | <a href="/marka/sumak">Sumak pissu pompaları</a></p>""",
[
{"q":"Foseptik pompası ne sıklıkla çalışmalı?",
 "a":"Foseptik pompası, depodaki sıvı belirli bir dolum seviyesine (genellikle %80-90 kapasitede) ulaştığında devreye girmeli ve gerektiğinde depoyu boşaltmalıdır. Bir ailevi kullanımda bu genellikle <strong>haftada 1-4 kez</strong> gerçekleşir; daha sık çalışma depo kapasitesinin yetersiz olduğuna veya pompa debisinin düşük kaldığına işaret edebilir. Şamandıralı (float) otomatik başlatma tercih edilmelidir."},
{"q":"Foseptik pompası tıkandığında ne yapmalıyım?",
 "a":"Pompanın tıkandığı anlaşılıyorsa (motor çalışıyor ama su akmıyor veya akış azalmış): <strong>önce elektriği kesin</strong>, pompayı kuyudan çıkarın, impeller bölgesini temizleyin. Vortex impellerli pompalar bez, mendil ve lifli maddelerden tıkanabilir; bu maddelerin sisteme girmemesi için fosseptik girişine ızgara konulması önerilir. Şebeke/sondaj suyu olmayan sistemlerde yağmur suyu girişi de tıkanmaya neden olabilir."},
{"q":"Ev tipi fosseptik için kaç kW pompa gerekir?",
 "a":"4-6 kişilik konut fosseptik sistemi için genellikle <strong>0,75-1,1 kW</strong> güçlü bir foseptik pompası yeterlidir. Boyutlamada; günlük atık su miktarı (kişi başı ~150-200 lt/gün), boşaltma borusunun uzunluğu ve yüksekliği belirleyicidir. Uzun veya dik boşaltma hattı için daha güçlü pompa (1,5-2,2 kW) seçilmesi gerekebilir. Teknik ekibimiz proje bazlı hesaplama yapar."},
{"q":"Foseptik pompasının double (çift) mekanik contası neden önemli?",
 "a":"Foseptik suyunda zararlı bakteriler ve aşındırıcı kimyasallar bulunduğundan motor contası en kritik bileşendir. <strong>Çift mekanik conta</strong> (double seal) tasarımında iç conta arızalansa bile dış conta motor sargılarını korur; bu ara bölge yağ veya su doldurularak izlenir. Tek contalı pompalar zamanla conta aşınmasıyla motora sızan kirli su nedeniyle arıza yapar. Özellikle ağır kullanım için çift contalı model tercih edilmelidir."},
{"q":"Fosseptik sistemi yerine ne kullanılabilir?",
 "a":"Kanalizasyona bağlanamayan alanlarda fosseptik alternatifi olarak <strong>paket biyolojik arıtma sistemi, sızdırma kuyusu veya aerobik arıtma ünitesi</strong> kullanılabilir. Bunların hepsi belirli kapasitede bir toplama pompasına ihtiyaç duyar. Foseptik yerine biyolojik arıtma tercih edilirse işlenen su bahçe sulamasında kullanılabilir. Uygun sistem seçimi yerel belediye ve çevre mevzuatına bağlıdır."}
],
"Foseptik Dalgıç Pompa Fiyatları | Koşar Ticaret",
"Fosseptik ve pissu tahliyesi için vortex ve kanal impellerli dalgıç pompalar. 0,75-7,5 kW "
"seçenekler, çift mekanik conta. Pedrollo ve Sumak garantisi, uygun fiyat."),

# ── 122 Jet Pompalar (Derinden Emisli) ───────────────────────────────────────
(122,
"""<h2>Jet Pompa (Derinden Emişli) Modelleri ve Fiyatları</h2>
<p><strong>Jet pompa</strong>, santrifüj pompa ile birlikte çalışan ejektör mekanizmasını kullanarak normal yüzey pompalarının erişemeyeceği derinliklerdeki sudan emiş yapan özeldirdir. Türkiye'de <strong>8-35 metre derinliğindeki artezyen veya kazıma kuyularda</strong> dalgıç pompa kullanamayan durumlarda yaygın tercih edilir.</p>
<h3>Teknik Çalışma Prensibi</h3>
<p>Ejektör, ana pompadan bir kısım suyu hızla daraltılmış bir nozuldan geçirerek düşük basınç bölgesi (venturi etkisi) yaratır. Bu düşük basınç, kuyudaki suyu yukarı çeker. Tek ejektörlü (shallow well) modeller <strong>max 8-9 m</strong>, çift ejektörlü (deep well) modeller ise <strong>max 25-35 m</strong> derinlikten emiş yapabilir.</p>
<h3>Dezavantajlar ve Alternatifler</h3>
<p>Jet pompalar dalgıç pompalara göre %20-30 daha az verimlidir çünkü üretilen basıncın bir kısmı ejektör devresine harcanır. 35 metrenin üzerindeki kuyular için <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompası</a> verimlilik açısından çok daha doğru tercihtir. <a href="/kategoriler/su-pompalari/santrifuj-pompalar">Santrifüj pompalar</a> ile karşılaştırma için ilgili sayfamıza bakabilirsiniz.</p>""",
[
{"q":"Jet pompa ile dalgıç pompa arasında hangisini seçmeliyim?",
 "a":"Kuyunuz 8-25 m arasındaysa ve dalgıç pompa kurulumunun (kablo, çelik boru) maliyetini karşılamak istemiyorsanız jet pompa makul bir alternatiftir. Ancak <strong>25 m'den derin, yüksek verim gerektiren veya 7/24 çalışacak</strong> sistemlerde dalgıç pompa her zaman daha verimli, daha az arızalı ve daha uzun ömürlüdür. Jet pompanın tek avantajı zemin üstünde bulunduğundan bakım kolaylığıdır."},
{"q":"Jet pompam neden su çekmez veya priming (doldurma) yapamıyor?",
 "a":"Jet pompanın su çekememesinin başlıca nedenleri: <strong>emme hattında hava kaçağı</strong> (conta, boru bağlantıları), ejektör nozulunun kireç veya tortu tıkaması, geri valf (clapet) arızası veya kuyu su seviyesinin teorik emme sınırını aşması. İlk yapılacak iş emme hattını ve geri valfi kontrol etmektir; ardından ejektörü söküp temizlemek gerekebilir. Uzun süreli bekleme sonrası pompayı su ile doldurmadan (priming) çalıştırmak emme sorununa yol açar."},
{"q":"Jet pompa basınç tankı olmadan çalışır mı?",
 "a":"Teknik olarak çalışır; ancak basınç tankı olmayan bir jet pompa sistemi <strong>her su talebiyle birlikte start-stop</strong> yapar. Bu da motoru hızla yıpratır, elektrik tüketimini artırır ve kısa devre (short cycling) nedeniyle motor ömrünü yarıya indirir. Jet pompalar hidrofor tankı ile birlikte kullanılmalıdır; en az <strong>24 litre basınç tankı</strong> önerilir."},
{"q":"Jet pompanın bakımı nasıl yapılır?",
 "a":"Yılda en az bir kez: ejektör nozulunu söküp kireç ve tortu temizliği yapın, emme hattı contalarını kontrol edin, geri valfi ve basınç tankı ön basıncını (1,5-2 bar) kontrol edin. Kışın uzun süre kullanılmayacaksa sistemi tamamen boşaltın; donmadan kaynaklanan boru çatlakları jet pompa arızalarının en yaygın nedenidir. Her mevsim açılışında su doldurarak (priming) çalıştırın."},
{"q":"Jet pompa ile bahçe sulaması yapabilir miyim?",
 "a":"Evet. Bahçe sulaması için jet pompa makul bir tercihtir; özellikle yüzeysel kuyu veya göletten su çekiliyorsa kullanışlıdır. Dikkat edilmesi gereken; sulama süresinin uzun olduğu durumlarda motorun aşırı ısınmaması için <strong>termal koruma şalterli veya aralıklı çalışmaya uygun</strong> model seçilmesidir. Sulama debisi ve basıncı gereksinimlerinize göre uygun modeli belirlemek için teknik ekibimizle iletişime geçebilirsiniz."}
],
"Jet Pompa (Derinden Emişli) Fiyatları | Koşar Ticaret",
"8-35 metre derinlikte kuyu suyu için jet pompalar. Tek ve çift ejektörlü modeller, "
"ev ve tarım kullanımı. Koşar Ticaret'ten garantili, uygun fiyatlı seçenekler."),

# ── 121 Preferikal Pompalar (Surtme Fanli) ────────────────────────────────────
(121,
"""<h2>Preferikal Pompa (Sürtme Fanlı) Modelleri ve Fiyatları</h2>
<p><strong>Preferikal pompa</strong> (periferik pompa / türbin pompa), santrifüj pompalardan farklı olarak çevresel kanallı çok kanatlı impeller tasarımıyla <strong>yüksek basınç / düşük debi</strong> kombinasyonu sağlayan yüzey tipi su pompasıdır. Debi 1-5 m³/s gibi düşük kalırken basma yüksekliği 40-120 m'ye ulaşabilir; bu özelliği onu bahçe sulaması, konut su tesisatı ve küçük kapasiteli hidrofor sistemleri için ideal kılar.</p>
<h3>Avantajları</h3>
<ul>
  <li>Küçük ve kompakt yapı, düşük ağırlık</li>
  <li>Kendi kendine dolabilir (self-priming özellikli modeller)</li>
  <li>Düşük debide santrifüj pompadan çok daha yüksek basınç</li>
  <li>Ekonomik fiyat: ev ve küçük tarım kullanımı için uygun maliyet</li>
</ul>
<p>Yüksek debi gerektiren uygulamalar için <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a>, yüksek basınç + yüksek debi için <a href="/kategoriler/su-pompalari/kademeli-pompalar">kademeli pompa</a> sayfalarımızı inceleyiniz. <a href="/marka/pedrollo">Pedrollo PKm serisini</a> stokta bulunduruyoruz.</p>""",
[
{"q":"Preferikal pompa ne anlama gelir?",
 "a":"'Preferikal' kelimesi İngilizce 'peripheral' (çevresel) sözcüğünün Türkçe transkripsiyonudur. Bu pompa tipinde su, dönen impeller etrafındaki dairesel kanalda tekrar tekrar hızlandırılarak basınç kazanır — standart santrifüj impellerinde sadece tek geçiş olan enerjileme yerine çok geçişli enerji aktarımı yapılır. Bu nedenle küçük boyutuna karşın yüksek basınç üretebilir."},
{"q":"Preferikal pompa ile santrifüj pompa arasındaki fark nedir?",
 "a":"<strong>Preferikal pompa</strong> düşük debi (1-5 m³/s), yüksek basınç (40-120 m) üretir; aynı güç için santrifüjden çok daha az su geçirir ama çok daha yüksek basınç sağlar. <strong>Santrifüj pompa</strong> ise yüksek debi (5-500 m³/s), orta basınç için idealdir. Bahçede küçük damlama sisteminiz veya evinizde düşük debili ama yüksek basınç gereken su tesisatı varsa preferikal doğru seçimdir."},
{"q":"Preferikal pompa hangi derinlikteki kuyudan su çeker?",
 "a":"Self-priming özellikli preferikal pompalar teorik olarak max <strong>8-9 m</strong> derinliğe kadar emiş yapabilir (standart atmosfer koşullarında). Pratikte boru kayıpları nedeniyle 6-7 m etkili emme derinliği kabul edilebilir. Bu sınırı aşan kuyular için derin kuyu dalgıç pompası veya ejektörlü jet pompa tercih edilmelidir."},
{"q":"Preferikal pompa neden vibrasyon yapıyor?",
 "a":"Preferikal pompada titreşim genellikle <strong>hava kilidi (air lock), kavitasyon veya dengesiz impeller</strong> nedeniyle oluşur. Emme hattında hava olması kavitasyona neden olur; pompayı durdurarak emme borusunu suyla doldurun ve yeniden çalıştırın. Titreşim devam ediyorsa impeller aşınmış veya kısmen tıkalı olabilir; sökülüp temizlenmesi gerekir."},
{"q":"Preferikal pompa ile hidrofor sistemi kurabilir miyim?",
 "a":"Evet, preferikal pompalar ev ve küçük konut hidrofor sistemleriyle mükemmel uyum sağlar. Basınç tankı (genellikle 24 lt) ve otomatik presostat ile birlikte kullanıldığında pompa sadece basınç düştüğünde devreye girer; bu da motor ömrünü uzatır ve enerji tasarrufu sağlar. <a href='/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar'>Ev tipi hidrofor sistemleri</a> sayfamızda hazır paket çözümler mevcuttur."}
],
"Preferikal Pompa (Sürtme Fanlı) Fiyatları | Koşar Ticaret",
"Bahçe sulaması ve konut su tesisatı için preferikal pompalar. Yüksek basınç, düşük debi, "
"kompakt yapı. Pedrollo PKm serisi ve diğer markalar Koşar Ticaret'te."),

# ── 133 Tek Fanli Santrifuj Pompa ─────────────────────────────────────────────
(133,
"""<h2>Tek Fanlı Santrifüj Pompa Modelleri ve Fiyatları</h2>
<p><strong>Tek fanlı (tek kademeli) santrifüj pompa</strong>, tek bir dönen çarkın (impeller) sıvıya kinetik enerji kazandırıp bunu basınca dönüştürdüğü en yaygın pompa tipidir. Tüm santrifüj pompaların yaklaşık %70'ini oluşturan bu tip; tarımsal sulama, bina su tesisatı, sanayi soğutma ve yangın sistemlerinde standart çözüm olarak kullanılır.</p>
<h3>Teknik Özellikler</h3>
<ul>
  <li>Debi: 1 – 500 m³/s</li>
  <li>Basma yüksekliği: 5 – 50 m (tek kademede)</li>
  <li>Motor gücü: 0,25 – 30 kW</li>
  <li>Çalışma sıcaklığı: 0 – 90°C (malzemeye bağlı)</li>
</ul>
<p>Daha yüksek basınç için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/cift-fanli-santrifuj-pompa">çift fanlı santrifüj pompalar</a> veya <a href="/kategoriler/su-pompalari/kademeli-pompalar">kademeli pompalar</a>, büyük debiler için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/salyangoz-pompalar-bol-su-veren">salyangoz pompalar</a> sayfamıza bakınız. <a href="/marka/pedrollo">Pedrollo</a> ve <a href="/marka/sumak">Sumak</a> markalarında geniş model yelpazesi mevcuttur.</p>""",
[
{"q":"Tek fanlı santrifüj pompa ne kadar yüksekliğe su basabilir?",
 "a":"Tek kademeli bir santrifüj pompanın pratik basma yüksekliği genellikle <strong>5-50 m</strong> arasındadır. Bu değer impeller çapı ve motor hızına (RPM) bağlıdır. 50 m üzerinde basma yüksekliği gerekiyorsa çok kademeli (kademeli) pompalar veya çift fanlı modeller tercih edilmelidir. Tek kademeli pompalarda verimi en yüksek noktada çalışmak için pompa karakteristik eğrisinin (Q-H eğrisi) sistemin çalışma noktasına uygun olması kritiktir."},
{"q":"Santrifüj pompa neden priming (ön dolum) istiyor?",
 "a":"Standart santrifüj pompalar kuru (havalı) olduklarında emme yapamazlar; pompa kasasının su ile dolu olması gerekir. Bu 'priming' işlemi; pompa kasasına su doldurarak veya vakum uygulanarak yapılır. <strong>Self-priming (kendi kendine dolum)</strong> özellikli modeller bu adımı otomatik gerçekleştirir. Sürtme fanlı (preferikal) pompalar genellikle self-priming'dir; standart santrifüj pompalar değildir."},
{"q":"Santrifüj pompa devir sayısı (RPM) ne anlama gelir?",
 "a":"Pompa devir sayısı motor ve pompa milinin dakikadaki dönüş sayısıdır. Türkiye'de 50 Hz şebekeyle çalışan asenkron motorlar <strong>2-kutuplu: ~2900 RPM, 4-kutuplu: ~1450 RPM</strong> çalışır. Yüksek devir daha yüksek basınç ve debi ancak daha fazla aşınma anlamına gelir. Sanayi tesislerinde inverter (VFD) ile devir ayarlanarak sistem ihtiyacına göre enerji optimizasyonu yapılır."},
{"q":"Tek fanlı santrifüj pompa ne sıklıkla bakım gerektirir?",
 "a":"Bakım ihtiyacı kullanım yoğunluğuna göre değişir. Sürekli çalışan (7/24) sanayi pompaları için <strong>6 ayda bir</strong> mekanik conta, rulman ve balans kontrolü önerilir. Aralıklı çalışan sulama veya konut pompaları için <strong>yılda bir</strong> kontrol yeterlidir. Sert veya kumlu suda çalışan pompalar daha sık conta değişimi gerektirir."},
{"q":"Paslanmaz çelik mi yoksa döküm demir santrifüj pompa mı tercih etmeliyim?",
 "a":"<strong>Döküm demir</strong> gövdeli pompalar daha dayanıklı ve ucuzdur; temiz ve hafif kirli sularda uzun ömürlüdür. <strong>Paslanmaz çelik</strong> gövdeli pompalar korozif (tuzlu, asidik, klorlu) sular için gereklidir; fiyatı daha yüksektir ama havuz ve deniz suyu uygulamalarında zorunludur. Impeller malzemesi de önemlidir: bronz impeller genel kullanım için, paslanmaz çelik impeller kimyasal uygulamalar için tercih edilir."}
],
"Tek Fanlı Santrifüj Pompa Fiyatları | Koşar Ticaret",
"Sulama, bina tesisatı ve sanayi için tek kademeli santrifüj pompalar. Pedrollo ve Sumak "
"markalı, geniş debi-basınç aralığı. Koşar Ticaret yetkili satıcısından uygun fiyat."),

# ── 144 Santrifuj Pompalar (Sulama) ──────────────────────────────────────────
(144,
"""<h2>Sulama İçin Santrifüj Pompa Modelleri ve Fiyatları</h2>
<p>Tarımsal sulama uygulamaları, <strong>yüksek debi, orta basınç ve dayanıklı yapı</strong> gerektiren özel pompa ihtiyacı doğurur. Santrifüj sulama pompaları; gölet, kanal, rezervuar veya kuyudan büyük tarım arazilerini, meyve bahçelerini ve sera işletmelerini beslemek için tasarlanmış yatay veya dikey eksenli pompasıdır. Türkiye'nin yoğun tarımsal sulama sezonunda sahada kesintisiz çalışabilmesi için <strong>güçlü motor ve aşınmaya dayanıklı impeller</strong> öncelikli seçim kriterleridir.</p>
<h3>Sulama Pompası Türleri</h3>
<ul>
  <li><strong>Yatay eksenli santrifüj pompa</strong> — Büyük debili kanal ve rezervuar sulaması</li>
  <li><strong>Salyangoz pompa</strong> — Sulama kanalları için çok yüksek debi, düşük basınç (<a href="/kategoriler/su-pompalari/santrifuj-pompalar/salyangoz-pompalar-bol-su-veren">salyangoz pompalar</a>)</li>
  <li><strong>Dikey dalgıç pompa</strong> — Kuyudan yüksek debili tarımsal çekim</li>
  <li><strong>Dizel motorlu pompa</strong> — Elektriksiz tarla ve uzak arazi sulaması (<a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/dizel-su-motorlari">dizel pompalar</a>)</li>
</ul>""",
[
{"q":"Tarım sulaması için hangi tip pompa daha uygundur?",
 "a":"Sulama kaynağına göre seçim yapılır. <strong>Gölet/kanal sulamasında</strong> yatay santrifüj veya salyangoz pompa, <strong>sığ kuyularda</strong> preferikal veya jet pompa, <strong>derin kuyularda</strong> 4\"/6\" dalgıç pompa tercih edilir. Elektriğin olmadığı uzak arazilerde dizel motorlu pompa kaçınılmazdır. Sulama sisteminin (damlama, yağmurlama, karık) gerektirdiği çalışma basıncına göre pompa boyutlandırılması gerekir."},
{"q":"1 dönümlük bahçe için kaç debi pompa yeterli?",
 "a":"Yağmurlama sulamasında 1 dönüm (1.000 m²) için yaklaşık <strong>5-15 m³/saat debi</strong> gerekirken damlama sulamasında bu değer 0,5-3 m³/saate düşer. Domates veya biber için günde 4-6 lt/bitki, meyve ağacı için 20-40 lt/gün hesabıyla toplam ihtiyaç belirlenir. Büyük çaplı sulama projelerinde sistem hesabının bir ziraat mühendisi tarafından yapılması önerilir."},
{"q":"Sulama pompası sezonluk kullanımda nasıl saklanmalı?",
 "a":"Sulama sezonu bitiminde pompayı <strong>temiz suyla yıkayın</strong>, emme ve basma ağızlarını kapatın, kasadaki tüm suyu boşaltın (donma önlemi). Motor yağlı ise yağ seviyesini kontrol edin. Uzun süreli depolama için <strong>ağız kapakları takılmış halde kuru ve korunaklı ortamda</strong> depolayın. Paslanmaz veya bronz impellerli modeller depolama sürecinde çok daha az sorun yaratır."},
{"q":"Sulama pompasını inverter (VFD) ile kullanmak avantajlı mı?",
 "a":"Evet, özellikle değişken su ihtiyacı olan büyük işletmelerde inverter kullanımı <strong>%20-40 enerji tasarrufu</strong> sağlar. Inverter, pompa hızını anlık sulama talebine göre otomatik ayarlar; sabit hızda çalışan pompaların kısma vanası kayıpları ortadan kalkar. Ayrıca yumuşak start-stop sayesinde motor ve boru hattı ömrü uzar."},
{"q":"Sulama borusu çapı ile pompa debisi arasındaki ilişki nedir?",
 "a":"Boru içindeki su hızı optimum <strong>1-2,5 m/s</strong> arasında tutulmalıdır; bu aralığın dışındaki hızlar aşırı boru kaybına (yüksek hız) veya tortu birikimine (düşük hız) neden olur. Seçilecek boru çapı formülü: Q(m³/s) = v × A; A = π × r². Örneğin 10 m³/saat debide 1,5 m/s hız için DN63 (63 mm iç çap) polietilen boru yeterlidir. Sulama projesinde boru kayıpları hesabı ihmal edilmemelidir."}
],
"Sulama İçin Santrifüj Pompa Fiyatları | Koşar Ticaret",
"Tarım ve bahçe sulaması için santrifüj pompalar. Yüksek debi, dayanıklı impeller, kanal ve "
"kuyu uygulamaları. Pedrollo, Sumak ve diğer markalar Koşar Ticaret'te."),

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
print(f"\nPart-1: {updated} kategori guncellendi.")
