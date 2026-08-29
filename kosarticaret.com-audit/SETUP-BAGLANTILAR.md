# Cursor ↔ Google Search Console + GA4 Bağlantısı

> Amaç: Aylık SEO operasyonunda (`AYLIK-SEO-PLANI.md` B1) manuel export'u ortadan kaldırmak.
> Yöntem: **Servis hesabı (JSON key)** — tarayıcı OAuth akışı yok, headless çalışır.
> DataForSEO MCP zaten kurulu; ona dokunulmayacak.

---

## BÖLÜM 1 — Senin Google Cloud Adımların (bir kere, ~20 dk)

### 1.1 Proje
1. https://console.cloud.google.com → sağ üstten proje seç veya **Yeni Proje** (`kosar-seo`).

### 1.2 API'leri etkinleştir
**APIs & Services → Library** → şunları aç (Enable):
- **Google Search Console API**
- **Google Analytics Data API**

### 1.3 Servis hesabı + JSON key
1. **APIs & Services → Credentials → + Create Credentials → Service account**.
2. Ad: `kosar-seo-reader` → Create and Continue → rol vermeden **Done**.
3. Oluşan servis hesabına tıkla → **Keys → Add Key → Create new key → JSON → Create**.
4. İnen dosyayı şuraya koy (repo DIŞINA):
   ```
   C:\Users\PC\.config\kosar-seo\gsc-ga4-service-account.json
   ```
   (klasör yoksa oluştur)
5. Servis hesabının **e-posta adresini** kopyala — şu formatta:
   `kosar-seo-reader@kosar-seo.iam.gserviceaccount.com`

### 1.4 Search Console'a erişim ver
1. https://search.google.com/search-console → **Ayarlar → Kullanıcılar ve izinler**.
2. **Kullanıcı ekle** → servis hesabı e-postası → izin: **Tam** (veya "Kısıtlı" da yeter).

### 1.5 GA4'e erişim ver
1. https://analytics.google.com → **Yönetici (⚙) → Mülk erişim yönetimi**.
2. Sağ üst **+ → Kullanıcı ekle** → servis hesabı e-postası → rol: **Görüntüleyici** → Ekle.

### 1.6 GA4 Property ID'yi al
- **Yönetici → Mülk ayarları** → sağ üstte **MÜLK KİMLİĞİ** (9-10 haneli sayı, örn. `312345678`).

### 1.7 Cursor'a ver
Bu 3 bilgiyi Cursor sohbetine yaz:
- JSON key yolu: `C:\Users\PC\.config\kosar-seo\gsc-ga4-service-account.json`
- GA4 Property ID: `________`
- GSC property tipi: `https://kosarticaret.com/` (URL öneki) mi, `sc-domain:kosarticaret.com` (domain) mi?
  (Search Console açılışında hangi mülkü görüyorsan o.)

> **Not:** Aynı JSON key, Claude Code'daki `seo` skill'i için de kullanılır:
> `claude-seo run google_auth.py` → `google-api.json`'a `service_account_path` olarak ekle.

---

## BÖLÜM 2 — Cursor'un Adımları

Cursor aşağıdaki komutla bunları yapacak:
1. `.cursor/mcp.json`'a GSC ve GA4 MCP sunucularını ekler (DataForSEO'yu bozmadan).
2. `.gitignore`'a key dosyası kalıplarını ekler.
3. Sen bilgileri verince yapılandırmayı tamamlar ve bağlantıyı test eder.

---

## BÖLÜM 3 — Cursor'a Yapıştırılacak Komut

```text
Cursor'a Google Search Console + GA4 MCP bağlantısı kuracağız. Rehber:
kosarticaret.com-audit/SETUP-BAGLANTILAR.md (BÖLÜM 1 adımlarını ben yaptım / yapıyorum).
DataForSEO MCP zaten kurulu — ONA DOKUNMA.

GÖREVLER:

1. Mevcut MCP config'i oku ve göster: proje kökünde `.cursor/mcp.json`
   (yoksa `%USERPROFILE%\.cursor\mcp.json`). Hangi dosyayı kullanacağını söyle.

2. Şu iki sunucuyu ekle (servis hesabı / JSON key auth):

   a) "google-search-console":
      command: npx
      args: ["-y", "mcp-server-gsc"]
      env: { "GOOGLE_APPLICATION_CREDENTIALS": "C:\\Users\\PC\\.config\\kosar-seo\\gsc-ga4-service-account.json" }

   b) "google-analytics-ga4":
      Windows'ta pipx veya uvx ile çalıştır (hangisi varsa):
      command: uvx   (veya: pipx run)
      args: ["google-analytics-mcp"]
      env: {
        "GOOGLE_APPLICATION_CREDENTIALS": "C:\\Users\\PC\\.config\\kosar-seo\\gsc-ga4-service-account.json"
      }
      Not: resmi paket = google-analytics-mcp (PyPI). uvx/pipx yoksa bana kur komutunu ver.

3. `.gitignore`'a ekle (yoksa oluştur):
   *service-account*.json
   *.gsc-key.json
   .config/kosar-seo/

4. Ben şu 3 bilgiyi verince config'i tamamla:
   - JSON key yolu (yukarıda placeholder)
   - GA4 Property ID  → GA4 sunucusuna env "GA4_PROPERTY_ID" veya tool argümanı olarak kullan
   - GSC property adı (https://kosarticaret.com/ veya sc-domain:kosarticaret.com)

5. Cursor'u yeniden başlatmamı söyle. Sonra bağlantıyı test et:
   - GSC: kosarticaret.com, son 28 gün, en çok tıklanan 10 sorgu + 10 sayfa
   - GSC: index kapsamı özeti (kaç sayfa indexli / hariç)
   - GA4: son 28 gün organik oturum + en çok organik trafik alan 10 açılış sayfası
   Çıktıları göster.

6. Çalışınca `SETUP-BAGLANTILAR.md`'nin sonuna "✅ Kuruldu — {tarih}" + test çıktısı özeti ekle.

KURALLAR:
- JSON key dosyasını ASLA commit'leme, içeriğini yazdırma/loglama.
- mcp.json'a gerçek dosya yolunu yaz; JSON'un kendisi repo dışında kalsın.
- DataForSEO girdisini değiştirme.
- Sorun çıkarsa dur ve bana net hata mesajını ver.

Başla: önce mcp.json'u göster ve ne ekleyeceğini özetle.
```

---

## Kuruldu

✅ **Kuruldu — 29 Ağustos 2026**

### Yapılandırma
| Alan | Değer |
|---|---|
| MCP dosyası | `C:\Users\PC\.cursor\mcp.json` (DataForSEO dokunulmadı) |
| JSON key | `C:\Users\PC\.config\kosar-seo\gsc-ga4-service-account.json` |
| Servis hesabı | `kosar-gsc@singular-object-499013-e9.iam.gserviceaccount.com` |
| GCP proje | `singular-object-499013-e9` |
| GA4 Property ID | `482446325` |
| GSC mülkü | `https://kosarticaret.com/` |

### Test özeti (1–28 Ağustos 2026)

**GSC — en çok tıklanan sorgular:** sanayi tipi vantilatör (10), koşar ticaret (6), sumak pompa (6), kaysu pompa (4), pedrollo pompa (4)

**GSC — en çok tıklanan sayfalar:** `/blog/hidrofor-kurulumu-montaj-rehberi` (15), ana sayfa (13), `/kategoriler/vantilatorler/sanayi-tipi-vantilator` (12)

**GSC — sitemap:** `sitemap.xml` → 3480 web URL gönderildi, hata 0, son indirme 28 Ağustos

**GA4 — organik:** 366 oturum, 313 kullanıcı

**GA4 — top organik açılış sayfaları:** `/` (27), `/blog/hidrofor-kurulumu-montaj-rehberi` (15), `/kategoriler/vantilatorler/sanayi-tipi-vantilator` (12)

> Not: GSC API site geneli index/hariç toplamı vermez; sitemap submitted/indexed en yakın sinyal.

### Aylık B1 otomasyon komutu

```bash
php artisan seo:fetch-monthly-data
```

Çıktı: `storage/seo-reports/monthly/{YYYY-MM}/b1-report.json` + `RAPOR.md`
