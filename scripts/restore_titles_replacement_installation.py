#!/usr/bin/env python3
"""Rebuild product SEO titles: short name, Bay Area, Installation, Replacement last."""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
META = ROOT / "database" / "data" / "page-metadata"

MAX_TITLE = 60

# Longest → shortest; Replacement is always the last add-on.
SUFFIXES = (
    "| Bay Area Installation & Replacement",
    "| Bay Area Installation Replacement",  # no "&" saves 2 chars
    "| Bay Area Installation",
    "| Bay Area",
)

FILLERS = {
    "collection",
    "series",
    "systems",
    "system",
    "style",
    "line",
    "premium",
    "ultra",
    "tm",
    "plus",
    "improved",
    "thermally",
    "architectural",
    "efficient",
    "energy",
    "affordable",
    "true",
    "quality",
    "value",
    "west",
    "coast",
    "hybrid",
    "builders",
    "homemaker",
    "atlantic",
    "custom",
}

PRODUCT_WORDS = {"windows", "window", "doors", "door"}
MATERIALS = {
    "vinyl",
    "wood",
    "clad",
    "aluminum",
    "aluminium",
    "fiberglass",
    "steel",
    "hybrid",
}


def clean_name(name: str) -> str:
    name = re.sub(r"\bJELD-WEN\s+Jeld\s+Wen\b", "JELD-WEN", name, flags=re.I)
    name = re.sub(r"\bJeld\s+Wen\b", "JELD-WEN", name, flags=re.I)
    name = re.sub(r"\bJELD-WEN(?:\s+JELD-WEN)+\b", "JELD-WEN", name, flags=re.I)
    return re.sub(r"\s+", " ", name).strip()


def bare_token(token: str) -> str:
    return re.sub(r"[^a-z0-9]+", "", token.casefold())


def base_variants(name: str) -> list[str]:
    words = [w for w in clean_name(name).split() if w]
    variants: list[str] = []
    seen: set[str] = set()

    def add(parts: list[str]) -> None:
        text = re.sub(r"\s+", " ", " ".join(parts)).strip(" -|,")
        if text and text.casefold() not in seen:
            seen.add(text.casefold())
            variants.append(text)

    add(words)

    current = words[:]
    progress = True
    while progress and len(current) > 1:
        progress = False
        for i in range(len(current) - 1, 0, -1):
            token = current[i]
            if any(ch.isdigit() for ch in token):
                continue
            bare = bare_token(token)
            if bare in MATERIALS:
                continue
            if bare not in FILLERS and bare not in PRODUCT_WORDS:
                continue
            current = current[:i] + current[i + 1 :]
            add(current)
            progress = True
            break

    for variant in list(variants):
        parts = variant.split()
        if len(parts) > 2 and bare_token(parts[-1]) in PRODUCT_WORDS:
            add(parts[:-1])
        if len(parts) > 2 and bare_token(parts[-1]) in MATERIALS:
            add(parts[:-1])

    digit_words = [w for w in words if any(ch.isdigit() for ch in w)]
    if digit_words:
        add([words[0], digit_words[-1]])
        if len(words) > 1 and bare_token(words[1]) not in FILLERS | PRODUCT_WORDS:
            add([words[0], words[1], digit_words[-1]])

    return variants


def enrich_bases(name: str, bases: list[str], page: str) -> list[str]:
    out: list[str] = []
    seen: set[str] = set()

    def add(text: str) -> None:
        text = re.sub(r"\s+", " ", text).strip()
        if text and text.casefold() not in seen:
            seen.add(text.casefold())
            out.append(text)

    want_doors = page.startswith("/door-types/")
    want_windows = page.startswith("/window-type")

    if want_doors or want_windows:
        # Prefer keeping product word; try plural then singular for fit.
        for base in bases:
            lower = base.casefold()
            if want_doors and "door" not in lower:
                add(f"{base} Doors")
                add(f"{base} Door")
            if want_windows and "window" not in lower:
                add(f"{base} Windows")
                add(f"{base} Window")
        for base in bases:
            lower = base.casefold()
            if want_doors and lower.endswith(" doors"):
                add(base[:-1])  # Door
            if want_windows and lower.endswith(" windows"):
                add(base[:-1])  # Window
            add(base)
        return out

    for base in bases:
        add(base)
    return out


def build_candidates(name: str, page: str = "") -> list[str]:
    bases = enrich_bases(name, base_variants(name), page)
    out: list[str] = []
    # Prefer fuller suffix first; within each suffix, try longer then shorter names.
    for suffix in SUFFIXES:
        for base in bases:
            out.append(f"{base} {suffix}")

    seen: set[str] = set()
    unique: list[str] = []
    for item in out:
        key = item.casefold()
        if key in seen:
            continue
        seen.add(key)
        unique.append(item)
    return unique


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
        candidate = re.sub(r"\s+", " ", candidate).strip()
        if not candidate or len(candidate) > MAX_TITLE:
            continue
        key = candidate.casefold()
        owner = titles.get(key)
        if owner is None or owner == page:
            titles[key] = page
            return candidate
    return None


def needs_rebuild(page: str, seo: dict) -> bool:
    h1 = (seo.get("h1") or "").strip()
    if not h1:
        return False
    return page.startswith(
        ("/brand-collections/", "/door-types/", "/window-type/", "/window-types/")
    )


def sync_social(seo: dict, old_title: str, new_title: str) -> None:
    for block_name in ("og", "twitter"):
        block = seo.get(block_name)
        if not isinstance(block, dict):
            continue
        current = block.get("title") or ""
        if current in ("", old_title) or re.search(r"installation|replacement|bay area", current, re.I):
            block["title"] = new_title


def main() -> int:
    titles = collect_titles()
    changed: list[tuple[str, str, str, int]] = []
    failed: list[tuple[str, str, str]] = []

    for path in sorted(META.rglob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        page = data.get("path") or str(path)
        if not needs_rebuild(page, seo):
            continue

        old = seo.get("title") or ""
        h1 = (seo.get("h1") or "").strip()

        old_key = old.casefold()
        if titles.get(old_key) == page:
            del titles[old_key]

        new = claim(titles, page, build_candidates(h1, page))
        if not new:
            failed.append((page, h1, old))
            titles[old_key] = page
            continue

        if new == old:
            titles[new.casefold()] = page
            continue

        seo["title"] = new
        sync_social(seo, old, new)
        data["seo"] = seo
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        changed.append((page, old, new, len(new)))

    over = []
    dups: dict[str, list[str]] = {}
    bad = []
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        title = seo.get("title") or ""
        page = data.get("path") or str(path)
        if len(title) > MAX_TITLE:
            over.append((page, len(title), title))
        dups.setdefault(title.casefold(), []).append(page)
        if needs_rebuild(page, seo):
            if not re.search(r"bay area", title, re.I):
                bad.append((page, title, "missing Bay Area"))
            elif not re.search(r"installation", title, re.I):
                # allowed only if name+Bay Area already maxed uniqueness
                pass

    real_dups = {k: v for k, v in dups.items() if len(v) > 1 and k}
    print(f"changed={len(changed)} failed={len(failed)} over={len(over)} bad={len(bad)} dups={len(real_dups)}")
    for page, old, new, length in changed[:25]:
        print(f"  {page} ({length})\n    {old}\n    -> {new}")
    if len(changed) > 25:
        print(f"  … +{len(changed) - 25} more")
    if failed:
        print("FAILED", failed[:10])
    if over or real_dups:
        print("OVER", over[:5])
        print("DUPS", list(real_dups.items())[:3])
        return 1

    # Sample suffix distribution
    from collections import Counter

    c = Counter()
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        page = data.get("path") or ""
        if not page.startswith(("/brand-collections/", "/door-types/", "/window-type/")):
            continue
        t = (data.get("seo") or {}).get("title") or ""
        if t.endswith("| Bay Area Installation & Replacement"):
            c["full"] += 1
        elif t.endswith("| Bay Area Installation Replacement"):
            c["full_no_amp"] += 1
        elif t.endswith("| Bay Area Installation"):
            c["install"] += 1
        elif t.endswith("| Bay Area"):
            c["bay"] += 1
        else:
            c["other"] += 1
    print("suffix_counts", dict(c))

    sys.path.insert(0, str(ROOT / "scripts"))
    try:
        from update_titles_replacement_installation import rebuild_canvas_groups

        rebuild_canvas_groups()
        print("canvas ok")
    except Exception as exc:  # noqa: BLE001
        print("canvas skip:", exc)

    for name in (
        "export_seo_rework_report_html.py",
        "export_faq_report_html.py",
        "export_faq_sources_report_html.py",
    ):
        script = ROOT / "scripts" / name
        if script.exists():
            subprocess.check_call([sys.executable, str(script)])
    print("reports ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
