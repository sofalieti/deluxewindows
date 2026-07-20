#!/usr/bin/env python3
"""Export FAQ + People Also Ask provenance report (color-coded HTML)."""

from __future__ import annotations

import html
import json
import re
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
RESEARCH = ROOT / "database" / "data" / "seo-research"
METADATA = ROOT / "database" / "data" / "page-metadata"
OUT = ROOT / "public" / "seo-reports" / "SEO_FAQ_SOURCES_REPORT.html"

QUESTION_BLOCKLIST = (
    "home depot", "lowes", "lowe's", "menards", "pella", "renewal by andersen",
    "window world", "rip-off", "ripoff", "lawsuit", "paid off", "for free",
    "credit score", "who bought", "what is happening", "anderson renewal",
    "in the usa", "top 10 window manufacturers", "competitor",
)

QUESTION_STOPWORDS = {
    "is", "are", "the", "a", "an", "of", "for", "to", "in", "on", "do", "does",
    "did", "what", "which", "who", "whos", "how", "much", "many", "better",
    "best", "than", "vs", "and", "or", "it", "worth", "window", "windows",
    "door", "doors", "i", "my", "your", "you", "can", "should", "there",
}

FAMILY_META: dict[str, tuple[str, int]] = {
    "static": ("Служебные / статика", 10),
    "windows": ("Материалы окон", 20),
    "doors": ("Материалы дверей", 30),
    "brands": ("Бренды окон", 40),
    "door-brands": ("Бренды дверей", 50),
    "window-type": ("Бренд + материал (окна)", 60),
    "door-types": ("Бренд + материал (двери)", 70),
    "brand-collections": ("Серии / коллекции", 80),
    "window-replacement": ("Города", 90),
    "county-hub-pages": ("Округа", 100),
    "blog": ("Блог", 110),
}

# Detect generated-fill templates → human-readable idea.
GENERATED_IDEAS: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"^how do i get installed pricing for ", re.I), "Шаблон бренда: как получить установленную цену"),
    (re.compile(r"^how soon can .+ be installed", re.I), "Шаблон бренда: сроки поставки и монтажа"),
    (re.compile(r"^which .+ does deluxe windows install", re.I), "Шаблон бренда: какой lineup ставим"),
    (re.compile(r"^how long does .+ installation take", re.I), "Шаблон бренда: длительность монтажа"),
    (re.compile(r"^can i see .+ before ordering", re.I), "Шаблон бренда: шоурум / образцы"),
    (re.compile(r"^how much do .+ cost installed", re.I), "Шаблон материала: цена установленная"),
    (re.compile(r"^is removal of the old ", re.I), "Шаблон материала: демонтаж включён"),
    (re.compile(r"^what are the main benefits of ", re.I), "Шаблон материала: выгоды"),
    (re.compile(r"^how long do .+ last\??$", re.I), "Шаблон материала: срок службы"),
    (re.compile(r"^what maintenance do ", re.I), "Шаблон материала: уход"),
    (re.compile(r"^why choose .+ for a bay area home", re.I), "Шаблон brand+material: почему эта комбинация"),
    (re.compile(r"^what do .+ cost installed", re.I), "Шаблон brand+material: цена"),
    (re.compile(r"^is installation included in the .+ quote", re.I), "Шаблон brand+material: монтаж в quote"),
    (re.compile(r"^which series are available for ", re.I), "Шаблон brand+material: доступные серии"),
    (re.compile(r"^which styles are available for ", re.I), "Шаблон door-type: стили"),
    (re.compile(r"^who installs .+ near me", re.I), "Шаблон brand+material: кто ставит рядом"),
    (re.compile(r"^how fast can .+ be ordered and installed", re.I), "Шаблон door-type: скорость заказа/монтажа"),
    (re.compile(r"^what should homeowners know about ", re.I), "Шаблон коллекции: обзор серии"),
    (re.compile(r"^which configurations does .+ offer", re.I), "Шаблон коллекции: конфигурации"),
    (re.compile(r"^how is installed pricing for .+ calculated", re.I), "Шаблон коллекции: как считается цена"),
    (re.compile(r"^how does .+ compare with other ", re.I), "Шаблон коллекции: сравнение с sibling series"),
    (re.compile(r"^how do i order .+ with installation", re.I), "Шаблон коллекции: как заказать с монтажом"),
    (re.compile(r"^how much does window replacement cost in ", re.I), "Шаблон города: стоимость замены"),
    (re.compile(r"^do i need a permit to replace windows in ", re.I), "Шаблон города: разрешение / permit"),
    (re.compile(r"^which window brands work best for ", re.I), "Шаблон города: подходящие бренды"),
    (re.compile(r"^how long does window replacement take in ", re.I), "Шаблон города: сроки проекта"),
    (re.compile(r"^how do i book a free window replacement estimate in ", re.I), "Шаблон города: запись на estimate"),
    (re.compile(r"^which cities does deluxe windows serve in ", re.I), "Шаблон округа: города покрытия"),
    (re.compile(r"^what does window replacement cost in ", re.I), "Шаблон округа: стоимость"),
    (re.compile(r"^do .+ cities require window replacement permits", re.I), "Шаблон округа: permits"),
    (re.compile(r"^which window features matter most in ", re.I), "Шаблон округа: важные фичи климата"),
    (re.compile(r"^how do i schedule window installation in ", re.I), "Шаблон округа: запись на монтаж"),
    (re.compile(r"^what is the key takeaway from ", re.I), "Шаблон блога: ключевой вывод статьи"),
    (re.compile(r"^how should bay area homeowners apply ", re.I), "Шаблон блога: как применить"),
    (re.compile(r"^when should i get professional help after reading ", re.I), "Шаблон блога: когда звать профи"),
    (re.compile(r"^what services does deluxe windows provide", re.I), "Шаблон статики (home): услуги"),
    (re.compile(r"^where is the deluxe windows showroom", re.I), "Шаблон статики (home): шоурум"),
    (re.compile(r"^which window materials can i compare", re.I), "Шаблон статики (/windows): материалы"),
    (re.compile(r"^what determines replacement window pricing", re.I), "Шаблон статики (/windows): факторы цены"),
    (re.compile(r"^how do i pick the right window style", re.I), "Шаблон статики (/windows): выбор стиля"),
    (re.compile(r"^which door types does deluxe windows install", re.I), "Шаблон статики (/doors): типы дверей"),
    (re.compile(r"^how much does door replacement cost", re.I), "Шаблон статики (/doors): цена дверей"),
    (re.compile(r"^sliding, hinged or folding", re.I), "Шаблон статики (/doors): выбор patio"),
    (re.compile(r"^can i pay for window replacement in installments", re.I), "Шаблон статики (/financing)"),
    (re.compile(r"^when are financing terms confirmed", re.I), "Шаблон статики (/financing)"),
    (re.compile(r"^does financing change the project price", re.I), "Шаблон статики (/financing)"),
    (re.compile(r"^how do i confirm a deluxe windows offer", re.I), "Шаблон статики (/special-offers)"),
    (re.compile(r"^which products qualify for current promotions", re.I), "Шаблон статики (/special-offers)"),
    (re.compile(r"^can offers be combined with financing", re.I), "Шаблон статики (/special-offers)"),
]

FAQ_HUB_QUESTIONS = {
    "How much does window replacement cost in the Bay Area?",
    "How much does it cost to replace all windows in a house?",
    "Is it worth replacing 20 year old windows?",
    "Do I need a permit to replace windows in the Bay Area?",
    "How long does a window replacement project take?",
    "What is the cheapest time of year to replace windows?",
    "Should I choose retrofit or full-frame window replacement?",
    "Which window brands does Deluxe Windows carry?",
}


def esc(s: object) -> str:
    return html.escape(str(s), quote=True)


def clean_question(question: str) -> str:
    question = re.sub(r"\s+", " ", question).strip().strip('"')
    if not question.endswith("?"):
        question += "?"
    if not question:
        return question
    return question[0].upper() + question[1:]


def question_signature(question: str) -> str:
    words = re.findall(r"[a-z0-9']+", question.casefold())
    content = sorted(set(words) - QUESTION_STOPWORDS)
    return " ".join(content)


def question_allowed(question: str) -> bool:
    lowered = question.casefold()
    return not any(token in lowered for token in QUESTION_BLOCKLIST)


def detect_generated_idea(question: str) -> str | None:
    for pattern, label in GENERATED_IDEAS:
        if pattern.search(question):
            return label
    return None


def load_raw_paa() -> list[dict]:
    rows: list[dict] = []
    for file in sorted((RESEARCH / "paa").glob("*.json")):
        data = json.loads(file.read_text(encoding="utf-8"))
        source = data.get("source") or "Apify Google PAA"
        collected = data.get("collected_at") or ""
        for seed, payload in (data.get("queries") or {}).items():
            for q in payload.get("paa") or []:
                rows.append(
                    {
                        "file": file.name,
                        "seed": seed,
                        "question": clean_question(str(q)),
                        "source": source,
                        "collected_at": collected,
                        "blocked": not question_allowed(str(q)),
                    }
                )
    return rows


def load_pages() -> list[dict]:
    pages: list[dict] = []
    for path in sorted(METADATA.rglob("*.json")):
        data = json.loads(path.read_text(encoding="utf-8"))
        rel = path.relative_to(METADATA)
        family = rel.parts[0]
        seo = data.get("seo") or {}
        faq = []
        for item in data.get("faq") or []:
            if not isinstance(item, dict):
                continue
            q = (item.get("question") or "").strip()
            a = (item.get("answer") or "").strip()
            if q or a:
                faq.append({"question": q, "answer": a})
        pages.append(
            {
                "family": family,
                "path": data.get("path") or "",
                "title": seo.get("title") or path.stem,
                "faq": faq,
            }
        )
    return pages


def classify_faq(
    question: str,
    paa_by_norm: dict[str, dict],
    paa_by_sig: dict[str, dict],
) -> dict:
    cleaned = clean_question(question)
    norm = cleaned.casefold()
    sig = question_signature(cleaned)

    if cleaned in FAQ_HUB_QUESTIONS or norm in {q.casefold() for q in FAQ_HUB_QUESTIONS}:
        return {
            "origin": "bank",
            "label": "FAQ bank / /faq hub",
            "idea": "Вопрос из FAQ bank (content map / cost cluster) — зафиксированный ответ для /faq",
            "seed": "",
            "paa_question": "",
        }

    paa = paa_by_norm.get(norm) or paa_by_sig.get(sig)
    if paa:
        return {
            "origin": "paa",
            "label": "People Also Ask",
            "idea": f"Реальный PAA-вопрос · seed «{paa.get('seed', '')}» · ответ сгенерирован answer_paa() (факты бренда / цена / локаль)",
            "seed": paa.get("seed") or "",
            "paa_question": paa.get("question") or cleaned,
        }

    idea = detect_generated_idea(cleaned)
    if idea:
        return {
            "origin": "generated",
            "label": "Сгенерировано (шаблон)",
            "idea": idea,
            "seed": "",
            "paa_question": "",
        }

    return {
        "origin": "generated",
        "label": "Сгенерировано (прочее)",
        "idea": "Семейный fill / доп. проход — шаблон не распознан точно, но не из PAA pool",
        "seed": "",
        "paa_question": "",
    }


def main() -> None:
    dataset = json.loads((RESEARCH / "dataset.json").read_text(encoding="utf-8"))
    raw_paa = load_raw_paa()
    pages = load_pages()

    paa_pool = dataset.get("paa_pool") or []
    paa_by_norm: dict[str, dict] = {}
    paa_by_sig: dict[str, dict] = {}
    for entry in paa_pool:
        q = clean_question(str(entry.get("question") or ""))
        paa_by_norm[q.casefold()] = {**entry, "question": q}
        paa_by_sig[question_signature(q)] = {**entry, "question": q}

    # Also index raw PAA for display (may include blocked)
    raw_by_seed: dict[str, list[dict]] = defaultdict(list)
    for row in raw_paa:
        raw_by_seed[row["seed"]].append(row)

    published: list[dict] = []
    for page in pages:
        for item in page["faq"]:
            meta = classify_faq(item["question"], paa_by_norm, paa_by_sig)
            published.append(
                {
                    "path": page["path"],
                    "title": page["title"],
                    "family": page["family"],
                    "question": item["question"],
                    "answer": item["answer"],
                    **meta,
                }
            )

    used_paa_norms = {
        clean_question(r["paa_question"] or r["question"]).casefold()
        for r in published
        if r["origin"] == "paa"
    }
    unused_paa = [
        e for e in paa_pool
        if clean_question(str(e.get("question") or "")).casefold() not in used_paa_norms
    ]
    blocked_raw = [r for r in raw_paa if r["blocked"]]

    counts = defaultdict(int)
    for r in published:
        counts[r["origin"]] += 1

    by_family: dict[str, list[dict]] = defaultdict(list)
    for r in published:
        by_family[r["family"]].append(r)

    families = sorted(by_family.keys(), key=lambda f: FAMILY_META.get(f, (f, 999))[1])

    unique_raw = len({r["question"].casefold() for r in raw_paa})
    unique_pool = len(paa_pool)

    parts: list[str] = []
    parts.append(
        f"""<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>FAQ + People Also Ask Sources — Deluxe Windows</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
<style>
:root {{
  --ink: #142033;
  --muted: #5b6b7c;
  --line: #d7e0ea;
  --paper: #f4f7fb;
  --card: #fff;
  --accent: #0f4d89;
  --shadow: 0 10px 30px rgba(20,32,51,.06);
  --paa: #0d7377;
  --paa-bg: #e6f5f5;
  --paa-border: #9fd3d1;
  --gen: #b54708;
  --gen-bg: #fffaeb;
  --gen-border: #fec84b;
  --bank: #6941c6;
  --bank-bg: #f4ebff;
  --bank-border: #d6bbfb;
  --blocked: #667085;
  --blocked-bg: #f2f4f7;
}}
* {{ box-sizing: border-box; }}
html {{ scroll-behavior: smooth; }}
body {{
  margin: 0;
  color: var(--ink);
  background:
    radial-gradient(1100px 480px at 8% -8%, #d8f3f1 0%, transparent 55%),
    radial-gradient(900px 420px at 100% 0%, #efe7ff 0%, transparent 50%),
    var(--paper);
  font-family: "DM Sans", system-ui, sans-serif;
  line-height: 1.55;
  font-size: 15px;
}}
.wrap {{ max-width: 1100px; margin: 0 auto; padding: 40px 20px 80px; }}
.hero {{
  padding: 36px 32px;
  border-radius: 24px;
  background: linear-gradient(145deg, #0d7377 0%, #6941c6 48%, #12263c 100%);
  color: #f4f8fc;
  box-shadow: var(--shadow);
  margin-bottom: 22px;
}}
.hero h1 {{
  margin: 0;
  font-family: Fraunces, Georgia, serif;
  font-size: clamp(1.9rem, 4vw, 2.6rem);
  font-weight: 600;
  letter-spacing: -.02em;
}}
.hero .lede {{ margin: 10px 0 0; color: #d7e4f5; max-width: 62ch; }}
.stats {{
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
  margin-top: 18px;
}}
.stat {{
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 14px;
  padding: 12px 14px;
}}
.stat strong {{ display: block; font-size: 1.35rem; }}
.stat span {{ color: #c5d4e8; font-size: .78rem; }}
.legend {{
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 0 0 18px;
  padding: 14px 16px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
  box-shadow: var(--shadow);
}}
.legend-item {{
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: .9rem;
}}
.badge {{
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .02em;
  white-space: nowrap;
}}
.badge-paa {{ background: var(--paa-bg); color: var(--paa); border: 1px solid var(--paa-border); }}
.badge-generated {{ background: var(--gen-bg); color: var(--gen); border: 1px solid var(--gen-border); }}
.badge-bank {{ background: var(--bank-bg); color: var(--bank); border: 1px solid var(--bank-border); }}
.badge-blocked {{ background: var(--blocked-bg); color: var(--blocked); }}
.toolbar {{
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: end;
  margin: 0 0 18px;
  padding: 14px 16px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
}}
.toolbar label {{
  display: grid;
  gap: 4px;
  flex: 1 1 220px;
  font-size: .75rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .04em;
}}
.toolbar input, .toolbar select {{
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 9px 12px;
  font: inherit;
  min-width: 180px;
  background: #fbfcfe;
}}
.btn {{
  border: 1px solid var(--line);
  background: #fff;
  color: var(--accent);
  border-radius: 999px;
  padding: 8px 14px;
  font: inherit;
  font-size: .88rem;
  font-weight: 600;
  cursor: pointer;
}}
.toc {{ display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 22px; }}
.toc a {{
  text-decoration: none;
  color: var(--accent);
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 999px;
  padding: 8px 14px;
  font-size: .88rem;
  font-weight: 500;
}}
.section {{
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: var(--shadow);
  margin-bottom: 16px;
  overflow: hidden;
}}
.section > summary, .fold > summary, .qa > summary {{
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  font-weight: 600;
  user-select: none;
}}
.section > summary::-webkit-details-marker,
.fold > summary::-webkit-details-marker,
.qa > summary::-webkit-details-marker {{ display: none; }}
.section > summary::after, .fold > summary::after, .qa > summary::after {{
  content: "";
  width: 10px; height: 10px;
  border-right: 2px solid var(--muted);
  border-bottom: 2px solid var(--muted);
  transform: rotate(45deg);
  transition: transform .18s ease;
  flex: 0 0 auto;
}}
.section[open] > summary::after,
.fold[open] > summary::after,
.qa[open] > summary::after {{ transform: rotate(-135deg); }}
.section-body {{ padding: 0 20px 20px; }}
.section h2 {{
  margin: 0;
  font-family: Fraunces, Georgia, serif;
  font-size: 1.25rem;
}}
.summary-meta {{ display: flex; flex-wrap: wrap; align-items: center; gap: 10px; min-width: 0; }}
.count-pill {{
  display: inline-flex;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: .75rem;
  font-weight: 700;
  background: #eef3f8;
  color: var(--muted);
}}
.intro {{ margin: 0 0 14px; color: var(--muted); }}
.callout {{
  background: #eef3f8;
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
  margin: 0 0 14px;
  color: var(--muted);
  font-size: .92rem;
}}
.card-list {{ display: grid; gap: 10px; }}
.fold, .qa {{
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fbfcfe;
}}
.qa {{ background: #fff; margin-bottom: 8px; }}
.qa:last-child {{ margin-bottom: 0; }}
.qa.origin-paa {{ border-color: var(--paa-border); background: linear-gradient(180deg, #f3fbfb 0%, #fff 40%); }}
.qa.origin-generated {{ border-color: var(--gen-border); background: linear-gradient(180deg, #fffcf5 0%, #fff 40%); }}
.qa.origin-bank {{ border-color: var(--bank-border); background: linear-gradient(180deg, #faf7ff 0%, #fff 40%); }}
.fold > summary, .qa > summary {{ padding: 12px 14px; font-size: .92rem; }}
.fold-body, .qa-body {{ padding: 0 14px 14px; }}
.qa-body.compact {{ padding: 12px 14px; }}
.meta-grid {{ display: grid; gap: 8px; }}
.meta-row {{
  display: grid;
  grid-template-columns: 100px 1fr;
  gap: 10px;
  padding: 10px 12px;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
}}
.meta-row b {{
  color: var(--muted);
  font-size: .72rem;
  text-transform: uppercase;
  letter-spacing: .04em;
  padding-top: 2px;
}}
.mono {{
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: .82em;
  background: #eef3f8;
  padding: 1px 6px;
  border-radius: 6px;
}}
.seed-chip {{
  display: inline-block;
  background: var(--paa-bg);
  color: var(--paa);
  border-radius: 8px;
  padding: 2px 8px;
  font-size: .82rem;
  font-weight: 600;
}}
.muted {{ color: var(--muted); }}
.hidden {{ display: none !important; }}
.footer {{ margin-top: 28px; color: var(--muted); font-size: .85rem; text-align: center; }}
@media (max-width: 900px) {{
  .stats {{ grid-template-columns: 1fr 1fr; }}
  .meta-row {{ grid-template-columns: 1fr; }}
}}
</style>
</head>
<body>
<div class="wrap">
<header class="hero">
  <h1>FAQ + People Also Ask</h1>
  <p class="lede">Источники всех вопросов: реальный Google PAA (Apify), FAQ bank и семейные шаблоны apply_seo_from_research.py. Цвет карточки = происхождение вопроса.</p>
  <div class="stats">
    <div class="stat"><strong>{unique_raw}</strong><span>Уникальных PAA из Apify</span></div>
    <div class="stat"><strong>{unique_pool}</strong><span>В paa_pool (dataset)</span></div>
    <div class="stat"><strong>{counts['paa']}</strong><span>FAQ с сайта ← PAA</span></div>
    <div class="stat"><strong>{counts['generated']}</strong><span>FAQ сгенерированы</span></div>
    <div class="stat"><strong>{counts['bank']}</strong><span>FAQ bank / hub</span></div>
  </div>
</header>

<div class="legend">
  <div class="legend-item"><span class="badge badge-paa">PAA</span> Вопрос взят из People Also Ask (Google via Apify). Ответ написан скриптом под страницу.</div>
  <div class="legend-item"><span class="badge badge-generated">Generated</span> Вопрос из семейного шаблона (не было в PAA / добили до 5–6 FAQ).</div>
  <div class="legend-item"><span class="badge badge-bank">FAQ bank</span> Зафиксированный набор для /faq из content map.</div>
  <div class="legend-item"><span class="badge badge-blocked">Blocked</span> PAA отфильтрован (Home Depot, Pella, lawsuit…).</div>
</div>

<div class="toolbar">
  <label>Поиск
    <input id="filter-q" type="search" placeholder="milgard, permit, San Rafael…">
  </label>
  <label>Источник
    <select id="filter-origin">
      <option value="">Все</option>
      <option value="paa">Только PAA</option>
      <option value="generated">Только Generated</option>
      <option value="bank">Только FAQ bank</option>
    </select>
  </label>
  <button type="button" class="btn" id="expand-all">Раскрыть всё</button>
  <button type="button" class="btn" id="collapse-all">Свернуть</button>
</div>

<nav class="toc">
  <a href="#published">FAQ на сайте</a>
  <a href="#raw-paa">Все PAA (Apify)</a>
  <a href="#unused">Неиспользованные PAA</a>
  <a href="#blocked">Заблокированные</a>
</nav>
"""
    )

    # Published FAQs by family
    parts.append(
        f"""
<details class="section" id="published" open>
  <summary><span class="summary-meta"><h2>1. FAQ на страницах сайта</h2><span class="count-pill">{len(published)} Q&amp;A</span></span></summary>
  <div class="section-body">
    <p class="intro">Каждая карточка помечена цветом. Внутри — идея/seed и полный ответ.</p>
    <div class="card-list">
"""
    )

    for fam in families:
        rows = by_family[fam]
        label = FAMILY_META.get(fam, (fam, 999))[0]
        c_paa = sum(1 for r in rows if r["origin"] == "paa")
        c_gen = sum(1 for r in rows if r["origin"] == "generated")
        c_bank = sum(1 for r in rows if r["origin"] == "bank")
        parts.append(
            f"""
<details class="fold family" data-family="{esc(fam)}" open>
  <summary>
    <span class="summary-meta">
      <span>{esc(label)}</span>
      <span class="count-pill">{len(rows)}</span>
      <span class="badge badge-paa">{c_paa} PAA</span>
      <span class="badge badge-generated">{c_gen} Gen</span>
      {"<span class='badge badge-bank'>" + str(c_bank) + " Bank</span>" if c_bank else ""}
    </span>
  </summary>
  <div class="fold-body"><div class="card-list">
"""
        )
        # group by page path
        by_path: dict[str, list[dict]] = defaultdict(list)
        for r in rows:
            by_path[r["path"] or "?"].append(r)
        for path, items in by_path.items():
            title = items[0]["title"]
            search_blob = " ".join(
                [path, title, *[f"{i['question']} {i['answer']} {i['idea']} {i['seed']}" for i in items]]
            ).lower()
            origins = ",".join(sorted({i["origin"] for i in items}))
            parts.append(
                f"""
<details class="fold page" data-search="{esc(search_blob)}" data-origins="{esc(origins)}">
  <summary>
    <span class="summary-meta">
      <span class="mono">{esc(path)}</span>
      <span>{esc(title)}</span>
      <span class="count-pill">{len(items)}</span>
    </span>
  </summary>
  <div class="fold-body">
"""
            )
            for i, item in enumerate(items, 1):
                origin = item["origin"]
                badge_class = {
                    "paa": "badge-paa",
                    "generated": "badge-generated",
                    "bank": "badge-bank",
                }.get(origin, "badge-blocked")
                seed_html = (
                    f'<div class="meta-row"><b>PAA seed</b><div><span class="seed-chip">{esc(item["seed"])}</span></div></div>'
                    if item["seed"]
                    else ""
                )
                parts.append(
                    f"""
<details class="qa origin-{esc(origin)}" data-origin="{esc(origin)}" data-search="{esc((item['question'] + ' ' + item['answer'] + ' ' + item['idea'] + ' ' + item['seed']).lower())}">
  <summary>
    <span class="summary-meta">
      <span class="badge {badge_class}">{esc(item['label'])}</span>
      <span>Q{i}. {esc(item['question'])}</span>
    </span>
  </summary>
  <div class="qa-body">
    <div class="meta-grid">
      <div class="meta-row"><b>Идея / источник</b><div>{esc(item['idea'])}</div></div>
      {seed_html}
      <div class="meta-row"><b>Ответ</b><div>{esc(item['answer'])}</div></div>
    </div>
  </div>
</details>
"""
                )
            parts.append("</div></details>")
        parts.append("</div></div></details>")
    parts.append("</div></div></details>")

    # Raw PAA by seed
    parts.append(
        f"""
<details class="section" id="raw-paa" open>
  <summary><span class="summary-meta"><h2>2. Все People Also Ask (Apify)</h2><span class="count-pill">{len(raw_paa)} строк · {unique_raw} уник.</span></span></summary>
  <div class="section-body">
    <div class="callout">Сырые JSON: <span class="mono">database/data/seo-research/paa/*.json</span> · источник Apify google-search-scraper · собрано 2026-07-18. Seed = поисковый запрос, под которым Google показал блок PAA.</div>
    <div class="card-list">
"""
    )
    for seed in sorted(raw_by_seed.keys()):
        rows = raw_by_seed[seed]
        file_name = rows[0]["file"]
        parts.append(
            f"""
<details class="fold">
  <summary>
    <span class="summary-meta">
      <span class="seed-chip">{esc(seed)}</span>
      <span class="mono">{esc(file_name)}</span>
      <span class="count-pill">{len(rows)}</span>
    </span>
  </summary>
  <div class="fold-body"><div class="card-list">
"""
        )
        for row in rows:
            badge = (
                '<span class="badge badge-blocked">Blocked</span>'
                if row["blocked"]
                else '<span class="badge badge-paa">PAA</span>'
            )
            used = row["question"].casefold() in used_paa_norms
            used_badge = (
                '<span class="badge badge-paa">На сайте</span>'
                if used and not row["blocked"]
                else ('<span class="badge badge-blocked">Не на сайте</span>' if not row["blocked"] else "")
            )
            origin_cls = "blocked" if row["blocked"] else "paa"
            parts.append(
                f"""
<div class="qa origin-{origin_cls}">
  <div class="qa-body compact">
    <div class="summary-meta">{badge} {used_badge}<span>{esc(row['question'])}</span></div>
  </div>
</div>
"""
            )
        parts.append("</div></div></details>")
    parts.append("</div></div></details>")

    # Unused
    parts.append(
        f"""
<details class="section" id="unused">
  <summary><span class="summary-meta"><h2>3. PAA в pool, но не попали на сайт</h2><span class="count-pill">{len(unused_paa)}</span></span></summary>
  <div class="section-body">
    <p class="intro">Были в dataset.paa_pool, но не совпали с опубликованным FAQ (блок-лист, дубликат, не влезли в лимит 4–6, или не нашли релевантную страницу).</p>
    <div class="card-list">
"""
    )
    for e in unused_paa:
        targets = ", ".join(e.get("targets") or [])
        parts.append(
            f"""
<details class="fold">
  <summary><span class="summary-meta"><span class="badge badge-paa">PAA</span><span>{esc(e.get('question'))}</span></span></summary>
  <div class="fold-body">
    <div class="meta-grid">
      <div class="meta-row"><b>Seed</b><div><span class="seed-chip">{esc(e.get('seed') or '')}</span></div></div>
      <div class="meta-row"><b>Targets</b><div class="mono">{esc(targets)}</div></div>
    </div>
  </div>
</details>
"""
        )
    parts.append("</div></div></details>")

    # Blocked
    parts.append(
        f"""
<details class="section" id="blocked">
  <summary><span class="summary-meta"><h2>4. Заблокированные PAA</h2><span class="count-pill">{len(blocked_raw)}</span></span></summary>
  <div class="section-body">
    <p class="intro">Не публикуем verbatim: Home Depot / Lowes / Pella / Renewal by Andersen / lawsuit / free и т.п. (QUESTION_BLOCKLIST в apply_seo_from_research.py).</p>
    <div class="card-list">
"""
    )
    for row in blocked_raw:
        parts.append(
            f'<div class="qa"><div class="qa-body compact"><span class="badge badge-blocked">Blocked</span> <span class="seed-chip">{esc(row["seed"])}</span> {esc(row["question"])}</div></div>'
        )
    parts.append("</div></div></details>")

    parts.append(
        """
<p class="footer">Собрано из page-metadata + seo-research/paa + dataset.json · noindex · Deluxe Windows</p>
</div>
<script>
(function () {
  const qInput = document.getElementById('filter-q');
  const originSel = document.getElementById('filter-origin');
  const pages = Array.from(document.querySelectorAll('.page[data-search]'));
  const qas = Array.from(document.querySelectorAll('.qa[data-origin]'));

  function apply() {
    const q = (qInput.value || '').trim().toLowerCase();
    const origin = originSel.value;

    qas.forEach((el) => {
      const hay = el.getAttribute('data-search') || '';
      const o = el.getAttribute('data-origin') || '';
      const matchQ = !q || hay.includes(q);
      const matchO = !origin || o === origin;
      el.classList.toggle('hidden', !(matchQ && matchO));
      if (matchQ && matchO && (q || origin)) {
        const parent = el.closest('details');
        if (parent) parent.open = true;
      }
    });

    pages.forEach((page) => {
      const hay = page.getAttribute('data-search') || '';
      const origins = page.getAttribute('data-origins') || '';
      const visibleQa = page.querySelectorAll('.qa[data-origin]:not(.hidden)').length;
      const matchQ = !q || hay.includes(q) || visibleQa > 0;
      const matchO = !origin || origins.split(',').includes(origin);
      const show = matchQ && matchO && (origin ? visibleQa > 0 : true);
      page.classList.toggle('hidden', !show);
      if (show && (q || origin)) page.open = true;
    });

    document.querySelectorAll('details.family').forEach((fam) => {
      const visible = fam.querySelectorAll('.page:not(.hidden)').length;
      fam.classList.toggle('hidden', visible === 0);
      if (visible > 0 && (q || origin)) fam.open = true;
    });
  }

  qInput.addEventListener('input', apply);
  originSel.addEventListener('change', apply);
  document.getElementById('expand-all').addEventListener('click', () => {
    document.querySelectorAll('details').forEach((d) => { if (!d.classList.contains('hidden')) d.open = true; });
  });
  document.getElementById('collapse-all').addEventListener('click', () => {
    document.querySelectorAll('details.page, details.qa, details.fold:not(.family)').forEach((d) => { d.open = false; });
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
    print(
        f"published={len(published)} paa={counts['paa']} gen={counts['generated']} "
        f"bank={counts['bank']} raw_paa={len(raw_paa)} unused={len(unused_paa)} blocked={len(blocked_raw)}"
    )


if __name__ == "__main__":
    main()
