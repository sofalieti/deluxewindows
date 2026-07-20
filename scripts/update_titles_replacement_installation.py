#!/usr/bin/env python3
"""Update SEO titles: Installation → Replacement & Installation (sense-aware, ≤60, unique).

Also sync og/twitter titles when they mirrored the old title or still say Installation-only,
update apply_seo_from_research.py templates, rebuild canvas GROUPS, regenerate HTML reports.
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
METADATA = ROOT / "database" / "data" / "page-metadata"
CANVAS = Path(
    r"C:\Users\archi\.cursor\projects\d-Projects-deluxe-windows-new"
    r"\canvases\seo-rework-report.canvas.tsx"
)
APPLY_SCRIPT = ROOT / "scripts" / "apply_seo_from_research.py"

FAMILY_ORDER = [
    ("static/home.json", "home", "Главная"),
    ("windows/", "windows", "Материалы окон"),
    ("doors/", "doors", "Материалы дверей"),
    ("brands/", "brands", "Бренды окон"),
    ("door-brands/", "door-brands", "Бренды дверей"),
    ("window-type/", "window-type", "Бренд + материал (окна)"),
    ("door-types/", "door-types", "Бренд + материал (двери)"),
    ("brand-collections/", "brand-collections", "Серии / коллекции"),
    ("window-replacement/", "window-replacement", "Города (service areas)"),
    ("county-hub-pages/", "county-hub-pages", "Округа"),
    ("blog/", "blog", "Блог"),
    ("static/", "static", "Служебные / статика"),
]


def has_replacement_and_installation_phrase(title: str) -> bool:
    return bool(
        re.search(
            r"replacement\s*&\s*installation|replacement and installation",
            title,
            re.I,
        )
    )


def has_replacement_word(title: str) -> bool:
    return bool(re.search(r"\breplacement\b", title, re.I))


def transform_title(title: str) -> str:
    """Only rewrite titles that say Installation without Replacement."""
    if not re.search(r"\binstallation\b", title, re.I):
        return title
    if has_replacement_and_installation_phrase(title):
        return title
    if has_replacement_word(title):
        # Already signals replacement + installation separately — leave as-is.
        return title

    t = title
    rules = [
        (r"Prices & Installation", "Replacement & Installation"),
        (r"\|\s*Installation Cost & Options", "| Replacement & Installation"),
        (r"\|\s*Installation Cost & Series", "| Replacement & Installation"),
        (r"\|\s*Installation Cost\b", "| Replacement & Installation"),
        (r"\|\s*Installation\b", "| Replacement & Installation"),
        (r"Entry & Patio Door Installation Bay Area", "Entry & Patio Door Replacement & Installation"),
        (r"Window & Door Installation Experts", "Window & Door Replacement & Installation Experts"),
        (r"Window & Door Installation Gallery", "Window & Door Replacement & Installation Gallery"),
        (r"Window Installation Bay Area", "Window Replacement & Installation Bay Area"),
        (r"Door Installation Bay Area", "Door Replacement & Installation Bay Area"),
        (r"Windows Installation\b", "Windows Replacement & Installation"),
        (r"Door Installation\b", "Door Replacement & Installation"),
        (r"Window Installation\b", "Window Replacement & Installation"),
    ]
    for pat, repl in rules:
        nt = re.sub(pat, repl, t, count=1, flags=re.I)
        if nt != t:
            return nt

    return re.sub(r"\bInstallation\b", "Replacement & Installation", t, count=1)


def fit_title(title: str, max_len: int = 60) -> str:
    if len(title) <= max_len:
        return title
    t = title
    shortenings = [
        (r"JELD-WEN Jeld Wen ", "JELD-WEN "),
        (r"Western Window Systems ", "Western Windows "),
        (r"All Weather Architectural ", "All Weather "),
        (r"All Weather Aluminum ", "All Weather "),
        (r" Thermally Improved", ""),
        (r" Quality Vinyl Windows", " Vinyl"),
        (r" Series Vinyl Windows", " Series"),
        (r" Vinyl Windows", " Vinyl"),
        (r" Clad Wood Windows", " Clad Wood"),
        (r" \| Patio & Entry", ""),
        (r" \| Entry & Patio", ""),
        (r", CA", ""),
        (r" Bay Area$", ""),
        (r" \| Bay Area", ""),
        (r"Window Replacement & Installation Bay Area", "Window Replacement & Installation"),
        (r"Door Replacement & Installation Bay Area", "Door Replacement & Installation"),
        (r"Replacement & Installation Bay Area", "Replacement & Installation"),
    ]
    for pat, repl in shortenings:
        if len(t) <= max_len:
            break
        t = re.sub(pat, repl, t)
    if len(t) > max_len and " | " in t:
        left, right = t.rsplit(" | ", 1)
        budget = max_len - len(right) - 3
        if budget >= 12:
            t = left[:budget].rstrip(" -|,") + " | " + right
        else:
            t = t[:max_len].rstrip()
    elif len(t) > max_len:
        t = t[:max_len].rstrip()
    return t


def ensure_unique(title: str, used: set[str], path: str) -> str:
    key = title.casefold()
    if key not in used:
        used.add(key)
        return title
    # Disambiguate with short path token while staying ≤60.
    token = path.strip("/").split("/")[-1].replace("-", " ")[:12].title()
    base = title
    for n in range(1, 20):
        suffix = f" | {token}" if n == 1 else f" | {token} {n}"
        budget = 60 - len(suffix)
        candidate = (base[:budget].rstrip(" -|,") + suffix) if len(base) + len(suffix) > 60 else base + suffix
        candidate = candidate[:60].rstrip()
        if candidate.casefold() not in used:
            used.add(candidate.casefold())
            return candidate
    used.add(key)
    return title


def update_metadata() -> tuple[int, list[tuple[str, str, str]]]:
    files = sorted(METADATA.rglob("*.json"))
    # First pass: collect current titles for uniqueness pool
    used: set[str] = set()
    records: list[tuple[Path, dict]] = []
    for path in files:
        data = json.loads(path.read_text(encoding="utf-8"))
        records.append((path, data))
        title = (data.get("seo") or {}).get("title") or ""
        if title:
            used.add(title.casefold())

    changes: list[tuple[str, str, str]] = []
    changed_count = 0

    for path, data in records:
        seo = data.get("seo")
        if not isinstance(seo, dict):
            continue
        old = str(seo.get("title") or "")
        if not old:
            continue
        new = fit_title(transform_title(old))
        if new == old:
            continue

        # Free old title from pool, claim new
        used.discard(old.casefold())
        new = ensure_unique(new, used, str(data.get("path") or path.stem))

        seo["title"] = new
        for nest in ("og", "twitter"):
            block = seo.get(nest)
            if not isinstance(block, dict):
                continue
            nested_title = str(block.get("title") or "")
            if nested_title == old or (
                re.search(r"\binstallation\b", nested_title, re.I)
                and not has_replacement_word(nested_title)
            ):
                block["title"] = new if nested_title == old else fit_title(transform_title(nested_title))

        path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        changed_count += 1
        changes.append((str(data.get("path") or path), old, new))

    return changed_count, changes


def patch_apply_script() -> int:
    text = APPLY_SCRIPT.read_text(encoding="utf-8")
    pairs = [
        (
            '"Entry & Patio Door Installation Bay Area | Cost"',
            '"Entry & Patio Door Replacement & Installation | Cost"',
        ),
        (
            '"About Us | Bay Area Window & Door Installation Experts"',
            '"About Us | Bay Area Window & Door Replacement & Installation Experts"',
        ),
        (
            '"Window & Door Installation Gallery | Bay Area Projects"',
            '"Window & Door Replacement & Installation Gallery | Bay Area Projects"',
        ),
        (
            'f"{brand} Window Installation Bay Area"',
            'f"{brand} Window Replacement & Installation Bay Area"',
        ),
        (
            'f"{brand} Door Installation Bay Area | Patio & Entry"',
            'f"{brand} Door Replacement & Installation | Patio & Entry"',
        ),
        (
            'f"{brand} Door Installation Bay Area"',
            'f"{brand} Door Replacement & Installation Bay Area"',
        ),
        (
            'f"{entity} Door Installation Bay Area | Entry & Patio"',
            'f"{entity} Door Replacement & Installation | Entry & Patio"',
        ),
        (
            'f"{entity} Door Installation Bay Area | Cost"',
            'f"{entity} Door Replacement & Installation | Cost"',
        ),
        (
            'f"{entity} Doors Bay Area | Installation Cost"',
            'f"{entity} Doors Bay Area | Replacement & Installation"',
        ),
        (
            'f"{entity} | Installation Cost & Series"',
            'f"{entity} | Replacement & Installation"',
        ),
        (
            'f"{entity} | Installation Cost"',
            'f"{entity} | Replacement & Installation"',
        ),
        (
            'f"{entity} | Installation Cost & Options"',
            'f"{entity} | Replacement & Installation"',
        ),
        (
            'f"{entity} | Prices & Installation"',
            'f"{entity} | Replacement & Installation"',
        ),
        (
            'f"{compact} | Prices & Installation"',
            'f"{compact} | Replacement & Installation"',
        ),
    ]
    n = 0
    for old, new in pairs:
        if old in text and old != new:
            text = text.replace(old, new)
            n += 1
    APPLY_SCRIPT.write_text(text, encoding="utf-8")
    return n


def family_for(rel: str) -> tuple[str, str] | None:
    rel = rel.replace("\\", "/")
    if rel == "static/home.json":
        return "home", "Главная"
    if rel == "static/brand-index.json":
        return "brand", "Каталог брендов"
    for prefix, family, label in FAMILY_ORDER:
        if prefix.endswith(".json"):
            if rel == prefix:
                return family, label
            continue
        if rel.startswith(prefix):
            if family == "static" and Path(rel).name == "home.json":
                continue
            if family == "static" and Path(rel).name == "brand-index.json":
                continue
            return family, label
    return None


def rebuild_canvas_groups() -> None:
    groups_map: dict[str, dict] = {}
    for path in sorted(METADATA.rglob("*.json")):
        rel = str(path.relative_to(METADATA)).replace("\\", "/")
        fam = family_for(rel)
        if not fam:
            continue
        family, label = fam
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        row = {
            "path": data.get("path") or "",
            "title": seo.get("title") or "",
            "h1": seo.get("h1") or "",
            "primary": seo.get("primary_keyword") or "",
            "descLen": len(seo.get("description") or ""),
            "titleLen": len(seo.get("title") or ""),
        }
        if family not in groups_map:
            groups_map[family] = {"family": family, "label": label, "count": 0, "rows": []}
        groups_map[family]["rows"].append(row)
        groups_map[family]["count"] += 1

    # Preserve label order from FAMILY_ORDER
    ordered = []
    seen = set()
    for _, family, label in FAMILY_ORDER:
        if family in groups_map and family not in seen:
            g = groups_map[family]
            g["label"] = label
            ordered.append(g)
            seen.add(family)
    for family, g in groups_map.items():
        if family not in seen:
            ordered.append(g)

    src = CANVAS.read_text(encoding="utf-8")
    payload = json.dumps(ordered, ensure_ascii=False, separators=(",", ":"))
    new_src, n = re.subn(
        r"const GROUPS = \[.*?\];",
        f"const GROUPS = {payload};",
        src,
        count=1,
        flags=re.S,
    )
    if n != 1:
        # Canvas uses `] as const;`
        new_src, n = re.subn(
            r"const GROUPS = \[.*?\] as const;",
            f"const GROUPS = {payload} as const;",
            src,
            count=1,
            flags=re.S,
        )
    if n != 1:
        raise SystemExit("Could not rewrite GROUPS in canvas")
    CANVAS.write_text(new_src, encoding="utf-8")


def main() -> None:
    changed, samples = update_metadata()
    patched = patch_apply_script()
    rebuild_canvas_groups()

    # Regenerate HTML reports
    for script in (
        "export_seo_rework_report_html.py",
        "export_faq_report_html.py",
        "export_faq_sources_report_html.py",
    ):
        subprocess.check_call([sys.executable, str(ROOT / "scripts" / script)])

    print(f"titles_updated={changed} apply_templates_patched={patched}")
    print("sample changes:")
    for path, old, new in samples[:20]:
        print(f"  {path}")
        print(f"    - {old}")
        print(f"    + {new} ({len(new)})")

    # Validate
    over = []
    dups = {}
    for path in METADATA.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        title = (data.get("seo") or {}).get("title") or ""
        if len(title) > 60:
            over.append((data.get("path"), len(title), title))
        dups.setdefault(title.casefold(), []).append(data.get("path"))
    dup_list = {k: v for k, v in dups.items() if len(v) > 1 and k}
    print(f"over60={len(over)} duplicate_titles={len(dup_list)}")
    for item in over[:10]:
        print(" OVER", item)
    for k, v in list(dup_list.items())[:5]:
        print(" DUP", v, k[:80])


if __name__ == "__main__":
    main()
