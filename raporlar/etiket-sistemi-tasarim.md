# Ürün etiket sistemi — Aşama 1 tasarım

7 Ekim 2026. Bu dosya analiz ve tasarımdır. Kod, veritabanı yazması ve canlı yayın yoktur.

Sayıların kaynağı:

- DataForSEO, konum `Turkiye`, dil `tr`, 7 Ekim 2026. Hacim: keyword overview. SERP: live advanced, masaüstü, derinlik 10.
- Search Console, mülk `https://kosarticaret.com/`, 8 Haziran 2025 – 4 Ekim 2026, sorgu ve sorgu × sayfa.
- Kampa etiket trafiği: Labs ranked keywords, URL `meta-etiket`, aynı gün. Özet: `raporlar/rakip-kampa-2026-10.md`.
- Ürün sayıları: yerel katalog okuması, aktif ürün 1.394, özelliği olan 1.387, boş 7, birbirinden farklı özellik anahtarı 528. Canlı veritabanı yazılmadı. Canlı katalog yerelden farklıysa Aşama 2 önizlemesi bu sayıları yeniden sayar.

Bu turun DataForSEO çağrıları 3 USD tavanının altında kaldı (overview, suggestions, Kampa etiket listesi ve yaklaşık bir düzine SERP).

## Ne etiket olur

Etiket, kategoriyi kesen bir özelliktir: güç, faz, malzeme, çıkış ölçüsü, marka+tip. Kategorinin zaten hedeflediği ifade etiket olmaz.

Sahip sayfalar etiket yapılmaz: su pompaları, dalgıç pompalar ve altları, santrifüj ve altları, jet, kademeli, sirkülasyon, yangın, havuz, hidroforlar, hidrofor grubu, Sumak hidrofor, Pedrollo hidrofor, hidromat. Marka sayfaları da durur: `/marka/sumak`, `/marka/pedrollo`, `/marka/kaysu`, `/marka/winpo`.

Blog etiketi ayrı yoldadır: `/blog/etiket/{tag}`. Ürün `tags` alanı meta anahtar kelimedir. Yeni sistem bu ikisine yazmaz.

## Index şartı

Dördü birden gerekir. Biri eksikse sayfa ya hiç açılmaz ya da taslak / noindex kalır.

1. Aylık hacim en az 100, ya da Search Console’da 16 ayda en az 300 gösterim.
2. Kurala uyan en az 6 aktif ürün.
3. SERP’te liste veya kategori tipi sayfalar sıralanıyor.
4. Sitede aynı sorgunun sahibi başka sayfa yok.

HP sorgularının hiçbiri 16 ayda 300 gösterime ulaşmıyor. “1 hp hidrofor” yaklaşık 4 gösterim (bir blog yazısı 1, bir Kaysu ürün sayfası 3). Hacim eşiği DataForSEO’dan geliyor, Search Console’dan değil.

## Index adayları

Ayrıntı `raporlar/etiket-adaylari.csv` içindedir. İlk yayın dalgasına ancak şunlar girer:

| Aday | Aylık hacim | Zirve | Uygun ürün | SERP, 7 Eki 2026 |
|---|---:|---|---:|---|
| 1 HP hidrofor | 720 | Tem 2026, 1.000 | 25 | Liste |
| 2 HP dalgıç pompa | 590 | Haz 2026, 880 | 39 | Liste. Kampa, “2 hp dalgıç pompa fiyatları” etiketinde Labs sıra 4 |
| 1 HP dalgıç pompa | 320 | Tem 2026, 590 | 51 | Liste. Trendyol etiket 1. sıra |
| 3 HP hidrofor | 110 | Oca 2026, 210 | 20 | Liste. sanayiavm etiketi organik 4. sıra |
| Monofaze dalgıç pompa | 110 | Haz 2026, 170 | 119 | Liste. Blog “monofaze trifaze farkı” bilgi niyetinde kalır, bu sayfanın canonical’ı olmaz |

“1 hp hidrofor fiyatları” (480) ve “2 hp hidrofor fiyatları” (390) ayrı sayfa değildir. Aynı etiketin hedef kelimesinin fiyat biçimidir.

## Hacmi yeten, index olmayan

- 2 HP hidrofor: hacim 390, uygun ürün 9. Organik sonuç tek ürün ağırlıklı. Taslak kalır.
- 1,5 HP hidrofor: hacim 260, uygun ürün 33. Organik sonuç tek ürün. AI özeti bir etiket sayfası anıyor. Taslak kalır.
- 0,5 HP hidrofor: hacim 210, uygun ürün 9. Organik sonuç tek ürün. Taslak kalır.
- Sumak dalgıç pompa: hacim 720, marka Sumak + dalgıç ağacı 130 ürün, SERP liste. `/marka/sumak` ve kural 12 “sumak pompa”yı tutuyor. Açılmaz.
- Sessiz hidrofor: hacim 880, SERP liste. Özellikte “sessiz” alanı yok. `Ses Seviyesi` yalnız 3 üründe var ve bu etiket için kullanılamaz. Açılmaz.
- 12 volt dalgıç pompa: hacim 880, SERP liste. Voltaj alanında 12 V olan ürün 0. Adında “12 VOLT” geçen 1 ürün var (Sumak STNF750G). 6 ürün şartı yok. Açılmaz.
- 2 inç dalgıç pompa: hacim 140. İlk çıkış ölçüsü taraması 186 dedi. Serbest metin (`2"`, `2 inç`, `50 mm`) karışık olduğu için bu sayı index kararı değildir. Canlı SERP de çekilmedi. Açılmaz.
- Bahçe sulama pompası: hacim 260. SERP bahçe pompası kategorileri. Kullanım alanı özelliği yok. Santrifüj sulama kategorisiyle çakışır. Açılmaz.
- 1 HP su pompası (320) ve 2 HP su pompası (210): hidrofor ve dalgıç etiketlerini aynı anda yer. Ayrı URL yok. “Hidrofor 1 HP” (170) de 1 HP hidrofor ile aynı sayfadır.

Kampa’nın trafikli etiketlerinin çoğu bu katalogda yok (termostat, aspiratör, yağ, nipel). Yaklaşık 1.001 etiket sayfasının trafiği taşıyan kısmı küçük. O çiftlik kopyalanmaz.

## Eşleştirme

Ürün etikete yalnız yapılandırılmış alandan ve kategoriden girer.

Güç anahtarları: `Güç`, `Motor Gücü`, `Güç (HP)`, `Güç (kW)`, `HP`, `kW`. Kablo, tüketim ve seçenek anahtarları sayılmaz. Metindeki `1 HP`, `1,5 HP` okunur. Anahtar adı HP ise çıplak sayı da HP sayılır. Yalnız kW yazan değer HP sayılmaz.

HP bandı, onay ister, eşleşme sayılmaz:

| Etiket | Yalnız kW, HP yazısı yok | Örnek |
|---|---:|---|
| 1 HP hidrofor | 1 | 0,75 kW |
| 1 HP dalgıç | 1 | 0,75 kW |
| 1,5 HP hidrofor | 2 | 1,1 kW |
| 2 HP hidrofor | 8 | 1,35–1,6 kW |
| 3 HP hidrofor | 8 | 2,2 kW |

Güç alanı hiç olmayanlar: hidrofor ağacında 4 ürün, dalgıç ağacında 7 ürün. İsimde HP geçse de etikete girmezler.

2,2 HP ve 1,2 HP, 2 HP etiketine girmez. Ad taramasında “2.2 HP” içindeki 2’yi “2 HP” sanan ilk sayım kullanılmadı.

Faz: voltaj, elektrik veya faz alanında `monofaze` / `trifaze` kelimesi gerekir. Yalnız `220 V` veya `380 V` yetmez. Monofaze dalgıçta 119 ürün kelimeyle eşleşir. 21 üründe bu alan yok. 28 ürünün adında monofaze yazar, alanda yalnız 220/230 V vardır; onlar dışarıda.

Gövde: `Pompa Gövdesi` / `Gövde Malzemesi` içinde paslanmaz, AISI 304/316 veya inox. Çarkın paslanmaz olması gövdeyi paslanmaz yapmaz. Dalgıçta gövde paslanmaz 13, gövde alanı boş 268, adında paslanmaz olup gövde doğrulanamayan 67. Hacim 50 olduğu için sayfa açılmaz. Alan yine de doldurulmalı.

Marka+tip: Sumak dalgıç, marka kaydı Sumak ve dalgıç kategorisi. İsim araması değil. Sayfa yine açılmaz; gerekçe yamyamlıktır.

## 7 Ekim 2026 karar

Aşama 2 bu kararla kuruldu. Beş etiket taslak. Sitemap’te yok. Aşama 3 (index) ayrı onay.

- kW → HP yalnız standart tablo ve tek motor değeri. `3x2.2 kW` gibi çarpanlı değer tabloya girmez.
- Çekilen güç / P1 ve birimi belirsiz güç dışarıda.
- Yalnız `220 V` veya `230 V` (50 Hz sayılmaz) monofaze sayılır. `220/380`, `230/400`, `380`, `400` trifaze sayılır.
- Paslanmaz, sessiz ve 12 V açılmaz.
- Hidrofor HP etiketi tek motorlu üründür. Çift pompalı, üç pompalı, iki pompalı, 2× ve 3× girmez. Sumak B/C harfi, açıklama veya teknik tablo pompa sayısını doğrulamadan tek başına çıkarmaz.
- Dalgıç pompa etiketine yalnız motor girmez. Adında «dalgıç motoru» veya «pompa motoru» vardır. «Motorlu pompa» kalır.
- Standart HP yazısı, tablodaki kW ile çelişirse etiket dışıdır. 3 HP (3 kW) buna girer. 2,2 HP gibi tabloda karşılığı olmayan açık HP yazısı durur.

## Sizden istenecekler

1. Yalnız kW yazan değerler HP’ye çevrilsin mi? Tablo yukarıda. Onay yoksa o ürünler etiket dışında kalır.
2. Güç alanı boş 4 hidrofor ve 7 dalgıç. HP değerini yazın ya da dışarıda kalsınlar.
3. Adında monofaze olup alanda yalnız 220 V yazan 28 dalgıç. Öneri: 220 V, monofaze sayılmaz. Onaylarsanız kural böyle kalır.
4. Paslanmaz dalgıç sayfası açılmayacak. Gövde malzemesi boş olanlarda alanın doldurulması ayrı bir katalog işi.
5. Sessiz ve 12 volt için alan yok. Bu iki sayfa açılmaz. Alan eklenirse yeniden ölçülür.
6. 2 inç çıkış ölçüsü Aşama 2’de örnekle doğrulanmadan sayfa olmaz.

## Teknik tasarım

Yeni tablolar. `products.tags` ve blog etiketine dokunulmaz.

- `collections`: ad, slug, hedef kelime, durum (`draft`, `noindex`, `index`), kısa açıklama, SSS (soru + yanıt), ilgili kategori id’leri. Hacim ve SERP notu kayıt alanıdır; canlı hacim her istekte çekilmez.
- `collection_rules`: koleksiyon, kural türü (kategori ağacı, HP eşitliği, faz kelimesi, gövde kelimesi, marka). Birden fazla kural aynı koleksiyonda AND bağlanır.
- `collection_product`: ürün, koleksiyon, kaynak (`rule`, `include`, `exclude`). Exclude her zaman kazanır. Include, özelliği boş olup elle doğrulanan ürün içindir. Sessiz kW→HP dönüşümü yoktur.

Slug’da yıl yok. Örnek: `1-hp-hidrofor`, `2-hp-dalgic-pompa`, `monofaze-dalgic-pompa`.

URL: `/koleksiyon/{slug}`. `/kategoriler`, `/marka`, `/urun`, `/blog/etiket` durur. Sayfa 1 canonical kendisi. Sayfa 2 ve sonrası `?page=` ile kendi adresine canonical, `noindex, follow`. Filtre ve sıralama parametresi noindex. Bunu bugünkü `CatalogPaginationSeo` yapıyor; aynı sınıf kullanılır. Sitemap’e yalnız `index` olanların 1. sayfası girer.

Şema: `CollectionPage` + `ItemList` + `BreadcrumbList`. Görünen ürün, fiyat ve sıra ile aynı. Yorum yıldızı yok. Teklif fiyatı ürün sayfasında kalır; koleksiyon liste fiyatı uydurmaz.

Sayfa: tek H1 (etiket adı). Giriş cümlesi canlı ürünlerden kurulur: adet, en düşük ve en yüksek fiyat, güç aralığı, markalar. Sabit şablon cümle yok. Ürün listesi mevcut kartla. İlgili kategoriye gerçek `<a href>`. SSS yalnız o sorgunun PAA veya Search Console sorusundan. Örnek, 1 HP hidrofor: kaç metreden su çeker, kaç bar basar, kaç kW harcar. Fiyat sorusu SSS’ye “şu kadar TL” diye yazılmaz; fiyat listede durur. Bilgi niyetli soru varsa mevcut rehbere link verilir, etiket o rehberin yerine geçmez.

Ürün sayfası: ürünün gerçekten üye olduğu en fazla 5 koleksiyon linki. Taslak link basılmaz. Noindex koleksiyon, iç gezinme için basılabilir; index politikası sayfanın kendi robots’unda durur.

Panel: liste, düzenleme, kural, üç önizleme (kurala uyan, alanı boş, adında kelime olup kural dışı), dahil/hariç arama, durum. Index seçeneği, kurala uyan aktif ürün 6’nın altındayken kapalıdır. Ürün kaydı koleksiyon üyeliklerini yeniden hesaplar. Index bir koleksiyon 6’nın altına düşerse uyarı düşer; kendiliğinden noindex olmaz.

Aşama 2’de bütün koleksiyonlar `draft` çıkar. Google’a açık sayfa yok.

## Test

- 1 HP hidrofor yalnız HP’si 1 olan hidroforu alır. 0,75 kW ve 1,5 HP dışarıda.
- 2,2 HP, 2 HP koleksiyonuna girmez.
- Monofaze, alanda kelime yoksa 220 V ile girmez.
- Paslanmaz çark, döküm gövdeyi paslanmaz yapmaz.
- Exclude, kural eşleşmesini ezer. Include, kural dışı ürünü ekler.
- Ürün 6’nın altındayken durum index olamaz.
- Sitemap’te draft ve noindex yok.
- Sayfa 1 canonical kendisi, index. Sayfa 2 `noindex, follow`, canonical `?page=2`.
- Ürün sayfasında 5’ten fazla koleksiyon linki yok.

## İş büyüklüğü

Aşama 2, onaydan sonra, yaklaşık 5–8 iş günü. Asıl iş 528 özellik anahtarını tek okuyucuya indirmektir. Panel, vitrin, sitemap ve test bunun üstündedir. Canlıya taslak çıkar. Ayrı commit, yalnız bu işin dosyaları.

Aşama 3 ayrı onaydır. Haftada en fazla 5 index. O haftanın kategori işiyle aynı kümeye dokunan etiket o hafta açılmaz. Her etiket konu kaydı ve karar defteri ister. Ölçüm 21 gün. İlk 5’in sonucu yazılmadan sonraki 5 açılmaz.

İlk beş, şartlar bozulmazsa: 1 HP hidrofor, 2 HP dalgıç, 1 HP dalgıç, 3 HP hidrofor, monofaze dalgıç. 2 HP / 1,5 HP / 0,5 HP hidrofor bu dalgada yok; SERP tek ürün ağırlıklı.
