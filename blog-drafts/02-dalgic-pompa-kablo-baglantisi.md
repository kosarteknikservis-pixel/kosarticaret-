# Dalgıç Pompa Kablo Bağlantısı: Kesit Seçimi ve Güvenli Montaj

**Meta Title:** Dalgıç Pompa Kablo Bağlantısı: Kesit Seçimi Rehberi
**Meta Description:** Dalgıç pompa kablo kesiti nasıl seçilir? Voltaj düşümü, monofaze/trifaze bağlantı, ek yeri ve güvenlik. Doğru kablo ile pompa ömrünü koruyun.
**Slug:** dalgic-pompa-kablo-baglantisi-kesit-secimi
**Kategori:** Dalgıç pompa
**Pillar Link:** /kategoriler/su-pompalari/dalgic-pompalar

---

Dalgıç pompa seçimi doğru olsa bile **yanlış kablo kesiti veya hatalı bağlantı** motoru yakabilir, debiyi düşürebilir veya kaçak akım rölesini sürekli attırır. Bu rehberde kablo kesiti hesabının pratik mantığını, monofaze/trifaze farkını ve güvenli montaj kurallarını anlatıyoruz.

Kurulum genel: [dalgıç pompa kurulumu](/blog/dalgic-pompa-kurulum-ipuclari) · Derin kuyu kablo/hortum: [kablo ve hortum yönetimi](/blog/derin-kuyu-pompa-kablo-hortum-yonetimi) · Ürünler: [dalgıç pompalar](/kategoriler/su-pompalari/dalgic-pompalar) · [derin kuyu dalgıç](/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa)

## Neden Kablo Kesiti Bu Kadar Kritik?

Dalgıç motor suyun içinde çalışır; soğutma suya bağlıdır ama **elektrik hattı kuyu/sarnıç dışındadır**. Hat uzadıkça voltaj düşümü artar. Motor etiketindeki gerilimden belirgin düşük voltaj:

- kalkış akımını yükseltir,
- ısınmayı artırır,
- koruma rölesini gereksiz açtırır,
- uzun vadede sargı ömrünü kısaltır.

Bu yüzden “elimdeki 2,5 mm² yeter” varsayımı, özellikle 30–80 m kablo uzunluğunda risklidir.

## Kablo Seçiminde Bakılacak 4 Bilgi

1. **Motor gücü (kW / HP)** ve etiket amperi  
2. **Besleme:** 220 V monofaze mi, 380 V trifaze mi?  
3. **Kablo uzunluğu** (pano → pompa, gidiş-dönüş toplamı değil; üretici tablosu genelde tek yön uzunluk kullanır — tabloyu okuyun)  
4. **Ortam:** yağlı / kuru kablo tipi, yeraltı veya kuyu içi uygunluk

Etiket okunmuyorsa model sayfasındaki teknik tablodan veya [Pompa Seçici](/pompa-secici) ile daralttığınız motor grubundan başlayın; kesin değer için üretici kablo tablosu esas alınır.

## Pratik Kesit Yönlendirmesi (Yaklaşık)

Aşağıdaki aralıklar **yönlendirmedir**; kesin seçim için motor etiket amperi + üretici tablosu + elektrikçi onayı gerekir.

| Motor (yaklaşık) | Besleme | Kısa hat (≤20 m) | Orta hat (20–50 m) | Uzun hat (50–100 m) |
|------------------|---------|------------------|--------------------|---------------------|
| 0,37–0,75 kW | 220 V | 2,5–4 mm² | 4 mm² | 6 mm² |
| 1,1–1,5 kW | 220 V | 4 mm² | 4–6 mm² | 6–10 mm² |
| 2,2–4 kW | 380 V | 2,5–4 mm² | 4 mm² | 4–6 mm² |
| 5,5 kW+ | 380 V | 4–6 mm² | 6–10 mm² | üretici tablosu |

**Kural:** Uzun hat + monofaze kombinasyonunda kesiti cömert tutun. Trifaze aynı gücü daha düşük akımla taşır; yine de uzunluk hesabını atlamayın.

## Monofaze ve Trifaze Bağlantı Farkı

### Monofaze (220 V)
- Faz + nötr + toprak  
- Kondansatörlü motorlarda kalkış kondansatörü ve bağlantı şeması kritiktir  
- Yanlış kondansatör veya ters bağlantı motorun dönmemesine veya aşırı ısınmasına yol açar  

### Trifaze (380 V)
- Üç faz + toprak (nötr uygulamaya göre)  
- Faz sırası dönüş yönünü belirler; ters dönüş debiyi düşürür veya sıfırlar  
- İlk çalışmada kısa süreli test ile dönüş yönü doğrulanmalıdır  

Topraklama her iki sistemde de zorunludur. Su + elektrik aynı ortamda olduğu için kaçak akım koruması (uygun tip/eşik) panoda düşünülmelidir.

## Güvenli Kablo Montajı — Kontrol Listesi

1. **Güç kablosunu askı halatı yapmayın.** Ağırlığı askı donanımına verin; detay için [kablo/hortum yönetimi](/blog/derin-kuyu-pompa-kablo-hortum-yonetimi).  
2. **Ek yeri (splice)** mümkünse kuyu dışında, kuru ve erişilebilir kutuda olsun. Su altı ek zorunluysa üreticinin onayladığı ek setini kullanın.  
3. **Klips aralığı** üretici önerisine uyun; salınan kablo izolasyonu aşındırır.  
4. **Kuyu başı geçişinde** keskin köşe bırakmayın; radius / koruyucu kullanın.  
5. **Pano tarafında** terminal sıkılığı, kablo yüksüğü ve toprak sürekliliğini kontrol edin.  
6. **İlk çalıştırmada** akım pensesi ile etiket amperine yakınlık bakın; aşırı akım → kesit, voltaj veya mekanik kilitlenme şüphesi.

## Sık Yapılan Hatalar

- İnce kablo + uzun hat → düşük voltaj, yanık koku, röle atması  
- Topraksız bağlantı → kaçak riski  
- Faz sırasını kontrol etmeden sürekli çalışma (trifaze)  
- Kabloyu pompayla birlikte çekip taşıyıcı gibi kullanmak  
- Islak ek yeri → aralıklı arıza, teşhisi zor “bazen çalışıyor” senaryosu  

Arıza ayrımı için: [dalgıç pompa çalışmıyor](/blog/dalgic-pompa-calismiyor-ariza-cozum) · Debi şüphesi: [debi düşüşü teşhisi](/blog/derin-kuyu-debi-dususu-teshis)

## Ne Zaman Elektrikçi / Servis Şart?

- Trifaze pano, şalter ve röle seçimi  
- 50 m üzeri hat ve 2,2 kW üzeri motorlar  
- Mevcut tesisatta kaçak akım / toprak sorunu  
- Patlamış motor sonrası “aynı kabloyla tekrar bağlayayım” yaklaşımı  

Pompa modelini netleştirmek için [dalgıç pompalar](/kategoriler/su-pompalari/dalgic-pompalar) ve [derin kuyu dalgıç](/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa) kategorilerine bakın; saha ölçülerinizle [Pompa Seçici](/pompa-secici) veya [iletişim](/iletisim) üzerinden destek alın. Marka örnekleri: [Pedrollo](/marka/pedrollo), [Sumak](/marka/sumak), [Kaysu](/marka/kaysu).

## Sık Sorulan Sorular

### Dalgıç pompa kablo kesiti nasıl hesaplanır?
Motor etiket amperi, besleme voltajı ve kablo uzunluğu ile üretici tablosundan seçilir. Uzun hatlarda voltaj düşümünü sınırlamak için kesit büyütülür.

### 2,5 mm² kablo her dalgıç pompada yeter mi?
Hayır. Kısa hat ve küçük monofaze motorlarda yeterli olabilir; uzun hat veya yüksek güçte yetersiz kalır.

### Kabloyu pompayla birlikte asmak sorun olur mu?
Evet. Güç kablosu taşıyıcı değildir; askı halatı kullanılmalıdır.

### Ters dönüş nasıl anlaşılır?
Trifazede debi çok düşük/sıfır olabilir. Kısa testte faz sırası düzeltilir; sürekli ters çalışmada mekanik risk artar.

### Islak yerde kablo eki yapılabilir mi?
Mümkünse yapılmamalıdır. Zorunluysa yalnızca onaylı su altı ek seti ve doğru izolasyon ile, tercihen servis tarafından yapılmalıdır.
