# Aşama A — Yayın paketi `asama-a-v1` (onay bekliyor)

**Durum:** Canlıya gönderilmedi. Bu belge son onay için hazırlık.  
**Kapsam:** kosarticaret.com. kosar.net.tr yok. Servis bağlantısı yok.  
**Kapsam doğrulama:** Önceki Aşama A (404/not_found, hata sayfası, `sirala`→`siralama`) + 14 bağlantı düzeltmesi birlikte.

---

## Sürüm

| Alan | Değer |
|---|---|
| Paket | **asama-a-v1** |
| Hazırlık | 2026-11-19 |
| İçerik ops | `docs/audit/release/asama-a-v1/content-ops.json` |
| Aktarım scripti | `scripts/asama-a-content-transfer.php` |
| Canlı ön kontrol | `live-precheck.json` — **24/24 ready**, diverge 0 (2026-11-19) |
| Yerel idempotency | dry-run×2 → `already_done=17` kayıt, ek değişiklik 0 |

---

## Tek yayın listesi

### A) Kod (git ile)

| Dosya | Ne |
|---|---|
| `config/legacy_redirects.php` | `not_found` 6 path; `gone` boş |
| `config/legacy_product_redirects.php` | Alarko 301 kaldırıldı |
| `config/redirects.php` | yedek/genleşme/Alarko notu |
| `app/Http/Middleware/LegacyRedirect.php` | `not_found`→404 |
| `app/Support/CatalogPaginationSeo.php` | `sirala`→`siralama` + allowlist |
| `app/Http/Controllers/Shop/{Category,Product,Brand,Collection}Controller.php` | sort redirect |
| `resources/views/errors/partials/missing-page.blade.php` | ortak metin + ara + iletişim |
| `resources/views/errors/{404,410}.blade.php` | partial |
| `tests/Unit/CatalogPaginationSeoTest.php` | edge |
| `tests/Feature/LegacyRedirectTest.php` | 404 + regresyon |
| `tests/Feature/CatalogSortParamTest.php` | filtre korunumu |
| `scripts/asama-a-content-transfer.php` | içerik aktarım/geri dönüş |

### B) İçerik aktarımı (DB alan bazlı — toplu kopya yok)

17 kayıt (15 blog + 2 ürün), yalnız `content` / `description`.  
14 finding’in kaynak href’leri; finding 13 (`item.url`) kod/şablon false positive → içerik aktarımı yok.

| ID | Slug | Alan | Değişiklikler (from → to) |
|---:|---|---|---|
| 40 | `kosar-vantilator-modelleri-rehberi` | content | marka-karsilastirma→secimi-rehberi; is-guvenligi→unwrap |
| 96 | `salyangoz-pompa-rehberi` | content | tarimsal-…-secimi→…-secimi-rehberi |
| 139 | `sirkulasyon-sistemi-tasarimi-pompa-egrisi-dengeleme` | content | debi-ve-basma→debi-basma |
| 141 | `sirkulasyon-pompa-egrisi-nasil-okunur` | content | aynı |
| 207 | `santrifuj-pompa-kavitasyon-belirtileri-onleme` | content | aynı |
| 208 | `santrifuj-pompa-kisa-cevrim-start-stop` | content | tank-presostat / isletme / histerezis → mevcut yazılar |
| 209 | `santrifuj-pompa-debi-dususu-teshis` | content | tank-presostat→sistem-tasarimi |
| 211 | `santrifuj-pompa-basinc-dususu-calism-noktasi` | content | tank + debi |
| 214 | `santrifuj-pompa-bakim-disiplini-periyodik-kontrol` | content | tank-presostat→sistem-tasarimi |
| 215 | `kademeli-pompa-isletme-sistemi` | content | yangin-sistemi-tasarimi→yangin-pompasi-nedir… |
| 218 | `kademeli-pompa-debi-dususu-teshis` | content | monoblok-…-pompalar→monoblok-yatay-kademeli |
| 220 | `kademeli-pompa-kisa-cevrim-start-stop` | content | kisa-cevrim-teshis + yangin tasarım |
| 221 | `kademeli-pompa-yuksek-basinc-hat-isletme` | content | by-pass unwrap; yangin tasarım→nedir |
| 224 | `kademeli-pompa-bakim-disiplini-periyodik-kontrol` | content | yangin-bakim unwrap |
| 230 | `jet-pompa-kisa-cevrim-start-stop` | content | kisa-cevrim-teshis→kisa-cevrim |
| 2750 | `troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli` | description | `/marka/Sumak`→`/marka/sumak` |
| 2751 | `troy-hmj-15-dokum-govdeli-jet-pompa-1-hp-49-mss-4-m3-h` | description | Sumak case + Sumak-hmj ürün → troy-hmj-h-15 |

Canlı ön kontrol (2026-11-19): tüm kaynaklar 200; beklenen eski href **24/24 mevcut**; diverge **0**. Üzerine kör yazma yok — script `from` yoksa ve `to` da yoksa **diverged_skip_write**.

---

## Aktarım yöntemi (onay sonrası sıra)

1. **Kod deploy** — yalnız yukarıdaki A listesi (kirli ağaçtaki diğer dosyalar bu pakete girmez).  
2. `php artisan view:clear` / `config:clear` (canlı).  
3. Canlıda içerik:  
   `php scripts/asama-a-content-transfer.php --dry-run`  
   → `apply_ready` beklenir; `diverged>0` ise **uygulama**.  
4. `php scripts/asama-a-content-transfer.php --apply`  
   → yedek: `docs/audit/release/asama-a-v1/backups/<stamp>/`  
5. Aynı komutu tekrar dry-run → `already_done`, `would_change_fields=0` (idempotent).  
6. `docs/audit/release/asama-a-v1/post-publish-verify.md` kontrolleri.

---

## Yedek ve geri dönüş

| Katman | Nasıl |
|---|---|
| İçerik | `--apply` her değişen alan için JSON yedek (`before`). Geri: `php scripts/asama-a-content-transfer.php --rollback=<backup_dir>` |
| Kod | Deploy edilen commit’i geri al / önceki release. |
| Config cache | `config:clear` + `view:clear` |

Toplu DB restore gerekmez.

---

## Yayın sonrası kontrol (özet)

1. 14 düzeltme canlı kaynak HTML’de  
2. 6 legacy path → **404** (+ ortak hata metni)  
3. `sirala`→`siralama`, filtre/sayfa, robots/canonical  
4. Çalışan kategori/ürün/marka/ara/iletişim **200** / beklenen 301  

Detay: `docs/audit/release/asama-a-v1/post-publish-verify.md`

---

## Onay kapısı

Bu mesaj **yayın onayı değildir**. Onay verilince: kod A + içerik B yukarıdaki sırayla.
