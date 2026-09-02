#!/usr/bin/env python3
"""Fetch GSC search queries for panel cache. Outputs JSON to stdout."""
from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import date, timedelta
from typing import Any

from google.oauth2 import service_account
from googleapiclient.discovery import build

GSC_SCOPES = ["https://www.googleapis.com/auth/webmasters.readonly"]


def env(name: str, default: str | None = None) -> str:
    value = os.environ.get(name, default)
    if not value:
        raise SystemExit(f"Missing environment variable: {name}")
    return value


def period_end() -> date:
    return date.today() - timedelta(days=1)


def period_start(days: int) -> date:
    return period_end() - timedelta(days=days - 1)


def load_credentials():
    path = env("GOOGLE_APPLICATION_CREDENTIALS")
    return service_account.Credentials.from_service_account_file(path, scopes=GSC_SCOPES)


def gsc_site_totals(svc, site: str, start: str, end: str) -> dict:
    result = (
        svc.searchanalytics()
        .query(
            siteUrl=site,
            body={"startDate": start, "endDate": end, "dataState": "all"},
        )
        .execute()
    )
    rows = result.get("rows", [])
    if not rows:
        return {"clicks": 0, "impressions": 0}
    row = rows[0]
    return {
        "clicks": int(row.get("clicks", 0)),
        "impressions": int(row.get("impressions", 0)),
    }


def gsc_query(svc, site: str, start: str, end: str, row_limit: int = 25000) -> list[dict]:
    body = {
        "startDate": start,
        "endDate": end,
        "dimensions": ["query"],
        "rowLimit": row_limit,
        "dataState": "all",
    }
    result = svc.searchanalytics().query(siteUrl=site, body=body).execute()
    rows = []
    for row in result.get("rows", []):
        keys = row.get("keys", [])
        rows.append(
            {
                "query": keys[0] if keys else "",
                "clicks": int(row.get("clicks", 0)),
                "impressions": int(row.get("impressions", 0)),
                "ctr": round(float(row.get("ctr", 0)), 4),
                "position": round(float(row.get("position", 0)), 2),
            }
        )
    rows.sort(key=lambda item: item["clicks"], reverse=True)
    return rows


def fetch(days: int) -> dict[str, Any]:
    site = env("GSC_SITE_URL", "https://kosarticaret.com/")
    start = period_start(days)
    end = period_end()
    start_s = start.isoformat()
    end_s = end.isoformat()

    creds = load_credentials()
    gsc = build("searchconsole", "v1", credentials=creds, cache_discovery=False)

    site_list = gsc.sites().list().execute().get("siteEntry", [])
    site_urls = [entry.get("siteUrl", "") for entry in site_list if entry.get("permissionLevel") != "siteUnverifiedUser"]
    if site not in site_urls:
        site = site_urls[0] if site_urls else site

    queries = gsc_query(gsc, site, start_s, end_s)
    totals = gsc_site_totals(gsc, site, start_s, end_s)
    totals["query_count"] = len(queries)

    return {
        "fetched_at": date.today().isoformat(),
        "source": "api:seo:fetch-gsc-keywords",
        "site_url": site,
        "period": {
            "days": days,
            "start": start_s,
            "end": end_s,
        },
        "totals": totals,
        "queries": queries[:100],
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--days", type=int, required=True)
    args = parser.parse_args()
    payload = fetch(args.days)
    json.dump(payload, sys.stdout, ensure_ascii=False, indent=2)
    sys.stdout.write("\n")


if __name__ == "__main__":
    main()
