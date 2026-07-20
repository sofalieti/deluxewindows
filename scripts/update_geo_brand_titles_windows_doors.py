#!/usr/bin/env python3
"""Rewrite brand + city + county SEO titles: drop Installation/Replacement from titles.

Cities/counties → "Windows & Doors in …"
Brands → "{Brand} Windows|Doors | Bay Area"
Also sync og/twitter/schema names and apply_seo_from_research.py templates.
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
META = ROOT / "database" / "data" / "page-metadata"
APPLY = ROOT / "scripts" / "apply_seo_from_research.py"
WP = ROOT / "webflow-data" / "current" / "imports" / "window-replacement.json"

SHORT_BRAND = {
    "All Weather Architectural Aluminum": "All Weather",
    "Western Window Systems": "Western Windows",
}


def load_city_county() -> dict[str, tuple[str, str]]:
    data = json.loads(WP.read_text(encoding="utf-8"))
    out: dict[str, tuple[str, str]] = {}
    for item in data["items"]:
        if item.get("isDraft") or item.get("isArchived"):
            continue
        fd = item["fieldData"]
        slug = fd.get("city-slug") or fd.get("slug")
        city = fd.get("city-name") or fd.get("name") or ""
        county = fd.get("county") or ""
        if slug and city:
            out[str(slug)] = (str(city), str(county))
    return out


def collect_titles() -> dict[str, str]:
    titles: dict[str, str] = {}
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        title = (data.get("seo") or {}).get("title") or ""
        page = data.get("path") or str(path)
        if title:
            titles[title.casefold()] = page
    return titles


def claim(titles: dict[str, str], page: str, candidates: list[str]) -> str:
    for candidate in candidates:
        if len(candidate) > 60:
            continue
        key = candidate.casefold()
        owner = titles.get(key)
        if owner is None or owner == page:
            titles[key] = page
            return candidate
    raise RuntimeError(f"No unique ≤60 title for {page}: {candidates}")


def release(titles: dict[str, str], title: str | None, page: str) -> None:
    if not title:
        return
    key = title.casefold()
    if titles.get(key) == page:
        del titles[key]


def sync_social(seo: dict, old_title: str | None, new_title: str) -> None:
    for block_name in ("og", "twitter"):
        block = seo.get(block_name)
        if not isinstance(block, dict):
            continue
        current = block.get("title") or ""
        if current in ("", old_title) or re.search(
            r"replacement|installation", current, re.I
        ):
            block["title"] = new_title


def brand_entity_from_seo(seo: dict, stem: str, kind: str) -> str:
    h1 = seo.get("h1") or ""
    if kind == "windows":
        m = re.match(r"^(.*?) Windows", h1)
        if m:
            return m.group(1).strip()
    else:
        m = re.match(r"^(.*?) Doors", h1)
        if m:
            return m.group(1).strip()
    return stem.replace("-", " ").title()


def update_brands(titles: dict[str, str], family: str, noun: str) -> list[tuple[str, str, str]]:
    changed: list[tuple[str, str, str]] = []
    folder = META / family
    for path in sorted(folder.glob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data["seo"]
        page = data["path"]
        entity = brand_entity_from_seo(seo, path.stem, noun.casefold())
        brand = SHORT_BRAND.get(entity, entity)
        old = seo.get("title")
        release(titles, old, page)
        new_title = claim(
            titles,
            page,
            [
                f"{brand} {noun} | Bay Area",
                f"{brand} {noun} Bay Area",
                f"{brand} {noun} | Deluxe",
                f"{brand} {noun} Bay Area Dealer",
            ],
        )
        if new_title == old:
            continue
        seo["title"] = new_title
        sync_social(seo, old, new_title)
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        changed.append((page, old or "", new_title))
    return changed


def update_cities(titles: dict[str, str], city_county: dict[str, tuple[str, str]]) -> list[tuple[str, str, str]]:
    changed: list[tuple[str, str, str]] = []
    folder = META / "window-replacement"
    for path in sorted(folder.glob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data["seo"]
        page = data["path"]
        slug = path.stem
        city, county = city_county.get(slug, (slug.replace("-", " ").title(), ""))
        location = f"{city}, {county}" if county else city
        h1 = f"Windows & Doors in {location}"
        old = seo.get("title")
        release(titles, old, page)
        new_title = claim(
            titles,
            page,
            [
                h1,
                f"Windows & Doors in {city}",
                f"Windows & Doors | {city}, CA",
                f"Windows & Doors {city} CA",
            ],
        )
        seo["title"] = new_title
        seo["h1"] = h1
        sync_social(seo, old, new_title)
        schema = data.get("schema")
        if isinstance(schema, dict):
            schema_data = schema.get("data")
            if isinstance(schema_data, dict):
                schema_data["name"] = h1
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        changed.append((page, old or "", new_title))
    return changed


def update_counties(titles: dict[str, str]) -> list[tuple[str, str, str]]:
    changed: list[tuple[str, str, str]] = []
    folder = META / "county-hub-pages"
    for path in sorted(folder.glob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data["seo"]
        page = data["path"]
        # entity from existing h1 or slug
        h1_old = seo.get("h1") or ""
        m = re.search(r"in (.+)$", h1_old)
        county = m.group(1).strip() if m else path.stem.replace("-", " ").title()
        h1 = f"Windows & Doors in {county}"
        old = seo.get("title")
        release(titles, old, page)
        new_title = claim(
            titles,
            page,
            [
                h1,
                f"Windows & Doors | {county}",
                f"Windows & Doors {county}",
            ],
        )
        seo["title"] = new_title
        seo["h1"] = h1
        sync_social(seo, old, new_title)
        schema = data.get("schema")
        if isinstance(schema, dict):
            schema_data = schema.get("data")
            if isinstance(schema_data, dict):
                schema_data["name"] = h1
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        changed.append((page, old or "", new_title))
    return changed


def patch_apply_script() -> None:
    text = APPLY.read_text(encoding="utf-8")
    replacements = [
        (
            """        title = pick_title([
            f"{brand} Window Replacement & Installation | Bay Area",
            f"{brand} Windows Bay Area | Replacement & Installation",
            f"{brand} Window Replacement & Installation Bay Area",
        ], titles, path)""",
            """        title = pick_title([
            f"{brand} Windows | Bay Area",
            f"{brand} Windows Bay Area",
            f"{brand} Windows | Deluxe",
        ], titles, path)""",
        ),
        (
            """        title = pick_title([
            f"{brand} Door Replacement & Installation | Patio & Entry",
            f"{brand} Doors Bay Area | Replacement & Installation",
            f"{brand} Door Replacement & Installation Bay Area",
        ], titles, path)""",
            """        title = pick_title([
            f"{brand} Doors | Bay Area",
            f"{brand} Doors Bay Area",
            f"{brand} Doors | Patio & Entry",
        ], titles, path)""",
        ),
        (
            """        title = pick_title([
            f"Window Replacement {city}, CA | Cost & Installation",
            f"Window Replacement {city} CA | Installation",
            f"Window Replacement {city}, CA",
        ], titles, path)
        h1 = f"Window Replacement in {city}, California" """,
            """        county = getattr(ctx, "county_name", "") or ""
        location = f"{city}, {county}" if county else city
        title = pick_title([
            f"Windows & Doors in {location}",
            f"Windows & Doors in {city}",
            f"Windows & Doors | {city}, CA",
        ], titles, path)
        h1 = f"Windows & Doors in {location}" """,
        ),
        (
            """        title = pick_title([
            f"Window Replacement {county} | Installation & Cost",
            f"{county} Window Replacement & Installation",
            f"Window Replacement {county}",
        ], titles, path)
        h1 = f"Window & Door Replacement in {county}" """,
            """        title = pick_title([
            f"Windows & Doors in {county}",
            f"Windows & Doors | {county}",
            f"Windows & Doors {county}",
        ], titles, path)
        h1 = f"Windows & Doors in {county}" """,
        ),
    ]
    for old, new in replacements:
        if old not in text:
            raise RuntimeError(f"apply_seo template block not found:\n{old[:120]}…")
        text = text.replace(old, new)
    APPLY.write_text(text, encoding="utf-8")


def validate(titles_map: dict[str, str]) -> None:
    # rebuild from disk
    seen: dict[str, list[str]] = {}
    over = []
    bad_geo = []
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        title = seo.get("title") or ""
        page = data.get("path") or str(path)
        if len(title) > 60:
            over.append((page, len(title), title))
        seen.setdefault(title.casefold(), []).append(page)
        if page.startswith("/window-replacement/") or page.startswith("/county-hub-pages/") or "/county" in page:
            if re.search(r"installation|replacement", title, re.I):
                bad_geo.append((page, title))
        if page.startswith("/brands/") or page.startswith("/door-brands/"):
            if re.search(r"installation|replacement", title, re.I):
                bad_geo.append((page, title))
    dups = {k: v for k, v in seen.items() if len(v) > 1 and k}
    if over or dups or bad_geo:
        raise RuntimeError(f"validation failed over={over} dups={dups} bad={bad_geo}")


def main() -> int:
    city_county = load_city_county()
    titles = collect_titles()
    changed = []
    changed += update_brands(titles, "brands", "Windows")
    changed += update_brands(titles, "door-brands", "Doors")
    changed += update_cities(titles, city_county)
    changed += update_counties(titles)
    patch_apply_script()
    validate(titles)

    print(f"changed={len(changed)}")
    for page, old, new in changed[:20]:
        print(f"  {page}\n    {old}\n    -> {new}")
    if len(changed) > 20:
        print(f"  … +{len(changed) - 20} more")

    for script in (
        "export_seo_rework_report_html.py",
        "export_faq_report_html.py",
        "export_faq_sources_report_html.py",
    ):
        subprocess.check_call([sys.executable, str(ROOT / "scripts" / script)])
    print("reports regenerated")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
