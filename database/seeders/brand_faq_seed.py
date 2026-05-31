# -*- coding: utf-8 -*-
"""
Marka FAQ verileri — araştırılmış, gerçekçi sorular.
Çalıştır: python database/seeders/brand_faq_seed.py
"""
import sqlite3, json, sys, os
sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur  = conn.cursor()

# (id, faq_list)
BRANDS = [

    # ─── 19 — Pedrollo ────────────────────────────────────────────────────────
    # İtalyan marka, 1974 kuruluş, 160+ ülke ihracat, ISO 9001
    (
        19,
        [
            {
                "q": "Pedrollo pompalar Türkiye'de yetkili servisi var mı?",
                "a": "Evet. Pedrollo, Türkiye'de yetkili distribütör ve servis ağı aracılığıyla teknik destek sunar. Koşar Ticaret olarak Pedrollo'nun yetkili satıcısıyız; garanti kapsamındaki arızalarda yetkili servis yönlendirmesi yapıyoruz. Yedek parçalar (mekanik conta, impeller, motor yatağı) stokta tutulmakta olup çoğunlukla aynı gün temin edilebilmektedir."
            },
            {
                "q": "Pedrollo CP serisi ile PKm serisi pompaların farkı nedir?",
                "a": "<strong>CP serisi</strong>, bronz impellerli güçlendirilmiş plastik gövdesiyle bahçe ve hafif tarım sulaması için ekonomik bir seçenektir; 0,25–1,5 kW arasında değişen güç aralığıyla konut uygulamalarında yaygın kullanılır. <strong>PKm serisi</strong>, döküm demir gövde ve bronz impellerle daha yüksek performans ve uzun ömür sunar; küçük ticari bina ve çiftlik sulama sistemlerinde tercih edilir. İkisi de santrifüj tiptir; seçim debiye ve bütçeye göre yapılır."
            },
            {
                "q": "Pedrollo 4SR derin kuyu pompası kaç metreye kadar çalışır?",
                "a": "Pedrollo 4SR serisi derin kuyu dalgıç pompaları, <strong>model ve kademe sayısına bağlı olarak 20 metreden 300 metreye kadar</strong> basma yüksekliği sağlayabilir. Örneğin 4SR2/11 modeli en fazla ~110 m, 4SR4/29 modeli ise ~290 m basma yüksekliğine ulaşır. Debi aralığı 2 ile 30 m³/saat arasında değişir. Kuyunun derinliği, statik ve dinamik su seviyeleri ile ihtiyaç duyulan debiye göre doğru modeli seçmek kritiktir."
            },
            {
                "q": "Pedrollo Easy Tech hidrofor sistemi nedir, nasıl çalışır?",
                "a": "Pedrollo <strong>Easy Tech</strong>, entegre elektronik basınç sensörü ve inverter sürücüsüyle donatılmış akıllı bir hidrofor sistemidir. Geleneksel presostat-tanklı sistemlerden farklı olarak Easy Tech; <strong>kullanım miktarına göre pompa hızını otomatik ayarlar</strong>, sabit basınç sağlar ve pompanın sürekli start-stop yapmasını önler. Bu sayede enerji tüketimi %30-50 azalır, pompa ömrü uzar ve su darbesi (water hammer) riski ortadan kalkar. Kuru çalışma koruma sensörü de entegre edilmiştir."
            },
            {
                "q": "Pedrollo pompalar için garanti süresi ve koşulları nedir?",
                "a": "Pedrollo pompaları <strong>2 yıl üretici garantisi</strong> kapsamındadır. Garanti; üretim hatası, malzeme kusuru ve montaj hatalarını kapsar. Garanti dışı kalan durumlar: yanlış montaj, kuru çalıştırma, voltaj dalgalanması, mekanik hasar ve yetkisiz müdahale. Garanti için satın alma fişi ve yetkili satıcı belgesi (Koşar Ticaret faturası) gereklidir. Garanti sonrası orijinal yedek parça ile servis desteği sunulmaya devam eder."
            }
        ]
    ),

    # ─── 20 — Sumak ───────────────────────────────────────────────────────────
    # Türk markası, Sumak Pompa San. Tic. A.Ş., İstanbul merkezli
    (
        20,
        [
            {
                "q": "Sumak pompa Pedrollo'ya göre nasıl bir tercih?",
                "a": "<strong>Sumak</strong>, yerli üretim ve geniş servis ağıyla öne çıkarken <strong>Pedrollo</strong> İtalyan üretim kalitesi ve uzun ömrüyle tanınır. Fiyat açısından Sumak genellikle %20-35 daha ekonomiktir. Yoğun kullanım gerektirmeyen ev ve küçük tarım uygulamaları için Sumak mükemmel bir değer sunarken, 7/24 çalışacak veya yüksek hassasiyet gerektiren sanayi uygulamalarında Pedrollo tercih edilir. Her iki markada da Koşar Ticaret güvencesiyle orijinal ürün ve garanti sunuyoruz."
            },
            {
                "q": "Sumak SKS ve SKT hidrofor serileri arasındaki fark nedir?",
                "a": "<strong>SKS serisi</strong>, tek pompalı standart ev tipi hidrofordur; küçük daireler ve müstakil evler için 24-50 litre tank kapasitesiyle üretilir. <strong>SKT serisi</strong> ise çift pompalı, daha büyük tanklı ve yüksek kapasiteli hidrofor grubudur; çok katlı küçük apartmanlar ve ticari binalarda kullanılır. Her iki seride de otomatik presostat, manometre ve kuru çalışma koruması standarttır."
            },
            {
                "q": "Sumak pompa yedek parçaları kolay bulunuyor mu?",
                "a": "Evet. Sumak'ın Türkiye genelinde <strong>geniş yetkili servis ve yedek parça ağı</strong> bulunmaktadır. Mekanik contalar, impellerler, basınç tankı membranları ve motor kapakçıkları gibi standart parçalar çoğu büyük ilde stokta tutulmaktadır. Koşar Ticaret olarak Sumak yedek parçalarına da ulaşım sağlıyoruz. Bu durum, özellikle acil arıza senaryolarında tamir süresini önemli ölçüde kısaltır."
            },
            {
                "q": "Sumak santrifüj pompalar tarımsal sulama için uygun mu?",
                "a": "Evet, Sumak'ın SK ve SP serisi santrifüj pompaları tarımsal sulama için uygundur. 0,5-4 kW motor gücü aralığında üretilen bu pompalar; bahçe, sera ve küçük-orta ölçekli tarla sulamasında ekonomik bir çözüm sunar. Sulama sisteminin <strong>debi (m³/saat) ve basma yüksekliği (m)</strong> değerleri hesaplandıktan sonra doğru modeli belirlemek için teknik ekibimizden destek alabilirsiniz."
            },
            {
                "q": "Sumak pompa kaç yıl dayanır?",
                "a": "Düzenli bakım yapıldığında ve doğru kurulum koşullarında çalıştırıldığında Sumak pompaların beklenen kullanım ömrü <strong>8-12 yıldır</strong>. Pompa ömrünü kısaltan başlıca faktörler: kuru çalıştırma, voltaj dalgalanması, kirli veya kireçli su ve aşırı kullanım (sürekli maksimum kapasitede çalışma). Yılda bir yapılan mekanik conta ve impeller kontrolü bu süreyi uzatır."
            }
        ]
    ),

    # ─── 18 — Horoz Electric ──────────────────────────────────────────────────
    # Türk markası, Horoz Elektrik, elektrik malzemeleri üreticisi
    (
        18,
        [
            {
                "q": "Pompa motorunu korumak için hangi Horoz Electric ürünleri gereklidir?",
                "a": "Pompa motorunu korumak için temel üç ürün gereklidir: <strong>(1) Termik manyetik şalter (motor koruma şalteri)</strong> — aşırı akım ve kısa devreye karşı anında devre keser. <strong>(2) Motor koruma rölesi (termik röle)</strong> — faz dengesizliği ve aşırı ısınmayı algılar. <strong>(3) Faz sırası ve faz kaybı rölesi</strong> — üç fazlı pompa motorlarını eksik faz veya faz sırası hatasından korur. Doğru boyutlarda seçilen bu üçlü, pompa motorunuzun ömrünü önemli ölçüde uzatır."
            },
            {
                "q": "Pompa için hangi amper değerinde termik şalter seçilmeli?",
                "a": "Termik şalter akım ayarı, pompanın <strong>motor plakatındaki nominal akım (FLA) değerine</strong> göre yapılır. Genel kural: şalter ayar aralığı nominal akımı içermeli ve akım ayarı nominal değere eşit veya %5-10 üzerinde olmalıdır. Örneğin 2,2 kW / 380V üç fazlı bir pompa yaklaşık 5 A çekiyorsa 4-6,3 A ayar aralıklı bir termik şalter uygundur. Yıldız-üçgen başlatma durumunda şalter, motor akımının 1/√3 katına göre seçilir."
            },
            {
                "q": "Horoz Electric ürünleri CE belgeli ve orijinal mi?",
                "a": "Evet. Horoz Elektrik'in ürettiği termik şalterler, kontaktörler ve sigorta otomatları <strong>CE belgeli ve IEC/EN standartlarına uygun</strong> olarak üretilmektedir. Horoz, Türkiye merkezli bir üretici olup ürünleri kalite kontrol sertifikalarıyla birlikte piyasaya çıkar. Koşar Ticaret'ten satın alınan Horoz ürünleri faturalı ve orijinaldir; piyasada dolaşan taklit ürünlere karşı dikkatli olunmasını öneririz."
            },
            {
                "q": "Kontaktör ile termik şalter arasındaki fark nedir?",
                "a": "<strong>Kontaktör</strong>, motoru açıp kapayan güçlü bir elektromanyetik anahtardır; kumanda gerilimi uygulandığında devreyi kapatır, kesildiğinde açar. Kendi kendine koruması yoktur. <strong>Termik manyetik şalter (motor koruma şalteri)</strong> ise hem kontaktör hem de aşırı yük koruma özelliğini tek gövdede birleştirir; küçük motorlarda tek başına yeterlidir. Büyük güçlü pompa motorlarında genellikle <strong>kontaktör + termik röle + sigorta otomat</strong> kombinasyonu tercih edilir."
            },
            {
                "q": "Üç fazlı pompayı tek fazlı şebekeye bağlayabilir miyim?",
                "a": "Hayır, üç fazlı pompa motoru (380V, 3∼) tek fazlı şebekeye (220V, 1∼) doğrudan bağlanamaz. Bunun için <strong>frekans invertörü (VFD)</strong> veya kapasitör devreli özel adaptörler kullanılabilir; ancak bu yöntemler motor gücünü düşürür ve her durumda uygulanamaz. En doğru çözüm, mevcut şebeke tipine uygun (tek fazlı veya üç fazlı) pompa modeli seçmektir. Teknik ekibimiz kurulumunuza uygun pompa ve şalter kombinasyonunu belirlemenize yardımcı olur."
            }
        ]
    ),

    # ─── 16 — Kaysu Pompa ─────────────────────────────────────────────────────
    # Türk markası, konut ve küçük tarım segmenti
    (
        16,
        [
            {
                "q": "Kaysu pompa Türkiye'de mi üretiliyor?",
                "a": "Evet, Kaysu Pompa Türkiye menşeli bir markadır. Yerli üretim avantajı; yedek parça temininin kolaylığı, teknik destek erişiminin hızı ve ürün fiyatlarının ithal alternatiflerine göre daha rekabetçi olması anlamına gelir. Türkiye genelindeki servis ağı sayesinde arıza durumunda kısa sürede teknik destek alınabilmektedir."
            },
            {
                "q": "Kaysu santrifüj pompa ne kadar basınç üretir?",
                "a": "Kaysu'nun konut tipi santrifüj pompa modelleri genellikle <strong>20-45 metre basma yüksekliği ve 1,5-7 m³/saat debi</strong> aralığında çalışır. Bu değerler bahçe sulama, küçük tarla sulaması ve konut su tesisatı ihtiyaçlarına uygundur. Daha yüksek basınç veya debi gerektiren uygulamalar için Pedrollo veya Sumak kademeli pompa seçeneklerini değerlendirmenizi öneririz."
            },
            {
                "q": "Kaysu pompa yedek parçaları bulunuyor mu?",
                "a": "Kaysu pompaların standart yedek parçaları (mekanik conta, impeller, motor kapakçığı) büyükşehirlerdeki pompa bayilerinde ve teknik malzeme dükkanlarında genellikle bulunabilmektedir. Koşar Ticaret olarak Kaysu ürünlerini stokladığımız için teknik destek ve yedek parça konusunda yardımcı olabiliriz. Eski veya nadir modeller için parça bulma güçlüğü yaşanabilir; bu nedenle pompayı değiştirmeyi de bir seçenek olarak değerlendirmenizi öneririz."
            },
            {
                "q": "Kaysu hidrofor ev kullanımı için yeterli mi?",
                "a": "Evet. Kaysu'nun ev tipi hidrofor modelleri, <strong>tek daire ve müstakil konut</strong> uygulamaları için yeterlidir. 0,5-1 HP motor ve 24 litre tank kapasitesiyle günlük su kullanımında tatmin edici basınç ve konfor sağlar. Çok katlı apartman veya yoğun ticari kullanım gerektiren yerlerde daha büyük kapasiteli Sumak veya Pedrollo hidrofor grupları tercih edilmelidir."
            },
            {
                "q": "Kaysu pompa kurulumu kolay mı, kendim yapabilir miyim?",
                "a": "Kaysu'nun konut tipi santrifüj pompaları ve ev tipi hidroforları görece basit montaj yapısına sahiptir. <strong>Boru bağlantısı, elektrik bağlantısı ve presostat ayarı</strong> konusunda temel teknik bilgiye sahip biri için kurulum yapılabilir; ürünlerle birlikte Türkçe kurulum kılavuzu sunulmaktadır. Ancak elektrik bağlantısının mutlaka yetkili elektrikçi tarafından yapılmasını, garantinin korunması için yetkili servis kurulumunu öneririz."
            }
        ]
    ),

    # ─── 21 — Winpo ───────────────────────────────────────────────────────────
    # Pompa markası, geniş ürün yelpazesi
    (
        21,
        [
            {
                "q": "Winpo pompaların kalitesi ve güvenilirliği nasıl?",
                "a": "Winpo, geniş ürün yelpazesi ve rekabetçi fiyatıyla öne çıkan bir pompa markasıdır. Standart konut ve orta ölçekli ticari uygulamalar için yeterli performans sunar. Ağır sanayi veya 7/24 kesintisiz çalışma gerektiren ortamlar için daha üst segmentten Pedrollo veya Sumak tercih edilmesi önerilir. Koşar Ticaret'ten satın alınan Winpo ürünleri faturalı, garantili ve orijinaldir."
            },
            {
                "q": "Winpo dalgıç pompa kaç metre derinlikte çalışır?",
                "a": "Winpo'nun keson kuyu ve sarnıç tipi dalgıç pompaları genellikle <strong>8-25 metre</strong> kurulum derinliğinde çalışır; basma yükseklikleri modele göre değişir. Derin kuyu modelleri (4\" çap) ise 50-100 metre derinliğe kadar uygun olabilir. Kuyunuzun derinliği, statik su seviyesi ve ihtiyaç duyduğunuz debi değerlerini paylaşarak doğru modeli belirlemek için teknik ekibimizle iletişime geçebilirsiniz."
            },
            {
                "q": "Winpo pompalar için garanti süresi ne kadardır?",
                "a": "Winpo pompaları Koşar Ticaret üzerinden satın alındığında <strong>2 yıl satıcı garantisi</strong> kapsamındadır. Garanti; normal kullanım koşullarında ortaya çıkan üretim ve malzeme hatalarını kapsar. Kuru çalıştırma, yanlış voltaj, fiziksel hasar ve yetkisiz tadilat garanti dışı bırakır. Arıza durumunda fatura ile birlikte Koşar Ticaret'e başvurmanız yeterlidir."
            },
            {
                "q": "Winpo santrifüj pompa ile Sumak santrifüj pompa arasında nasıl seçim yapmalıyım?",
                "a": "Her iki marka da konut ve küçük tarım uygulamaları için uygundur. <strong>Winpo</strong> genellikle daha geniş model çeşitliliği ve uygun fiyatıyla öne çıkar. <strong>Sumak</strong> ise daha köklü yerli servis ağı ve yedek parça erişimiyle güven verir. Seçimde fiyat, yedek parça ulaşılabilirliği ve kullanım yoğunluğu belirleyicidir. Teknik ekibimiz her iki markayı da kendi envanterimizde bulundurduğundan projenize uygun olanı birlikte belirleyebiliriz."
            },
            {
                "q": "Winpo drenaj pompası bodrum katı su tahliyesi için uygun mu?",
                "a": "Evet. Winpo'nun drenaj dalgıç pompaları, bodrum katındaki yağmur suyu ve sızıntı suyunun tahliyesi için yaygın olarak kullanılmaktadır. Çalışma suyu seviyesine dikkat edin: çoğu drenaj pompası <strong>en az 10-15 mm su seviyesinde</strong> çalışabilir. Düşük su seviyeli ve ince tortu içeren sular için özel düz geçişli (flat suction) drenaj pompaları mevcuttur. Eğer tahliye edilecek su katı madde içeriyorsa foseptik (bıçaklı) pompa modeline geçilmesi önerilir."
            }
        ]
    ),

    # ─── 17 — Koşar (firma kendi markası) ─────────────────────────────────────
    (
        17,
        [
            {
                "q": "Koşar Ticaret hangi markaların yetkili satıcısı ve distribütörüdür?",
                "a": "Koşar Ticaret; <strong>Pedrollo (İtalya), Sumak, Horoz Electric, Kaysu ve Winpo</strong> başta olmak üzere önde gelen pompa ve elektrik malzemeleri markalarının yetkili satıcısı konumundadır. Yetkili satıcı sıfatıyla sunduğumuz tüm ürünler <strong>orijinal, faturalı ve resmi garanti</strong> kapsamındadır. Belirli bir markaya ait yetkililik belgesini görmek isterseniz bizimle iletişime geçebilirsiniz."
            },
            {
                "q": "Teknik danışmanlık hizmeti ücretli mi?",
                "a": "Hayır. Koşar Ticaret olarak <strong>pompa seçimi, kapasite hesaplama ve sistem önerisi konusundaki teknik danışmanlık hizmetimiz tamamen ücretsizdir.</strong> Hangi pompayı alacağınızı belirlemek için boru çapı, debi ihtiyacı, basma yüksekliği ve kullanım amacını bize iletmeniz yeterlidir. Uzman ekibimiz size en uygun ürünü ve fiyatı belirler."
            },
            {
                "q": "Sipariş verildikten kaç günde ürün teslim edilir?",
                "a": "Stokta bulunan ürünlerde siparişler <strong>aynı gün veya ertesi iş günü kargoya verilir</strong>; büyük şehirlere genellikle 1-2 iş günü içinde ulaşır. Stokta bulunmayan özel modeller veya büyük hidrofor grupları için temin süresi 3-7 iş günü olabilir. Sipariş öncesinde stok durumu ve tahmini teslimat süresi hakkında bilgi almak için bizimle iletişime geçebilirsiniz."
            },
            {
                "q": "Ürünlere montaj ve kurulum hizmeti sağlıyor musunuz?",
                "a": "Doğrudan saha kurulum hizmeti sunmamakla birlikte, <strong>İstanbul ve çevre illeri için yetkili montajcı/tesisatçı yönlendirmesi</strong> yapabiliyoruz. Büyük ölçekli hidrofor grubu kurulumlarında ise proje bazlı teknik destek organizasyonu sağlıyoruz. Ürünlerimizle birlikte verilen kurulum kılavuzu ve teknik dokümantasyon, deneyimli bir tesisatçının kolayca kurulum yapmasına imkân tanır."
            },
            {
                "q": "Ürün iade ve değişim koşulları nedir?",
                "a": "Koşar Ticaret'ten satın alınan ürünlerde <strong>teslim tarihinden itibaren 14 gün içinde</strong>, ürün kullanılmamış ve orijinal ambalajında olmak kaydıyla iade veya değişim yapılabilir. Garanti kapsamındaki arızalarda yetkili servis süreci işletilir. İade veya değişim talebi için satın alma faturanızla birlikte bizimle iletişime geçmeniz yeterlidir. Nakliye bedeli müşteriye aittir."
            }
        ]
    ),
]

updated = 0
for brand_id, faq_list in BRANDS:
    faq_json = json.dumps(faq_list, ensure_ascii=False)
    cur.execute("UPDATE brands SET faq=? WHERE id=?", (faq_json, brand_id))
    if cur.rowcount:
        updated += 1
        print(f"  ✓ id={brand_id:2d}  {len(faq_list)} soru eklendi")
    else:
        print(f"  ✗ id={brand_id} BULUNAMADI")

conn.commit()
conn.close()
print(f"\nToplam {updated} marka FAQ ile güncellendi.")
