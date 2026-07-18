"""Apply the approved SEO workbook to file-backed page metadata.

The workbook is treated as the research baseline. Titles and descriptions are
rewritten where needed to remove placeholders, keep every page unique, preserve
search-intent separation, and ensure all public SEO copy is English.

Schema and FAQ data are intentionally left unchanged.
"""

from __future__ import annotations

import html
import json
import re
from collections import Counter
from pathlib import Path

import openpyxl


ROOT = Path(__file__).resolve().parents[1]
WORKBOOK = ROOT / "Deluxe_Windows_SEO_content_map.xlsx"
METADATA_ROOT = ROOT / "database" / "data" / "page-metadata"
SHEET = "01 SEO-карта страниц"

TITLE_FIELD = "Title (≤60 зн.)"
DESCRIPTION_FIELD = "Meta Description (150-160 зн.)"
H1_FIELD = "H1"
KEYWORDS_FIELD = "Целевые ключи"

CYRILLIC = re.compile(r"[А-Яа-яЁё]")
PLACEHOLDER = re.compile(r"^\s*\(", re.UNICODE)

PAGE_TYPE = {
    "Главная": "homepage",
    "Служебная/хаб": "hub",
    "Бренд": "brand",
    "Бренд+тип окна": "brand-material",
    "Локальная: город": "local-city",
    "Локальная: округ": "local-county",
    "Тип окна (материал)": "window-material",
    "Тип двери (материал)": "door-material",
    "Серия/коллекция": "product-series",
    "Блог": "informational",
}

SHORT_BRAND = {
    "All Weather Architectural Aluminum": "All Weather Aluminum",
    "Western Window Systems": "Western Window Systems",
}

COLLECTION_BRAND = {
    "brickmould-300": "Simonton",
    "brickmould-600": "Simonton",
    "contractor": "Simonton",
    "classic-series": "Ply Gem",
    "east-2000-series": "Ply Gem",
    "east-5100-series": "Ply Gem",
    "east-premium-series": "Ply Gem",
    "east-pro-series": "Ply Gem",
    "west-400-series": "Ply Gem",
    "west-pro-series-200": "Ply Gem",
    "west-pro-series-700": "Ply Gem",
    "fairfield-70-series-vinyl-windows": "Alside",
    "fairfield-80-series-vinyl-windows": "Alside",
    "fusion-r-quality-vinyl-windows": "Alside",
    "mezzo-r-energy-efficient-vinyl-windows-west-coast": "Alside",
    "sovereign-ultra-premium-vinyl-windows": "Alside",
    "valuepro-tm---affordable-vinyl-windows": "Alside",
    "vero-tm-vinyl-windows---true-quality-true-value": "Alside",
    "series-8630": "Western Window Systems",
    "series-8660-8665-8670": "Western Window Systems",
}

STATIC_TITLES = {
    "/": "Bay Area Window & Door Replacement | Deluxe Windows",
    "/windows": "Replacement Windows Bay Area | Deluxe Windows",
    "/doors": "Entry & Patio Doors Bay Area | Deluxe Windows",
    "/brand": "Window & Door Brands | Deluxe Windows",
    "/contacts": "Contact Our Burlingame Showroom | Deluxe Windows",
    "/faq": "Window Replacement FAQ | Deluxe Windows",
    "/about": "About Deluxe Windows | Bay Area Window Experts",
    "/financing": "Window & Door Financing | Deluxe Windows",
    "/special-offers": "Window Replacement Offers | Deluxe Windows",
    "/blog": "Window & Door Advice Blog | Deluxe Windows",
    "/gallery": "Window & Door Project Gallery | Deluxe Windows",
    "/glossary": "Window & Door Glossary | Deluxe Windows",
    "/testimonials": "Deluxe Windows Reviews | Bay Area Customers",
}

STATIC_DESCRIPTIONS = {
    "/": (
        "Premium window and door replacement across the Bay Area. Compare Milgard, "
        "Marvin, Andersen and more. Visit our showroom or get a free estimate."
    ),
    "/windows": (
        "Explore replacement windows for Bay Area homes by material, style and brand. "
        "Compare energy-efficient options and request a free in-home estimate."
    ),
    "/doors": (
        "Explore entry, patio and exterior doors for Bay Area homes. Compare materials, "
        "styles and leading brands, then request a free in-home estimate."
    ),
    "/brand": (
        "Compare window and door brands available from Deluxe Windows, including Milgard, "
        "Marvin, Andersen, Simonton and more. Request a free estimate."
    ),
    "/contacts": (
        "Contact Deluxe Windows or visit our Burlingame showroom for help choosing "
        "replacement windows and doors. Schedule a free in-home estimate today."
    ),
    "/faq": (
        "Get clear answers about window replacement costs, installation, permits, "
        "financing, warranties and materials from our experienced Bay Area team."
    ),
    "/about": (
        "Meet Deluxe Windows, a trusted Bay Area window and door company offering expert "
        "product guidance, precise installation and free in-home estimates."
    ),
    "/financing": (
        "Explore flexible window and door financing options for your Bay Area project. "
        "Review payment choices and request a free estimate from Deluxe Windows."
    ),
    "/special-offers": (
        "View current window and door replacement offers from Deluxe Windows. Save on "
        "select Bay Area projects and request a free in-home estimate today."
    ),
    "/blog": (
        "Read practical window and door guides for Bay Area homeowners, including buying "
        "advice, energy tips, materials and replacement planning."
    ),
    "/gallery": (
        "Browse completed window and door installations by Deluxe Windows across the Bay "
        "Area. Explore project ideas, products and finishes for your home."
    ),
    "/glossary": (
        "Learn essential window and door terms, from Low-E glass and frame materials to "
        "installation methods, energy ratings and common product features."
    ),
    "/testimonials": (
        "Read reviews from Bay Area homeowners who chose Deluxe Windows for window and "
        "door replacement, product guidance and professional installation."
    ),
}

BLOG_SEO = {
    "/blog/do-new-windows-increase-home-value-for-bay-area-homeowners": (
        "Do New Windows Increase Home Value? [2026] | Deluxe",
        "Do New Windows Increase Home Value for Bay Area Homes?",
        "New windows can increase home value by improving efficiency, comfort and curb "
        "appeal. See what Bay Area homeowners should consider before replacing windows.",
    ),
    "/blog/how-to-measure-windows-for-replacement": (
        "How to Measure Replacement Windows [2026] | Deluxe",
        "How to Measure Windows for Replacement",
        "Measure replacement windows at three points for width and height, then use the "
        "smallest dimensions. Follow our step-by-step Bay Area measurement guide.",
    ),
    "/blog/the-ultimate-door-buyers-guide": (
        "Door Buyer's Guide [2026] | Deluxe Windows",
        "The Complete Door Buyer's Guide",
        "Choose the right entry or patio door by comparing materials, security, glass, "
        "energy performance and styles in this practical Bay Area buyer's guide.",
    ),
    "/blog/what-kind-of-window-frame-is-right-for-you": (
        "Best Window Frame Materials [2026] | Deluxe Windows",
        "Which Window Frame Material Is Right for You?",
        "Compare vinyl, wood, aluminum and fiberglass window frames by cost, maintenance, "
        "durability and efficiency to find the best fit for your Bay Area home.",
    ),
    "/blog/window-buyers-guide": (
        "Window Buyer's Guide [2026] | Deluxe Windows",
        "Complete Window Buyer's Guide",
        "Choose replacement windows with confidence. Compare frame materials, styles, "
        "glass, energy ratings and installation options for Bay Area homes.",
    ),
    "/blog/5-quick-and-easy-ways-on-how-to-temporarily-fix-a-window": (
        "5 Temporary Fixes for a Broken Window [2026] | Deluxe",
        "5 Ways to Temporarily Fix a Broken Window",
        "Use these five temporary fixes to reduce drafts, cover cracks and secure a "
        "damaged window until a professional Bay Area replacement can be scheduled.",
    ),
    "/blog/comprehensive-guide-to-choosing-the-right-replacement-windows": (
        "How to Choose Replacement Windows [2026] | Deluxe",
        "How to Choose the Right Replacement Windows",
        "Compare replacement window materials, styles, glass and energy ratings before "
        "you buy. Use this guide to plan the right upgrade for your Bay Area home.",
    ),
    "/blog/do-energy-efficient-windows-and-doors-make-a-difference-for-bay-area-homeowners": (
        "Do Energy-Efficient Windows Work? [2026] | Deluxe",
        "Do Energy-Efficient Windows and Doors Make a Difference?",
        "Energy-efficient windows and doors can improve comfort and reduce heat transfer. "
        "Learn which features matter most for Bay Area homes and utility costs.",
    ),
    "/blog/how-can-replacement-windows-keep-your-home-cool": (
        "How Replacement Windows Keep Homes Cool [2026] | Deluxe",
        "How Replacement Windows Help Keep Your Home Cool",
        "Replacement windows help keep homes cooler with Low-E glass, insulated frames "
        "and tighter seals. Learn which options perform best in Bay Area climates.",
    ),
    "/blog/how-long-do-windows-last": (
        "How Long Do Windows Last? Lifespan Guide [2026] | Deluxe",
        "How Long Do Windows Last?",
        "Most windows last 15 to 30 years, depending on material, climate and maintenance. "
        "Learn the warning signs that your Bay Area windows need replacement.",
    ),
    "/blog/new-construction-windows-vs-replacement-windows": (
        "New Construction vs Replacement Windows [2026] | Deluxe",
        "New Construction Windows vs. Replacement Windows",
        "New construction windows replace the full framed opening, while replacement "
        "windows fit an existing opening. Compare costs, scope and the best use cases.",
    ),
    "/blog/why-vinyl-windows-are-the-smart-choice-for-bay-area-homeowners": (
        "Why Choose Vinyl Windows? [2026] | Deluxe Windows",
        "Why Vinyl Windows Work Well for Bay Area Homes",
        "Vinyl windows offer low maintenance, strong efficiency and practical pricing. "
        "See why they are a popular replacement choice for Bay Area homeowners.",
    ),
}


def normalize_path(value: object) -> str:
    path = "/" + str(value or "").strip().strip("/")
    return "/" if path == "/" else path


def clean_text(value: object) -> str:
    text = html.unescape(str(value or ""))
    return re.sub(r"\s+", " ", text).strip()


def english_value(value: object) -> bool:
    text = clean_text(value)
    return bool(text) and not PLACEHOLDER.match(text) and not CYRILLIC.search(text)


def title_case_slug(slug: str) -> str:
    special = {
        "jeld-wen": "JELD-WEN",
        "ply-gem": "Ply Gem",
        "all-weather": "All Weather",
        "wood-clad": "Wood-Clad",
    }
    if slug in special:
        return special[slug]
    return " ".join(word.capitalize() for word in slug.split("-"))


def compact_brand(name: str) -> str:
    return SHORT_BRAND.get(name, name)


def brand_and_material(path: str, brand_names: dict[str, str]) -> tuple[str, str]:
    slug = path.rsplit("/", 1)[-1]
    prefix_matches = [
        candidate for candidate in brand_names if slug.startswith(candidate + "-")
    ]
    if prefix_matches:
        brand_slug = max(prefix_matches, key=len)
        material_slug = slug[len(brand_slug) + 1 :]
    else:
        embedded_matches = [
            candidate
            for candidate in brand_names
            if f"-{candidate}-" in f"-{slug}-"
        ]
        if not embedded_matches:
            raise RuntimeError(f"Cannot identify brand for {path}")
        brand_slug = max(embedded_matches, key=len)
        material_slug = re.sub(
            rf"(^|-){re.escape(brand_slug)}(-|$)",
            "-",
            slug,
        ).strip("-")
    material_slug = re.sub(r"-windows$", "", material_slug)
    material_slug = material_slug.replace("aluminium", "aluminum")
    return brand_names[brand_slug], title_case_slug(material_slug)


def page_entity(row: dict[str, object], brand_names: dict[str, str]) -> str:
    path = normalize_path(row["URL"])
    page_type = PAGE_TYPE[str(row["Тип страницы"])]
    h1 = clean_text(row[H1_FIELD])

    if page_type == "brand":
        product = "Doors" if path.startswith("/door-brands/") else "Windows"
        return h1.split(f" {product}", 1)[0]
    if page_type == "brand-material":
        brand, material = brand_and_material(path, brand_names)
        return f"{brand} {material} Windows"
    if page_type == "local-city":
        return h1.removeprefix("Window Replacement in ").removesuffix(", California")
    if page_type == "local-county":
        return h1.removeprefix("Window & Door Replacement in ")
    if page_type in {"window-material", "door-material"}:
        prefix = path.rsplit("/", 1)[-1]
        prefix = re.sub(r"-(windows|doors)$", "", prefix)
        return title_case_slug(prefix)
    if page_type == "product-series":
        slug = path.rsplit("/", 1)[-1]
        brand = COLLECTION_BRAND.get(slug)
        if brand and not h1.lower().startswith(brand.lower()):
            return f"{brand} {h1}"
    return h1


def build_title(
    row: dict[str, object],
    current: dict[str, object],
    brand_names: dict[str, str],
) -> str:
    path = normalize_path(row["URL"])
    page_type = PAGE_TYPE[str(row["Тип страницы"])]
    entity = page_entity(row, brand_names)

    if path in STATIC_TITLES:
        return STATIC_TITLES[path]
    if path in BLOG_SEO:
        return BLOG_SEO[path][0]
    if page_type == "brand":
        product = "Doors" if path.startswith("/door-brands/") else "Windows"
        brand = compact_brand(entity)
        title = f"{brand} {product} Bay Area | Deluxe Windows"
        if len(title) > 60:
            title = f"{brand} {product} | Deluxe Windows"
        return title
    if page_type == "brand-material":
        brand, material = brand_and_material(path, brand_names)
        title = f"{compact_brand(brand)} {material} Windows | Deluxe Windows"
        if len(title) > 60:
            title = f"{compact_brand(brand)} {material} Windows | Deluxe"
        return title
    if page_type == "local-city":
        title = f"Window Replacement {entity}, CA | Deluxe Windows"
        if len(title) > 60:
            title = f"Replacement Windows {entity}, CA | Deluxe"
        return title
    if page_type == "local-county":
        title = f"{entity} Window Replacement | Deluxe Windows"
        if len(title) > 60:
            title = f"{entity} Replacement Windows | Deluxe"
        return title
    if page_type == "window-material":
        return f"{entity} Windows Bay Area | Deluxe Windows"
    if page_type == "door-material":
        return f"{entity} Doors Bay Area | Deluxe Windows"
    if page_type == "product-series":
        title = f"{entity} | Deluxe Windows"
        if len(title) > 60:
            title = f"{entity} | Deluxe"
        if len(title) > 60:
            for full, short in SHORT_BRAND.items():
                title = title.replace(full, short)
        if len(title) > 60:
            compact = entity
            for phrase in (
                " Energy Efficient",
                " Ultra Premium",
                " Affordable",
                " True Quality True Value",
                " West Coast",
                " Quality",
            ):
                compact = compact.replace(phrase, "")
            title = f"{compact} | Deluxe Windows"
        return title

    proposed = clean_text(row[TITLE_FIELD])
    if english_value(proposed) and len(proposed) <= 60:
        return proposed

    return clean_text(current["seo"]["title"])


def build_h1(
    row: dict[str, object],
    current: dict[str, object],
    brand_names: dict[str, str],
) -> str:
    path = normalize_path(row["URL"])
    page_type = PAGE_TYPE[str(row["Тип страницы"])]

    if path in BLOG_SEO:
        return BLOG_SEO[path][1]
    if page_type == "brand-material":
        brand, material = brand_and_material(path, brand_names)
        return f"{brand} {material} Windows"

    proposed = clean_text(row[H1_FIELD])
    if english_value(proposed):
        return proposed

    schema_data = current.get("schema", {}).get("data", {})
    return clean_text(
        schema_data.get("headline")
        or schema_data.get("name")
        or current["seo"]["title"].split("|", 1)[0]
    )


def description_seed(
    row: dict[str, object],
    current: dict[str, object],
    brand_names: dict[str, str],
) -> str:
    path = normalize_path(row["URL"])
    page_type = PAGE_TYPE[str(row["Тип страницы"])]
    entity = page_entity(row, brand_names)

    if path in STATIC_DESCRIPTIONS:
        return STATIC_DESCRIPTIONS[path]
    if path in BLOG_SEO:
        return BLOG_SEO[path][2]
    if page_type == "brand":
        product = "door" if path.startswith("/door-brands/") else "window"
        return (
            f"Compare {entity} {product} collections for Bay Area homes, including "
            f"popular styles, energy options and warranty coverage. Request a free estimate."
        )
    if page_type == "brand-material":
        brand, material = brand_and_material(path, brand_names)
        return (
            f"Compare {brand} {material.lower()} windows, available series, energy "
            f"performance and installed pricing. Get Bay Area guidance and a free estimate."
        )
    if page_type == "local-city":
        return (
            f"Get expert window replacement in {entity}, CA with leading brands, "
            f"professional installation and solutions for local homes. Request a free estimate."
        )
    if page_type == "local-county":
        return (
            f"Explore window and door replacement across {entity}. Compare leading brands, "
            f"energy-efficient products and professional installation. Get a free estimate."
        )
    if page_type == "window-material":
        return (
            f"Compare {entity.lower()} windows for Bay Area homes, including leading brands, "
            f"energy ratings and installed pricing. Request a free in-home estimate."
        )
    if page_type == "door-material":
        return (
            f"Compare {entity.lower()} entry and patio doors for Bay Area homes. Explore "
            f"leading brands, custom sizes and professional installation. Get a free estimate."
        )
    if page_type == "product-series":
        return (
            f"Explore {entity} specifications, glass, colors, hardware and installed pricing "
            f"for Bay Area homes. Compare options and request a free estimate."
        )

    proposed = clean_text(row[DESCRIPTION_FIELD])
    return proposed if english_value(proposed) else clean_text(current["seo"]["description"])


def fit_description(text: str) -> str:
    text = clean_text(text)
    replacements = (
        ("professionally installed", "installed"),
        ("professional installation", "expert installation"),
        ("available series", "series"),
        ("energy performance", "efficiency"),
        ("Request a free in-home estimate.", "Request a free estimate."),
    )
    for old, new in replacements:
        if len(text) <= 160:
            break
        text = text.replace(old, new)

    if len(text) > 160:
        sentences = [
            sentence.strip()
            for sentence in re.split(r"(?<=[.!?])\s+", text)
            if sentence.strip()
        ]
        if "free estimate" in text.lower() and len(sentences) > 1:
            base = " ".join(
                sentence
                for sentence in sentences
                if "free estimate" not in sentence.lower()
            )
            cta = " Request a free estimate."
            if len(base) + len(cta) > 160:
                limit = 160 - len(cta) - 1
                base = base[:limit].rsplit(" ", 1)[0].rstrip(" ,;:-") + "."
            text = base.rstrip() + cta
        else:
            shortened = text[:159].rsplit(" ", 1)[0].rstrip(" ,;:-")
            text = shortened + "."

    additions = (
        " Request a free estimate.",
        " Get expert Bay Area guidance.",
        " Explore your options today.",
        " Visit our Burlingame showroom.",
        " Learn more today.",
        " Get expert help.",
        " Contact our team.",
    )
    for addition in additions:
        if len(text) >= 145:
            break
        if "free estimate" in addition.lower() and "free estimate" in text.lower():
            continue
        if addition.strip().lower() not in text.lower() and len(text) + len(addition) <= 160:
            text += addition

    return text


def parse_keywords(
    row: dict[str, object],
    h1: str,
    path: str,
    brand_names: dict[str, str],
) -> list[str]:
    raw = clean_text(row.get(KEYWORDS_FIELD))
    keywords = [
        item.strip().lower()
        for item in raw.split(";")
        if item.strip() and not CYRILLIC.search(item)
    ]
    if not keywords:
        keywords = [h1.lower(), path.rsplit("/", 1)[-1].replace("-", " ")]

    page_type = PAGE_TYPE[str(row["Тип страницы"])]
    if page_type == "brand":
        if path.startswith("/door-brands/"):
            brand = page_entity(row, brand_names)
            phrase = f"{brand} doors".lower()
            keywords = [
                phrase,
                f"{brand} patio doors".lower(),
                f"{brand} sliding doors".lower(),
                f"{brand} entry doors".lower(),
            ]
    elif page_type == "brand-material":
        brand, material = brand_and_material(path, brand_names)
        phrase = f"{brand} {material} windows".lower()
        keywords = [phrase, f"{phrase} prices"]
    elif page_type == "product-series":
        slug = path.rsplit("/", 1)[-1]
        if slug in COLLECTION_BRAND:
            phrase = h1.lower()
            keywords = [phrase, f"{phrase} prices"]

    return list(dict.fromkeys(keywords))


def load_rows() -> list[dict[str, object]]:
    workbook = openpyxl.load_workbook(WORKBOOK, data_only=True, read_only=True)
    values = list(workbook[SHEET].values)
    headers = [str(value) for value in values[0]]
    return [dict(zip(headers, row)) for row in values[1:]]


def load_metadata() -> tuple[dict[str, dict[str, object]], dict[str, Path]]:
    records: dict[str, dict[str, object]] = {}
    paths: dict[str, Path] = {}
    for file in METADATA_ROOT.rglob("*.json"):
        data = json.loads(file.read_text(encoding="utf-8"))
        path = normalize_path(data["path"])
        records[path] = data
        paths[path] = file
    return records, paths


def assert_unique(values: list[str], label: str) -> None:
    duplicates = [value for value, count in Counter(values).items() if count > 1]
    if duplicates:
        raise RuntimeError(f"Duplicate {label}: {duplicates}")


def main() -> None:
    rows = load_rows()
    metadata, files = load_metadata()
    row_paths = {normalize_path(row["URL"]) for row in rows}

    if row_paths != set(metadata):
        raise RuntimeError(
            f"Workbook/metadata coverage mismatch. "
            f"Missing: {sorted(set(metadata) - row_paths)}; "
            f"unknown: {sorted(row_paths - set(metadata))}"
        )

    brand_names: dict[str, str] = {}
    for row in rows:
        path = normalize_path(row["URL"])
        if path.startswith("/brands/"):
            slug = path.rsplit("/", 1)[-1]
            brand_names[slug] = page_entity(row, {})

    results: list[dict[str, object]] = []
    used_primary: set[str] = set()

    for row in rows:
        path = normalize_path(row["URL"])
        data = metadata[path]
        seo = data["seo"]
        existing_schema = json.dumps(data.get("schema"), sort_keys=True)
        existing_faq = json.dumps(data.get("faq"), sort_keys=True)

        title = build_title(row, data, brand_names)
        h1 = build_h1(row, data, brand_names)
        description = fit_description(description_seed(row, data, brand_names))
        keywords = parse_keywords(row, h1, path, brand_names)
        primary = keywords[0]

        if primary in used_primary:
            primary = h1.lower()
            if primary in used_primary:
                primary = f"{h1.lower()} {path.rsplit('/', 1)[-1].replace('-', ' ')}"
            keywords = [primary, *[keyword for keyword in keywords if keyword != primary]]
        used_primary.add(primary)

        og = seo.get("og") if isinstance(seo.get("og"), dict) else {}
        twitter = seo.get("twitter") if isinstance(seo.get("twitter"), dict) else {}
        image = clean_text(og.get("image"))

        seo.update(
            {
                "title": title,
                "description": description,
                "canonical": f"https://www.deluxewindows.com{'' if path == '/' else path}",
                "h1": h1,
                "primary_keyword": primary,
                "target_keywords": keywords,
                "search_intent": PAGE_TYPE[str(row["Тип страницы"])],
                "priority": clean_text(row["Приоритет"]),
                "robots": (
                    "index,follow,max-image-preview:large,"
                    "max-snippet:-1,max-video-preview:-1"
                ),
                "og": {
                    "title": title,
                    "description": description,
                    "image": image,
                    "type": clean_text(og.get("type")) or (
                        "article" if path.startswith("/blog/") and path != "/blog" else "website"
                    ),
                },
                "twitter": {
                    "title": title,
                    "description": description,
                    "image": clean_text(twitter.get("image")) or image,
                    "card": clean_text(twitter.get("card"))
                    or clean_text(seo.get("twitter_card"))
                    or "summary_large_image",
                },
            }
        )
        seo.pop("twitter_card", None)

        if json.dumps(data.get("schema"), sort_keys=True) != existing_schema:
            raise RuntimeError(f"Schema changed unexpectedly for {path}")
        if json.dumps(data.get("faq"), sort_keys=True) != existing_faq:
            raise RuntimeError(f"FAQ changed unexpectedly for {path}")

        files[path].write_text(
            json.dumps(data, indent=2, ensure_ascii=False) + "\n",
            encoding="utf-8",
        )
        results.append(
            {
                "path": path,
                "title": title,
                "description": description,
                "h1": h1,
                "primary_keyword": primary,
            }
        )

    assert_unique([str(item["title"]) for item in results], "titles")
    assert_unique([str(item["description"]) for item in results], "descriptions")
    assert_unique([str(item["h1"]) for item in results], "H1 recommendations")
    assert_unique([str(item["primary_keyword"]) for item in results], "primary keywords")

    errors: list[str] = []
    for item in results:
        path = str(item["path"])
        title = str(item["title"])
        description = str(item["description"])
        for label, value in item.items():
            if isinstance(value, str) and CYRILLIC.search(value):
                errors.append(f"{path}: Cyrillic in {label}")
        if len(title) > 60:
            errors.append(f"{path}: title is {len(title)} characters")
        if not 140 <= len(description) <= 160:
            errors.append(f"{path}: description is {len(description)} characters")

    if errors:
        raise RuntimeError("\n".join(errors))

    print(
        json.dumps(
            {
                "updated": len(results),
                "unique_titles": len({item["title"] for item in results}),
                "unique_descriptions": len({item["description"] for item in results}),
                "unique_h1": len({item["h1"] for item in results}),
                "unique_primary_keywords": len(
                    {item["primary_keyword"] for item in results}
                ),
                "title_max": max(len(str(item["title"])) for item in results),
                "description_min": min(
                    len(str(item["description"])) for item in results
                ),
                "description_max": max(
                    len(str(item["description"])) for item in results
                ),
                "schemas_changed": 0,
                "faqs_changed": 0,
            },
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
