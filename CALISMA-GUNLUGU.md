# Koşar Ticaret — Çalışma Günlüğü

Hangi tarihte ne yapıldığının kaydı. En yeni tarih en üstte. Her iş canlıya `canliya-gonder.yml` ile gönderildi; commit kodları git geçmişinde aranabilir.

İlgili dosyalar: `SEO-IS-TAKVIMI.md` (ileriye dönük takvim) · `BLOG-30GUN-PLAN.md` (blog ritmi) · `storage/seo-reports/weekly/` (haftalık brief'ler) · `.cursor/rules/12-ticari-kelime-sahipligi.mdc` (kelime → sahip sayfa haritası).

---

## 30 Eylül 2026 (Çarşamba)

**Haftalık blog** (brief: `storage/seo-reports/weekly/2026-09-30-blog-brief.md`)
- "Hidrofor bağlantı şeması" yazısı güçlendirildi (`kuyu-suyu-hidrofor-baglanti-rehberi`). Kümenin aylık hacmi yaklaşık 1.100; sayfa 8. sıradaydı ve "bağlantı şeması" sorgusunda hiç tık almıyordu. Yazı 387 kelimeden 765 kelimeye çıktı; yüzey ve dalgıç şemaları, elektrik bağlantı tablosu ve 5 SSS eklendi.
- "Hidrofor basınç ayarı" yazısı yeniden odaklandı (`hidrofor-presostat-histerezis-set-noktalari`). Sorgu ayda 480 aranıyor, sayfa indexli olduğu halde 28 günde hiç gösterim almamıştı. Başlık arama diline çevrildi; vida tablosu, kat sayısına göre basınç tablosu ve ayar adımları eklendi.
- İki yazıya GEO bloğu (kısa cevap ve tablo) eklendi. Slug'lar değişmedi.

**Proje kaydı**
- Bu çalışma günlüğü (`CALISMA-GUNLUGU.md`) oluşturuldu.

**Ürün teknik tabloları** (`e023791`)
- 127 ürünün teknik özellik tablosu düzeltildi. Fiyat, stok, slug ve URL değişmedi.
- 13 üründe satırları kaymış tablo onarıldı: başlık ile değer yer değiştirmişti (SBRT 30/2, 5SD7/5SDT5/5SDT7, SMAC tankları, SMAC700 BR, STNF750G, SMKT 750/2 DJY, SSP50/12, SSP50/15, SSP65/10).
- 4 birim hatası düzeltildi: BLC 70/100M 1.1 kW, SDT 75/3 C 80 mm, SMHT 200 1.5 kW, CP 170 380 V trifaze.
- 110 üründe 141 satır netleşti. "0-5 m³/h" gibi eski filtre aralıkları yerine ürün adındaki kesin değer yazıldı; bu yalnızca değer aralığın içindeyse yapıldı.
- Migration: `2026_09_30_110000_fix_product_spec_tables.php`.

**Eski ürün adreslerinin yönlendirmeleri** (`54f8400`, `df358c5`, `e45fc11`)
- Eski WooCommerce ürün adreslerinden 420'si doğru ürüne veya kategoriye 301 ile gidiyor; hepsi canlıda kontrol edildi.
- Eşleştirici artık aktif ürünü ve config anahtarının ham halini önce arıyor. Bu, `smh`→`sm3h` ve `m³-h` normalizasyon hatalarını kapattı.
- Kategori tahmincisinde fan, jet ve dalgıç kuralları düzeltildi.

**Kategori içerikleri, dalga 9–10** (`9dbded4`, `0c5868d`)
- Foseptik Dalgıç Pompa ve Bıçaklı Dalgıç Pompa: açıklama, seçim rehberi ve SSS yeniden yazıldı.
- Hidrofor Sistemleri, Hidrofor Grubu, Pedrollo Hidrofor ve Sumak Hidrofor yeniden yazıldı. "Her kat için 1 bar" hatası düzeltildi (doğrusu kat başına yaklaşık 0,3 bar).
- Böylece dört markanın ürünü olan 48 kategoriden 46'sı tamamlandı. Kalan 2'si (Su Pompası, Dalgıç Pompa) pasif ve 301 ile üst sayfaya gidiyor.

## 29 Eylül 2026 (Salı)

**Deploy güvenliği** (`dba07ac`, `454ca37`)
- Ardonat seed komutları ve kategori/marka içeriğini ezen `--force` adımları deploy'dan çıkarıldı. Bu adımlar panelde girilen fiyat, stok ve içerikleri her deploy'da sıfırlıyordu.

**Yeni ürün** (`20af773`)
- Renato R-3001 2500W kumandalı dikey karbon ısıtıcı ve Renato markası eklendi.

**Ürün toplu düzeltmesi, "Faz 0"** (`cc54803`)
- Önceden hazırlanan 1.371 ürünlük düzeltme canlıya uygulandı: açıklama, teknik özellik, ad ve meta.
- Uygulamadan önce canlı veritabanı yedeklendi (`/home/admin/db-backups/`). Fiyat, stok, slug ve kategori değişmedi.

**Marka sayfaları, "Faz 1"** (`4e3b42e`, `eff6e2c`, `58a7b75`, `a929be4`)
- Sumak, Kaysu, Pedrollo ve Winpo marka sayfaları Search Console ve DataForSEO hacimlerine göre yeniden yazıldı. Hedef sorgular: "sumak pompa" (3.600/ay), "sumak dalgıç pompa", "sumak hidrofor", "kaysu pompa", "pedrollo pompa", "winpo pompa".
- Title, meta, metin, seri tablosu ve SSS yenilendi. Kaysu ve Winpo ürünleri doğru alt kategorilere eklendi.

**Kategori başlıkları ve breadcrumb, "Faz 2"** (`f000a0f`)
- 14 kategoride yanlış marka ve seri adları düzeltildi (Sumak SKS/SKT, Pedrollo Easy gibi katalogda olmayan seriler).
- 7 Pedrollo foseptik pompanın breadcrumb'ı "Foseptik Dalgıç Pompa" oldu.

**Kategori metinleri, "Faz 3" ve dalga 1–8** (`c872e13`, `06647ca`, `f0c5f92`, `53b17a3`, `31038f1`, `5641a07`, `66ae462`, `d04ced6`, `f124f92`, `5882c2a`, `9c84f7b`)
- Hidrofor, dalgıç, derin kuyu, drenaj, kirli su, sirkülasyon, santrifüj, kademeli, paslanmaz, özel amaçlı, preferikal, jet, temiz su, sintine ve çamur kategorileri gerçek ürün verisiyle yeniden yazıldı.
- Kanıtsız iddialar çıkarıldı: servis ağı, "NSF/WRAS belgeli", dayanaksız yüzde oranları, "UL/FM sertifikalı".

**Ürün başlıkları ve açıklamaları** (`48953d3`, `33460f2`, `c5cad54`, `d730872`, `f8f3612`, `69a3fd7`)
- Dört markanın ürün başlıkları kurallı hale getirildi. Kesik meta başlıklar düzeltildi ve "Santrafüj" gibi yazım hataları giderildi.
- Kaysu açıklamalarındaki görünen CSS, boş bölümler ve linksiz servis cümleleri temizlendi.
- "Periferik" yazımı "Preferikal" yapıldı. Servis cümleleri İstanbul servisine çevrildi.
- Sıfır tıklamalı 26 ürünün kısa açıklaması ve başlığı yenilendi.

**Teknik SEO** (`c67c488`)
- Ürün breadcrumb şemasında kategori öğesine URL verildi. Bu, Search Console'daki "İçerik haritaları" hatasını kapattı.

## 28 Eylül 2026 (Pazartesi)

- Etiket sayfaları noindex yapıldı, sayfalama self-canonical oldu. Teknik özellik eşlemesi, başlık/meta düzeltmeleri ve robots AI grubu eklendi (`f90efce`).

## 27 Eylül 2026 (Pazar)

- Ticari kelime sahipliği haritası kuruldu (`0f19f89`): her ticari kelimenin tek bir sahip sayfası var.
- `su-pompasi` ve `dalgic-pompa` kategorileri pasif oldu; 301 ile ana kategorilere gidiyor.
- Fiyat rehberi blogları "maliyetini ne belirler" yazısına dönüştü; tek katalog linki `config/blog_commercial_links.php` içinde.

## 26 Eylül 2026 (Cuma)

- Haftalık blog: "apartman hidrofor fiyatları" ve "en iyi dalgıç pompa" yazıları GEO kurallarıyla güçlendirildi (`f152d68`, `f4cb08e`).

## 24 Eylül 2026 (Çarşamba)

- Haftada 2 blog rutini başladı: Salı konu seçimi, Çarşamba ve Cuma yazı (`6ea62ed`).
- Hidromat yazısı güçlendirildi, vantilatör kurulum yazısı eklendi. Her blog yazısına GEO kapısı eklendi: kısa cevap, tablo ve SSS (`888e89b`).

## 14–17 Eylül 2026

- Ardonat ısıtıcı kategori ağacı ve üç Ardonat ürünü eklendi (14 Eylül).
- SEO iş takvimi, haftalık GSC notu, dalgıç pompa kablo yazısı ve 30 günlük blog planı oluşturuldu (14 Eylül).
- 13 blog yazısına kategori ve marka iç linki eklendi. GEO blokları kategori, marka ve bloglara genişletildi (16 Eylül).
- Eski filtre adreslerinin tarama şişmesi robots ve tek adımlı yönlendirme ile kesildi. Kalan 12 adet 404 adres 301'e bağlandı (16–17 Eylül).

## 1–8 Eylül 2026

- Panelde Google arama kelimeleri (GSC) ekranı ve otomatik veri çekimi eklendi.
- Ürün schema'sı zenginleştirildi. Yasal sayfalar dolduruldu. Google Tag Manager ve e-ticaret olayları eklendi.
- Rakip fiyat takibi (onaydan sonra uygulama) ve Google Shopping fiyat taraması eklendi.

## Ağustos 2026

- Blog kümeleri tamamlandı: jet pompa işletme (221–230). GSC verisine göre CTR meta çalışması yapıldı.
- SEO Faz 1–4 (29 Ağustos): redirect, heading, robots, kategori içerikleri, yazar sayfaları, drift kontrolü ve GSC kelime takibi.
- GEO: AI bot robots kuralları, `llms.txt` ve kısa cevap blokları eklendi. Marka sayfalarına özel H1 verildi.

## Temmuz 2026

- Blog kümeleri yayına alındı (yaklaşık 230 yazı): hidrofor, dalgıç, su pompası, vantilatör, sirkülasyon, marka, yangın, özel amaçlı, kademeli, santrifüj, jet, hidrofor grubu, tank–presostat, foseptik, drenaj, derin kuyu, havuz.
- IndexNow, image sitemap, OG meta ve blog kapakları eklendi. Form spam koruması için reCAPTCHA v2 kullanıldı.
- Panelden manuel sipariş oluşturma eklendi.

## Haziran 2026

- Site canlıya alındı (30 Mayıs–1 Haziran): vitrin ve mobil tasarım, PayTR ödeme ve taksit, kargo/KDV ayarları, Paraşüt ve SMTP.
- Google Merchant feed, WordPress'ten kalan eski adreslerin 301'leri, pompa seçici ve teklif modu eklendi.
- Pazaryeri modülü ve Trendyol pilotu, DHL eCommerce kargo, Telegram sipariş bildirimi, ödeme hatırlatma e-postası eklendi.

---

## Açık kalan işler (30 Eylül 2026 itibarıyla)

- **Teknik değer doğrulaması (B grubu):** Üretici kataloğundan doğrulanması gereken ürünler: SHT 24/4, SYMT 6-100/6, WNP1/WNP2 kat/daire, WNP-V 1100 F, WNP 750 PD, CMI 2-7T, 7-16T/9-18T, SDF 13A, SMT 10, Smj-150, CK 90-E, MCm 15/45, 4SR 6/17, JCRm 2A, SMKT hidrofor kat. Buna ürün adıyla çelişen 41 aralık satırı da dahil.
- **Kaysu değerleri (C grubu):** 1355, 1361, 1378 ve WQH1500 için doğru değerler kullanıcıdan bekleniyor.
- **Sıralama takibi:** İçerik çalışmasının etkisi birkaç haftada görülür. "sumak pompa" 27 Eylül'de ortalama 40. sıradaydı; Ekim ortasında Search Console'dan tekrar bakılmalı.
