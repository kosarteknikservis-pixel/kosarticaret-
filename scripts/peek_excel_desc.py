#!/usr/bin/env python3
import json
import sys
import zipfile
import xml.etree.ElementTree as ET
import re

NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
slug = sys.argv[1] if len(sys.argv) > 1 else "sumak-smj-150-5-kat-10-daire-hidrofor"
xlsx = sys.argv[2] if len(sys.argv) > 2 else "final_woocommerce_seo_import.xlsx"


def col_idx(ref: str) -> int:
    m = re.match(r"^([A-Z]+)", ref or "")
    if not m:
        return 0
    n = 0
    for ch in m.group(1):
        n = n * 26 + (ord(ch) - 64)
    return n


def cell_text(cell) -> str:
    t = cell.get("t")
    if t == "inlineStr":
        is_el = cell.find("m:is", NS)
        if is_el is not None:
            return "".join(x.text or "" for x in is_el.findall(".//m:t", NS))
    v = cell.find("m:v", NS)
    return v.text if v is not None and v.text is not None else ""


def parse_row(row) -> list[str]:
    line: dict[int, str] = {}
    for c in row.findall("m:c", NS):
        line[col_idx(c.get("r", ""))] = cell_text(c)
    if not line:
        return []
    mx = max(line)
    return [line.get(i, "") for i in range(1, mx + 1)]


with zipfile.ZipFile(xlsx) as z:
    root = ET.fromstring(z.read("xl/worksheets/sheet1.xml"))
rows = root.findall(".//m:sheetData/m:row", NS)
headers = parse_row(rows[0])
slug_i = headers.index("URL Slug") if "URL Slug" in headers else -1
desc_i = headers.index("Description") if "Description" in headers else -1
for row in rows[1:]:
    cells = parse_row(row)
    if len(cells) < len(headers):
        cells.extend([""] * (len(headers) - len(cells)))
    if slug_i >= 0 and cells[slug_i] == slug:
        desc = cells[desc_i] if desc_i >= 0 else ""
        print("has_table", "<table" in desc.lower())
        idx = desc.lower().find("özellik")
        if idx < 0:
            idx = desc.lower().find("ozellik")
        print(desc[max(0, idx - 80) : idx + 500] if idx >= 0 else desc[:600])
        break
else:
    print("row not found for slug", slug)
