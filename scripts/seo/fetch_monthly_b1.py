#!/usr/bin/env python3
"""Fetch GSC + GA4 data for monthly SEO B1 automation. Outputs JSON to stdout."""
from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import date, timedelta
from typing import Any

from google.analytics.data_v1beta import BetaAnalyticsDataClient
from google.analytics.data_v1beta.types import (
    DateRange,
    Dimension,
    Filter,
    FilterExpression,
    Metric,
    RunReportRequest,
)
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.errors import HttpError

GSC_SCOPES = ["https://www.googleapis.com/auth/webmasters.readonly"]
GA4_SCOPES = ["https://www.googleapis.com/auth/analytics.readonly"]
ORGANIC_FILTER = FilterExpression(
    filter=Filter(
        field_name="sessionDefaultChannelGroup",
        string_filter=Filter.StringFilter(value="Organic Search"),
    )
)


def env(name: str, default: str | None = None) -> str:
    value = os.environ.get(name, default)
    if not value:
        raise SystemExit(f"Missing environment variable: {name}")
    return value


def period_end() -> date:
    return date.today() - timedelta(days=1)


def period_start(days: int) -> date:
    return period_end() - timedelta(days=days - 1)


def previous_period(start: date, end: date) -> tuple[date, date]:
    length = (end - start).days + 1
    prev_end = start - timedelta(days=1)
    prev_start = prev_end - timedelta(days=length - 1)
    return prev_start, prev_end


def load_credentials(scopes: list[str]):
    path = env("GOOGLE_APPLICATION_CREDENTIALS")
    return service_account.Credentials.from_service_account_file(path, scopes=scopes)


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


def gsc_query(svc, site: str, start: str, end: str, dimensions: list[str], row_limit: int = 25000) -> list[dict]:
    body = {
        "startDate": start,
        "endDate": end,
        "dimensions": dimensions,
        "rowLimit": row_limit,
        "dataState": "all",
    }
    result = svc.searchanalytics().query(siteUrl=site, body=body).execute()
    rows = []
    for row in result.get("rows", []):
        keys = row.get("keys", [])
        item: dict[str, Any] = {
            "clicks": int(row.get("clicks", 0)),
            "impressions": int(row.get("impressions", 0)),
            "ctr": round(float(row.get("ctr", 0)), 4),
            "position": round(float(row.get("position", 0)), 2),
        }
        for i, dim in enumerate(dimensions):
            item[dim] = keys[i] if i < len(keys) else ""
        rows.append(item)
    return rows


def gsc_totals(rows: list[dict]) -> dict:
    return {
        "clicks": sum(r["clicks"] for r in rows),
        "impressions": sum(r["impressions"] for r in rows),
        "row_count": len(rows),
    }


def ga4_run(client, property_id: str, request: RunReportRequest) -> Any:
    return client.run_report(request)


def ga4_organic_summary(client, property_id: str, start: str, end: str) -> dict:
    prop = f"properties/{property_id}"
    report = ga4_run(
        client,
        property_id,
        RunReportRequest(
            property=prop,
            date_ranges=[DateRange(start_date=start, end_date=end)],
            metrics=[
                Metric(name="sessions"),
                Metric(name="totalUsers"),
                Metric(name="screenPageViews"),
                Metric(name="averageSessionDuration"),
            ],
            dimension_filter=ORGANIC_FILTER,
        ),
    )
    if not report.rows:
        return {"sessions": 0, "users": 0, "pageviews": 0, "avg_session_duration_sec": 0.0}
    row = report.rows[0]
    return {
        "sessions": int(row.metric_values[0].value or 0),
        "users": int(row.metric_values[1].value or 0),
        "pageviews": int(row.metric_values[2].value or 0),
        "avg_session_duration_sec": round(float(row.metric_values[3].value or 0), 1),
    }


def ga4_landing_pages(client, property_id: str, start: str, end: str, limit: int = 25) -> list[dict]:
    prop = f"properties/{property_id}"
    report = ga4_run(
        client,
        property_id,
        RunReportRequest(
            property=prop,
            date_ranges=[DateRange(start_date=start, end_date=end)],
            dimensions=[Dimension(name="landingPagePlusQueryString")],
            metrics=[Metric(name="sessions"), Metric(name="totalUsers")],
            dimension_filter=ORGANIC_FILTER,
            limit=limit,
        ),
    )
    pages = []
    for row in report.rows:
        pages.append(
            {
                "landing_page": row.dimension_values[0].value,
                "sessions": int(row.metric_values[0].value or 0),
                "users": int(row.metric_values[1].value or 0),
            }
        )
    return pages


def ga4_conversions(client, property_id: str, start: str, end: str) -> dict:
    prop = f"properties/{property_id}"
    for metric in ("ecommercePurchases", "keyEvents"):
        try:
            report = ga4_run(
                client,
                property_id,
                RunReportRequest(
                    property=prop,
                    date_ranges=[DateRange(start_date=start, end_date=end)],
                    metrics=[Metric(name=metric)],
                    dimension_filter=ORGANIC_FILTER,
                ),
            )
            value = int(report.rows[0].metric_values[0].value or 0) if report.rows else 0
            return {"metric": metric, "value": value, "status": "ok"}
        except Exception as exc:
            last_error = str(exc)
    return {"metric": None, "value": None, "status": "unavailable", "note": last_error}


def build_opportunities(queries: list[dict]) -> dict:
    near_first_page = []
    no_clicks = []
    for row in queries:
        if row["impressions"] < 5:
            continue
        if 4 <= row["position"] <= 15:
            near_first_page.append(
                {
                    "query": row["query"],
                    "clicks": row["clicks"],
                    "impressions": row["impressions"],
                    "position": row["position"],
                }
            )
        if row["clicks"] == 0 and row["impressions"] >= 10:
            no_clicks.append(
                {
                    "query": row["query"],
                    "impressions": row["impressions"],
                    "position": row["position"],
                }
            )
    near_first_page.sort(key=lambda r: r["position"])
    no_clicks.sort(key=lambda r: r["impressions"], reverse=True)
    return {
        "near_first_page": near_first_page[:25],
        "high_impressions_no_clicks": no_clicks[:25],
    }


def pct_change(current: float, previous: float) -> float | None:
    if previous == 0:
        return None if current == 0 else 100.0
    return round(((current - previous) / previous) * 100, 1)


def fetch(days: int) -> dict:
    key_path = env("GOOGLE_APPLICATION_CREDENTIALS")
    site = env("GSC_SITE_URL", "https://kosarticaret.com/")
    ga4_property = env("GA4_PROPERTY_ID").replace("properties/", "")

    end = period_end()
    start = period_start(days)
    prev_start, prev_end = previous_period(start, end)

    start_s = start.isoformat()
    end_s = end.isoformat()
    prev_start_s = prev_start.isoformat()
    prev_end_s = prev_end.isoformat()

    meta = json.load(open(key_path, encoding="utf-8"))

    gsc_creds = load_credentials(GSC_SCOPES)
    gsc = build("searchconsole", "v1", credentials=gsc_creds, cache_discovery=False)

    sites = gsc.sites().list().execute().get("siteEntry", [])
    site_urls = [s.get("siteUrl") for s in sites]
    if site not in site_urls:
        site = site_urls[0] if site_urls else site

    queries = gsc_query(gsc, site, start_s, end_s, ["query"], 25000)
    queries.sort(key=lambda r: r["clicks"], reverse=True)

    pages = gsc_query(gsc, site, start_s, end_s, ["page"], 25000)
    pages.sort(key=lambda r: r["clicks"], reverse=True)

    devices = gsc_query(gsc, site, start_s, end_s, ["device"], 10)
    devices.sort(key=lambda r: r["clicks"], reverse=True)

    daily = gsc_query(gsc, site, start_s, end_s, ["date"], 400)
    daily.sort(key=lambda r: r["date"])

    countries = gsc_query(gsc, site, start_s, end_s, ["country"], 250)
    countries.sort(key=lambda r: r["clicks"], reverse=True)

    prev_queries = gsc_query(gsc, site, prev_start_s, prev_end_s, ["query"], 25000)
    current_totals = gsc_site_totals(gsc, site, start_s, end_s)
    previous_totals = gsc_site_totals(gsc, site, prev_start_s, prev_end_s)
    current_totals["row_count"] = len(queries)
    previous_totals["row_count"] = len(prev_queries)

    sitemaps = []
    try:
        sm = gsc.sitemaps().list(siteUrl=site).execute()
        for item in sm.get("sitemap", []):
            contents = {}
            for c in item.get("contents", []):
                contents[c.get("type", "unknown")] = {
                    "submitted": c.get("submitted"),
                    "indexed": c.get("indexed"),
                }
            sitemaps.append(
                {
                    "path": item.get("path"),
                    "last_submitted": item.get("lastSubmitted"),
                    "last_downloaded": item.get("lastDownloaded"),
                    "errors": item.get("errors"),
                    "warnings": item.get("warnings"),
                    "is_pending": item.get("isPending"),
                    "contents": contents,
                }
            )
    except HttpError as exc:
        sitemaps = [{"error": f"{exc.resp.status} {exc._get_reason()}"}]

    ga4_creds = load_credentials(GA4_SCOPES)
    ga4_client = BetaAnalyticsDataClient(credentials=ga4_creds)

    organic = ga4_organic_summary(ga4_client, ga4_property, start_s, end_s)
    organic_prev = ga4_organic_summary(ga4_client, ga4_property, prev_start_s, prev_end_s)
    landing_pages = ga4_landing_pages(ga4_client, ga4_property, start_s, end_s, 25)
    conversions = ga4_conversions(ga4_client, ga4_property, start_s, end_s)

    return {
        "generated_at": date.today().isoformat(),
        "service_account": meta.get("client_email"),
        "gcp_project": meta.get("project_id"),
        "period": {
            "days": days,
            "start": start_s,
            "end": end_s,
        },
        "comparison_period": {
            "start": prev_start_s,
            "end": prev_end_s,
        },
        "gsc": {
            "site_url": site,
            "available_sites": site_urls,
            "totals": current_totals,
            "comparison_totals": previous_totals,
            "change_pct": {
                "clicks": pct_change(current_totals["clicks"], previous_totals["clicks"]),
                "impressions": pct_change(current_totals["impressions"], previous_totals["impressions"]),
            },
            "top_queries": queries[:50],
            "top_pages": pages[:50],
            "devices": devices,
            "daily": daily,
            "countries_top10": countries[:10],
            "sitemaps": sitemaps,
            "opportunities": build_opportunities(queries),
            "api_limits_note": "GSC API does not expose sitewide index coverage totals; use sitemap stats + manual GSC UI for index/export screenshots.",
        },
        "ga4": {
            "property_id": ga4_property,
            "organic": organic,
            "organic_comparison": organic_prev,
            "organic_change_pct": {
                "sessions": pct_change(organic["sessions"], organic_prev["sessions"]),
                "users": pct_change(organic["users"], organic_prev["users"]),
            },
            "landing_pages": landing_pages,
            "conversions": conversions,
        },
        "manual_checks": [
            "GSC → Guvenlik ve Manuel Islemler → Manuel islemler (temiz mi?)",
            "GSC → Guvenlik ve Manuel Islemler → Guvenlik sorunlari",
            "GSC → Deneyim → Temel Web Verileri (mobil/masaustu ozet ekran goruntusu)",
            "GSC → Dizine ekleme → Sayfalar (index/haric export — API kapsam disi)",
        ],
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--days", type=int, default=int(os.environ.get("SEO_MONTHLY_PERIOD_DAYS", "90")))
    args = parser.parse_args()
    payload = fetch(args.days)
    json.dump(payload, sys.stdout, ensure_ascii=False, indent=2)
    sys.stdout.write("\n")


if __name__ == "__main__":
    main()
