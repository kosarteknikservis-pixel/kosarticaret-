# Senin Aylık SEO Görevlerin — kosarticaret.com

> Cursor'un yapamadığı, sadece senin yapabileceğin işler. Ayda toplam ~3-5 saat.
> `AYLIK-SEO-PLANI.md` Bölüm B'nin genişletilmiş, adım adım hâli.
>
> **Ritim:**
> - Ayın **1-3'ü** → B1 (veri) + B2 (içerik konusu)
> - Ay **içinde** → B4 (off-site, haftalık)
> - İçerik hazır olunca → B3 (onaylar)
> - **3 ayda bir** → B5 (strateji)

---

## B1 — Veri Sağlama (ayın 1-3'ü)

**Hedef:** `storage/seo-reports/monthly/{YYYY-MM}/` klasörünü doldur, sonra Cursor sohbetine ekle.
Örn. Eylül 2026 için: `storage/seo-reports/monthly/2026-09/RAPOR.md`

> **Kısa yol (önerilen):** Bağlantı kurulduysa tek komut (~1 dk):
> ```bash
> php artisan seo:fetch-monthly-data
> ```
> Çıktı: `b1-report.json` + `RAPOR.md` + `gsc-performance-latest.json`
>
> Manuel export yalnızca API'nin veremediği maddeler için gerekir (index export, CWV ekran görüntüsü, manuel işlem kontrolü).

### B1.0 — Otomatik çekim (API)

`.env` içinde (bkz. `.env.example`):
```
GOOGLE_SEO_CREDENTIALS=C:\Users\PC\.config\kosar-seo\gsc-ga4-service-account.json
GSC_SITE_URL=https://kosarticaret.com/
GA4_PROPERTY_ID=482446325
```

Ayın 1-3'ünde:
```bash
php artisan seo:fetch-monthly-data
# veya kısa dönem: php artisan seo:fetch-monthly-data --days=28
```

**B1 otomatik kontrol listesi:**
- [x] gsc-sorgular → API (`top_queries`)
- [x] gsc-sayfalar → API (`top_pages`)
- [x] gsc-cihazlar → API (`devices`)
- [x] gsc-gunluk → API (`daily`)
- [x] ga4-trafik-edinme → API (`ga4.organic`)
- [x] ga4-acilis-sayfalari → API (`ga4.landing_pages`)
- [x] ga4-donusum → API (varsa) veya not
- [x] gsc-sitemap → API (`sitemaps`)
- [ ] gsc-indexleme.csv — **manuel** (API kapsam dışı)
- [ ] gsc-cwv.png — **manuel** ekran görüntüsü
- [ ] Manuel işlem / güvenlik — **manuel** GSC UI

---

### B1.1 — Google Search Console (manuel yedek)

`search.google.com/search-console` → kosarticaret.com property.

**a) Performans → Arama sonuçları**
1. Sol menü **Performans** → **Arama sonuçları**.
2. Üstte tarih: **Son 3 ay** seç. Yanına **"Karşılaştır"** → **"Önceki döneme"** (veya YoY için "Geçen yıl").
3. **Sorgular** sekmesi açıkken sağ üst **⬇ Dışa Aktar** → **Excel/CSV**. Dosya adı: `gsc-sorgular.csv`
4. **Sayfalar** sekmesine geç → tekrar **Dışa Aktar** → `gsc-sayfalar.csv`
5. **Ülkeler** sekmesi → Türkiye'yi doğrula (trafik ağırlıklı TR olmalı). Export şart değil.
6. **Cihazlar** sekmesi → **Dışa Aktar** → `gsc-cihazlar.csv` (mobil/masaüstü kırılımı).
7. **Tarihler** sekmesi → **Dışa Aktar** → `gsc-gunluk.csv` (trend grafiği için).

**b) Sayfalar (indexleme raporu)**
1. Sol menü **Dizine ekleme → Sayfalar**.
2. Sağ üst **Dışa Aktar** → `gsc-indexleme.csv`
   (indexli + "Dizine eklenmedi" nedenleri: taranmadı, keşfedildi-indexlenmedi, kopya,
   yönlendirme, 404, soft 404, noindex...).
3. Grafikte ani düşüş/artış varsa ekran görüntüsü al → `gsc-indexleme-grafik.png`

**c) Site Haritaları**
1. **Dizine ekleme → Site haritaları**.
2. Tüm sitemap'ler "Başarılı" mı, "Keşfedilen URL" sayıları makul mü — ekran görüntüsü:
   `gsc-sitemap.png`

**d) Deneyim & uyarılar (hızlı kontrol)**
1. **Deneyim → Temel Web Verileri** → mobil/masaüstü "İyi/İyileştirme gerekli/Kötü" URL sayıları → `gsc-cwv.png`
2. **Güvenlik ve Manuel İşlemler → Manuel işlemler** → "Sorun algılanmadı" olmalı. Değilse hemen bana/Cursor'a haber.
3. **Güvenlik ve Manuel İşlemler → Güvenlik sorunları** → temiz mi.

**e) Bağlantılar (bilgi amaçlı, 3 ayda bir yeterli)**
1. **Bağlantılar** → **En çok bağlantı alan sayfalar** ve **En iyi bağlantı metni** → **Dışa Aktar** → `gsc-baglantilar.csv`

### B1.2 — Google Analytics 4

`analytics.google.com` → kosarticaret.com property.

**a) Organik trafik özeti**
1. **Raporlar → Edinme → Trafik edinme**.
2. Tarih: son 3 ay, "önceki dönemle karşılaştır" açık.
3. Tabloda **"Session primary channel group"** boyutu → **Organic Search** satırına bak.
4. Sağ üst **paylaş/indir** ikonu → **CSV indir** → `ga4-trafik-edinme.csv`

**b) Açılış sayfası bazında organik**
1. **Raporlar → Etkileşim → Açılış sayfası** (yoksa: Keşfet ile "Açılış sayfası" + "Oturumlar" + filtre: kanal = Organic Search).
2. Filtre ekle: **Session primary channel group = Organic Search**.
3. İndir → `ga4-acilis-sayfalari.csv`

**c) Dönüşüm / e-ticaret (kuruluysa)**
1. **Raporlar → Para kazanma → E-ticaret satın alımları** veya **Dönüşümler**.
2. Organic Search filtresi ile → `ga4-donusum.csv`
3. Kurulu değilse: bu ayki notuna "GA4 e-ticaret takibi kurulmalı" yaz (Cursor kurulumda yardımcı olur).

### B1.3 — Klasörü teslim et
- `storage/seo-reports/monthly/{YYYY-MM}/` içindeki `RAPOR.md` + `b1-report.json` hazır olmalı.
- Cursor sohbetine bu klasörü + `SENIN-AYLIK-GOREVLERIN.md` + `seo-takip/` + geçen ay raporunu ekle.
- Bölüm C komutunu yapıştır.

**B1 kontrol listesi (manuel export yedek):**
- [ ] gsc-sorgular.csv
- [ ] gsc-sayfalar.csv
- [ ] gsc-cihazlar.csv
- [ ] gsc-gunluk.csv
- [ ] gsc-indexleme.csv
- [ ] gsc-sitemap.png
- [ ] gsc-cwv.png
- [ ] Manuel işlem / güvenlik temiz mi kontrol edildi
- [ ] ga4-trafik-edinme.csv
- [ ] ga4-acilis-sayfalari.csv
- [ ] ga4-donusum.csv (veya "kurulmalı" notu)

---

## B2 — İçerik Konusu Önerme + Teknik Doğruluk

### B2.1 — Bu ayın konu başlıkları (ayın ilk haftası)
**3-5 konu öner.** Cursor kelime hacmi/rekabeti ekleyip önceliklendirecek — sen sadece
"neyi yazalım" de. En iyi kaynaklar (rakiplerde olmayan, gerçek değer katan):

- **Telefonda/WhatsApp'ta en çok sorulan sorular** — "en çok şunu soruyorlar" dediğin her şey bir içerik.
- **Bayi / usta / mühendis soruları** — teknik seçim soruları.
- **İade & şikayet sebepleri** — "yanlış model aldı" → "doğru model nasıl seçilir" içeriği.
- **Saha tecrübesi** — "şantiyede en sık şu arıza", "şu markada şu sorun oluyor", "şu uygulamada şuna dikkat".
- **Yeni gelen ürün grupları** — stoğa yeni giren seri → tanıtım + karşılaştırma içeriği.
- **Sezon** (bkz. `AYLIK-SEO-PLANI.md` Ek-3): 2 ay sonrasının kelimesine şimdi içerik.
- **Proje/ihale konuları** — yangın pompası, hidrant, bina yönetmeliği gereksinimleri.

**Nasıl ilet:** Cursor sohbetine kısa madde madde yaz. Örnek:
```
Bu ay içerik önerileri:
1. "Apartman kaç katına kadar tek hidrofor yeter?" — çok soruluyor
2. Dalgıç pompa kablo eklemesi / su geçirmez ek yapımı — usta sorusu
3. Winpo yeni WNP-X serisi geldi, Pedrollo muadiliyle karşılaştırma
4. Kış öncesi kalorifer/sirkülasyon pompası değişim rehberi
```

### B2.2 — Cursor'un önerdiği brief'leri onayla
Cursor her içerik için brief üretir (`AYLIK-SEO-PLANI.md` Ek-4). Sen bak:
- Hedef kelime doğru mu, biz gerçekten bu kelimede yarışmalı mıyız?
- Kapsam mantıklı mı, eksik/fazla başlık var mı?
- "Bu konuyu biz mi yazmalıyız yoksa rakip çok mu güçlü" → gerekirse ertele.

### B2.3 — Teknik doğruluk kontrolü (KRİTİK)
Cursor içeriği yazınca **yayından önce** teknik hataları kontrol et:
- **Sayısal değerler:** debi (m³/h, lt/dk), basma yüksekliği (mSS, bar), güç (kW / HP),
  gerilim (220V / 380V), emme derinliği, tank hacmi — hepsi gerçekçi mi?
- **Ürün/marka bilgisi:** "Pedrollo şunu yapar", "Sumak şu seride" doğru mu?
- **Fiyat aralığı iddiaları:** "X-Y TL arası" güncel mi (yoksa iddiayı kaldırt).
- **Uygulama önerileri:** "10 katlı binaya şu yeter" — mühendislik açısından savunulabilir mi?
- **Uydurma yok:** Cursor yorum sayısı, sertifika, "Türkiye'nin en büyüğü" gibi doğrulanamaz
  iddia yazmışsa sildir.

> Bu adım en önemlisi. Yanlış teknik bilgi = müşteri yanlış ürün alır + iade + güven kaybı +
> Google "güvenilmez" sinyali. 10 dakikanı ayır.

---

## B3 — Onaylar

Cursor 3 tür değişiklik için senden onay isteyecek. Her biri ayrı branch/PR:

### B3.1 — İçerik (yayın öncesi)
- B2.3 teknik kontrolü yaptın mı?
- Marka sesi uygun mu (premium/kurumsal/sade — CLAUDE.md)?
- Yazar bilgisi gerçek kişi mi (bkz. B3.4)?
- ✅ dersen Cursor yayınlar + sitemap `lastmod` günceller + IndexNow/GSC bildirir.

### B3.2 — İç linkleme PR'ı
- Anchor metinleri doğal mı, spam kokmuyor mu (aynı exact-match 20 kez değil)?
- Linkler doğru sayfaya mı gidiyor?
- Alakasız yere zorla link var mı?
- Cursor önce/sonra tablo verecek (hangi para sayfası kaç link kazandı).

### B3.3 — Redirect / template değişiklikleri
- Cursor "şu eski URL'i şuraya yönlendirdim" listesi verecek.
- Hiçbiri genel `/kategoriler` veya ana sayfaya gitmemeli (eşdeğer sayfaya gitmeli).
- Template değişikliğinde: sepet/ödeme/ürün sayfası bozulmadığını canlı test et.
- Deploy sonrası `php artisan config:cache` + `route:cache` çalıştı mı teyit et.

### B3.4 — Yazar (bir kere ayarla, sonra tekrar kullan)
Blog yazıları için gerçek bir yazar kimliği lazım (E-E-A-T):
- Kim yazıyor/onaylıyor? (sen, bir mühendis, teknik ekipten biri)
- 2-3 cümle uzmanlık notu: "X yıldır pompa/hidrofor sektöründe, şu konularda uzman."
- Varsa LinkedIn linki.
- Cursor bunu `/yazar/{slug}` sayfası + `BlogPosting` şemasına ekleyecek.

---

## B4 — Off-Site (ay içinde, haftalık ritim)

Backlink inşası ayrı (senin stratejik işin). Bunlar rutin off-site sinyaller:

### B4.1 — Google Business Profile (HAFTALIK — en yüksek öncelik)
`business.google.com`
- [ ] **Haftada 1 post** — yeni blog/rehber, öne çıkan ürün, kampanya, sezon hatırlatması.
- [ ] **Tüm yeni yorumlara yanıt** — olumluya teşekkür, olumsuza çözüm odaklı (24-48 saat içinde).
- [ ] **Ayda 3-5 yeni foto** — ürün, depo, sevkiyat, ekip, kurulum.
- [ ] **Soru-Cevap** — gelen soruları yanıtla; sık soruları kendin sor-yanıtla.
- [ ] Ürün/hizmet listesi, çalışma saatleri, kategori güncel mi (çeyreklik).

### B4.2 — Ürün Yorumu Toplama (AYLIK)
- [ ] Sipariş sonrası (teslimattan ~1 hafta) otomatik e-posta/SMS ile yorum iste.
- [ ] Hedef: ayda en az **+15-20 onaylı ürün yorumu**.
- [ ] Neden önemli: ürün şemasındaki `AggregateRating`'i besler → Google'da **yıldız** → tıklama artışı.
- [ ] Otomasyon yoksa Cursor'a "sipariş sonrası yorum e-postası kuralım" de.

### B4.3 — İtibar / Marka Bahsi (AYLIK)
- [ ] Şikayetvar, Google yorumları, Trustpilot, Ekşi vb. — yeni bahisleri kontrol et, yanıtla.
- [ ] Olumsuzları çözüme bağla (Google bunu güven sinyali sayar).

### B4.4 — Sosyal (yeni içerik çıkınca)
- [ ] Her yeni blog/rehber → LinkedIn + Instagram paylaşımı (dolaylı trafik + keşif sinyali).

### B4.5 — NAP Tutarlılığı (değişiklik olursa)
- [ ] İşletme **adı + adres + telefon** her yerde birebir aynı mı: site footer, GBP, dizinler,
      faturalar. Telefon/adres/şube değişirse → Cursor'a söyle (şema + footer + sitemap güncellenmeli).

---

## B5 — Stratejik Gözden Geçirme (3 AYDA BİR)

Çeyrek sonunda ~1 saat:

- [ ] **Yeni ürün grubu** stoğa girdi mi → yeni kategori sayfası + içerik ihtiyacı var mı? Cursor'a listele.
- [ ] **İş önceliği** değişti mi — hangi ürün grubunu büyütmek istiyorsun? → `seo-takip/kelime-listesi.csv`
      önceliklerini buna göre güncellet.
- [ ] **Rakip manzarası** — yeni güçlü rakip çıktı mı, biri düştü mü? (Cursor raporundan)
- [ ] **İçerik kapasitesi** — aylık hedefi tutturabildik mi? Dışarıdan teknik yazar/editör
      desteği gerekli mi?
- [ ] **Sonuç değerlendirmesi** — 3 aylık KPI: takip kelimelerinde ilk 10 sayısı, organik
      trafik, organik dönüşüm. Trend yukarı mı? Değilse Cursor ile kök neden analizi.
- [ ] **Teknik borç** — `ACTION-PLAN.md`'de kapatılmamış madde kaldı mı?

---

## Otomasyon (kuruldu — B1 ~1 dakika)

GSC + GA4 MCP Cursor'da aktif. Aylık veri için:

```bash
php artisan seo:fetch-monthly-data
```

Kurulum: `kosarticaret.com-audit/SETUP-BAGLANTILAR.md`

Sen sadece **manuel işlem/güvenlik**, **CWV ekran görüntüsü** ve **index export** kontrolüne odaklan; B2/B3/B4/B5'e geç.

---

## Aylık Zaman Bütçesi

| Görev | Süre | Ne zaman |
|-------|------|----------|
| B1 veri (manuel) | 45 dk (API'li: 5 dk) | Ayın 1-3'ü |
| B2 konu + teknik kontrol | 60 dk | Ayın 1. haftası + içerik geldiğinde |
| B3 onaylar | 30 dk | İçerik/PR hazır olunca |
| B4 off-site | 20 dk/hafta | Haftalık |
| B5 strateji | 60 dk | 3 ayda bir |
| **Toplam** | **~3-4 saat/ay** | |
