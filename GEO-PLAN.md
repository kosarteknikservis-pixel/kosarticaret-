# GEO (AI Arama) Çalışma Planı — KOŞAR

> Tarih: **16 Eylül 2026**  
> Kapsam: Google AI Overviews / AI Mode · ChatGPT web · Perplexity  
> Üst kural: `11-master-seo-system` + Google: **GEO = iyi SEO**; ayrı “AI hilesi” yok  
> İlgili: `BLOG-SEO-DESTEK-PLANI.md` · `BLOG-30GUN-PLAN.md` · `storage/seo-reports/BLOG-LIVE-SYNC.md`

---

## 0) İlkeler (SEO kurallarına uyum)

| Yapılır | Yapılmaz |
|---------|----------|
| Kısa, alıntılanabilir cevap (HTML’de görünür) | Gizli / CSS-gizli “AI metni” |
| Soru H2 + tablo + FAQ (gerçek içerik) | Keyword stuffing / doorway |
| Fiyat bandı = **orientasyon**; ürün kartı esas | Sahte fiyat / şişirme schema |
| SSR Blade (zaten var) | JS-only kritik içerik |
| `llms.txt` küratörlü hub listesi | Spam link çiftliği |
| Marka/kategori doğal iç link | Exact-match sitewide spam |
| AI bot crawl açık (mevcut robots) | robots ile AI’yi kesmek |

Google’ın reddettiği efsaneler (plana **dahil değil**): “llms.txt tek başına sıralama”, mention-farm, AI-rephrase spam, chunking hilesi.

**Horoz / Ardonat:** marka GEO genişlemesi **sonraya** (bilinçli).

---

## 1) Mevcut durum (ne var?)

| Parça | Durum |
|-------|--------|
| `config/geo_page_blocks.php` + `geo-block` component | Var — kısa cevap / fiyat bandı / seçim tablosu |
| Kategori GEO bloğu | hidrofor-sistemleri · dalgıç · sanayi vantilatör |
| Marka GEO bloğu | Sumak · Pedrollo *(Winpo/Kaysu/Koşar yok)* |
| Blog GEO bloğu | ~8–10 pillar yazı (fiyat, nedir, vs, marka karşılaştırma…) |
| `/llms.txt` | Canlıda var; hub + seçili rehberler |
| robots AI bot | Açık (audit) |
| GEO blog örnekleri | `kac-katli-binaya-hangi-hidrofor`, `dalgic-vs-hidrofor`, `en-iyi-dalgic-pompa-markasi` |
| Blog → kategori/marka | Kısmi (%61 ikisi birden) — `BLOG-SEO-DESTEK-PLANI` |

**Verdict:** Temel GEO altyapısı **kurulu**; kapsam **dar**. Sıradaki iş = genişletme + citability + ölçüm — yeni “sihirli sistem” değil.

---

## 2) Hedef (90 gün)

1. Ticari hub sayfalarında AI’nin alıntılayabileceği **tek paragraf cevap + tablo**  
2. Bloglarda soru niyetli yazılarda **ilk 40–60 kelimede net cevap**  
3. Marka/kategori sayfalarına blog rehberleri (ters map) — SEO planı D3 ile birlikte  
4. `llms.txt` güncel hub + en iyi 20–25 rehber (şişkin değil)  
5. Aylık: 5 sorgu için AI Overview / Perplexity **gözlem notu** (uydurma skor yok)

---

## 3) Fazlar

### Faz G0 — Bağımlılık (önce / paralel)

`BLOG-SEO-DESTEK-PLANI` **D1** (13 sıfır-link) + **D3** (ters map).  
GEO alıntısı crawlable `<a href>` ister; linksiz sayfa zayıf kalır.

### Faz G1 — GEO blok genişletme (kod/config · 1–2 gün)

`config/geo_page_blocks.php` eksikleri:

**Kategori (öncelik):**
- `hidrofor-sistemleri/hidroforlar` (+ ev-tipi, hidrofor-grubu kısa varyant veya parent’a güven)
- `su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa`
- `su-pompalari/sirkulasyon-pompalari`
- `su-pompalari/santrifuj-pompalar`
- `su-pompalari/jet-pompalar-derinden-emisli` (kanonik slug)
- foseptik / drenaj dalgıç (1’er blok)

**Marka (şimdi):**
- Winpo · Kaysu · Koşar  
*(Horoz · Ardonat sonra)*

**Blog (citability):**
- `hidrofor-kurulumu-montaj-rehberi`
- `dalgic-pompa-kablo-baglantisi-kesit-secimi`
- `kac-katli-binaya-hangi-hidrofor` (varsa güçlendir)
- `BLOG-30GUN` yeni yazıları yayınlandıkça 1 blok

Her blok: `short_answer` (1 paragraf) + isteğe bağlı `selection_table` + `price_band` (orientasyon) + `guide_cta` → ilgili kategori/marka.

Onay sonrası implement; test: `tests/Feature/GeoPageBlocksTest.php` genişlet.

### Faz G2 — Blog citability şablonu (içerik · **haftalık zorunlu**)

> **24.09.2026:** Haftalık blog rutini (`BLOG-30GUN-PLAN.md` + `database/blog-queue/README.md`) ile birleştirildi.  
> Her Çarşamba/Cuma yazısında G2 checklist + `geo_page_blocks.blog.{slug}` + gerektiğinde `llms.featured_blog_slugs`.

Yeni ve düzeltilen yazılarda zorunlu:

1. Girişte **2–3 cümle doğrudan cevap**  
2. En az bir **H2 soru** (`… nedir?`, `… nasıl seçilir?`)  
3. Bir **karşılaştırma / seçim tablosu**  
4. **FAQ 4–6** (görünür HTML; schema mevcut politikaya uyumlu)  
5. Altta: 1 kategori + 1 marka (uygunsa) crawlable link  
6. `config/geo_page_blocks.php` blog bloğu  

`BLOG-30GUN` / haftalık brief yazıları bu şablonla üretilir → GEO + klasik SEO birlikte.

### Faz G3 — `llms.txt` bakım (aylık 15 dk)

- En fazla **25 rehber** (en yüksek GSC tıklama / ticari niyet)  
- Ölü / ince URL çıkarma  
- Yeni pillar ekleme (kablo, kurulum, vs)  
- Marka listesi doğru kalır; Horoz/Ardonat satırları durabilir (sayfa var) ama blog cluster zorlanmaz

### Faz G4 — Ölçüm (ayın 1–3’ü ile birlikte)

Kanıt kaynakları (uydurma yok):

| Kaynak | Ne bakılır |
|--------|------------|
| GSC | Bilgi niyeti sorgular (nedir, nasıl, kaç kat…) |
| Manuel | TR’de 5 sorgu: AI Overview var mı, kim alıntılanıyor |
| DataForSEO SERP (opsiyonel) | `ai_overview` öğesi |

Örnek sorgu seti:
1. hidrofor nedir  
2. kaç katlı binaya hangi hidrofor  
3. dalgıç pompa mı hidrofor mu  
4. sumak mı pedrollo mu hidrofor  
5. sanayi tipi vantilatör nasıl seçilir  

Not: `storage/seo-reports/geo/YYYY-MM-gozlem.md`

### Faz G5 — Otorite (yavaş · ekstra)

GEO’da marka bahsi önemli; manipülasyon yok:

- YouTube / teknik kısa video (kurulum, seçim) — varsa  
- Tutarlı NAP + hakkımızda E-E-A-T  
- Üretici / bayi ilişkisini sayfada net ifade (zaten var)  
- Dış “mention farm” **yok**

---

## 4) Takvim önerisi (GEO + blog SEO)

| Hafta | İş |
|-------|-----|
| **Bu hafta** | G0: D1 link düzeltmeleri · GEO plan onayı |
| **W1** | G1: kategori+marka geo_page_blocks (Winpo/Kaysu/Koşar + 5 kategori) |
| **W2** | G1 blog blokları (kurulum, kablo, 30gün yeni yazılar) · G3 llms.txt refresh |
| **W3–W4** | G2: D2 marka link + 30gün citability şablonu |
| **Ay başı** | G4 gözlem notu · fiyat bandı güncelliği kontrol |
| **Sonra** | Horoz/Ardonat GEO · G5 içerik |

Salı kontrol checklist’ine ek satır: “GEO gözlem / llms güncel mi?” (ayda 1 yeter).

---

## 5) Başarı kriteri (gerçekçi)

- Hub sayfalarında GEO bloğu görünür + SSR  
- Bilgi sorgularında GSC impressiyon/CTR trendi (garanti pozisyon yok)  
- AI Overview’da alıntı = bonus; yoksa da klasik SEO değeri kalır  
- “Bütün marka aramalarında 1. sıradayız” hedefi GEO planına **yazılmaz**

---

## 6) Uygulama sırası (onay sonrası)

1. `BLOG-SEO-DESTEK-PLANI` **D1**  
2. **G1** geo_page_blocks genişlet + test + deploy  
3. **G3** llms.txt  
4. **D3** internal_links + **D2**  
5. **G2** + `BLOG-30GUN` yazıları  
6. **G4** ilk gözlem  

---

*Plan only — kod değişikliği bu mesajda yok. Onay: “G1’e geç” veya “önce D1”.*
