#!/usr/bin/env python3
"""Add Bay Area to SEO titles that mention Installation but lack Bay Area."""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
META = ROOT / "database" / "data" / "page-metadata"
APPLY = ROOT / "scripts" / "apply_seo_from_research.py"

SUFFIX_OLD = "| Replacement & Installation"
SUFFIX_NEW = "| Bay Area Replacement & Installation"
SUFFIX_SHORT = "| Bay Area Installation"


def collect_titles() -> dict[str, str]:
    titles: dict[str, str] = {}
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        title = (data.get("seo") or {}).get("title") or ""
        page = data.get("path") or str(path)
        if title:
            titles[title.casefold()] = page
    return titles


def claim(titles: dict[str, str], page: str, candidates: list[str]) -> str | None:
    for candidate in candidates:
        if len(candidate) > 60:
            continue
        key = candidate.casefold()
        owner = titles.get(key)
        if owner is None or owner == page:
            titles[key] = page
            return candidate
    return None


def rewrite_title(title: str, page: str, titles: dict[str, str]) -> str | None:
    if not re.search(r"installation", title, re.I):
        return None
    if re.search(r"bay area", title, re.I):
        return None

    candidates: list[str] = []

    if title.endswith(SUFFIX_OLD):
        base = title[: -len(SUFFIX_OLD)].rstrip()
        candidates.extend(
            [
                f"{base} {SUFFIX_NEW}".replace("  ", " "),
                f"{base} {SUFFIX_SHORT}".replace("  ", " "),
                f"{base} | Bay Area",
            ]
        )
        # Progressive shortening of left side
        while " " in base and len(base) > 12:
            base = base.rsplit(" ", 1)[0]
            candidates.append(f"{base} {SUFFIX_SHORT}".replace("  ", " "))
            candidates.append(f"{base} | Bay Area")
    elif page == "/about":
        candidates = [
            "About Us | Bay Area Replacement & Installation",
            "About Us | Bay Area Window & Door Installation",
            "About Us | Bay Area Installation Experts",
        ]
    elif page == "/doors":
        candidates = [
            "Door Replacement & Installation Bay Area | Cost",
            "Door Replacement & Installation | Bay Area",
            "Doors Bay Area | Replacement & Installation",
        ]
    elif page == "/gallery":
        candidates = [
            "Window & Door Installation Gallery | Bay Area",
            "Installation Gallery | Bay Area Projects",
            "Bay Area Window & Door Installation Gallery",
        ]
    elif page == "/faq":
        candidates = [
            "Window FAQ | Bay Area Cost, Permits & Installation",
            "Window Replacement FAQ | Bay Area Installation",
            "FAQ | Bay Area Window Installation",
        ]
    else:
        # Generic: try insert Bay Area before Installation
        candidates = [
            re.sub(r"\bInstallation\b", "Bay Area Installation", title, count=1),
            re.sub(
                r"Replacement & Installation",
                "Bay Area Replacement & Installation",
                title,
                count=1,
            ),
            f"{title} | Bay Area" if len(title) + 11 <= 60 else title[: 60 - 11].rstrip(" |") + " | Bay Area",
        ]

    old_key = title.casefold()
    if titles.get(old_key) == page:
        del titles[old_key]

    return claim(titles, page, candidates)


def sync_social(seo: dict, old_title: str, new_title: str) -> None:
    for block_name in ("og", "twitter"):
        block = seo.get(block_name)
        if not isinstance(block, dict):
            continue
        current = block.get("title") or ""
        if current in ("", old_title) or (
            re.search(r"installation", current, re.I)
            and not re.search(r"bay area", current, re.I)
        ):
            block["title"] = new_title


def patch_apply_script() -> None:
    text = APPLY.read_text(encoding="utf-8")
    pairs = [
        (
            'f"{entity} | Replacement & Installation"',
            'f"{entity} | Bay Area Replacement & Installation"',
        ),
        (
            'f"{compact} | Replacement & Installation"',
            'f"{compact} | Bay Area Replacement & Installation"',
        ),
        (
            'f"{entity} Door Replacement & Installation | Entry & Patio"',
            'f"{entity} Door Replacement & Installation Bay Area"',
        ),
        (
            'f"{entity} Door Replacement & Installation | Cost"',
            'f"{entity} Door Replacement & Installation | Bay Area"',
        ),
        (
            '"Entry & Patio Door Replacement & Installation | Cost"',
            '"Door Replacement & Installation Bay Area | Cost"',
        ),
        (
            '"Window Replacement FAQ | Cost, Permits & Installation"',
            '"Window FAQ | Bay Area Cost, Permits & Installation"',
        ),
        (
            '"About Us Window & Door Replacement & Installation Experts"',
            '"About Us | Bay Area Replacement & Installation"',
        ),
        (
            '"Window & Door Replacement & Installation Gallery Projects"',
            '"Window & Door Installation Gallery | Bay Area"',
        ),
        (
            '"About Us | Bay Area Window & Door Replacement & Installation Experts"',
            '"About Us | Bay Area Replacement & Installation"',
        ),
        (
            '"Window & Door Replacement & Installation Gallery | Bay Area Projects"',
            '"Window & Door Installation Gallery | Bay Area"',
        ),
    ]
    n = 0
    for old, new in pairs:
        if old in text:
            text = text.replace(old, new)
            n += 1
    APPLY.write_text(text, encoding="utf-8")
    print(f"apply_seo patches: {n}")


def main() -> int:
    titles = collect_titles()
    changed: list[tuple[str, str, str]] = []

    for path in sorted(META.rglob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        old = seo.get("title") or ""
        page = data.get("path") or str(path)
        new = rewrite_title(old, page, titles)
        if not new or new == old:
            continue
        seo["title"] = new
        sync_social(seo, old, new)
        data["seo"] = seo
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        changed.append((page, old, new))

    patch_apply_script()

    # validate
    over = []
    dups: dict[str, list[str]] = {}
    missing_ba = []
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        title = seo.get("title") or ""
        page = data.get("path") or str(path)
        if len(title) > 60:
            over.append((page, len(title), title))
        dups.setdefault(title.casefold(), []).append(page)
        if re.search(r"installation", title, re.I) and not re.search(r"bay area", title, re.I):
            missing_ba.append((page, title))

    real_dups = {k: v for k, v in dups.items() if len(v) > 1 and k}
    if over or real_dups or missing_ba:
        print("VALIDATION ISSUES")
        print("over", over[:10])
        print("dups", list(real_dups.items())[:5])
        print("missing_ba", missing_ba[:10])
        return 1

    print(f"changed={len(changed)}")
    for page, old, new in changed[:15]:
        print(f"  {page}\n    {old}\n    -> {new}")
    if len(changed) > 15:
        print(f"  … +{len(changed) - 15} more")

    sys.path.insert(0, str(ROOT / "scripts"))
    from update_titles_replacement_installation import rebuild_canvas_groups

    rebuild_canvas_groups()
    for script in (
        "export_seo_rework_report_html.py",
        "export_faq_report_html.py",
        "export_faq_sources_report_html.py",
    ):
        subprocess.check_call([sys.executable, str(ROOT / "scripts" / script)])
    print("reports ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
