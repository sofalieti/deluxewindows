#!/usr/bin/env python3
"""Export seo-rework-report.canvas.tsx → polished interactive HTML report."""

from __future__ import annotations

import html
import json
import re
from collections import defaultdict
from pathlib import Path

CANVAS = Path(
    r"C:\Users\archi\.cursor\projects\d-Projects-deluxe-windows-new"
    r"\canvases\seo-rework-report.canvas.tsx"
)
OUT = Path(r"D:\Projects\deluxe windows new\SEO_REWORK_REPORT.html")


def load_json_const(src: str, name: str):
    m = re.search(rf"const {name} = (\[.*?\]) as const;", src, re.S)
    if not m:
        raise SystemExit(f"Could not find const {name}")
    return json.loads(m.group(1))


def js_string(raw: str) -> str:
    """Decode a JS double-quoted string body without mangling UTF-8 Cyrillic."""
    return (
        raw.replace(r"\\", "\0")
        .replace(r"\"", '"')
        .replace(r"\n", "\n")
        .replace(r"\t", "\t")
        .replace("\0", "\\")
    )


def field(block: str, key: str) -> str:
    mm = re.search(rf'{key}:\s*"((?:\\.|[^"\\])*)"', block)
    return js_string(mm.group(1)) if mm else ""


def iter_top_level_objects(chunk: str):
    """Yield top-level `{...}` blocks, respecting nested braces (e.g. paths with `{city}`)."""
    i = 0
    n = len(chunk)
    while i < n:
        if chunk[i] != "{":
            i += 1
            continue
        depth = 0
        in_str = False
        esc_c = False
        start = i
        while i < n:
            ch = chunk[i]
            if in_str:
                if esc_c:
                    esc_c = False
                elif ch == "\\":
                    esc_c = True
                elif ch == '"':
                    in_str = False
            else:
                if ch == '"':
                    in_str = True
                elif ch == "{":
                    depth += 1
                elif ch == "}":
                    depth -= 1
                    if depth == 0:
                        yield chunk[start : i + 1]
                        i += 1
                        break
            i += 1
        else:
            break


def const_array_chunk(src: str, name: str) -> str:
    start = src.find(f"const {name} = [")
    if start < 0:
        return ""
    end = src.find("] as const;", start)
    return src[start:end] if end > start else ""


def extract_expansion(src: str) -> list[dict[str, str]]:
    chunk = const_array_chunk(src, "EXPANSION")
    if not chunk:
        return []
    keys = ("priority", "category", "url", "title", "why", "content", "status")
    out = []
    for b in iter_top_level_objects(chunk):
        if "priority:" not in b:
            continue
        row = {k: field(b, k) for k in keys}
        if row["url"] or row["title"]:
            out.append(row)
    return out


def extract_schema_by_page(src: str) -> list[dict[str, str]]:
    chunk = const_array_chunk(src, "SCHEMA_BY_PAGE")
    rows = []
    for b in iter_top_level_objects(chunk):
        if "family:" not in b:
            continue
        rows.append(
            {
                "family": field(b, "family"),
                "paths": field(b, "paths"),
                "count": field(b, "count"),
                "stack": field(b, "stack"),
                "primary": field(b, "primary"),
                "notes": field(b, "notes"),
            }
        )
    return rows


def extract_upgrades(src: str) -> list[dict[str, str]]:
    chunk = const_array_chunk(src, "SCHEMA_UPGRADES")
    rows = []
    for b in iter_top_level_objects(chunk):
        if "pri:" not in b:
            continue
        rows.append({"pri": field(b, "pri"), "item": field(b, "item"), "why": field(b, "why")})
    return rows


def esc(s: object) -> str:
    return html.escape(str(s), quote=True)


def pri_class(pri: str) -> str:
    p = pri.upper()
    if p.startswith("P0"):
        return "badge badge-p0"
    if p.startswith("P1"):
        return "badge badge-p1"
    if p.startswith("P2"):
        return "badge badge-p2"
    return "badge badge-misc"


def main() -> None:
    src = CANVAS.read_text(encoding="utf-8")
    groups = load_json_const(src, "GROUPS")
    summary = load_json_const(src, "SUMMARY")
    expansion = extract_expansion(src)
    schema_by_page = extract_schema_by_page(src)
    upgrades = extract_upgrades(src)
    total = sum(int(g["count"]) for g in groups)

    by_cat: dict[str, list] = defaultdict(list)
    for row in expansion:
        by_cat[row["category"] or "Другое"].append(row)

    parts: list[str] = []
    parts.append(
        """<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SEO Rework Report — Deluxe Windows</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
<style>
:root {
  --ink: #142033;
  --muted: #5b6b7c;
  --line: #d7e0ea;
  --paper: #f6f8fb;
  --card: #ffffff;
  --accent: #0f4d89;
  --accent-soft: #e8f1fa;
  --p0: #b42318;
  --p0-bg: #fef3f2;
  --p1: #b54708;
  --p1-bg: #fffaeb;
  --p2: #027a48;
  --p2-bg: #ecfdf3;
  --shadow: 0 10px 30px rgba(20, 32, 51, 0.06);
  --radius: 16px;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  margin: 0;
  color: var(--ink);
  background:
    radial-gradient(1200px 500px at 10% -10%, #dbeafe 0%, transparent 55%),
    radial-gradient(900px 400px at 100% 0%, #eef2ff 0%, transparent 50%),
    var(--paper);
  font-family: "DM Sans", system-ui, sans-serif;
  line-height: 1.55;
  font-size: 15px;
}
.wrap { max-width: 1080px; margin: 0 auto; padding: 40px 20px 80px; }
.hero {
  display: grid;
  gap: 18px;
  padding: 36px 32px;
  border-radius: 24px;
  background: linear-gradient(145deg, #0f4d89 0%, #163a5f 55%, #12263c 100%);
  color: #f4f8fc;
  box-shadow: var(--shadow);
  margin-bottom: 28px;
}
.hero h1 {
  margin: 0;
  font-family: Fraunces, Georgia, serif;
  font-size: clamp(2rem, 4vw, 2.75rem);
  font-weight: 600;
  letter-spacing: -0.02em;
  line-height: 1.15;
}
.hero .lede { margin: 0; color: #c9daf0; max-width: 52ch; }
.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-top: 8px;
}
.stat {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 14px;
  padding: 14px 16px;
}
.stat strong { display: block; font-size: 1.45rem; font-weight: 700; }
.stat span { color: #b7cae2; font-size: 0.82rem; }
.toc {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0 0 28px;
}
.toc a {
  text-decoration: none;
  color: var(--accent);
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 0.88rem;
  font-weight: 500;
}
.toc a:hover { background: var(--accent-soft); }
.section {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 18px;
  overflow: hidden;
}
.section > summary,
.fold > summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 22px;
  font-weight: 600;
  font-size: 1.05rem;
  user-select: none;
}
.section > summary::-webkit-details-marker,
.fold > summary::-webkit-details-marker { display: none; }
.section > summary::after,
.fold > summary::after {
  content: "";
  width: 10px; height: 10px;
  border-right: 2px solid var(--muted);
  border-bottom: 2px solid var(--muted);
  transform: rotate(45deg);
  transition: transform .18s ease;
  flex: 0 0 auto;
}
.section[open] > summary::after,
.fold[open] > summary::after { transform: rotate(-135deg); }
.section-body { padding: 0 22px 22px; }
.section h2 {
  margin: 0;
  font-family: Fraunces, Georgia, serif;
  font-size: 1.35rem;
  font-weight: 600;
}
.muted { color: var(--muted); }
.intro {
  margin: 0 0 14px;
  color: var(--muted);
}
.callout {
  background: var(--accent-soft);
  border: 1px solid #c5d9ef;
  border-radius: 14px;
  padding: 14px 16px;
  margin: 0 0 16px;
}
.table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th, td { padding: 10px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
th { background: #f0f4f8; font-size: 0.78rem; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
tr:last-child td { border-bottom: 0; }
code, .mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.82em;
  background: #eef3f8;
  padding: 1px 6px;
  border-radius: 6px;
}
.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: .02em;
}
.badge-p0 { background: var(--p0-bg); color: var(--p0); }
.badge-p1 { background: var(--p1-bg); color: var(--p1); }
.badge-p2 { background: var(--p2-bg); color: var(--p2); }
.badge-misc { background: #eef2f6; color: #475467; }
.card-list { display: grid; gap: 10px; }
.fold {
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fbfcfe;
}
.fold > summary {
  font-size: 0.95rem;
  padding: 14px 16px;
}
.fold-summary-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  min-width: 0;
}
.fold-body { padding: 0 16px 16px; }
.meta-grid {
  display: grid;
  gap: 8px;
  margin-top: 8px;
}
.meta-row {
  display: grid;
  grid-template-columns: 88px 1fr;
  gap: 10px;
  padding: 10px 12px;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
}
.meta-row b { color: var(--muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: .04em; padding-top: 2px; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 14px; }
.chip {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 0.88rem;
}
.chip strong { color: var(--accent); }
.footer {
  margin-top: 28px;
  color: var(--muted);
  font-size: 0.85rem;
  text-align: center;
}
@media (max-width: 800px) {
  .stats { grid-template-columns: 1fr 1fr; }
  .hero { padding: 28px 20px; }
  .meta-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="wrap">
"""
    )

    parts.append(
        f"""
<header class="hero">
  <div>
    <h1>SEO Rework Report</h1>
    <p class="lede">Deluxe Windows · page-metadata · 19 июля 2026 · Title / H1 / Schema.org</p>
  </div>
  <div class="stats">
    <div class="stat"><strong>{total}</strong><span>Страниц с SEO</span></div>
    <div class="stat"><strong>{len(groups)}</strong><span>Типов страниц</span></div>
    <div class="stat"><strong>{len(expansion)}</strong><span>Новых страниц в плане</span></div>
    <div class="stat"><strong>≤60</strong><span>Max title length</span></div>
  </div>
</header>

<nav class="toc" aria-label="Содержание">
  <a href="#summary">Итоги</a>
  <a href="#schema">Schema.org</a>
  <a href="#types">Типы страниц</a>
  <a href="#expansion">Новые страницы</a>
  <a href="#inventory">Инвентарь</a>
</nav>

<details class="section" id="summary" open>
  <summary><h2>Итоги SEO Rework</h2></summary>
  <div class="section-body">
    <div class="callout">
      Пересобраны Title, Meta Description, H1 и keywords на основе Google Ads Search Terms,
      Search Console Queries и People Also Ask (Apify). FAQ обновлены; цены в FAQ привязаны к hero-промо страницы.
    </div>
    <p class="muted">
      Правила: уникальность Title / Description / H1 / primary keyword · Titles продающие
      (Installation / Replacement) · «Deluxe» только на главной · города только на городских страницах ·
      Description 140–160 · English only.
    </p>
  </div>
</details>
"""
    )

    # Schema
    parts.append(
        """
<details class="section" id="schema" open>
  <summary><h2>1. Schema.org JSON-LD</h2></summary>
  <div class="section-body">
    <p class="intro">Organization вынесен отдельно — один блок «о компании» на всех страницах. Остальное page-specific через SchemaBuilder + page-metadata.</p>
    <div class="callout">
      <b>Site-wide:</b> HomeAndConstructionBusiness<br>
      <span class="mono">@id https://www.deluxewindows.com/#organization</span>
      <ul>
        <li>name, url, telephone</li>
        <li>description, priceRange $$</li>
        <li>aggregateRating 4.9 / 231 reviews</li>
        <li>hours Mon–Fri 08–18, Sat 09–15</li>
        <li>areaServed GeoCircle (Bay Area, 100 km)</li>
      </ul>
    </div>
"""
    )

    parts.append('<details class="fold" open><summary>Типы schema по семействам</summary><div class="fold-body"><div class="table-wrap"><table>')
    parts.append("<thead><tr><th>Семейство</th><th>Paths</th><th>N</th><th>Primary</th><th>Стек</th></tr></thead><tbody>")
    for r in schema_by_page:
        parts.append(
            "<tr>"
            f"<td>{esc(r['family'])}</td>"
            f"<td class='mono'>{esc(r['paths'])}</td>"
            f"<td>{esc(r['count'])}</td>"
            f"<td>{esc(r['primary'])}</td>"
            f"<td>{esc(r['stack'])}</td>"
            "</tr>"
        )
    parts.append("</tbody></table></div></div></details>")

    notes = [r for r in schema_by_page if r.get("notes")]
    if notes:
        parts.append('<details class="fold"><summary>Заметки по семействам</summary><div class="fold-body"><div class="table-wrap"><table>')
        parts.append("<thead><tr><th>Семейство</th><th>Notes</th></tr></thead><tbody>")
        for r in notes:
            parts.append(f"<tr><td>{esc(r['family'])}</td><td>{esc(r['notes'])}</td></tr>")
        parts.append("</tbody></table></div></div></details>")

    if upgrades:
        parts.append('<details class="fold" open><summary>Что ещё можно добавить</summary><div class="fold-body"><div class="card-list">')
        for r in upgrades:
            parts.append(
                f"""<details class="fold">
<summary><span class="fold-summary-row"><span class="{pri_class(r['pri'])}">{esc(r['pri'])}</span><span>{esc(r['item'])}</span></span></summary>
<div class="fold-body"><div class="meta-grid"><div class="meta-row"><b>Зачем</b><div>{esc(r['why'])}</div></div></div></div>
</details>"""
            )
        parts.append("</div></div></details>")

    parts.append("</div></details>")

    # Summary types
    parts.append(
        """
<details class="section" id="types" open>
  <summary><h2>2. Сводка по типам страниц</h2></summary>
  <div class="section-body">
    <div class="chips">
"""
    )
    for s in summary:
        parts.append(f"<div class='chip'><strong>{s['count']}</strong> {esc(s['type'])}</div>")
    parts.append("</div><div class='table-wrap'><table><thead><tr><th>Тип</th><th>Семейство</th><th>Кол-во</th></tr></thead><tbody>")
    for s in summary:
        parts.append(f"<tr><td>{esc(s['type'])}</td><td class='mono'>{esc(s['family'])}</td><td>{s['count']}</td></tr>")
    parts.append("</tbody></table></div></div></details>")

    # Expansion
    parts.append(
        f"""
<details class="section" id="expansion" open>
  <summary><h2>3. Предложенные новые страницы ({len(expansion)})</h2></summary>
  <div class="section-body">
    <p class="intro">Из Deluxe_Windows_SEO_content_map.xlsx · лист «02 Новые страницы». Раскройте карточку — там «Почему», контент и статус.</p>
"""
    )
    for cat, rows in by_cat.items():
        parts.append(f'<details class="fold" open><summary>{esc(cat)} · {len(rows)}</summary><div class="fold-body"><div class="card-list">')
        for r in rows:
            parts.append(
                f"""<details class="fold">
<summary><span class="fold-summary-row"><span class="{pri_class(r['priority'])}">{esc(r['priority'])}</span><span>{esc(r['title'])}</span></span></summary>
<div class="fold-body">
  <div class="meta-grid">
    <div class="meta-row"><b>URL</b><div class="mono">{esc(r['url'])}</div></div>
    <div class="meta-row"><b>Почему</b><div>{esc(r['why'])}</div></div>
    <div class="meta-row"><b>Контент</b><div>{esc(r['content'])}</div></div>
    <div class="meta-row"><b>Статус</b><div>{esc(r['status'])}</div></div>
  </div>
</div>
</details>"""
            )
        parts.append("</div></div></details>")
    parts.append("</div></details>")

    # Inventory
    parts.append(
        f"""
<details class="section" id="inventory">
  <summary><h2>4. Инвентарь страниц ({total})</h2></summary>
  <div class="section-body">
    <p class="intro">Раскройте семейство, чтобы увидеть Path / Title / H1 / Primary keyword.</p>
    <div class="card-list">
"""
    )
    for g in groups:
        parts.append(
            f'<details class="fold"><summary>{esc(g["label"])} · {g["count"]}</summary>'
            '<div class="fold-body"><div class="table-wrap"><table>'
            "<thead><tr><th>Path</th><th>Title</th><th>H1</th><th>Primary</th></tr></thead><tbody>"
        )
        for r in g["rows"]:
            parts.append(
                "<tr>"
                f"<td class='mono'>{esc(r['path'])}</td>"
                f"<td>{esc(r['title'])}</td>"
                f"<td>{esc(r['h1'])}</td>"
                f"<td>{esc(r['primary'])}</td>"
                "</tr>"
            )
        parts.append("</tbody></table></div></div></details>")
    parts.append("</div></div></details>")

    parts.append(
        """
<p class="footer">Сгенерировано из Cursor canvas <b>seo-rework-report</b> · Deluxe Windows</p>
</div>
</body>
</html>
"""
    )

    OUT.write_text("".join(parts), encoding="utf-8")
    print(f"Wrote {OUT} ({OUT.stat().st_size} bytes)")
    print(f"expansion={len(expansion)} upgrades={len(upgrades)} schema={len(schema_by_page)}")
    if expansion:
        print("sample why:", expansion[0]["why"][:80])
    if upgrades:
        print("sample upgrade why:", upgrades[0]["why"][:80])


if __name__ == "__main__":
    main()
