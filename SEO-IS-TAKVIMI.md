# KOŞAR SEO + Fiyat İş Takvimi

> Başlangıç: **14 Eylül 2026 (Pazartesi)**  
> Kaynak: canlı site `kosarticaret.com` · Panel: Google piyasa / GSC / Merchant  
> Amaç: “Bekleyelim” değil — **hangi gün ne bakılacak / ne yazılacak** net olsun.  
> İlgili: `BLOG-PLAN.md` · `kosarticaret.com-audit/SENIN-AYLIK-GOREVLERIN.md`

---

## Ritim özeti

| Tip | Ne sıklıkla | Kim |
|-----|-------------|-----|
| **Kontrol (GSC / teknik / fiyat)** | Haftada 1 sabit gün | Siz + Cursor destek |
| **İçerik (blog / kategori güçlendirme)** | Haftada 1 sabit gün | Siz yazar / Cursor taslak |
| **Aylık büyük kontrol** | Her ayın **1–3’ü** | Siz (veri) + Cursor (analiz) |
| **Çeyrek strateji** | 3 ayda bir | Siz |

**Sabit günler (bundan sonra her hafta):**
- **Kontrol günü:** her **Salı** (09:00–11:00)
- **İçerik günü:** her **Perşembe** (10:00–13:00)

---

## Her Salı — Kontrol checklist (tekrarlayan)

Süre: ~60–90 dk

1. [ ] GSC → Performans (7 gün): düşen sayfa / artan impressiyon
2. [ ] GSC → Sayfalar: yeni 404 / “taranmadı” / soft 404
3. [ ] Merchant Center: ürün reddi / fiyat uyumsuzluğu
4. [ ] Panel → **Google piyasa**: “Biz pahalıyız” + inceleme bekleyenler
5. [ ] Kritik 5 ürün fiyatı: onayla / reddet / uygula (kör toplu yok)
6. [ ] Canlı 3 URL smoke: ana sayfa, 1 kategori, 1 PDP (mobil)

---

## Her Perşembe — İçerik checklist (tekrarlayan)

Süre: ~2–3 saat

1. [ ] 1 blog **veya** 1 kategori SEO metni (haftada en az 1 yayın)
2. [ ] Hedef keyword + H1 + meta title/description
3. [ ] En az 2 internal link (kategori + ilgili ürün/marka)
4. [ ] Görsel alt metni
5. [ ] Yayından sonra GSC URLInspection (isteğe bağlı) / IndexNow varsa tetikle

---

## Her ayın 1–3’ü — Aylık büyük kontrol

Süre: ~2–3 saat (ay başı)

| Gün | İş |
|-----|-----|
| **Ayın 1’i** | `php artisan seo:fetch-monthly-data` → `storage/seo-reports/monthly/YYYY-MM/` |
| **Ayın 2’si** | GSC index export + CWV ekran (manuel) · Merchant sağlık |
| **Ayın 3’ü** | Cursor’a raporu ver → “aylık SEO özeti + 10 aksiyon” · Fiyat: pahalı SKU listesi |

Detay adımlar: `kosarticaret.com-audit/SENIN-AYLIK-GOREVLERIN.md`

---

# 2026 Q4 — Tarihli takvim

## Eylül 2026 (kalan)

### Kontrol tarihleri
| Tarih | Gün | İş |
|-------|-----|-----|
| **14.09.2026** | Pzt (erken) | ✅ Haftalık GSC peek yapıldı → `storage/seo-reports/weekly/2026-09-14-kontrol.md` |
| **16.09.2026** | Salı | Haftalık kontrol (Merchant + Google piyasa + smoke) — GSC kısmı 14’te alındı |
| **23.09.2026** | Salı | Haftalık kontrol + 404/redirect turu |
| **30.09.2026** | Salı | Haftalık kontrol + Eylül kapanış notu |

### İçerik tarihleri (yazılacak / yayınlanacak)
| Tarih | Gün | İçerik (yazılacak) | Hedef |
|-------|-----|-------------------|--------|
| **14.09.2026** | Pzt (erken) | ✅ **Dalgıç Pompa Kablo Bağlantısı** (BLOG-PLAN #2) | `/blog/dalgic-pompa-kablo-baglantisi-kesit-secimi` |
| **18.09.2026** | Perşembe | (erken yayınlandı; bu slot boş / yedek revizyon) | — |
| **25.09.2026** | Perşembe | **Sanayi Tipi Vantilatör Kurulum Rehberi** (BLOG-PLAN #3) | vantilatör pillar |

### Eylül ekstra
| Tarih | İş |
|-------|-----|
| **19.09.2026** | Google piyasa: taranmamış kuyruk kontrol (KUYRUK sayısı) |
| **26.09.2026** | 10 kritik Pedrollo/Sumak ürün fiyat onayı |

---

## Ekim 2026

### Aylık büyük kontrol
| Tarih | İş |
|-------|-----|
| **01.10.2026** | Aylık veri: `seo:fetch-monthly-data` (2026-10 klasörü) |
| **02.10.2026** | GSC index + CWV ekran · Merchant reddedilenler |
| **03.10.2026** | Aylık 10 aksiyon listesi + fiyat “biz pahalıyız” turu |

### Kontrol tarihleri (Salı)
| Tarih | İş |
|-------|-----|
| **07.10.2026** | Haftalık kontrol |
| **14.10.2026** | Haftalık kontrol |
| **21.10.2026** | Haftalık kontrol |
| **28.10.2026** | Haftalık kontrol + Ekim kapanış |

### İçerik tarihleri (Perşembe — yazılacak)
| Tarih | İçerik | Not |
|-------|--------|-----|
| **09.10.2026** | Hidrofor kesik kesik çalışıyor (BLOG-PLAN #4) | hidrofor pillar |
| **16.10.2026** | Kuyu dalgıç pompa seçimi / derinlik | dalgıç pillar |
| **23.10.2026** | Kategori güçlendirme: Çift Fanlı Santrifüj (SEO metin) | kategori sayfası |
| **30.10.2026** | Jet pompa seçim rehberi | su pompaları |

---

## Kasım 2026

### Aylık büyük kontrol
| Tarih | İş |
|-------|-----|
| **01.11.2026** (Cmt→Pzt’ye kaydır) **03.11.2026** | Veri çekimi + GSC |
| **03.11.2026** | Aylık aksiyon + fiyat turu |

> 1–2 Kasım hafta sonu → **03.11.2026 Pazartesi** tek oturumda B1+B2 bitir.

### Kontrol tarihleri (Salı)
| Tarih |
|-------|
| **04.11.2026** |
| **11.11.2026** |
| **18.11.2026** |
| **25.11.2026** |

### İçerik tarihleri (Perşembe)
| Tarih | İçerik |
|-------|--------|
| **06.11.2026** | Ev tipi hidrofor nasıl seçilir |
| **13.11.2026** | Trifaze vs monofaze pompa |
| **20.11.2026** | Kategori: Derin Kuyu Dalgıç (SEO metin) |
| **27.11.2026** | Yangın pompası / özel amaçlı (niyet sayfası veya blog) |

---

## Aralık 2026

### Aylık büyük kontrol
| Tarih | İş |
|-------|-----|
| **01.12.2026** | `seo:fetch-monthly-data` |
| **02.12.2026** | GSC + Merchant + CWV |
| **03.12.2026** | Aylık aksiyon · **2026 Q4 özeti** · 2027 Q1 plan |

### Kontrol tarihleri (Salı)
| Tarih |
|-------|
| **02.12.2026** *(aylık ile birleşebilir)* |
| **09.12.2026** |
| **16.12.2026** |
| **23.12.2026** |
| **30.12.2026** | Yıl sonu hızlı kontrol |

### İçerik tarihleri (Perşembe)
| Tarih | İçerik |
|-------|--------|
| **04.12.2026** | Kış / depo-hidrofor bakım |
| **11.12.2026** | Pedrollo vs alternatif (karşılaştırma — spam yok, teknik) |
| **18.12.2026** | Kategori: Preferikal pompalar SEO metin |
| **25.12.2026** | Tatil — içerik yok; **26.12 veya 01.01**’e kaydır |

---

## 2027 — Her ay sabit (tarihler)

Her ay aynı kalıp (tarihler o aya göre):

| Tarih | İş tipi |
|-------|---------|
| **Ayın 1’i** | Veri çekimi (kontrol) |
| **Ayın 2’si** | GSC index + Merchant (kontrol) |
| **Ayın 3’ü** | 10 aksiyon + fiyat (kontrol/plan) |
| **Her Salı** | Haftalık kontrol |
| **Her Perşembe** | 1 içerik yazımı / yayını |

### Çeyrek strateji (3 ayda bir)
| Tarih | İş |
|-------|-----|
| **03.12.2026** | Q4 kapanış + Q1 plan (yukarıda) |
| **02.03.2027** | Q1 strateji: keyword boşluk, rakip 3 site, kategori IA |
| **01.06.2027** | Q2 strateji |
| **01.09.2027** | Q3 strateji |

---

## Sorumluluk ayrımı

| İş | Siz | Cursor / panel |
|----|-----|----------------|
| GSC / Merchant tıklama, onay | ✓ | — |
| Aylık veri API | komut çalıştır | rapor oku |
| Blog yazısı final + yayın | ✓ | taslak / SEO kontrol |
| Fiyat uygula | ✓ (onaylı) | tarama / öneri |
| Kod / redirect / meta fix | onay | uygula |

---

## Bu hafta hemen (14–20 Eylül 2026)

| Tarih | Ne yapılacak |
|-------|----------------|
| **14–15.09** | Bu takvimi oku · Google piyasa **Kuyruğa al** (taranmamış) durumu kontrol |
| **16.09 Salı** | İlk **Kontrol günü** (checklist) |
| **18.09 Perşembe** | İlk **İçerik günü**: Dalgıç pompa kablo yazısı |

---

## Notlar

- Tarih tatille çakışırsa: **bir sonraki iş gününe** kaydır; atlama.
- Haftada 0 içerik = rakibe boş alan. Minimum: **ayda 4 içerik**.
- Fiyat: tarama ≠ uygulama. Uygulama yalnız onay sonrası.
- Takvim güncellenince üstteki “Başlangıç” satırına tarih yazın.

*Oluşturma: 14.09.2026*
