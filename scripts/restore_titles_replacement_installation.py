#!/usr/bin/env python3
"""Rebuild Installation/Replacement titles from H1 with Bay Area, max 60 chars."""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
META = ROOT / "database" / "data" / "page-metadata"

SUFFIX_FULL = "| Bay Area Replacement & Installation"
SUFFIX_SWAP = "| Bay Area Installation & Replacement"
SUFFIX_BA_END = "| Installation & Replacement Bay Area"
SUFFIX_PLAIN = "| Installation & Replacement"

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


def collect_titles() -> dict[str, str]:
    titles: dict[str, str] = {}
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        title = (data.get("seo") or {}).get("title") or ""
        page = data.get("path") or str(path)
        if title:
            titles[title.casefold()] = page
    return titles


MAX_TITLE = 65


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


def clean_name(name: str) -> str:
    name = re.sub(r"\bJELD-WEN\s+Jeld\s+Wen\b", "JELD-WEN", name, flags=re.I)
    name = re.sub(r"\bJeld\s+Wen\b", "JELD-WEN", name, flags=re.I)
    name = re.sub(r"\bJELD-WEN(?:\s+JELD-WEN)+\b", "JELD-WEN", name, flags=re.I)
    return re.sub(r"\s+", " ", name).strip()


def bare_token(token: str) -> str:
    return re.sub(r"[^a-z0-9]+", "", token.casefold())


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


def enrich_bases(name: str, bases: list[str], page: str) -> list[str]:
    """Prefer bases that keep Windows/Doors on type pages."""
    out: list[str] = []
    seen: set[str] = set()

    def add(text: str) -> None:
        text = re.sub(r"\s+", " ", text).strip()
        if text and text.casefold() not in seen:
            seen.add(text.casefold())
            out.append(text)

    want_doors = page.startswith("/door-types/")
    want_windows = page.startswith("/window-type")

    # Product-marked bases first (so claim keeps Windows vs Doors distinct)
    if want_doors or want_windows:
        for base in bases:
            lower = base.casefold()
            if want_doors and "door" not in lower:
                add(f"{base} Doors")
            if want_windows and "window" not in lower:
                add(f"{base} Windows")
        for base in bases:
            add(base)
        return out

    for base in bases:
        add(base)
    return out


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

    # Free space for Bay Area suffix: drop trailing material if needed
    for variant in list(variants):
        parts = variant.split()
        if len(parts) > 2 and bare_token(parts[-1]) in MATERIALS:
            add(parts[:-1])

    digit_words = [w for w in words if any(ch.isdigit() for ch in w)]
    if digit_words:
        add([words[0], digit_words[-1]])
        if len(words) > 1 and bare_token(words[1]) not in FILLERS | PRODUCT_WORDS:
            add([words[0], words[1], digit_words[-1]])

    return variants


def build_candidates(name: str, page: str = "") -> list[str]:
    bases = enrich_bases(name, base_variants(name), page)
    out: list[str] = []

    for suffix in (SUFFIX_FULL, SUFFIX_SWAP, SUFFIX_BA_END):
        for base in bases:
            out.append(f"{base} {suffix}")
    for base in bases:
        out.append(f"{base} Bay Area {SUFFIX_PLAIN}")
    for base in bases:
        out.append(f"{base} {SUFFIX_PLAIN}")

    seen: set[str] = set()
    unique: list[str] = []
    for item in out:
        key = item.casefold()
        if key in seen:
            continue
        seen.add(key)
        unique.append(item)
    return unique


def needs_restore(seo: dict, page: str) -> bool:
    title = seo.get("title") or ""
    h1 = (seo.get("h1") or "").strip()
    if not h1:
        return False
    if not (
        page.startswith("/brand-collections/")
        or page.startswith("/door-types/")
        or page.startswith("/window-type/")
        or page.startswith("/window-types/")
    ):
        return False
    # Always rebuild these product pages to keep R&I + Bay Area consistent
    return True


def sync_social(seo: dict, old_title: str, new_title: str) -> None:
    for block_name in ("og", "twitter"):
        block = seo.get(block_name)
        if not isinstance(block, dict):
            continue
        current = block.get("title") or ""
        if current in ("", old_title) or re.search(r"installation|replacement", current, re.I):
            block["title"] = new_title


def main() -> int:
    titles = collect_titles()
    changed: list[tuple[str, str, str, int]] = []
    failed: list[tuple[str, str, str]] = []

    for path in sorted(META.rglob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        page = data.get("path") or str(path)
        if not needs_restore(seo, page):
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
    no_ri = []
    for path in META.rglob("*.json"):
        data = json.loads(path.read_text(encoding="utf-8"))
        seo = data.get("seo") or {}
        title = seo.get("title") or ""
        page = data.get("path") or str(path)
        if len(title) > MAX_TITLE:
            over.append((page, len(title), title))
        dups.setdefault(title.casefold(), []).append(page)
        if page.startswith(("/brand-collections/", "/door-types/", "/window-type/")):
            if not (
                re.search(r"replacement", title, re.I)
                and re.search(r"installation", title, re.I)
                and re.search(r"bay area", title, re.I)
            ):
                no_ri.append((page, title))

    real_dups = {k: v for k, v in dups.items() if len(v) > 1 and k}
    print(f"changed={len(changed)} failed={len(failed)} over={len(over)} no_ri={len(no_ri)} dups={len(real_dups)}")
    for page, old, new, length in changed[:30]:
        print(f"  {page} ({length})\n    {old}\n    -> {new}")
    if len(changed) > 30:
        print(f"  … +{len(changed) - 30} more")
    if failed:
        print("FAILED:")
        for row in failed[:20]:
            print(" ", row)
    if over or real_dups or no_ri:
        print("OVER", over[:5])
        print("DUPS", list(real_dups.items())[:3])
        print("NO_RI", no_ri[:5])
        return 1

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
