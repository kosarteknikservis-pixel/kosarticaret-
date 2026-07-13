# Blog kuyruğu

Yazı hazır → commit → canlıya deploy → **anında yayında**.

`publish_on` alanı artık zorunlu değil; manifest sırası liste önceliğini belirler. Deploy sırasında `blog:publish-due --force --all` tüm kuyruk dosyalarını içe aktarır ve `published_at` değerini **o an** olarak yazar (gelecek tarih yok).

## İçerik uzunluğu standardı

| Tip | Hedef |
|-----|-------|
| Cluster yazı | **600–1000 kelime** |
| Pillar | **1200–1800 kelime** |
| FAQ | 3–5 soru, cevap 2–4 cümle |

Her yazıda: giriş, 3–5 H2, en az 6 iç link, Pompa Seçici + iletişim CTA.

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
3. Commit + push + **Canlıya gönder**

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

## Sıradaki küme (plan)

Dalgıç pompa alt tip kümesi (derin kuyu, paslanmaz, drenaj alt segmentleri).

## Kapak görseli

Panelden: **960×540 px** (16:9), JPG/PNG/WebP.

## SEO başlık kuralı

Site adını yazmayın; sistem sonuna otomatik `| Koşar` ekler.

## Otomatik index bildirimi

Import sonrası **IndexNow** ile URL bildirimi yapılır.
