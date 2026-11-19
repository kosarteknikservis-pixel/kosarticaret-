#!/usr/bin/env python3
"""Live HTML precheck: expected old href still present? (no write)."""
from __future__ import annotations

import json
import pathlib
import re
import time
import urllib.request
import ssl
from urllib.parse import urljoin, urlparse

ROOT = pathlib.Path(__file__).resolve().parent
CTX = ssl.create_default_context()
UA = {"User-Agent": "KosarAuditBot/1.3 (+asama-a-v1-precheck)"}
BASE = "https://kosarticaret.com"
ops = json.loads((ROOT / "content-ops.json").read_text(encoding="utf-8"))["ops"]


def fetch(url: str) -> str:
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, context=CTX, timeout=30) as r:
        return r.read(500000).decode("utf-8", "ignore")


def hrefs(html: str) -> list[str]:
    out = []
    for m in re.finditer(r'<a\b[^>]*href=["\']([^"\']+)["\']', html, re.I):
        href = m.group(1)
        p = urlparse(urljoin(BASE, href))
        if "kosarticaret.com" in (p.netloc or "kosarticaret.com") or not p.netloc:
            out.append(p.path.rstrip("/") or p.path)
    return out


rows = []
by_source = {}
for op in ops:
    by_source.setdefault(op["source_url"], []).extend(op["replacements"])

for source, reps in by_source.items():
    url = BASE + source
    try:
        html = fetch(url)
        paths = hrefs(html)
        item = {"source_url": source, "http": 200, "checks": []}
        for rep in reps:
            frm = rep["from"].rstrip("/")
            to = (rep["to"] or "").rstrip("/") if rep["to"] else None
            item["checks"].append(
                {
                    "from": frm,
                    "to": to,
                    "from_present": frm in paths,
                    "to_present": (to in paths) if to else None,
                    "expected_for_apply": frm in paths,
                    "already_looks_applied": (frm not in paths)
                    and (to in paths if to else True),
                }
            )
        time.sleep(0.08)
    except Exception as e:  # noqa: BLE001
        item = {"source_url": source, "http": getattr(e, "code", 0), "error": str(e)[:200], "checks": []}
    rows.append(item)
    print(source, item.get("http"), "checks", len(item.get("checks", [])))

summary = {
    "sources": len(rows),
    "ready_ops": 0,
    "already_applied_ops": 0,
    "diverged_ops": 0,
    "fetch_errors": 0,
}
for r in rows:
    if r.get("http") != 200:
        summary["fetch_errors"] += 1
        continue
    for c in r["checks"]:
        if c["expected_for_apply"]:
            summary["ready_ops"] += 1
        elif c["already_looks_applied"]:
            summary["already_applied_ops"] += 1
        else:
            summary["diverged_ops"] += 1

out = {"checked_at": time.strftime("%Y-%m-%dT%H:%M:%S%z"), "summary": summary, "rows": rows}
(ROOT / "live-precheck.json").write_text(json.dumps(out, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps(summary, ensure_ascii=False))
