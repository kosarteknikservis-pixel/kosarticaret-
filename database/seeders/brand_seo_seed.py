# -*- coding: utf-8 -*-
"""
Marka SEO açıklamaları + meta bilgileri günceller.
Çalıştır: python database/seeders/brand_seo_seed.py
"""
import sqlite3, sys, os
sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur  = conn.cursor()

# (id, description_html, meta_title, meta_description)
BRANDS = [

    # ─── 19 — Pedrollo ────────────────────────────────────────────────────────
    (
        19,
        """<h2>Pedrollo Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Pedrollo</strong>, 1974'te İtalya'nın Verona şehrinde kurulan ve bugün 160'tan fazla ülkeye ihracat yapan dünya liderlerinden bir su pompası üreticisidir. Yüksek verimli motorları, paslanmaz çelik impellerleri ve uzun ömürlü mekanik contaları sayesinde Pedrollo pompalar; <strong>konut, tarım, sanayi ve ticari bina</strong> uygulamalarında küresel standart haline gelmiştir.</p>

<h3>Pedrollo Pompa Kategorileri</h3>
<ul>
  <li><a href="/kategoriler/su-pompalari/santrifuj-pompalar"><strong>Santrifüj Pompalar</strong></a> — CP, PKm, NKm serileri ile düşük-orta basınçlı sulama ve bina tesisatı.</li>
  <li><a href="/kategoriler/su-pompalari/dalgic-pompalar"><strong>Dalgıç Pompalar</strong></a> — 4" ve 6" çaplı 4SR/6SR serileri ile derin kuyu ve keson kuyu uygulamaları.</li>
  <li><a href="/kategoriler/su-pompalari/kademeli-pompalar"><strong>Kademeli Pompalar</strong></a> — 3CRm/4CRm dikey serisi ile yüksek basınçlı bina ve proses hatları.</li>
  <li><a href="/kategoriler/hidrofor-sistemleri"><strong>Hidrofor Sistemleri</strong></a> — Easy/Easy Tech serisi akıllı elektronik basınç kontrolü, tek ve çok pompalı gruplar.</li>
  <li><a href="/kategoriler/su-pompalari/sirkulasyon-pompalari"><strong>Sirkülasyon Pompaları</strong></a> — PD ve TOP serileri ile kalorifer ve yerden ısıtma devreleri.</li>
</ul>

<h3>Neden Pedrollo?</h3>
<p>Pedrollo'nun <strong>ISO 9001</strong> sertifikalı üretim tesislerinde üretilen pompaları; CE belgeli motorlar, bakımsız çalışmaya uygun rulmanlar ve korozyona karşı koruyucu yüzey işlemleriyle 10-15 yıl sorunsuz çalışma ömrü vaat eder. Koşar Ticaret olarak Pedrollo'nun Türkiye yetkili distribütörü sıfatıyla <strong>orijinal ürün, resmi garanti ve yetkili servis</strong> güvencesi sunuyoruz.</p>""",
        "Pedrollo Su Pompası Fiyatları | Yetkili Satıcı Koşar Ticaret",
        "Pedrollo santrifüj, dalgıç, kademeli pompa ve hidrofor sistemleri. İtalyan kalitesi, "
        "orijinal ürün garantisi. Türkiye yetkili distribütörü Koşar Ticaret'ten uygun fiyata.",
    ),

    # ─── 20 — Sumak ───────────────────────────────────────────────────────────
    (
        20,
        """<h2>Sumak Su Pompası ve Hidrofor Modelleri</h2>
<p><strong>Sumak Pompa</strong>, Türkiye'nin önde gelen yerli pompa üreticilerinden biri olarak konut, tarım ve küçük ölçekli sanayi uygulamaları için geniş bir ürün yelpazesi sunmaktadır. Uygun fiyatı, yaygın yedek parça ağı ve Türkiye genelindeki servis desteğiyle Sumak; özellikle ev tipi hidrofor ve santrifüj pompa segmentinde en çok tercih edilen markalar arasındadır.</p>

<h3>Sumak Pompa Kategorileri</h3>
<ul>
  <li><a href="/kategoriler/hidrofor-sistemleri/hidroforlar"><strong>Hidrofor Sistemleri</strong></a> — SKS ve SKT serileri ev tipi ve çok katlı bina hidroforları; 24-100 lt tank seçenekleri.</li>
  <li><a href="/kategoriler/su-pompalari/santrifuj-pompalar"><strong>Santrifüj Pompalar</strong></a> — Tek ve çift fanli modeller; tarımsal sulama ve bina tesisatı için ekonomik çözüm.</li>
  <li><a href="/kategoriler/su-pompalari/dalgic-pompalar"><strong>Dalgıç Pompalar</strong></a> — Temiz su, drenaj ve kirli su dalgıç pompaları; keson kuyu ve sarnıç uygulamaları.</li>
  <li><a href="/kategoriler/su-pompalari/sirkulasyon-pompalari"><strong>Sirkülasyon Pompaları</strong></a> — Kalorifer ve yerden ısıtma devreleri için enerji tasarruflu modeller.</li>
</ul>

<h3>Sumak'ın Avantajları</h3>
<p>Türkiye'de üretilen Sumak pompalarda <strong>yerli yedek parça bulunabilirliği</strong> ve <strong>geniş servis ağı</strong> en büyük avantajdır. İthal markaya göre daha kısa teslimat süresi ve ekonomik fiyatıyla Sumak; bütçe odaklı projeler için güvenilir bir tercih olmaya devam etmektedir. Koşar Ticaret olarak Sumak'ın yetkili satıcısıyız; stokta hazır ürünler için aynı gün kargo desteği sunuyoruz.</p>""",
        "Sumak Su Pompası ve Hidrofor Fiyatları | Koşar Ticaret",
        "Sumak santrifüj pompa, hidrofor, dalgıç pompa ve sirkülasyon pompaları. Türkiye'nin "
        "güvenilir yerli markası, uygun fiyat ve geniş servis ağı. Koşar Ticaret yetkili satıcısı.",
    ),

    # ─── 18 — Horoz Electric ──────────────────────────────────────────────────
    (
        18,
        """<h2>Horoz Electric Ürünleri ve Fiyatları</h2>
<p><strong>Horoz Electric</strong>, Türkiye'nin köklü elektrik malzemeleri markalarından biri olarak motor koruma röleleri, sigorta otomatları, kontaktörler, klemens rayları ve kablo bağlantı elemanları başta olmak üzere geniş bir ürün portföyü sunmaktadır. Özellikle pompa motorlarının korunmasında kullanılan <strong>termik manyetik şalter ve motor koruma röleleri</strong>, pompa tesisatlarının vazgeçilmez tamamlayıcısıdır.</p>

<h3>Horoz Electric Ürün Kategorileri</h3>
<ul>
  <li><strong>Motor Koruma Röleleri</strong> — Pompayı aşırı akım, faz kaybı ve ısınmaya karşı korur.</li>
  <li><strong>Termik Manyetik Şalterler</strong> — 1-65 A arasında geniş akım aralığı, endüstri standardı.</li>
  <li><strong>Kontaktörler</strong> — Motor devreye alma, yıldız-üçgen başlatma için güvenilir çözüm.</li>
  <li><strong>Sigorta Otomatları ve Aksesuarları</strong> — Pano ve tesisat güvenliği için tam seri.</li>
</ul>

<h3>Pompa Tesisatında Elektrik Güvenliği</h3>
<p>Bir su pompası sisteminin ömrü, doğru seçilmiş elektrik koruma ekipmanlarına doğrudan bağlıdır. <a href="/kategoriler/su-pompalari"><strong>Su pompası</strong></a> satın alırken uygun akım değerinde <strong>motor koruma rölesi</strong> kullanmak; kısa devre, aşırı ısı ve faz dengesizliğinden kaynaklanacak arızaları önler ve pompa ömrünü uzatır.</p>""",
        "Horoz Electric Ürünleri ve Fiyatları | Koşar Ticaret",
        "Horoz Electric motor koruma röleleri, termik şalterler, kontaktörler ve sigorta otomatları. "
        "Pompa tesisatı için elektrik güvenlik ekipmanları. Uygun fiyat, hızlı teslimat.",
    ),

    # ─── 16 — Kaysu Pompa ─────────────────────────────────────────────────────
    (
        16,
        """<h2>Kaysu Pompa Modelleri ve Fiyatları</h2>
<p><strong>Kaysu Pompa</strong>, Türk mühendislik birikimi ve uygun maliyetli üretimiyle konut ve küçük sanayi uygulamaları için pratik su pompası çözümleri sunan yerli bir pompa markasıdır. Özellikle <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a> ve <a href="/kategoriler/hidrofor-sistemleri">ev tipi hidrofor sistemi</a> ürünleriyle bilinmektedir.</p>

<h3>Kaysu Pompa Ürün Grubu</h3>
<ul>
  <li><strong>Santrifüj Su Pompaları</strong> — Bahçe sulama, küçük tarım ve ev suyu için ekonomik ve güvenilir modeller.</li>
  <li><strong>Ev Tipi Hidrofor Sistemleri</strong> — Kompakt tasarım, otomatik basınç kontrolü, kolay montaj.</li>
  <li><strong>Dalgıç Pompalar</strong> — Keson kuyu ve sarnıç uygulamaları için uygun fiyatlı çözümler.</li>
</ul>

<p>Kaysu Pompa ürünleri, Türkiye'nin her bölgesinde kolaylıkla ulaşılabilecek yedek parça ve servis ağıyla desteklenmektedir. Bütçe odaklı projeler için güvenilir ve pratik bir tercih olan Kaysu, Koşar Ticaret güvencesiyle sunulmaktadır.</p>""",
        "Kaysu Pompa Modelleri ve Fiyatları | Koşar Ticaret",
        "Kaysu markalı santrifüj pompa, ev tipi hidrofor ve dalgıç pompa modelleri. Uygun fiyatlı, "
        "güvenilir Türk yapımı su pompaları. Koşar Ticaret'ten hızlı teslimat.",
    ),

    # ─── 21 — Winpo ───────────────────────────────────────────────────────────
    (
        21,
        """<h2>Winpo Pompa Modelleri ve Fiyatları</h2>
<p><strong>Winpo</strong>, geniş ürün yelpazesi ve uygun fiyat politikasıyla öne çıkan bir su pompası markasıdır. Özellikle <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompa</a>, <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a> ve <a href="/kategoriler/hidrofor-sistemleri">hidrofor sistemi</a> kategorilerinde geniş model çeşitliliği sunmaktadır.</p>

<h3>Winpo Ürün Grupları</h3>
<ul>
  <li><strong>Dalgıç Pompalar</strong> — Drenaj, foseptik, temiz su ve kirli su modelleri; keson ve derin kuyu uygulamaları.</li>
  <li><strong>Santrifüj Su Pompaları</strong> — Yüksek verimli tek ve çok kademeli modeller; sulama ve bina tesisatı.</li>
  <li><strong>Hidrofor Sistemleri</strong> — Otomatik basınç kontrolü; ev ve küçük iş yerleri için kompakt çözümler.</li>
  <li><strong>Kademeli Pompalar</strong> — Çok katlı binalarda yüksek basınç için enerji verimli seçenekler.</li>
</ul>

<p>Koşar Ticaret olarak Winpo ürünlerini stokta tutarak <strong>hızlı teslimat</strong> ve <strong>teknik destek</strong> hizmetiyle sunuyoruz. Fiyat ve model karşılaştırması için uzman ekibimizle iletişime geçebilirsiniz.</p>""",
        "Winpo Pompa Modelleri ve Fiyatları | Koşar Ticaret",
        "Winpo dalgıç pompa, santrifüj pompa, hidrofor sistemi ve kademeli pompa modelleri. "
        "Uygun fiyat, geniş model yelpazesi. Koşar Ticaret'ten hızlı teslimat ve teknik destek.",
    ),

    # ─── 17 — Koşar ───────────────────────────────────────────────────────────
    (
        17,
        """<h2>Koşar Ticaret — Pompa ve Endüstriyel Ekipman Uzmanı</h2>
<p><strong>Koşar Ticaret</strong>, su pompaları, vantilatörler, hidrofor sistemleri ve endüstriyel ekipman alanında <strong>yılların deneyimiyle</strong> faaliyet gösteren uzman bir teknik ticaret firmasıdır. Müşterilerimize yalnızca ürün satmakla kalmıyor; <strong>teknik danışmanlık, doğru ürün seçimi ve satış sonrası destek</strong> hizmetleriyle de yanlarında yer alıyoruz.</p>

<h3>Neden Koşar Ticaret?</h3>
<ul>
  <li><strong>Geniş Ürün Yelpazesi:</strong> <a href="/kategoriler/su-pompalari">Su pompaları</a>, <a href="/kategoriler/hidrofor-sistemleri">hidrofor sistemleri</a> ve <a href="/kategoriler/vantilatorler">vantilatörler</a> dahil 1.000'i aşkın ürün.</li>
  <li><strong>Yetkili Distribütörlük:</strong> Pedrollo, Sumak ve diğer önde gelen markaların Türkiye yetkili satıcısı.</li>
  <li><strong>Teknik Uzmanlık:</strong> Pompa seçimi, kapasite hesaplama ve sistem tasarımında ücretsiz danışmanlık.</li>
  <li><strong>Hızlı Teslimat:</strong> Stok ürünlerde aynı gün veya ertesi gün kargo imkânı.</li>
  <li><strong>Güvenilir Garanti:</strong> Tüm ürünlerde resmi üretici garantisi ve yetkili servis yönlendirmesi.</li>
</ul>

<p>Pompa seçiminde doğru kararı vermek için <a href="/kategoriler/su-pompalari">su pompası çeşitlerimizi</a> inceleyebilir ya da uzman ekibimizle doğrudan iletişime geçebilirsiniz.</p>""",
        "Koşar Ticaret | Pompa, Hidrofor ve Vantilatör Uzmanı",
        "Koşar Ticaret; su pompaları, hidrofor sistemleri ve vantilatör alanında yetkili distribütör. "
        "Pedrollo ve Sumak garantili ürünler, ücretsiz teknik danışmanlık ve hızlı teslimat.",
    ),
]

updated = 0
for brand_id, desc, meta_title, meta_desc in BRANDS:
    cur.execute(
        "UPDATE brands SET description=?, meta_title=?, meta_description=? WHERE id=?",
        (desc, meta_title, meta_desc, brand_id)
    )
    if cur.rowcount:
        updated += 1
        print(f"  ✓ id={brand_id:2d}  title[{len(meta_title):2d}]  desc[{len(meta_desc):3d}]  {meta_title}")
    else:
        print(f"  ✗ id={brand_id} BULUNAMADI")

conn.commit()
conn.close()
print(f"\nToplam {updated} marka güncellendi.")
