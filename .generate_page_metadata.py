import html
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent
OUT = ROOT / "database" / "data" / "page-metadata"
IMPORTS = ROOT / "webflow-data" / "current" / "imports"
BASE = "https://www.deluxewindows.com"
STATIC_OG_IMAGE = "/webflow-assets/images/684da952cef202b8dda5788c_Meta%20cover-2.jpg"
EXPECTED = set()


def clean(value):
    if value is None:
        return ""
    text = str(value)
    text = re.sub(r"<br\s*/?>", " ", text, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    return re.sub(r"\s+", " ", html.unescape(text)).strip()


def image_url(value):
    if isinstance(value, dict):
        return value.get("url") or ""
    return value if isinstance(value, str) else ""


def meta_description(value, limit=180):
    text = clean(value)
    if len(text) <= limit:
        return text
    shortened = text[: limit + 1].rsplit(" ", 1)[0].rstrip(" ,;:-")
    return shortened + "…"


def write(key, path, title, description, primary_type="WebPage", image="", data=None, og_type="website"):
    EXPECTED.add(key)
    description = meta_description(description)
    payload = {
        "version": 1,
        "key": key,
        "path": path,
        "seo": {
            "title": clean(title),
            "description": description,
            "canonical": BASE + ("" if path == "/" else path),
            "og": {
                "title": clean(title),
                "description": description,
                "image": image_url(image),
                "type": og_type,
            },
            "twitter_card": "summary_large_image",
        },
        "faq": [],
        "schema": {
            "primary_type": primary_type,
            "data": data or {},
            "extra": [],
            "replace": [],
        },
    }
    target = OUT / f"{key}.json"
    target.parent.mkdir(parents=True, exist_ok=True)
    if target.exists():
        return
    target.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


STATIC = {
    "home": ("/", "Deluxe Windows | Window Replacement – San Francisco Bay Area", "Upgrade your Bay Area home with energy-efficient windows. Deluxe Windows offers over 20 years of expert installation of vinyl, aluminum, fiberglass and wood windows.", "WebSite"),
    "windows-index": ("/windows", "Windows for Bay Area Homes | Deluxe Windows California", "Discover high-performance vinyl, wood, aluminum, and fiberglass windows installed by Deluxe Windows across the Bay Area.", "CollectionPage"),
    "doors-index": ("/doors", "Doors for Bay Area Homes | Deluxe Windows California", "Discover stylish, durable, and energy-efficient entry and patio doors installed by Deluxe Windows across the Bay Area.", "CollectionPage"),
    "brand-index": ("/brand", "Top Window & Door Brands | Deluxe Windows – Bay Area", "Explore premium window and door brands including Andersen, Marvin, Milgard, Simonton, and more.", "CollectionPage"),
    "blog-index": ("/blog", "Window Tips & Design Blog | Deluxe Windows – Bay Area", "Expert window tips, buying guides, and design inspiration for Bay Area homeowners from Deluxe Windows.", "CollectionPage"),
    "gallery": ("/gallery", "Photo Gallery | Deluxe Windows – Bay Area", "Browse completed window and door replacement projects across the Bay Area by Deluxe Windows.", "CollectionPage"),
    "glossary": ("/glossary", "Window & Door Glossary | Deluxe Windows", "Learn common window, door, glass, installation, and energy-efficiency terms.", "DefinedTermSet"),
    "faq": ("/faq", "Window & Door FAQs | Deluxe Windows – Bay Area", "Get expert answers about window and door replacement, installation, permits, energy savings, and costs.", "WebPage"),
    "testimonials": ("/testimonials", "Customer Reviews | Deluxe Windows – Bay Area", "Read customer reviews about Deluxe Windows installation, service quality, and experience.", "WebPage"),
    "financing": ("/financing", "Window & Door Financing | Deluxe Windows – Bay Area", "Explore flexible financing options for window and door replacement in the Bay Area.", "WebPage"),
    "about": ("/about", "About Deluxe Windows | Window Experts in the Bay Area", "Discover why Bay Area homeowners trust Deluxe Windows for expert window and door installation.", "AboutPage"),
    "contacts": ("/contacts", "Contact Deluxe Windows | Bay Area Window Experts", "Contact Deluxe Windows for window and door installation across the Bay Area.", "ContactPage"),
    "special-offers": ("/special-offers", "Window Replacement Deals | Special Offers – Deluxe Windows", "Explore Deluxe Windows seasonal discounts, limited-time promotions, and financing offers.", "WebPage"),
}

for slug, (path, title, description, primary) in STATIC.items():
    write(
        f"static/{slug}",
        path,
        title,
        description,
        primary,
        image=STATIC_OG_IMAGE,
    )


def load_items(collection):
    data = json.loads((IMPORTS / f"{collection}.json").read_text(encoding="utf-8"))
    return [
        item for item in data.get("items", [])
        if not item.get("isArchived", False) and not item.get("isDraft", False)
    ]


def generic_description(fd, name):
    for field in (
        "seo-description", "meta-description", "project-summary",
        "property-listing---summary", "property-listing---excerpt",
        "property-listing---about", "agent---about", "long-description",
    ):
        value = clean(fd.get(field))
        if value:
            return value
    return f"Explore {name} from Deluxe Windows for Bay Area homes."


COLLECTIONS = [
    ("windows", "windows", "Product"),
    ("doors", "doors", "Product"),
    ("brands", "brands", "WebPage"),
    ("brand-collections", "brand-collections", "CollectionPage"),
    ("window-type", "window-type", "Product"),
    ("blog", "blog", "BlogPosting"),
]

for collection, route_prefix, primary in COLLECTIONS:
    for item in load_items(collection):
        fd = item.get("fieldData") or {}
        slug = clean(fd.get("slug"))
        if not slug:
            continue
        if collection == "windows" and (fd.get("hide") is True or fd.get("parent-collection") != "Windows"):
            continue
        if collection == "doors" and fd.get("hide") is True:
            continue
        if collection == "window-type" and not fd.get("property-listing---agent"):
            continue
        name = clean(fd.get("name")) or slug.replace("-", " ").title()
        title = clean(fd.get("seo-title") or fd.get("meta-title")) or f"{name} | Deluxe Windows"
        description = generic_description(fd, name)
        image = fd.get("opengraph-image") or fd.get("main-project-image") or fd.get("featured-image") or fd.get("property-listing---featured-image") or ""
        schema_data = {"name": name}
        if primary == "BlogPosting":
            schema_data.update({
                "headline": name,
                "date_published": item.get("lastPublished"),
                "date_modified": item.get("lastUpdated"),
            })
        write(
            f"{route_prefix}/{slug}",
            f"/{route_prefix}/{slug}",
            title,
            description,
            primary,
            image=image,
            data=schema_data,
            og_type="article" if primary == "BlogPosting" else "website",
        )


door_brand_data = {}
door_brand_path = ROOT / "database" / "data" / "door-brands.json"
if door_brand_path.exists():
    door_brand_data = {
        item.get("slug"): item
        for item in json.loads(door_brand_path.read_text(encoding="utf-8"))
        if item.get("slug")
    }

for item in load_items("brands"):
    fd = item.get("fieldData") or {}
    slug = clean(fd.get("slug"))
    if not slug:
        continue
    overlay = door_brand_data.get(slug, {})
    name = clean(fd.get("name")) or clean(overlay.get("name")) or slug.replace("-", " ").title()
    write(
        f"door-brands/{slug}",
        f"/door-brands/{slug}",
        f"{name} Doors | Deluxe Windows",
        clean(overlay.get("description")) or f"Explore {name} doors installed by Deluxe Windows in the Bay Area.",
        "Product",
        image=fd.get("opengraph-image") or fd.get("featured-image") or "",
        data={"name": f"{name} Doors", "brand": name},
    )


for item in load_items("county-hub-pages"):
    fd = item.get("fieldData") or {}
    slug = clean(fd.get("county-slug") or fd.get("slug"))
    if not slug:
        continue
    name = clean(fd.get("county-name") or fd.get("name")) or slug.replace("-", " ").title()
    write(
        f"county-hub-pages/{slug}",
        f"/county-hub-pages/{slug}",
        clean(fd.get("meta-title")) or f"{name} Window Replacement | Deluxe Windows",
        generic_description(fd, name),
        "CollectionPage",
        image=fd.get("hero-image") or "",
        data={"name": name},
    )


for item in load_items("window-replacement"):
    fd = item.get("fieldData") or {}
    slug = clean(fd.get("city-slug") or fd.get("slug"))
    if not slug:
        continue
    city = clean(fd.get("city-name") or fd.get("name")) or slug.replace("-", " ").title()
    write(
        f"window-replacement/{slug}",
        f"/window-replacement/{slug}",
        clean(fd.get("meta-title")) or f"{city} Window Replacement | Deluxe Windows",
        generic_description(fd, city),
        "Service",
        image=fd.get("og-image") or fd.get("hero-image") or "",
        data={"name": f"Window Replacement in {city}", "area_served": city},
    )

actual = {
    str(path.relative_to(OUT).with_suffix("")).replace("\\", "/")
    for path in OUT.rglob("*.json")
}
missing = sorted(EXPECTED - actual)
unexpected = sorted(actual - EXPECTED)
if missing or unexpected:
    raise SystemExit(
        f"Metadata coverage mismatch. Missing: {missing}; unexpected: {unexpected}"
    )

seen_paths = set()
for key in sorted(actual):
    payload = json.loads((OUT / f"{key}.json").read_text(encoding="utf-8"))
    path = payload.get("path")
    seo = payload.get("seo") or {}
    if payload.get("version") != 1 or payload.get("key") != key:
        raise SystemExit(f"Invalid version/key in {key}.json")
    if not path or path in seen_paths:
        raise SystemExit(f"Missing or duplicate public path in {key}.json")
    if not clean(seo.get("title")) or not clean(seo.get("description")):
        raise SystemExit(f"Missing SEO title/description in {key}.json")
    expected_canonical = BASE + ("" if path == "/" else path)
    if seo.get("canonical") != expected_canonical:
        raise SystemExit(f"Canonical mismatch in {key}.json")
    seen_paths.add(path)

print(f"Validated exact coverage for {len(actual)} metadata files in {OUT}")
