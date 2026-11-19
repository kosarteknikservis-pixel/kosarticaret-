# Aşama A v1 — Canlı yayın raporu

**Durum:** Yayın ve canlı kontroller tamamlandı.  
**Paket:** `asama-a-v1`  
**Site:** kosarticaret.com (kosar.net.tr yok, servis linki yok)

## Yayın zamanı / sürüm

| Alan | Değer |
|---|---|
| Kod push | 2026-11-19 09:12:59 +03 |
| Canlı kod yedeği | 2026-11-19 06:13:34 UTC (`backups/asama-a-v1-20261119-0913`) |
| Canlı kod SCP | ~09:13–09:14 +03 |
| İçerik apply | 2026-11-19 06:15:45 UTC |
| Post-publish check | 2026-11-19 09:16:47 +03 |
| Commit | `5e17ebe07e6287a3fbe46f1d3662343bc011a42b` |
| Dal | `origin/main` |

## Aktarım yöntemi

1. Canlı HTML ön kontrol (yeniden): **24/24 ready**, diverge 0  
2. Temiz worktree `origin/main` → yalnız paket dosyaları commit/push  
3. SSH seçici SCP (kirli WIP yok) + sunucu tar yedeği  
4. `view/config/cache:clear`  
5. İçerik: slug ile eşleştirme (yerel ID ≠ canlı ID; üzerine kör yazılmadı)  
6. `--apply` → alan yedekleri → idempotent dry-run **0 değişiklik**

## Güncellenen sayılar

| Metrik | Sayı |
|---|---:|
| Kod dosyası (commit) | 20 |
| İçerik kaydı | **17** (15 blog + 2 ürün) |
| Eski href operasyonu | **24** |
| Alan yedek dosyası | 17 + `index.json` |
| Backup dir (canlı) | `kosar/docs/audit/release/asama-a-v1/backups/20261119-061545` |
| Kod tar yedeği | `/home/admin/domains/kosarticaret.com/backups/asama-a-v1-20261119-0913/kosar-code-asama-a-v1.tgz` |

## Canlı kontrol sonuçları

| Katman | Sonuç |
|---|---|
| İçerik href (24) | **24/24 OK** |
| Legacy 404 (6) | **6/6 OK** + ortak metin + `/ara` + `/iletisim` |
| `sirala` / robots / canonical (5) | **5/5 OK** |
| Regresyon ürün/kategori/marka/ara/iletişim (8) | **8/8 OK** |
| Tekrar dry-run | `already_done=17`, `would_change_fields=0` |

## Not / kalan

1. Yerel DB ID’leri canlıdan farklıydı; aktarım **slug** ile yapıldı (script canlıda güncellendi).  
2. Finding 13 (`item.url` Alpine) bilerek kapsamdışı.  
3. Geri dönüş:  
   - içerik `php scripts/asama-a-content-transfer.php --rollback=docs/audit/release/asama-a-v1/backups/20261119-061545`  
   - kod `tar -xzf .../kosar-code-asama-a-v1.tgz` + `view/config:clear`  
4. Bu yayından kaynaklanan hata görülmedi; rollback uygulanmadı.
