#!/usr/bin/env python3
"""WooCommerce SEO Excel → UTF-8 JSON (inlineStr xlsx)."""
import argparse
import json
import re
import sys
import zipfile
import xml.etree.ElementTree as ET

NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


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


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("xlsx", help="Path to .xlsx file")
    parser.add_argument("-o", "--output", required=True, help="Output JSON path")
    parser.add_argument("--limit", type=int, default=0, help="Max data rows (0 = all)")
    args = parser.parse_args()

    with zipfile.ZipFile(args.xlsx) as z:
        root = ET.fromstring(z.read("xl/worksheets/sheet1.xml"))
    rows = root.findall(".//m:sheetData/m:row", NS)
    if not rows:
        print("No rows found", file=sys.stderr)
        return 1

    headers = parse_row(rows[0])
    data = []
    for row in rows[1:]:
        cells = parse_row(row)
        if len(cells) < len(headers):
            cells.extend([""] * (len(headers) - len(cells)))
        item = {headers[i]: cells[i] if i < len(cells) else "" for i in range(len(headers))}
        if not str(item.get("SKU", "")).strip() and not str(item.get("Name", "")).strip():
            continue
        data.append(item)
        if args.limit and len(data) >= args.limit:
            break

    with open(args.output, "w", encoding="utf-8") as f:
        json.dump({"headers": headers, "rows": data}, f, ensure_ascii=False, indent=2)

    print(f"Exported {len(data)} rows to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
