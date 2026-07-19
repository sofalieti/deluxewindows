#!/usr/bin/env python3
"""Export all page-metadata FAQ → polished interactive HTML report."""

from __future__ import annotations

import html
import json
from collections import defaultdict
from pathlib import Path

ROOT = Path(r"D:\Projects\deluxe windows new\database\data\page-metadata")
OUT = Path(r"D:\Projects\deluxe windows new\public\seo-reports\SEO_FAQ_REPORT.html")

FAMILY_META: dict[str, tuple[str, int]] = {
    "static": ("Служебные / статика", 10),
    "windows": ("Материалы окон", 20),
    "doors": ("Материалы дверей", 30),
    "brands": ("Бренды окон", 40),
    "door-brands": ("Бренды дверей", 50),
    "window-type": ("Бренд + материал (окна)", 60),
    "door-types": ("Бренд + материал (двери)", 70),
    "brand-collections": ("Серии / коллекции", 80),
    "window-replacement": ("Города (service areas)", 90),
    "county-hub-pages": ("Округа", 100),
    "blog": ("Блог", 110),
}


def esc(s: object) -> str:
    return html.escape(str(s), quote=True)


def family_label(slug: str) -> str:
    return FAMILY_META.get(slug, (slug, 999))[0]


def family_order(slug: str) -> int:
    return FAMILY_META.get(slug, (slug, 999))[1]


def load_pages() -> list[dict]:
    pages: list[dict] = []
    for path in sorted(ROOT.rglob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        rel = path.relative_to(ROOT)
        family = rel.parts[0]
        seo = data.get("seo") or {}
        faq = data.get("faq") or []
        items = []
        for item in faq:
            if not isinstance(item, dict):
                continue
            q = (item.get("question") or "").strip()
            a = (item.get("answer") or "").strip()
            if q or a:
                items.append({"question": q, "answer": a})
        pages.append(
            {
                "family": family,
                "key": data.get("key") or str(rel.with_suffix("")).replace("\\", "/"),
                "path": data.get("path") or "",
                "title": seo.get("title") or data.get("title") or path.stem,
                "h1": seo.get("h1") or "",
                "faq": items,
            }
        )
    pages.sort(key=lambda p: (family_order(p["family"]), p["path"] or p["key"]))
    return pages


def main() -> None:
    pages = load_pages()
    with_faq = [p for p in pages if p["faq"]]
    empty = [p for p in pages if not p["faq"]]
    total_q = sum(len(p["faq"]) for p in pages)

    by_family: dict[str, list[dict]] = defaultdict(list)
    for p in pages:
        by_family[p["family"]].append(p)

    families = sorted(by_family.keys(), key=family_order)

    parts: list[str] = []
    parts.append(
        """<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>FAQ Report — Deluxe Windows</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
<style>
:root {
  --ink: #142033;
  --muted: #5b6b7c;
  --line: #d7e0ea;
  --paper: #f4f7fb;
  --card: #ffffff;
  --accent: #0f4d89;
  --accent-soft: #e8f1fa;
  --teal: #0d7377;
  --teal-soft: #e6f5f5;
  --warn: #b54708;
  --warn-bg: #fffaeb;
  --shadow: 0 10px 30px rgba(20, 32, 51, 0.06);
  --radius: 16px;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  margin: 0;
  color: var(--ink);
  background:
    radial-gradient(1100px 480px at 8% -8%, #d8f3f1 0%, transparent 55%),
    radial-gradient(900px 420px at 100% 0%, #dbeafe 0%, transparent 50%),
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
  background: linear-gradient(145deg, #0d7377 0%, #0f4d89 52%, #12263c 100%);
  color: #f4f8fc;
  box-shadow: var(--shadow);
  margin-bottom: 22px;
}
.hero h1 {
  margin: 0;
  font-family: Fraunces, Georgia, serif;
  font-size: clamp(2rem, 4vw, 2.75rem);
  font-weight: 600;
  letter-spacing: -0.02em;
  line-height: 1.15;
}
.hero .lede { margin: 0; color: #c9e4e3; max-width: 56ch; }
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
.stat span { color: #b7d4d3; font-size: 0.82rem; }
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  margin: 0 0 18px;
  padding: 14px 16px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
  box-shadow: var(--shadow);
}
.toolbar label {
  flex: 1 1 240px;
  display: grid;
  gap: 4px;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .04em;
}
.toolbar input {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 10px 12px;
  font: inherit;
  color: var(--ink);
  background: #fbfcfe;
}
.toolbar input:focus {
  outline: 2px solid #9fd3d1;
  border-color: var(--teal);
}
.toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.btn {
  border: 1px solid var(--line);
  background: #fff;
  color: var(--accent);
  border-radius: 999px;
  padding: 8px 14px;
  font: inherit;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
}
.btn:hover { background: var(--accent-soft); }
.toc {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0 0 22px;
}
.toc a {
  text-decoration: none;
  color: var(--teal);
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 0.88rem;
  font-weight: 500;
}
.toc a:hover { background: var(--teal-soft); }
.section {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 16px;
  overflow: hidden;
}
.section > summary,
.page > summary,
.qa > summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 22px;
  font-weight: 600;
  user-select: none;
}
.section > summary::-webkit-details-marker,
.page > summary::-webkit-details-marker,
.qa > summary::-webkit-details-marker { display: none; }
.section > summary::after,
.page > summary::after,
.qa > summary::after {
  content: "";
  width: 10px; height: 10px;
  border-right: 2px solid var(--muted);
  border-bottom: 2px solid var(--muted);
  transform: rotate(45deg);
  transition: transform .18s ease;
  flex: 0 0 auto;
}
.section[open] > summary::after,
.page[open] > summary::after,
.qa[open] > summary::after { transform: rotate(-135deg); }
.section-body { padding: 0 22px 22px; }
.section h2 {
  margin: 0;
  font-family: Fraunces, Georgia, serif;
  font-size: 1.3rem;
  font-weight: 600;
}
.summary-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.count-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  background: var(--teal-soft);
  color: var(--teal);
}
.count-pill.empty {
  background: var(--warn-bg);
  color: var(--warn);
}
.intro { margin: 0 0 14px; color: var(--muted); }
.card-list { display: grid; gap: 10px; }
.page {
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fbfcfe;
}
.page > summary {
  font-size: 0.95rem;
  padding: 14px 16px;
}
.page-body { padding: 0 16px 16px; }
.empty-page-row { padding: 14px 16px; }
.page-path {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.82em;
  background: #eef3f8;
  padding: 2px 8px;
  border-radius: 6px;
  color: var(--accent);
  font-weight: 500;
}
.page-title {
  color: var(--ink);
  font-weight: 600;
}
.qa {
  border: 1px solid var(--line);
  border-radius: 10px;
  background: #fff;
  margin-bottom: 8px;
}
.qa:last-child { margin-bottom: 0; }
.qa > summary {
  padding: 12px 14px;
  font-size: 0.92rem;
  font-weight: 600;
  align-items: flex-start;
}
.qa-q {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 10px;
  min-width: 0;
}
.q-mark {
  width: 22px;
  height: 22px;
  border-radius: 999px;
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 0.72rem;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  margin-top: 1px;
}
.qa-body {
  padding: 0 14px 14px 46px;
  color: var(--muted);
}
.qa-body p { margin: 0; }
.empty-note {
  margin: 0;
  padding: 12px 14px;
  border-radius: 10px;
  background: var(--warn-bg);
  color: var(--warn);
  font-size: 0.9rem;
}
.hidden { display: none !important; }
.footer {
  margin-top: 28px;
  color: var(--muted);
  font-size: 0.85rem;
  text-align: center;
}
@media (max-width: 800px) {
  .stats { grid-template-columns: 1fr 1fr; }
  .hero { padding: 28px 20px; }
  .qa-body { padding-left: 14px; }
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
    <h1>FAQ Report</h1>
    <p class="lede">Все FAQ из page-metadata · Deluxe Windows · 19 июля 2026. Раскройте семейство → страницу → вопрос.</p>
  </div>
  <div class="stats">
    <div class="stat"><strong>{len(pages)}</strong><span>Страниц всего</span></div>
    <div class="stat"><strong>{len(with_faq)}</strong><span>С FAQ</span></div>
    <div class="stat"><strong>{total_q}</strong><span>Вопросов и ответов</span></div>
    <div class="stat"><strong>{len(empty)}</strong><span>Без FAQ</span></div>
  </div>
</header>

<div class="toolbar">
  <label>Поиск по URL, title или тексту FAQ
    <input id="faq-filter" type="search" placeholder="Например: milgard, San Rafael, permit…" autocomplete="off">
  </label>
  <div class="toolbar-actions">
    <button type="button" class="btn" id="expand-all">Раскрыть всё</button>
    <button type="button" class="btn" id="collapse-all">Свернуть всё</button>
  </div>
</div>

<nav class="toc" aria-label="Семейства">
"""
    )
    for fam in families:
        fam_pages = by_family[fam]
        q_count = sum(len(p["faq"]) for p in fam_pages)
        parts.append(
            f'<a href="#fam-{esc(fam)}">{esc(family_label(fam))} · {q_count}</a>'
        )
    if empty:
        parts.append(f'<a href="#empty">Без FAQ · {len(empty)}</a>')
    parts.append("</nav>")

    for fam in families:
        fam_pages = by_family[fam]
        q_count = sum(len(p["faq"]) for p in fam_pages)
        open_attr = " open" if fam in {"brands", "window-replacement", "static"} else ""
        parts.append(
            f"""
<details class="section family" id="fam-{esc(fam)}"{open_attr} data-family="{esc(fam)}">
  <summary>
    <span class="summary-meta">
      <h2>{esc(family_label(fam))}</h2>
      <span class="count-pill">{len(fam_pages)} стр. · {q_count} FAQ</span>
    </span>
  </summary>
  <div class="section-body">
    <div class="card-list">
"""
        )
        for p in fam_pages:
            n = len(p["faq"])
            pill = (
                f'<span class="count-pill">{n} Q&amp;A</span>'
                if n
                else '<span class="count-pill empty">нет FAQ</span>'
            )
            search_blob = " ".join(
                [
                    p["path"],
                    p["title"],
                    p["h1"],
                    p["key"],
                    *[f"{x['question']} {x['answer']}" for x in p["faq"]],
                ]
            ).lower()
            parts.append(
                f"""
<details class="page" data-search="{esc(search_blob)}">
  <summary>
    <span class="summary-meta">
      <span class="page-path">{esc(p['path'] or p['key'])}</span>
      <span class="page-title">{esc(p['title'])}</span>
      {pill}
    </span>
  </summary>
  <div class="page-body">
"""
            )
            if not p["faq"]:
                parts.append('<p class="empty-note">На этой странице FAQ пока пустой.</p>')
            else:
                for i, item in enumerate(p["faq"], 1):
                    parts.append(
                        f"""
<details class="qa">
  <summary>
    <span class="qa-q">
      <span class="q-mark">Q{i}</span>
      <span>{esc(item['question'])}</span>
    </span>
  </summary>
  <div class="qa-body"><p>{esc(item['answer'])}</p></div>
</details>
"""
                    )
            parts.append("</div></details>")
        parts.append("</div></div></details>")

    if empty:
        parts.append(
            f"""
<details class="section" id="empty">
  <summary>
    <span class="summary-meta">
      <h2>Страницы без FAQ</h2>
      <span class="count-pill empty">{len(empty)}</span>
    </span>
  </summary>
  <div class="section-body">
    <p class="intro">Эти metadata-файлы есть, но массив <span class="page-path">faq</span> пуст.</p>
    <div class="card-list">
"""
        )
        for p in empty:
            parts.append(
                f"""
<div class="page">
  <div class="page-body empty-page-row">
    <span class="page-path">{esc(p['path'] or p['key'])}</span>
    <span class="page-title"> — {esc(p['title'])}</span>
  </div>
</div>
"""
            )
        parts.append("</div></div></details>")

    parts.append(
        """
<p class="footer">Сгенерировано из database/data/page-metadata · Deluxe Windows</p>
</div>
<script>
(function () {
  const input = document.getElementById('faq-filter');
  const pages = Array.from(document.querySelectorAll('.page[data-search]'));
  const families = Array.from(document.querySelectorAll('details.family'));

  function applyFilter() {
    const q = (input.value || '').trim().toLowerCase();
    pages.forEach((page) => {
      const hay = page.getAttribute('data-search') || '';
      const match = !q || hay.includes(q);
      page.classList.toggle('hidden', !match);
      if (match && q) page.open = true;
    });
    families.forEach((fam) => {
      const visible = fam.querySelectorAll('.page[data-search]:not(.hidden)').length;
      fam.classList.toggle('hidden', visible === 0);
      if (q && visible > 0) fam.open = true;
    });
  }

  input.addEventListener('input', applyFilter);
  document.getElementById('expand-all').addEventListener('click', () => {
    document.querySelectorAll('details.section, details.page, details.qa').forEach((d) => {
      if (!d.classList.contains('hidden')) d.open = true;
    });
  });
  document.getElementById('collapse-all').addEventListener('click', () => {
    document.querySelectorAll('details.page, details.qa').forEach((d) => { d.open = false; });
  });
})();
</script>
</body>
</html>
"""
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text("".join(parts), encoding="utf-8")
    print(f"Wrote {OUT} ({OUT.stat().st_size} bytes)")
    print(f"pages={len(pages)} with_faq={len(with_faq)} empty={len(empty)} questions={total_q}")


if __name__ == "__main__":
    main()
