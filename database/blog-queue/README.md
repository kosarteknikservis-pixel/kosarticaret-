# Blog kuyruğu

Yazı hazır → commit → canlıya deploy → **anında yayında**.

`publish_on` alanı artık zorunlu değil; manifest sırası liste önceliğini belirler. Deploy sırasında `blog:publish-due --force --all` tüm kuyruk dosyalarını içe aktarır ve `published_at` değerini **o an** olarak yazar (gelecek tarih yok).

## İçerik uzunluğu standardı

| Tip | Hedef |
|-----|-------|
| Cluster yazı | **600–1000 kelime** |
| Pillar | **1200–1800 kelime** |
| FAQ | 4–6 soru, cevap 2–4 cümle |

Her yazıda: giriş, 3–5 H2, en az 6 iç link ve ilgili kategori + iletişim CTA. Pompa içeriklerinde uygunsa Pompa Seçici kullanılır; fan gibi ilgisiz kümelerde zorlanmaz.

## GEO / AI citability (zorunlu)

Her yeni veya güçlendirilen yazı AI Overviews / ChatGPT / Perplexity için:

1. İlk paragrafta **doğrudan cevap** (2–3 cümle)
2. En az bir **soru H2**
3. Bir **tablo** (karşılaştırma veya checklist)
4. **FAQ 4–6**
5. `config/geo_page_blocks.php` içinde `blog.{slug}` bloğu (`short_answer` + `selection_table`)
6. Yüksek niyetli yazılarda `config/seo.php` → `llms.featured_blog_slugs` (≤25 rehber)

Detay: `GEO-PLAN.md` · Ajan checklist: haftalık brief’te “GEO: evet” satırı.

## Komutlar

```bash
# Canlı deploy ile aynı: tüm kuyruk anında yayın
php artisan blog:publish-due --force --all

# Yalnızca henüz yayında olmayan (yeni) yazılar
php artisan blog:publish-due --force

# Tek dosya test
php artisan blog:import database/blog-queue/10-dalgic-pompa-nedir.json --force --from-queue

# Önizleme
php artisan blog:publish-due --dry-run --all
```

## Yeni yazı ekleme

1. `database/blog-queue/XX-baslik.json` oluştur (`kosar-blog-export` formatı)
2. `manifest.json` içine `file` + `title` ekle (sıra önemli)
3. Mevcut yazıyı güçlendirirken: manifest satırına `"force_update": true` ekle (yoksa `publish-due` atlar)
4. Commit + push + **Canlıya gönder** (`blog_aktar=true`)

JSON içindeki `published_at` isteğe bağlıdır; kuyruktan import edilirken yok sayılır.

## Küme durumu

| Küme | Dosyalar | Durum |
|------|----------|-------|
| Hidrofor | 01–09, 29 | Yayında |
| Dalgıç pompa | 10–18 | Yayında |
| Su pompası | 19–28 | Yayında |
| Vantilatör | 30–37 | Yayında |
| Sirkülasyon | 38–46 | Deploy ile yayınlanır |
| Marka | 47–55 | Yayında |
| Yangın pompası | 56–64 | Deploy ile yayınlanır |
| Özel amaçlı pompa | 65–72 | Yayında |
| Kademeli pompa | 73–81 | Deploy ile yayınlanır |
| Santrifüj alt tip | 82–90 | Deploy ile yayınlanır |
| Jet pompa | 91–100 | Deploy ile yayınlanır |
| Hidrofor grubu | 101–110 | Deploy ile yayınlanır |
| Dalgıç pompa alt tip | 111–120 | Deploy ile yayınlanır |
| Endüstriyel fan alt tip | 121–130 | Deploy ile yayınlanır |
| Sirkülasyon sistemi tasarımı | 131–140 | Deploy ile yayınlanır |
| Yangın sistem tasarımı | 141–150 | Deploy ile yayınlanır |
| Hidrofor tank–presostat | 151–160 | Deploy ile yayınlanır |
| Foseptik tanklı işletme | 161–170 | Deploy ile yayınlanır |
| Drenaj bodrum işletme | 171–180 | Deploy ile yayınlanır |
| Derin kuyu işletme | 181–190 | Deploy ile yayınlanır |
| Havuz pompa işletme | 191–200 | Deploy ile yayınlanır |
| Santrifüj pompa işletme | 201–210 | Deploy ile yayınlanır |
| Kademeli pompa işletme | 211–220 | Deploy ile yayınlanır |
| Jet pompa işletme | 221–230 | Deploy ile yayınlanır |

## Haftalık ritim (2 yazı)

- **Salı:** GSC 28g + envanter overlap → `storage/seo-reports/weekly/YYYY-MM-DD-blog-brief.md`
- **Çarşamba / Cuma:** brief’teki 2 konu (yeni JSON veya mevcut güçlendirme) + deploy
- Aynı niyet varsa yeni slug açma; eski yazıyı güncelle
- Plan: `BLOG-30GUN-PLAN.md`

## Sıradaki küme (plan — ezbere değil)

Yeni küme ancak GSC brief boş kalırsa: hidrofor grubu işletme (231–240). Önce haftalık GSC fırsatları.

## Kapak görseli

Panelden: **960×540 px** (16:9), JPG/PNG/WebP.

## SEO başlık kuralı

Site adını yazmayın; sistem sonuna otomatik `| Koşar` ekler.

## Otomatik index bildirimi

Import sonrası **IndexNow** ile URL bildirimi yapılır.
