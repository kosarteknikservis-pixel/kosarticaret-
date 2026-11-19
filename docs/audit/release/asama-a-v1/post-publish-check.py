#!/usr/bin/env python3
"""Post-publish live verification for asama-a-v1."""
from __future__ import annotations

import json
import pathlib
import re
import time
import urllib.error
import urllib.request
import ssl
from urllib.parse import urljoin, urlparse

ROOT = pathlib.Path(__file__).resolve().parent
CTX = ssl.create_default_context()
UA = {"User-Agent": "KosarAuditBot/1.3 (+asama-a-v1-post-publish)"}
BASE = "https://kosarticaret.com"
ops = json.loads((ROOT / "content-ops.json").read_text(encoding="utf-8"))["ops"]


def fetch(url: str, allow_redirects: bool = True):
    class NoRedir(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: ANN001
            raise urllib.error.HTTPError(newurl, code, msg, headers, fp)

    handlers = [urllib.request.HTTPSHandler(context=CTX)]
    if not allow_redirects:
        handlers.insert(0, NoRedir())
    opener = urllib.request.build_opener(*handlers)
    req = urllib.request.Request(url, headers=UA)
    try:
        with opener.open(req, timeout=30) as r:
            body = r.read(450000).decode("utf-8", "ignore")
            return r.status, r.geturl(), body, dict(r.headers)
    except urllib.error.HTTPError as e:
        body = e.read(200000).decode("utf-8", "ignore") if e.fp else ""
        loc = e.headers.get("Location", "") if e.headers else ""
        return e.code, loc or getattr(e, "url", ""), body, dict(e.headers or {})


def paths_from(html: str) -> set[str]:
    out = set()
    for m in re.finditer(r'<a\b[^>]*href=["\']([^"\']+)["\']', html, re.I):
        p = urlparse(urljoin(BASE, m.group(1))).path
        out.add(p.rstrip("/") or p)
    return out


def meta_robots(html: str) -> str:
    m = re.search(r'name=["\']robots["\'][^>]*content=["\']([^"\']+)', html, re.I)
    if not m:
        m = re.search(r'content=["\']([^"\']+)["\'][^>]*name=["\']robots', html, re.I)
    return m.group(1) if m else ""


def canon(html: str) -> str:
    m = re.search(r'rel=["\']canonical["\'][^>]*href=["\']([^"\']+)', html, re.I)
    if not m:
        m = re.search(r'href=["\']([^"\']+)["\'][^>]*rel=["\']canonical', html, re.I)
    return m.group(1) if m else ""


results = {"checked_at": time.strftime("%Y-%m-%dT%H:%M:%S%z"), "content_links": [], "legacy_404": [], "sort": [], "regression": []}

# 1) content link fixes
by_source = {}
for op in ops:
    by_source.setdefault(op["source_url"], []).extend(op["replacements"])

content_ok = 0
content_fail = 0
for source, reps in by_source.items():
    code, final, html, _ = fetch(BASE + source, True)
    paths = paths_from(html) if code == 200 else set()
    row = {"source": source, "http": code, "checks": []}
    for rep in reps:
        frm = rep["from"].rstrip("/")
        to = (rep["to"] or "").rstrip("/") if rep["to"] else None
        from_present = frm in paths
        to_present = (to in paths) if to else None
        ok = (not from_present) and (True if to is None else to_present)
        row["checks"].append({"from": frm, "to": to, "from_present": from_present, "to_present": to_present, "ok": ok})
        if ok:
            content_ok += 1
        else:
            content_fail += 1
    results["content_links"].append(row)
    time.sleep(0.05)

# 2) legacy 404
legacy = [
    "/kategori/yedek-parca",
    "/urun-kategori/yedek-parca",
    "/kategoriler/yedek-parca-ve-aksesuarlar",
    "/kategori/genlesme-tanklari",
    "/urun-kategori/bahce-yapi-market/genlesme-tanklari",
    "/urun/alarko-wsd25-17-kirli-ve-atik-su-dalgic-1-5-hp",
]
for path in legacy:
    code, final, html, _ = fetch(BASE + path, False)
    has_text = "artık bu adreste bulunmuyor" in html or "artik bu adreste bulunmuyor" in html
    has_search = 'action="' in html and "/ara" in html
    has_contact = "/iletisim" in html
    no_refresh = "http-equiv" not in html.lower() or "refresh" not in html.lower()
    results["legacy_404"].append(
        {
            "path": path,
            "status": code,
            "ok": code == 404,
            "has_text": has_text,
            "has_search": has_search,
            "has_contact": has_contact,
            "no_meta_refresh": no_refresh,
        }
    )
    time.sleep(0.05)

# 3) sort redirects
sort_cases = [
    ("/kategoriler/su-pompalari?sirala=fiyat-artan", 301, "siralama=fiyat-artan"),
    ("/kategoriler/su-pompalari?sirala=uydurma&page=2", 301, "page=2"),
    ("/kategoriler/su-pompalari?sirala=isim&siralama=fiyat-azalan&marka=sumak", 301, "siralama=fiyat-azalan"),
]
for path, expect_code, expect_frag in sort_cases:
    code, loc, html, headers = fetch(BASE + path, False)
    loc = loc or headers.get("Location", "")
    results["sort"].append(
        {
            "path": path,
            "status": code,
            "location": loc,
            "ok": code == expect_code and expect_frag in loc,
        }
    )
    time.sleep(0.05)

code, _, html, _ = fetch(BASE + "/kategoriler/su-pompalari", True)
results["sort"].append(
    {
        "path": "/kategoriler/su-pompalari",
        "status": code,
        "robots": meta_robots(html),
        "canonical": canon(html),
        "ok": code == 200 and "noindex" not in meta_robots(html).lower() and "/kategoriler/su-pompalari" in canon(html),
    }
)
code, _, html, _ = fetch(BASE + "/kategoriler/su-pompalari?siralama=fiyat-artan", True)
results["sort"].append(
    {
        "path": "/kategoriler/su-pompalari?siralama=fiyat-artan",
        "status": code,
        "robots": meta_robots(html),
        "canonical": canon(html),
        "ok": code == 200 and "noindex" in meta_robots(html).lower() and "/kategoriler/su-pompalari" in canon(html) and "siralama" not in canon(html),
    }
)

# 4) regression
reg = [
    "/kategori/dalgic-pompalar",
    "/kategoriler/su-pompalari",
    "/kategoriler/hidrofor-sistemleri",
    "/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa",
    "/marka/sumak",
    "/urun/troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli",
    "/ara?q=pompa",
    "/iletisim",
]
for path in reg:
    if path.startswith("/kategori/"):
        code, loc, _, headers = fetch(BASE + path, False)
        loc = loc or headers.get("Location", "")
        results["regression"].append({"path": path, "status": code, "location": loc, "ok": code in (301, 302) and "/kategoriler/" in loc})
    else:
        code, final, _, _ = fetch(BASE + path, True)
        results["regression"].append({"path": path, "status": code, "final": final, "ok": code == 200})
    time.sleep(0.05)

summary = {
    "content_checks_ok": content_ok,
    "content_checks_fail": content_fail,
    "legacy_404_ok": sum(1 for r in results["legacy_404"] if r["ok"]),
    "legacy_404_total": len(results["legacy_404"]),
    "sort_ok": sum(1 for r in results["sort"] if r["ok"]),
    "sort_total": len(results["sort"]),
    "regression_ok": sum(1 for r in results["regression"] if r["ok"]),
    "regression_total": len(results["regression"]),
}
results["summary"] = summary
(ROOT / "post-publish-results.json").write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps(summary, ensure_ascii=False, indent=2))
