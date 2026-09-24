# Blog + Marka/Kategori Güçlendirme Planı

## Aktif ritim (24 Eylül 2026’dan itibaren)

> Kaynak önceliği: **GSC gerçek arama** (son 28g) · Destek: DataForSEO hacim  
> Tempo: **haftada 2 blog** — **Salı = konu seçimi**, **Çarşamba = yazı 1**, **Cuma = yazı 2 + deploy**  
> Amaç: müşteri araması → kategori + marka besleme; ezber pillar yok; **tekrar yazı yok**  
> Envanter: ~241 canlı yazı · senkron → `storage/seo-reports/BLOG-LIVE-SYNC.md`  
> Haftalık brief’ler → `storage/seo-reports/weekly/YYYY-MM-DD-blog-brief.md`

### Tekrar engeli (her yazıda)

1. Canlı `blog_posts` + `database/blog-queue/` taranır.
2. Aynı niyet / aynı birincil sorgu → **yeni yazı yok**; mevcut güçlendirilir.
3. Yeni yazı yalnız farklı niyet varsa (kurulum vs arıza, marka vs genel, ölçü senaryosu).

### Link kuralları (zorunlu)

1. 1 pillar kategori → `/kategoriler/...`
2. 1 marka (uygorsa) → `/marka/...`
3. 1–2 ilgili blog
4. Exact-match spam / keyword stuffing yok
5. Meta title ≤ ~60 · description ≤ ~155 · tek H1 · FAQ 3–5

### GEO / AI arama (zorunlu — her yazı)

Kaynak: [`GEO-PLAN.md`](GEO-PLAN.md) Faz G2. Amaç: AI Overviews / ChatGPT / Perplexity alıntısı.

1. Girişte **2–3 cümle doğrudan cevap** (ilk 40–60 kelime)
2. En az bir **H2 soru** (`… nedir?`, `… nasıl kurulur?`)
3. Bir **karşılaştırma / seçim tablosu**
4. **FAQ 4–6** (görünür HTML)
5. `config/geo_page_blocks.php` → `blog.{slug}` **short_answer + selection_table** ekle
6. Ticari niyet varsa `config/seo.php` → `llms.featured_blog_slugs` (max ~25) güncelle
7. Gizli / stuffing / sahte fiyat yok — GEO = iyi SEO

### Pillar / marka rotasyonu

| Tip | URL / hedef |
|-----|-------------|
| Hidrofor | `/kategoriler/hidrofor-sistemleri/hidroforlar` |
| Dalgıç | `/kategoriler/su-pompalari/dalgic-pompalar` |
| Derin kuyu | `/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa` |
| Santrifüj | `/kategoriler/su-pompalari/santrifuj-pompalar` |
| Jet | `/kategoriler/su-pompalari/jet-pompalar-derinden-emisli` |
| Sirkülasyon | `/kategoriler/su-pompalari/sirkulasyon-pompalari` |
| Vantilatör | `/kategoriler/vantilatorler/sanayi-tipi-vantilator` |
| Markalar | Sumak · Pedrollo · Kaysu · Winpo · Koşar · Horoz · Ardonat |

> Bir küme 3 hafta üst üste tek başına ezilmesin.

---

## Arşiv — eski 4/hafta takvim (14 Eyl–14 Eki 2026)

> **Arşiv notu:** Tempo 4/hafta idi (Pzt·Çar·Cum·Paz). 24.09.2026 itibarıyla **2/hafta** rutine geçildi. Aşağıdaki satırlar gap/referans; otomatik üretim listesi değildir.

| # | Tarih | Başlık | Durum |
|---|-------|--------|-------|
| 1 | 14.09 | Dalgıç Pompa Kablo Bağlantısı | ✅ YAYINDA |
| 2 | 17.09 | Sanayi Tipi Vantilatör Kurulum | → Haftalık brief ile ele alındı |
| 3–18 | … | Eski 4/hafta listesi | Arşiv — GSC brief öncelikli |

Detaylı eski satırlar için git history / önceki sürüm.

---

## Üretim notu (Cursor)

- Tetikleyici: “Salı konu seç” / “bu haftanın blogları”
- JSON → `database/blog-queue/` + `manifest.json` + deploy (`blog_aktar` gerektiğinde)
- Cluster: **600–1000 kelime** · Pillar nadiren 1200–1800
- Toplu `--all` kullanma (SQLite lock); hedefli import

*Güncelleme: 24.09.2026 · Dosya: `BLOG-30GUN-PLAN.md`*
