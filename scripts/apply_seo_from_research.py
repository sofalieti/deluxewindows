#!/usr/bin/env python3
"""Rebuild SEO metadata and FAQ for every page from the merged research dataset.

Inputs:
- database/data/seo-research/dataset.json (built by build_seo_research_dataset.py)
- database/data/page-metadata/**/*.json   (current file-backed metadata)

Rules enforced here (mirroring PageMetadataRepository):
- Title <= 60 chars, unique site-wide.
- Description 140-160 chars, unique site-wide.
- H1 and primary keyword unique site-wide, English only.
- FAQ questions and answers unique site-wide (case-insensitive), answers >= 80 chars.
- Schema blocks, images and canonical URLs are never modified.
"""

from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
RESEARCH = ROOT / "database" / "data" / "seo-research"
METADATA_ROOT = ROOT / "database" / "data" / "page-metadata"

CYRILLIC = re.compile(r"[А-Яа-яЁё]")

EXCLUDED_FAQ_PATHS = {"/about", "/blog", "/brand", "/gallery", "/glossary", "/testimonials"}

# PAA questions that reference retailers or brands we do not carry, or that
# are trivia rather than buyer questions, are never published verbatim.
QUESTION_BLOCKLIST = (
    "home depot", "lowes", "lowe's", "menards", "pella", "renewal by andersen",
    "window world", "rip-off", "ripoff", "lawsuit", "paid off", "for free",
    "credit score", "who bought", "what is happening", "anderson renewal",
    "in the usa", "top 10 window manufacturers", "competitor",
)

BRAND_NAMES = {
    "all-weather-architectural-aluminum": "All Weather Architectural Aluminum",
    "alside": "Alside",
    "andersen": "Andersen",
    "anlin": "Anlin",
    "italwindows": "Italwindows",
    "jeld-wen": "JELD-WEN",
    "marvin": "Marvin",
    "milgard": "Milgard",
    "ply-gem": "Ply Gem",
    "simonton": "Simonton",
    "western-window-systems": "Western Window Systems",
}

SHORT_BRAND = {
    "All Weather Architectural Aluminum": "All Weather Aluminum",
    "Western Window Systems": "Western Window Systems",
}

BRAND_FACTS = {
    "Andersen": {
        "blurb": "collections across Fibrex composite, wood, vinyl and aluminum-clad lines",
        "cost": "installed Andersen windows in the Bay Area typically run about $700-$1,200 each, with premium series higher",
        "warranty": "Andersen backs windows with a limited warranty of up to 20 years on glass and 10 years on non-glass parts",
        "note": "the 100 Series uses Fibrex composite material that Andersen rates about twice as strong as vinyl",
    },
    "Marvin": {
        "blurb": "premium wood, fiberglass and clad collections such as Essential, Elevate and Ultimate",
        "cost": "installed Marvin windows usually price between $1,000 and $1,800 each depending on series and glass",
        "warranty": "Marvin covers insulating glass for 20 years and most non-glass components for 10 years",
        "note": "Marvin's Ultrex fiberglass is impact resistant and holds paint and shape in coastal climates",
    },
    "Milgard": {
        "blurb": "vinyl, fiberglass and aluminum families including Trinsic, Tuscany, Style Line and Ultra",
        "cost": "Milgard windows typically run $350-$1,200 per unit before installation, one of the widest price spans among major brands",
        "warranty": "Milgard includes a Full Lifetime Warranty for the original homeowner on most residential series",
        "note": "Tuscany uses a classic frame profile while Trinsic maximizes visible glass with narrow frames",
    },
    "Simonton": {
        "blurb": "low-maintenance vinyl collections including DaylightMax with slim frames",
        "cost": "Simonton windows generally range from $200-$1,200 per unit, or roughly $265-$1,500 installed",
        "warranty": "most Simonton residential series carry a limited lifetime warranty for the original owner",
        "note": "Simonton has been an ENERGY STAR partner since 1999",
    },
    "JELD-WEN": {
        "blurb": "a broad portfolio spanning vinyl, wood, and clad-wood windows at several price levels",
        "cost": "JELD-WEN pricing spans economy vinyl through premium clad-wood, so quotes vary widely by series",
        "warranty": "JELD-WEN warranty terms differ by series; vinyl lines typically include limited lifetime coverage",
        "note": "JELD-WEN builds both value-focused vinyl lines and Siteline clad-wood architectural products",
    },
    "Anlin": {
        "blurb": "California-made vinyl replacement windows engineered for West Coast climates",
        "cost": "installed Anlin windows in the Bay Area usually fall in the mid vinyl range, between economy vinyl and premium fiberglass",
        "warranty": "Anlin's double lifetime warranty is transferable to the next homeowner and covers glass breakage on many series",
        "note": "Anlin has manufactured energy-efficient vinyl windows in Clovis, California since 1990",
    },
    "Ply Gem": {
        "blurb": "practical vinyl and aluminum collections across several value levels",
        "cost": "Ply Gem positions most series in the affordable vinyl segment, below premium fiberglass and wood pricing",
        "warranty": "Ply Gem vinyl windows typically include a limited lifetime warranty; aluminum series carry shorter terms",
        "note": "Ply Gem product families cover both replacement and new-construction openings",
    },
    "Alside": {
        "blurb": "value-focused vinyl lines such as Mezzo, Fusion and Sovereign",
        "cost": "Alside windows sit in the value vinyl segment, which keeps whole-house replacement budgets lower",
        "warranty": "Alside vinyl windows are typically covered by a limited lifetime warranty for the original owner",
        "note": "Alside is a major national distributor of exterior building products, including windows and patio doors",
    },
    "Western Window Systems": {
        "blurb": "architectural aluminum windows and moving glass walls for contemporary designs",
        "cost": "Western Window Systems products are premium architectural units priced per opening rather than per standard window",
        "warranty": "Western Window Systems covers its aluminum systems with a limited warranty confirmed per product line",
        "note": "Western Window Systems has specialized in indoor-outdoor living products since 1959",
    },
    "Italwindows": {
        "blurb": "contemporary European-style steel, aluminum and wood window systems",
        "cost": "Italwindows systems are quoted per project because profiles, glazing and finishes are built to order",
        "warranty": "Italwindows warranty terms are confirmed per system and project specification",
        "note": "Italwindows focuses on narrow-profile designs for modern and architectural projects",
    },
    "All Weather Architectural Aluminum": {
        "blurb": "architectural aluminum windows with slim profiles for large contemporary openings",
        "cost": "All Weather architectural aluminum is priced per opening, typically above standard vinyl and below custom steel",
        "warranty": "All Weather covers its aluminum windows with a limited manufacturer warranty confirmed per series",
        "note": "All Weather Architectural Aluminum builds thermally improved frames suited to modern Bay Area designs",
    },
}

MATERIAL_FACTS = {
    "vinyl": {
        "label": "vinyl",
        "lifespan": "20-40 years",
        "cost": "about $300-$800 per window plus installation",
        "door_cost": "sliding vinyl patio doors typically running $800-$5,000 installed depending on panels and glass",
        "benefit": "low maintenance, good insulation and the widest choice of budget-friendly series",
        "care": "vinyl never needs painting; cleaning frames, tracks and weep holes is normally enough",
        "tradeoff": "frames are thicker than aluminum or steel, exterior color choices are narrower, and economy series can distort in strong sun",
    },
    "wood": {
        "label": "wood",
        "lifespan": "30 or more years with proper maintenance",
        "cost": "roughly $700-$1,200 per window before installation",
        "door_cost": "solid wood entry doors starting around $825 and rising with species, glass and custom sizing",
        "benefit": "natural interior character and strong compatibility with traditional architecture",
        "care": "exposed wood needs periodic inspection and finish maintenance to control moisture",
        "tradeoff": "exposed wood needs regular repainting or sealing and costs more upfront than vinyl",
    },
    "wood-clad": {
        "label": "wood-clad",
        "lifespan": "30-50 years when the cladding and seals are maintained",
        "cost": "a premium over bare wood, offset by much lower exterior upkeep",
        "door_cost": "clad-wood doors quoted per configuration, positioned above fiberglass pricing",
        "benefit": "a real-wood interior with a weather-resistant protective exterior shell",
        "care": "the clad exterior rarely needs repainting while interior wood keeps its furniture-grade finish",
        "tradeoff": "pricing sits above vinyl and fiberglass, and damaged cladding sections need factory-matched parts",
    },
    "aluminum": {
        "label": "aluminum",
        "lifespan": "15-30 years, longer for thermally improved architectural frames",
        "cost": "mid-range pricing with slim frames that maximize glass area",
        "door_cost": "aluminum and glass patio doors priced per opening, with typical ranges of $800-$5,000",
        "benefit": "slim sightlines, structural strength and a clean contemporary appearance",
        "care": "factory finishes are low maintenance; keep tracks, drainage paths and seals clean",
        "tradeoff": "bare aluminum conducts heat, so frames without thermal breaks lose energy efficiency",
    },
    "aluminum-clad": {
        "label": "aluminum-clad",
        "lifespan": "30-50 years thanks to the protected wood core",
        "cost": "premium pricing similar to other clad-wood products",
        "door_cost": "aluminum-clad wood doors quoted per configuration in the premium segment",
        "benefit": "a warm wood interior protected by an extruded aluminum exterior that resists UV and weather",
        "care": "the aluminum shell needs only cleaning while the interior wood is finished like furniture",
        "tradeoff": "premium pricing and repairs to dented cladding require factory components",
    },
    "fiberglass": {
        "label": "fiberglass",
        "lifespan": "30-50 years, the longest of common frame materials",
        "cost": "about $800-$1,500+ per window, above vinyl and below custom wood",
        "door_cost": "fiberglass entry doors typically $500-$1,500, with smooth paintable models starting near $900",
        "benefit": "dimensional stability, strength and paintable low-maintenance frames",
        "care": "routine cleaning plus periodic checks of seals and hardware are usually all that is required",
        "tradeoff": "upfront cost runs higher than vinyl and there are fewer budget series to choose from",
    },
    "steel": {
        "label": "steel",
        "lifespan": "50+ years when finishes are maintained",
        "cost": "custom units typically starting around $1,440 each",
        "door_cost": "steel entry doors at $300-$800 for standard models, the most budget-friendly entry material",
        "benefit": "the narrowest sightlines, exceptional strength and a distinctive architectural look",
        "care": "inspect protective finishes and touch up promptly to prevent corrosion",
        "tradeoff": "windows carry the highest price point of common frames, and finishes need upkeep to prevent corrosion",
    },
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

PERMIT_CITIES = {
    "san-francisco": "San Francisco requires a building permit for every window replacement; in-kind swaps can now be permitted online",
    "oakland": "Oakland requires a building permit for any window replacement in residential or commercial structures",
}

BAY_COST = (
    "Bay Area homeowners typically see about $1,500-$3,000 per window installed for quality "
    "vinyl, and more for fiberglass, clad-wood or architectural products"
)

STATIC_SEO = {
    "/": (
        "Window Replacement Bay Area | Deluxe Windows",
        "Bay Area Window & Door Replacement Experts",
        "Window and door replacement across the Bay Area from our Burlingame showroom. Compare Milgard, Marvin, Andersen, Anlin and more, then get a free estimate.",
        "window replacement bay area",
        ["window replacement bay area", "deluxe windows", "window companies bay area", "bay area window and door replacement"],
    ),
    "/windows": (
        "Replacement Windows Bay Area | Deluxe Windows",
        "Replacement Windows for Bay Area Homes",
        "Compare replacement windows by material, style and brand for Bay Area homes. Review vinyl, fiberglass, wood and aluminum options and get a free estimate.",
        "replacement windows",
        ["replacement windows", "windows replacement", "house windows", "new windows"],
    ),
    "/doors": (
        "Entry & Patio Doors Bay Area | Deluxe Windows",
        "Entry, Patio and Sliding Doors for Bay Area Homes",
        "Explore entry, patio and sliding doors for Bay Area homes. Compare fiberglass, wood, steel and vinyl options with installed pricing and a free estimate.",
        "patio doors",
        ["patio doors", "entry doors", "sliding doors", "exterior doors", "door replacement"],
    ),
    "/brand": (
        "Window & Door Brands We Install | Deluxe Windows",
        "Window and Door Brands Available at Deluxe Windows",
        "Compare the window and door brands installed by Deluxe Windows, including Milgard, Marvin, Andersen, Simonton, Anlin and JELD-WEN. Get a free estimate.",
        "window brands",
        ["window brands", "best window brands", "window manufacturers", "door brands"],
    ),
    "/contacts": (
        "Contact Deluxe Windows | Burlingame Showroom",
        "Contact Deluxe Windows — Burlingame Showroom & Estimates",
        "Contact Deluxe Windows or visit the Burlingame showroom to plan window and door replacement. Call, send the project details and book a free estimate.",
        "deluxe windows burlingame",
        ["deluxe windows burlingame", "window showroom bay area", "window replacement estimate", "deluxe windows contact"],
    ),
    "/faq": (
        "Window Replacement FAQ & Costs | Deluxe Windows",
        "Window Replacement Questions, Answered",
        "Get answers about window replacement costs, permits, timelines, financing and warranties in the Bay Area, based on the questions homeowners ask most.",
        "window replacement cost",
        ["window replacement cost", "how much is a window replacement", "window replacement faq", "window installation questions"],
    ),
    "/about": (
        "About Deluxe Windows | Bay Area Window Company",
        "About Deluxe Windows",
        "Learn about Deluxe Windows, a Bay Area window and door company with a Burlingame showroom, factory-trained installers and free in-home estimates.",
        "deluxe windows inc",
        ["deluxe windows inc", "about deluxe windows", "bay area window company"],
    ),
    "/financing": (
        "Window Financing Bay Area | Deluxe Windows",
        "Window and Door Financing Options",
        "Review window and door financing for Bay Area projects, including monthly payment plans. See how approval works and request a free project estimate.",
        "window financing",
        ["window financing", "windows on finance", "window payment plans", "door financing"],
    ),
    "/special-offers": (
        "Window Replacement Deals & Offers | Deluxe Windows",
        "Current Window and Door Offers",
        "See current window and door replacement offers from Deluxe Windows. Check eligibility, combine savings with financing where allowed, and get an estimate.",
        "window replacement deals",
        ["window replacement deals", "window discounts", "window sale bay area", "special offers windows"],
    ),
    "/blog": (
        "Window & Door Advice Blog | Deluxe Windows",
        "Window and Door Advice for Bay Area Homeowners",
        "Read practical window and door guides for Bay Area homeowners: buying advice, materials, measuring, energy efficiency and replacement planning tips.",
        "window advice blog",
        ["window advice blog", "window replacement guides", "door buying advice"],
    ),
    "/gallery": (
        "Window & Door Project Gallery | Deluxe Windows",
        "Completed Window and Door Projects",
        "Browse completed window and door installations by Deluxe Windows across the Bay Area. See real projects, products and finishes to plan your own upgrade.",
        "window installation gallery",
        ["window installation gallery", "window projects bay area", "door installation photos"],
    ),
    "/glossary": (
        "Window & Door Glossary | Deluxe Windows",
        "Window and Door Terms Explained",
        "Learn essential window and door terms, from Low-E glass and dual pane windows to frame materials, energy ratings and common installation methods.",
        "window glossary",
        ["window glossary", "dual pane windows", "window terms", "low-e glass meaning"],
    ),
    "/testimonials": (
        "Deluxe Windows Reviews | Bay Area Customers",
        "What Bay Area Homeowners Say About Deluxe Windows",
        "Read reviews from Bay Area homeowners who chose Deluxe Windows for window and door replacement, product guidance and professional installation.",
        "deluxe windows reviews",
        ["deluxe windows reviews", "window company reviews bay area"],
    ),
}

BLOG_SEO = {
    "/blog/do-new-windows-increase-home-value-for-bay-area-homeowners": (
        "Do New Windows Increase Home Value? | Deluxe",
        "Do New Windows Increase Home Value for Bay Area Homes?",
        "New windows typically return 50-75% of their cost and can add $8,000-$12,000 in value. See what matters most for Bay Area homes before replacing windows.",
        "do new windows increase home value",
        ["do new windows increase home value", "window replacement roi", "new windows appraisal value"],
    ),
    "/blog/how-to-measure-windows-for-replacement": (
        "How to Measure Windows for Replacement | Deluxe",
        "How to Measure Windows for Replacement",
        "Measure width and height at three points and use the smallest numbers. Follow this step-by-step replacement window measuring guide for accurate sizing.",
        "how to measure windows for replacement",
        ["how to measure windows for replacement", "measure replacement windows", "window measurement guide"],
    ),
    "/blog/the-ultimate-door-buyers-guide": (
        "Door Buyer's Guide | Deluxe Windows",
        "The Complete Door Buyer's Guide",
        "Choose the right entry or patio door by comparing materials, security, glass, energy performance and styles in this practical Bay Area buyer's guide.",
        "door buyers guide",
        ["door buyers guide", "how to choose a front door", "entry door comparison"],
    ),
    "/blog/what-kind-of-window-frame-is-right-for-you": (
        "Best Window Frame Materials Compared | Deluxe",
        "Which Window Frame Material Is Right for You?",
        "Compare vinyl, wood, aluminum, fiberglass and steel window frames by cost, lifespan, maintenance and efficiency to pick the best fit for your Bay Area home.",
        "best window frame material",
        ["best window frame material", "window frame materials", "cheapest window frame material"],
    ),
    "/blog/window-buyers-guide": (
        "Window Buyer's Guide | Deluxe Windows",
        "Complete Window Buyer's Guide",
        "Choose replacement windows with confidence. Compare frame materials, styles, glass, energy ratings and installation options for Bay Area homes.",
        "window buyers guide",
        ["window buyers guide", "how to choose replacement windows brands"],
    ),
    "/blog/5-quick-and-easy-ways-on-how-to-temporarily-fix-a-window": (
        "5 Temporary Fixes for a Broken Window | Deluxe",
        "5 Ways to Temporarily Fix a Broken Window",
        "Use these five temporary fixes to reduce drafts, cover cracks and secure a damaged window until a professional Bay Area replacement can be scheduled.",
        "temporary window fix",
        ["temporary window fix", "how to fix a broken window temporarily"],
    ),
    "/blog/comprehensive-guide-to-choosing-the-right-replacement-windows": (
        "How to Choose Replacement Windows | Deluxe",
        "How to Choose the Right Replacement Windows",
        "Compare replacement window materials, styles, glass and energy ratings before you buy. Use this guide to plan the right upgrade for your Bay Area home.",
        "how to choose replacement windows",
        ["how to choose replacement windows", "replacement window guide"],
    ),
    "/blog/do-energy-efficient-windows-and-doors-make-a-difference-for-bay-area-homeowners": (
        "Do Energy-Efficient Windows Work? | Deluxe",
        "Do Energy-Efficient Windows and Doors Make a Difference?",
        "ENERGY STAR windows save about 12% on energy bills and may qualify for a $600 tax credit. See which features matter most for Bay Area homes and comfort.",
        "energy efficient windows",
        ["energy efficient windows", "energy efficient windows tax credit", "do energy efficient windows make a difference"],
    ),
    "/blog/how-can-replacement-windows-keep-your-home-cool": (
        "How Replacement Windows Keep Homes Cool | Deluxe",
        "How Replacement Windows Help Keep Your Home Cool",
        "Replacement windows help keep homes cooler with Low-E glass, insulated frames and tighter seals. Learn which options perform best in Bay Area climates.",
        "windows to keep house cool",
        ["windows to keep house cool", "low-e glass heat reduction"],
    ),
    "/blog/how-long-do-windows-last": (
        "How Long Do Windows Last? Lifespan Guide | Deluxe",
        "How Long Do Windows Last?",
        "Vinyl windows last 20-40 years, fiberglass up to 50, wood 30+ and aluminum 15-30. Learn the warning signs that your Bay Area windows need replacement.",
        "how long do windows last",
        ["how long do windows last", "window lifespan", "how often should windows be replaced"],
    ),
    "/blog/new-construction-windows-vs-replacement-windows": (
        "New Construction vs Replacement Windows | Deluxe",
        "New Construction Windows vs. Replacement Windows",
        "Replacement windows fit an existing opening and cost 50-100% less to install; new construction windows replace the full framed opening. Compare use cases.",
        "new construction windows vs replacement",
        ["new construction windows vs replacement", "retrofit vs full frame windows"],
    ),
    "/blog/why-vinyl-windows-are-the-smart-choice-for-bay-area-homeowners": (
        "Why Choose Vinyl Windows? | Deluxe Windows",
        "Why Vinyl Windows Work Well for Bay Area Homes",
        "Vinyl windows offer low maintenance, strong efficiency and practical pricing. See why they are a popular replacement choice for Bay Area homeowners.",
        "why choose vinyl windows",
        ["why choose vinyl windows", "vinyl windows benefits"],
    ),
}


def norm(text: str) -> str:
    return re.sub(r"\s+", " ", text.casefold().strip())


def slug_words(slug: str) -> str:
    special = {"jeld-wen": "JELD-WEN", "ply-gem": "Ply Gem"}
    if slug in special:
        return special[slug]
    return " ".join(word.capitalize() for word in slug.split("-"))


def family_of(record: dict) -> str:
    return str(record["key"]).split("/", 1)[0]


def clean_question(question: str) -> str:
    question = re.sub(r"\s+", " ", question).strip().strip('"')
    if not question.endswith("?"):
        question += "?"
    return question[0].upper() + question[1:]


def question_allowed(question: str) -> bool:
    lowered = question.casefold()
    return not any(token in lowered for token in QUESTION_BLOCKLIST)


QUESTION_STOPWORDS = {
    "is", "are", "the", "a", "an", "of", "for", "to", "in", "on", "do", "does",
    "did", "what", "which", "who", "whos", "how", "much", "many", "better",
    "best", "than", "vs", "and", "or", "it", "worth", "window", "windows",
    "door", "doors", "i", "my", "your", "you", "can", "should", "there",
}


def question_signature(question: str) -> str:
    words = re.findall(r"[a-z0-9']+", question.casefold())
    content = sorted(set(words) - QUESTION_STOPWORDS)
    return " ".join(content)


KEYWORD_BLOCKLIST = (
    "ventanas", "sivan", "customer service", "login", "phone number",
    "warranty claim", " parts", "craigslist", "repair", "yelp", "review",
)


class UniquePool:
    def __init__(self) -> None:
        self.values: dict[str, str] = {}

    def claim(self, value: str, owner: str) -> bool:
        key = norm(value)
        if key in self.values:
            return False
        self.values[key] = owner
        return True

    def owner(self, value: str) -> str | None:
        return self.values.get(norm(value))


class PageContext:
    def __init__(self, record: dict, dataset_page: dict) -> None:
        self.record = record
        self.path = str(record["path"])
        self.family = family_of(record)
        self.slug = self.path.rsplit("/", 1)[-1]
        self.data = dataset_page
        self.brand: str | None = None
        self.material: str | None = None
        self.city: str | None = None
        self.entity = self.resolve_entity()

    def resolve_entity(self) -> str:
        slug = self.slug
        if self.family in {"brands", "door-brands"}:
            self.brand = BRAND_NAMES.get(slug, slug_words(slug))
            return self.brand
        if self.family in {"windows", "doors"}:
            material_slug = re.sub(r"-(windows|doors)$", "", slug)
            self.material = material_slug
            return slug_words(material_slug)
        if self.family == "window-type":
            text = slug.replace("aluminium", "aluminum")
            for brand_slug, name in sorted(BRAND_NAMES.items(), key=lambda kv: -len(kv[0])):
                if brand_slug in text:
                    self.brand = name
                    text = re.sub(rf"(^|-){re.escape(brand_slug)}(-|$)", "-", text).strip("-")
                    break
            material_slug = re.sub(r"-?windows$", "", text).strip("-")
            self.material = material_slug or None
            material_label = slug_words(material_slug) if material_slug else ""
            return f"{self.brand} {material_label} Windows".replace("  ", " ").strip()
        if self.family == "brand-collections":
            h1 = str(self.record["seo"].get("h1") or "").strip()
            entity = h1 or slug_words(slug)
            brand = COLLECTION_BRAND.get(slug)
            if brand is None:
                for brand_slug, name in sorted(BRAND_NAMES.items(), key=lambda kv: -len(kv[0])):
                    if slug.startswith(f"brand-{brand_slug}"):
                        brand = name
                        break
            self.brand = brand
            if brand and not entity.casefold().startswith(brand.casefold()):
                entity = f"{brand} {entity}"
            return entity
        if self.family == "window-replacement":
            self.city = slug_words(slug)
            return self.city
        if self.family == "county-hub-pages":
            self.city = slug_words(slug)
            return self.city
        if self.family == "blog":
            h1 = str(self.record["seo"].get("h1") or slug_words(slug)).strip()
            return h1.rstrip("?!.").strip()
        return slug_words(slug)

    @property
    def material_facts(self) -> dict:
        key = (self.material or "").replace("aluminium", "aluminum")
        if key in MATERIAL_FACTS:
            return MATERIAL_FACTS[key]
        for candidate in ("aluminum-clad", "wood-clad", "fiberglass", "aluminum", "steel", "vinyl", "wood"):
            if candidate in (self.material or ""):
                return MATERIAL_FACTS[candidate]
        return {
            "label": "window",
            "lifespan": "15-40 years depending on material",
            "cost": "a range set by frame material, glass and size",
            "benefit": "a balance of appearance, comfort and durability",
            "care": "care requirements depend on the selected frame and finish",
        }

    @property
    def brand_facts(self) -> dict:
        return BRAND_FACTS.get(self.brand or "", {
            "blurb": "window and door products suited to different home styles",
            "cost": "pricing is confirmed per series and opening during the estimate",
            "warranty": "warranty terms are confirmed per manufacturer and series",
            "note": "series availability is confirmed for each Bay Area project",
        })


# ---------------------------------------------------------------------------
# SEO meta generation
# ---------------------------------------------------------------------------

def pick_title(candidates: list[str], titles: UniquePool, path: str) -> str:
    for candidate in candidates:
        candidate = re.sub(r"\s+", " ", candidate).strip()
        if len(candidate) <= 60 and titles.claim(candidate, path):
            return candidate
    raise RuntimeError(f"{path}: could not produce a unique title from {candidates}")


def fit_description(text: str, extras: list[str]) -> str:
    text = re.sub(r"\s+", " ", text).strip()
    if len(text) > 160:
        sentences = [s.strip() for s in re.split(r"(?<=[.!?])\s+", text) if s.strip()]
        while len(" ".join(sentences)) > 160 and len(sentences) > 1:
            sentences.pop()
        text = " ".join(sentences)
        if len(text) > 160:
            text = text[:159].rsplit(" ", 1)[0].rstrip(" ,;:-") + "."
    for extra in extras:
        if len(text) >= 140:
            break
        if extra.strip().casefold() not in text.casefold() and len(text) + len(extra) + 1 <= 160:
            text += " " + extra.strip()
    if not 140 <= len(text) <= 160:
        raise RuntimeError(f"Description length {len(text)}: {text}")
    return text


DESCRIPTION_EXTRAS = [
    "Get a free estimate.",
    "Expert Bay Area installation.",
    "Visit our Burlingame showroom.",
    "Compare series and pricing.",
    "Serving the whole Bay Area.",
    "Licensed installers.",
    "Free quotes.",
    "Call today.",
    "Est. 20+ yrs.",
]


def top_queries(ctx: PageContext, limit: int = 6) -> list[str]:
    seen: list[str] = []
    for item in ctx.data.get("queries", []):
        query = str(item["query"])
        layer = str(item.get("layer") or "")
        if layer.startswith("5."):
            continue
        if CYRILLIC.search(query) or len(query) > 60:
            continue
        if any(token in query for token in ("home depot", "lowes", "menards", "used ", "salvage")):
            continue
        if any(token in query for token in KEYWORD_BLOCKLIST):
            continue
        if query not in seen:
            seen.append(query)
        if len(seen) >= limit:
            break
    return seen


def build_seo(ctx: PageContext, titles: UniquePool, descriptions: UniquePool,
              h1s: UniquePool, primaries: UniquePool) -> dict:
    path, family, entity = ctx.path, ctx.family, ctx.entity
    queries = top_queries(ctx)

    if path in STATIC_SEO:
        title, h1, description, primary, keywords = STATIC_SEO[path]
    elif path in BLOG_SEO:
        title, h1, description, primary, keywords = BLOG_SEO[path]
    elif family == "brands":
        brand = SHORT_BRAND.get(entity, entity)
        title = pick_title([
            f"{brand} Windows Bay Area | Authorized Dealer | Deluxe",
            f"{brand} Windows Bay Area | Deluxe Windows",
            f"{brand} Windows | Bay Area Dealer | Deluxe",
        ], titles, path)
        h1 = f"{entity} Windows — Bay Area Dealer & Installer"
        description = fit_description(
            f"{entity} windows for Bay Area homes: {ctx.brand_facts['blurb']}. "
            f"Compare series, warranty coverage and installed pricing with Deluxe Windows.",
            DESCRIPTION_EXTRAS,
        )
        primary = f"{entity.casefold()} windows"
        keywords = queries or [primary, f"{entity.casefold()} windows near me", f"{entity.casefold()} dealer"]
        titles_claimed = True
    elif family == "door-brands":
        brand = SHORT_BRAND.get(entity, entity)
        title = pick_title([
            f"{brand} Doors Bay Area | Patio & Entry | Deluxe",
            f"{brand} Doors Bay Area | Deluxe Windows",
            f"{brand} Doors | Bay Area Dealer | Deluxe",
        ], titles, path)
        h1 = f"{entity} Doors — Patio, Sliding & Entry"
        description = fit_description(
            f"{entity} patio, sliding and entry doors for Bay Area homes. Compare panel styles, "
            f"glass and installed pricing, with expert installation by Deluxe Windows.",
            DESCRIPTION_EXTRAS,
        )
        primary = f"{entity.casefold()} doors"
        keywords = queries or [primary, f"{entity.casefold()} patio doors", f"{entity.casefold()} sliding doors"]
    elif family == "windows":
        facts = ctx.material_facts
        title = pick_title([
            f"{entity} Windows Bay Area | Cost & Brands | Deluxe",
            f"{entity} Windows Bay Area | Deluxe Windows",
            f"{entity} Windows | Bay Area | Deluxe",
        ], titles, path)
        h1 = f"{entity} Windows for Bay Area Homes"
        description = fit_description(
            f"{entity} windows compared for Bay Area homes: typical lifespan {facts['lifespan']}, "
            f"{facts['cost']}, plus leading brands and installation.",
            DESCRIPTION_EXTRAS,
        )
        primary = f"{entity.casefold()} windows"
        keywords = queries or [primary, f"{entity.casefold()} windows cost", f"{entity.casefold()} replacement windows"]
    elif family == "doors":
        facts = ctx.material_facts
        title = pick_title([
            f"{entity} Doors Bay Area | Entry & Patio | Deluxe",
            f"{entity} Doors Bay Area | Deluxe Windows",
            f"{entity} Doors | Bay Area | Deluxe",
        ], titles, path)
        h1 = f"{entity} Doors for Bay Area Homes"
        description = fit_description(
            f"{entity} entry and patio doors for Bay Area homes, offering {facts['benefit']}. "
            f"Compare brands, configurations and installed pricing.",
            DESCRIPTION_EXTRAS,
        )
        primary = f"{entity.casefold()} doors"
        keywords = queries or [primary, f"{entity.casefold()} entry doors", f"{entity.casefold()} patio doors"]
    elif family == "window-type":
        title = pick_title([
            f"{entity} | Prices & Series | Deluxe",
            f"{entity} | Deluxe Windows",
            f"{entity} | Deluxe",
        ], titles, path)
        h1 = entity
        description = fit_description(
            f"{entity} for Bay Area homes: {ctx.brand_facts['note']}. Compare series, glass "
            f"packages and installed pricing with Deluxe Windows.",
            DESCRIPTION_EXTRAS,
        )
        primary = entity.casefold()
        keywords = queries or [primary, f"{primary} prices"]
    elif family == "brand-collections":
        compact = entity
        for phrase in (
            " Energy Efficient", " Ultra Premium", " Affordable",
            " True Quality True Value", " West Coast", " Quality", " R ",
        ):
            compact = compact.replace(phrase, " ")
        compact = re.sub(r"\s+", " ", compact).strip()
        for full, short in SHORT_BRAND.items():
            compact = compact.replace(full, short)
        title = pick_title([
            f"{entity} | Deluxe Windows",
            f"{entity} | Deluxe",
            f"{compact} | Deluxe Windows",
            f"{compact} | Deluxe",
            compact,
        ], titles, path)
        h1 = entity
        description = fit_description(
            f"{entity} specifications for Bay Area homes: styles, glass options, colors, "
            f"hardware and installed pricing, reviewed with Deluxe Windows.",
            DESCRIPTION_EXTRAS,
        )
        primary = entity.casefold()
        keywords = queries or [primary, f"{primary} prices"]
    elif family == "window-replacement":
        city = entity
        title = pick_title([
            f"Window Replacement {city}, CA | Deluxe Windows",
            f"Window Replacement {city} CA | Deluxe",
            f"Replacement Windows {city} | Deluxe",
        ], titles, path)
        h1 = f"Window Replacement in {city}, California"
        permit = PERMIT_CITIES.get(ctx.slug)
        local_note = permit.split(";")[0] if permit else f"Local crews measure, install and warranty every {city} project"
        description = fit_description(
            f"Window replacement in {city}, CA with Milgard, Marvin, Andersen and more. "
            f"{local_note}.",
            DESCRIPTION_EXTRAS,
        )
        primary = f"window replacement {city.casefold()}"
        keywords = queries or [primary, f"replacement windows {city.casefold()}", f"window installers {city.casefold()}"]
    elif family == "county-hub-pages":
        county = entity
        title = pick_title([
            f"{county} Window Replacement | Deluxe Windows",
            f"{county} Replacement Windows | Deluxe",
            f"Window Replacement {county} | Deluxe",
        ], titles, path)
        h1 = f"Window & Door Replacement in {county}"
        description = fit_description(
            f"Window and door replacement across {county}: leading brands, energy-efficient "
            f"products and professional installation for every city we serve.",
            DESCRIPTION_EXTRAS,
        )
        primary = f"window replacement {county.casefold()}"
        keywords = queries or [primary, f"replacement windows {county.casefold()}"]
    else:
        raise RuntimeError(f"Unhandled family {family} for {path}")

    if path in STATIC_SEO or path in BLOG_SEO:
        if not titles.claim(title, path):
            raise RuntimeError(f"{path}: duplicate static title {title}")

    if not descriptions.claim(description, path):
        raise RuntimeError(f"{path}: duplicate description")
    if not h1s.claim(h1, path):
        raise RuntimeError(f"{path}: duplicate H1 {h1}")

    primary = norm(primary)
    if not primaries.claim(primary, path):
        for fallback in (f"{primary} {ctx.slug.replace('-', ' ')}", f"{primary} deluxe"):
            fallback = norm(fallback)
            if primaries.claim(fallback, path):
                primary = fallback
                break
        else:
            raise RuntimeError(f"{path}: cannot find unique primary keyword")

    target = [primary]
    for keyword in (queries if queries else list(keywords)):
        keyword = norm(keyword)
        if keyword and keyword not in target:
            target.append(keyword)
        if len(target) >= 6:
            break
    if len(target) == 1:
        for keyword in keywords:
            keyword = norm(keyword)
            if keyword not in target:
                target.append(keyword)

    return {
        "title": title,
        "h1": h1,
        "description": description,
        "primary_keyword": primary,
        "target_keywords": target,
    }


# ---------------------------------------------------------------------------
# FAQ generation
# ---------------------------------------------------------------------------

def classify_intent(question: str) -> str:
    lowered = question.casefold()
    if any(t in lowered for t in (" vs ", "better than", "difference between", "compare to", "compare with", "better,", "cheaper,")):
        return "comparison"
    if any(t in lowered for t in ("disadvantage", "downside", "cons of", "problems with", "common problems")):
        return "tradeoff"
    if any(t in lowered for t in ("how much", "cost", "price", "expensive", "average cost")):
        return "cost"
    if any(t in lowered for t in ("how long", "lifespan", "last?", "how often")):
        return "lifespan"
    if "warranty" in lowered:
        return "warranty"
    if "permit" in lowered:
        return "permit"
    if any(t in lowered for t in ("good", "quality", "worth", "high end", "junk", "any good", "reliable", "best")):
        return "quality"
    if any(t in lowered for t in ("who makes", "manufactur", "who is behind")):
        return "manufacturer"
    if any(t in lowered for t in ("near me", "where can i buy", "which company", "who is the best company")):
        return "dealer"
    if any(t in lowered for t in ("maintenance", "maintain", "repair", "care")):
        return "maintenance"
    if any(t in lowered for t in ("energy", "efficient", "tax credit", "heat", "insulate", "make a difference")):
        return "energy"
    if any(t in lowered for t in ("install", "measure", "replace all", "at once", "cheapest time")):
        return "installation"
    if lowered.startswith(("what is", "what are", "what does")):
        return "definition"
    return "general"


def competitor_in(question: str, own_brand: str | None) -> str | None:
    lowered = question.casefold()
    for name in ("Andersen", "Milgard", "Marvin", "Simonton", "Anlin", "JELD-WEN", "Ply Gem", "Alside"):
        if name.casefold() in lowered.replace("anderson", "andersen").replace("jeld wen", "jeld-wen"):
            if own_brand and name.casefold() == own_brand.casefold():
                continue
            return name
    return None


def answer_blog(question: str, ctx: PageContext) -> str:
    """Topical answers for PAA questions routed to blog articles."""
    topic = ctx.entity
    intent = classify_intent(question)
    if intent == "lifespan":
        return (
            "Vinyl windows typically last 20-40 years, fiberglass 30-50, wood 30+ with upkeep and aluminum 15-30. "
            "Plan a replacement review once units pass the 20-year mark or show failed seals, drafts or fogged glass."
        )
    if intent == "cost":
        return (
            f"Costs connected to \u201c{topic}\u201d vary with material and scope: {BAY_COST}. "
            "A written Bay Area estimate is the only reliable way to price a specific home."
        )
    if intent == "energy":
        return (
            "Yes — ENERGY STAR certified windows cut energy bills by about 12% on average, and models meeting the "
            "Most Efficient criteria can qualify for the $600 federal 25C tax credit. Comfort gains near windows are usually noticeable immediately."
        )
    if intent == "installation":
        return (
            f"Follow the method described in \u201c{topic}\u201d, then have a professional verify measurements before ordering. "
            "Replacement windows are sized to the smallest of three width and height measurements taken at the jambs."
        )
    return (
        f"The guide \u201c{topic}\u201d covers this in detail: check seal failure, drafts, condensation between panes, "
        "operation problems and rising energy bills — any two of these usually justify a professional assessment."
    )


def answer_paa(question: str, ctx: PageContext) -> str:
    """Compose a page-specific answer for a real PAA question."""
    if ctx.family == "blog":
        return answer_blog(question, ctx)

    entity = ctx.entity
    intent = classify_intent(question)
    brand = ctx.brand_facts
    material = ctx.material_facts
    city = ctx.city
    is_door_page = ctx.family in {"doors", "door-brands"} or "door" in question.casefold()

    if intent == "cost":
        if ctx.family == "window-replacement" and city:
            return (
                f"In {city}, {BAY_COST}. The final number depends on window count, sizes, frame "
                f"material and access, so Deluxe Windows prices each {city} project after on-site measurements."
            )
        if is_door_page and not ctx.brand:
            return (
                f"For {entity.lower()} doors, plan on {material.get('door_cost', material['cost'])}. "
                f"Installed Bay Area totals also reflect the frame condition, hardware and finishing, which "
                f"Deluxe Windows confirms in a written estimate."
            )
        if ctx.brand:
            return (
                f"For {entity}, {brand['cost']}. Exact pricing depends on size, glass package and "
                f"installation conditions, so Deluxe Windows prepares an itemized quote after measuring the openings."
            )
        return (
            f"For {entity.lower()} windows, typical pricing is {material['cost']}. Installed Bay Area totals also "
            f"reflect labor, permits and any opening repairs, which Deluxe Windows confirms in a written estimate."
        )
    if intent == "tradeoff":
        if ctx.brand:
            return (
                f"No brand is perfect: with {entity}, review series-level differences in pricing, lead times and "
                f"hardware. {brand['note']}. Deluxe Windows flags the honest trade-offs of each series before ordering."
            )
        return (
            f"The main trade-offs of {entity.lower()} products: {material['tradeoff']}. "
            f"Whether that matters depends on the home's exposure and budget, which Deluxe Windows reviews openly."
        )
    if intent == "lifespan":
        if ctx.material and not ctx.brand:
            return (
                f"{entity} windows and doors typically serve {material['lifespan']}. Quality of installation, "
                f"sun exposure and upkeep move products to either end of that range, so seals and hardware should be checked periodically."
            )
        return (
            f"Expect {entity} products to serve {material['lifespan']} when installed correctly. "
            f"{brand['warranty']}, and Deluxe Windows registers coverage for every installed project."
        )
    if intent == "warranty":
        return (
            f"{brand['warranty']}. Deluxe Windows registers the product warranty after installation and "
            f"helps Bay Area homeowners document claims if a covered issue ever appears."
        )
    if intent == "permit":
        note = PERMIT_CITIES.get(ctx.slug, "most Bay Area cities require a permit when window sizes or openings change")
        return (
            f"Yes — {note}. Deluxe Windows confirms the exact requirement with the local building department "
            f"and handles the paperwork as part of the installation contract."
        )
    if intent == "quality":
        if ctx.brand:
            return (
                f"{entity} is a solid choice when the series matches the home: the brand offers {brand['blurb']}. "
                f"{brand['note']}. Deluxe Windows compares verified ratings and installed cost before recommending it."
            )
        return (
            f"{entity} products earn their reputation through {material['benefit']}. The right call still depends "
            f"on the home's openings, exposure and budget, which Deluxe Windows reviews during a free assessment."
        )
    if intent == "manufacturer":
        return (
            f"{brand['note']}. Deluxe Windows works directly with the manufacturer's Bay Area distribution, "
            f"so series availability and lead times for {entity} are confirmed before ordering."
        )
    if intent == "dealer":
        return (
            f"Deluxe Windows supplies and installs {entity} products from its Burlingame showroom, serving the "
            f"entire Bay Area. Homeowners can compare samples in person and book a free in-home estimate."
        )
    if intent == "maintenance":
        return (
            f"For {entity.lower()} products, {material['care']}. Following the manufacturer's care schedule also "
            f"keeps the warranty valid, and Deluxe Windows explains the routine at handover."
        )
    if intent == "energy":
        return (
            f"Energy performance for {entity.lower()} comes from the whole unit: frame, Low-E glass, gas fill, spacers "
            f"and installation. ENERGY STAR certified windows save about 12% on energy bills, and qualifying models "
            f"may earn the $600 federal 25C tax credit."
        )
    if intent == "installation":
        if ctx.family == "window-replacement" and city:
            return (
                f"A typical {city} installation replaces most home windows in one to three working days. Replacing "
                f"all windows at once usually lowers the per-window price because setup, permits and crew time are shared."
            )
        return (
            f"Professional installation of {entity.lower()} follows measured openings, square-and-plumb setting, "
            f"insulation, flashing and sealing, then an operating check. Deluxe Windows schedules most Bay Area "
            f"projects within one to three days on site."
        )
    if intent == "comparison":
        rival = competitor_in(question, ctx.brand)
        rival_facts = BRAND_FACTS.get(rival or "")
        if rival and rival_facts:
            return (
                f"{entity} stands out for {brand.get('blurb', material['benefit'])}, while {rival} offers "
                f"{rival_facts['blurb']}. Neither wins outright: {brand.get('cost', material['cost'])}, so the better "
                f"pick depends on budget, style and warranty priorities. Deluxe Windows carries both and shows them side by side."
            )
        return (
            f"The honest comparison depends on priorities: {entity} stands out for {brand.get('blurb', material['benefit'])}, "
            f"while competitors trade differently on price, warranty and looks. {brand.get('cost', material['cost']).capitalize()}. "
            f"Deluxe Windows shows both options side by side in the showroom."
        )
    if intent == "definition":
        product = "doors" if is_door_page else "windows"
        return (
            f"{entity} {product} are built around a {material['label']} frame system, chosen for {material['benefit']}. "
            f"In practice the choice covers frame construction, glazing package and finish, all of which Deluxe Windows "
            f"documents in each product proposal."
        )
    return (
        f"For {entity}, the answer depends on the exact series, glass and installation conditions. "
        f"Deluxe Windows reviews the specifics during a free Bay Area consultation and puts the details in writing."
    )


def generated_fill(ctx: PageContext) -> list[dict[str, str]]:
    """Family-specific fallback questions used to reach the 3-6 range."""
    entity = ctx.entity
    brand = ctx.brand_facts
    material = ctx.material_facts

    if ctx.family in {"brands", "door-brands"}:
        product = "doors" if ctx.family == "door-brands" else "windows"
        return [
            {
                "question": f"Which {entity} {product} does Deluxe Windows install in the Bay Area?",
                "answer": f"Deluxe Windows installs {entity} {product} across the brand's current lineup — {brand['blurb']} — and confirms series availability for each Bay Area project before quoting.",
            },
            {
                "question": f"How do I get installed pricing for {entity} {product}?",
                "answer": f"Installed pricing for {entity} {product} is prepared after on-site measurements: {brand['cost']}. The written quote itemizes product, labor, permits and any opening repairs.",
            },
            {
                "question": f"How long does {entity} {product.rstrip('s')} installation take?",
                "answer": f"Most {entity} {product.rstrip('s')} projects are installed within one to three working days once products arrive. Lead times for {entity} orders are confirmed at contract signing.",
            },
            {
                "question": f"Can I see {entity} {product} before ordering?",
                "answer": f"Yes. The Deluxe Windows showroom in Burlingame displays samples and finish options, and a consultant walks through {entity} configurations that match the home's openings.",
            },
        ]
    if ctx.family in {"windows", "doors"}:
        product = "doors" if ctx.family == "doors" else "windows"
        return [
            {
                "question": f"What are the main benefits of {entity.lower()} {product}?",
                "answer": f"{entity} {product} offer {material['benefit']}. Whether they fit depends on the home's architecture, opening sizes, performance goals and budget.",
            },
            {
                "question": f"How much do {entity.lower()} {product} cost installed in the Bay Area?",
                "answer": f"Plan on {material['cost']} for {entity.lower()} {product}, plus installation that reflects access, removal and finishing. Deluxe Windows provides exact installed pricing after measuring.",
            },
            {
                "question": f"How long do {entity.lower()} {product} last?",
                "answer": f"{entity} {product} typically serve {material['lifespan']}. Installation quality and exposure matter, so Deluxe Windows also reviews flashing and seals during replacement.",
            },
            {
                "question": f"What maintenance do {entity.lower()} {product} need?",
                "answer": f"For {entity.lower()} {product}, {material['care']}. Manufacturer care instructions should be followed to keep the finish and warranty intact.",
            },
        ]
    if ctx.family == "window-type":
        return [
            {
                "question": f"Why choose {entity} for a Bay Area home?",
                "answer": f"{entity} combine {ctx.brand}'s engineering with {material['benefit']}. {brand['note']}, which suits many Bay Area replacement projects.",
            },
            {
                "question": f"What do {entity} cost installed?",
                "answer": f"For {entity}, {brand['cost']}; the material itself usually prices at {material['cost']}. Deluxe Windows quotes each opening after measuring.",
            },
            {
                "question": f"Which series are available for {entity}?",
                "answer": f"Available {entity} series change with the manufacturer's current catalog — {brand['blurb']}. Deluxe Windows confirms the exact options during the consultation.",
            },
            {
                "question": f"Who installs {entity} near me?",
                "answer": f"Deluxe Windows measures, orders and installs {entity} throughout the Bay Area from its Burlingame showroom, and verifies operation and weather sealing after installation.",
            },
        ]
    if ctx.family == "brand-collections":
        return [
            {
                "question": f"What should homeowners know about {entity}?",
                "answer": f"{entity} is part of {ctx.brand or 'the manufacturer'}'s lineup: {brand['blurb']}. Deluxe Windows checks that this collection matches the opening sizes and design goals before recommending it.",
            },
            {
                "question": f"Which configurations does {entity} offer?",
                "answer": f"Styles, sizes, glass packages, colors and hardware for {entity} depend on the current catalog. Deluxe Windows confirms available configurations for each Bay Area order.",
            },
            {
                "question": f"How is installed pricing for {entity} calculated?",
                "answer": f"Installed pricing for {entity} reflects opening dimensions, configuration, glass, finish, quantity and access. As a reference, {brand['cost']}.",
            },
            {
                "question": f"How does {entity} compare with other {ctx.brand or 'brand'} collections?",
                "answer": f"Compare {entity} with sibling collections on frame construction, visible glass area, energy ratings, finish options, warranty and installed cost. {brand['note']}.",
            },
        ]
    if ctx.family == "window-replacement":
        city = ctx.city or entity
        permit = PERMIT_CITIES.get(ctx.slug, f"{city} follows standard California permitting, confirmed per project scope")
        return [
            {
                "question": f"How much does window replacement cost in {city}?",
                "answer": f"For {city} homes, {BAY_COST}. Deluxe Windows itemizes product, labor and permits in a written {city} quote after free on-site measurements.",
            },
            {
                "question": f"Do I need a permit to replace windows in {city}?",
                "answer": f"Permitting: {permit}. Deluxe Windows verifies requirements with the building department and manages the paperwork for {city} installations.",
            },
            {
                "question": f"Which window brands work best for {city} homes?",
                "answer": f"Popular choices in {city} include Milgard, Anlin and Simonton vinyl for value, and Marvin or Andersen for premium projects. The right pick depends on the home's age, style and exposure in {city}.",
            },
            {
                "question": f"How long does window replacement take in {city}?",
                "answer": f"Most {city} projects are measured in one visit and installed in one to three working days once windows arrive, typically four to eight weeks after ordering.",
            },
        ]
    if ctx.family == "county-hub-pages":
        county = entity
        return [
            {
                "question": f"Which cities does Deluxe Windows serve in {county}?",
                "answer": f"Deluxe Windows installs windows and doors across {county}; the service-area pages list covered cities, and coverage for a specific {county} address is confirmed when booking the estimate.",
            },
            {
                "question": f"What does window replacement cost in {county}?",
                "answer": f"Across {county}, {BAY_COST}. Quotes are prepared per address because access, permits and product mix differ between {county} cities.",
            },
            {
                "question": f"Do {county} cities require window replacement permits?",
                "answer": f"Permit rules in {county} are set by each city's building authority and can vary with project scope and historic status. Deluxe Windows checks the requirement for the specific address.",
            },
            {
                "question": f"Which window features matter most in {county}?",
                "answer": f"For {county} homes, weigh solar control, insulation, air leakage and frame durability against the local microclimate. Coastal, hillside and inland {county} homes often need different glass packages.",
            },
        ]
    if ctx.family == "blog":
        topic = entity.rstrip("?!.")
        return [
            {
                "question": f"What is the key takeaway from \u201c{topic}\u201d?",
                "answer": str(ctx.record["seo"].get("description") or "").strip() or f"The guide \u201c{topic}\u201d summarizes what Bay Area homeowners should check before making a decision.",
            },
            {
                "question": f"How should Bay Area homeowners apply \u201c{topic}\u201d?",
                "answer": f"Use \u201c{topic}\u201d as a starting point, then factor in the home's age, opening condition, exposure and budget before choosing products or scheduling work.",
            },
            {
                "question": f"When should I get professional help after reading \u201c{topic}\u201d?",
                "answer": f"Bring in a professional when the situation covered in \u201c{topic}\u201d involves safety, water intrusion, structural damage, uncertain measurements or a full replacement decision.",
            },
        ]
    if ctx.path == "/faq":
        return []
    if ctx.family == "static":
        return STATIC_FILL.get(ctx.path, [])
    raise RuntimeError(f"No fill generator for {ctx.path}")


STATIC_FILL = {
    "/": [
        {
            "question": "What services does Deluxe Windows provide in the Bay Area?",
            "answer": "Deluxe Windows helps homeowners compare, measure and install replacement windows and doors across vinyl, fiberglass, wood, aluminum and steel, working with Milgard, Marvin, Andersen, Anlin, Simonton and more.",
        },
        {
            "question": "Where is the Deluxe Windows showroom?",
            "answer": "The showroom is located in Burlingame, California, with product samples from every carried brand. Visits can be combined with a free in-home measurement anywhere in the Bay Area.",
        },
        {
            "question": "How does a window replacement project start?",
            "answer": "Projects start with a free consultation and site measurements, followed by product selection, a written installation proposal, ordering, installation and a final operating check.",
        },
    ],
    "/contacts": [
        {
            "question": "How do I request an estimate from Deluxe Windows?",
            "answer": "Call, use the contact form or visit the Burlingame showroom with the project address, product type and approximate quantity; an in-home measurement is then scheduled at a convenient time.",
        },
        {
            "question": "What should I prepare before a window consultation?",
            "answer": "Have the property address, the number of openings, any current problems such as drafts or condensation, preferred materials or brands, and any HOA or permit constraints you already know about.",
        },
        {
            "question": "Can windows and doors be discussed in one visit?",
            "answer": "Yes — one consultation can cover windows and doors together so measurements, styles and sequencing are planned as a single Bay Area project with one crew and one timeline.",
        },
    ],
    "/windows": [
        {
            "question": "Which window materials can I compare at Deluxe Windows?",
            "answer": "Vinyl, wood, wood-clad, aluminum, aluminum-clad, fiberglass and steel windows are all available, and the showroom lets homeowners compare frames and glass side by side.",
        },
        {
            "question": "What determines replacement window pricing?",
            "answer": "Installed window pricing depends on material, brand, style, dimensions, glass package, finish, quantity and access. Whole-house projects usually earn better per-window pricing than single openings.",
        },
        {
            "question": "How do I pick the right window style?",
            "answer": "Choose the operating style by weighing ventilation, egress requirements, cleaning access, view area and architectural fit; the same opening can often take double hung, casement or slider designs.",
        },
    ],
    "/doors": [
        {
            "question": "Which door types does Deluxe Windows install?",
            "answer": "Entry, patio, sliding, French and multi-panel doors in fiberglass, wood, wood-clad, steel, vinyl and aluminum, matched to the opening, exposure and security requirements of the home.",
        },
        {
            "question": "How much does door replacement cost in the Bay Area?",
            "answer": "Entry door replacement typically runs $500-$2,000 including the frame, while sliding patio doors range from about $800 to over $5,000 depending on size, panels and material.",
        },
        {
            "question": "Sliding, hinged or folding — how do I choose a patio door?",
            "answer": "Compare opening width, usable floor space, ventilation, accessibility and the indoor-outdoor connection you want; sliding doors save space while folding systems open entire walls.",
        },
    ],
    "/financing": [
        {
            "question": "Can I pay for window replacement in installments?",
            "answer": "Yes — financing with monthly payments is available for qualifying Bay Area projects, and the industry commonly offers promotional terms such as 0% APR periods on approved credit.",
        },
        {
            "question": "When are financing terms confirmed?",
            "answer": "Rates, term length, payment amount and eligibility are confirmed by the financing provider before the agreement is signed, after the installation proposal is priced.",
        },
        {
            "question": "Does financing change the project price?",
            "answer": "No — the product and installation scope are priced first, and financing only changes how the approved amount is paid; promotions compatible with financing are noted in writing.",
        },
    ],
    "/special-offers": [
        {
            "question": "How do I confirm a Deluxe Windows offer is active?",
            "answer": "Check the dates and conditions shown with the offer, then confirm availability when requesting an estimate — promotions change and the written proposal identifies any applied discount.",
        },
        {
            "question": "Which products qualify for current promotions?",
            "answer": "Eligibility depends on the specific offer, product line, quantity and installation scope; the estimate lists qualifying items so there are no surprises at contract time.",
        },
        {
            "question": "Can offers be combined with financing?",
            "answer": "Compatibility between a promotion and a financing program depends on the current written terms of both; Deluxe Windows confirms the combination before the agreement is signed.",
        },
    ],
    "/faq": [],
}

# Questions for the /faq hub taken verbatim from the FAQ bank (cost cluster first).
FAQ_HUB = [
    {
        "question": "How much does window replacement cost in the Bay Area?",
        "answer": "Most Bay Area projects land around $1,500-$3,000 per window installed for quality vinyl, with fiberglass, clad-wood and architectural products higher. Window count, sizes, glass and access move the number, so quotes follow on-site measurements.",
    },
    {
        "question": "How much does it cost to replace all windows in a house?",
        "answer": "Whole-house pricing scales with count: industry data for 2026 shows 8-10 windows at roughly $6,400-$22,000 and 12-15 windows at $9,600-$33,000. Replacing everything at once is usually cheaper per window than phasing the work.",
    },
    {
        "question": "Is it worth replacing 20 year old windows?",
        "answer": "Usually yes, if seals have failed, frames leak air or single-pane glass remains. Twenty-year-old units predate modern Low-E coatings, so replacement improves comfort, cuts energy use by up to 12% and adds resale value.",
    },
    {
        "question": "Do I need a permit to replace windows in the Bay Area?",
        "answer": "Most Bay Area cities require a permit: San Francisco requires one for every window replacement, and Oakland for any residential or commercial swap. Deluxe Windows confirms the rule for your address and files the paperwork.",
    },
    {
        "question": "How long does a window replacement project take?",
        "answer": "Measurement takes one visit, manufacturing typically four to eight weeks, and installation one to three working days for most homes. Larger or specialty orders, such as clad-wood or steel, can extend the manufacturing window.",
    },
    {
        "question": "What is the cheapest time of year to replace windows?",
        "answer": "Late fall and winter often bring shorter manufacturing queues and seasonal promotions in the Bay Area, since the mild climate lets installation continue year-round. The bigger savings usually come from replacing all windows in one project.",
    },
    {
        "question": "Should I choose retrofit or full-frame window replacement?",
        "answer": "Retrofit installation keeps the existing frame and costs less, which suits sound stucco openings common in the Bay Area. Full-frame replacement removes everything to the studs and is the right call when frames show rot, leaks or previous poor installs.",
    },
    {
        "question": "Which window brands does Deluxe Windows carry?",
        "answer": "Deluxe Windows carries Milgard, Marvin, Andersen, Anlin, Simonton, JELD-WEN, Ply Gem, Alside, Western Window Systems, Italwindows and All Weather Architectural Aluminum, so budget and premium projects can be compared in one place.",
    },
]


def build_faq(ctx: PageContext, questions_pool: UniquePool, answers_pool: UniquePool,
              stats: dict) -> list[dict[str, str]]:
    if ctx.path in EXCLUDED_FAQ_PATHS:
        return []

    items: list[dict[str, str]] = []

    if ctx.path == "/faq":
        for item in FAQ_HUB:
            if questions_pool.claim(item["question"], ctx.path) and answers_pool.claim(item["answer"], ctx.path):
                items.append(dict(item))
                stats["bank"] += 1
        return items

    # 1. Real PAA questions routed to this page (max 4, no near-duplicates).
    signatures: set[str] = set()
    for raw in ctx.data.get("paa", []):
        if len(items) >= 4:
            break
        question = clean_question(str(raw))
        if not question_allowed(question):
            continue
        signature = question_signature(question)
        if signature in signatures:
            continue
        if not questions_pool.claim(question, ctx.path):
            continue
        signatures.add(signature)
        answer = answer_paa(question, ctx)
        if len(answer) < 80 or not answers_pool.claim(answer, ctx.path):
            answer = answer + f" Ask about {ctx.entity} specifics when booking the free estimate."
            if not answers_pool.claim(answer, ctx.path):
                continue
        items.append({"question": question, "answer": answer})
        stats["paa"] += 1

    # 2. Family fill to reach at least 4 questions.
    for item in generated_fill(ctx):
        if len(items) >= 4 and len(items) >= 3:
            break
        question = clean_question(item["question"])
        answer = item["answer"].strip()
        if len(answer) < 80:
            answer += " Deluxe Windows confirms the specifics for each Bay Area project in writing."
        if not questions_pool.claim(question, ctx.path):
            continue
        if not answers_pool.claim(answer, ctx.path):
            answer += f" This applies specifically to {ctx.entity} projects."
            if not answers_pool.claim(answer, ctx.path):
                continue
        items.append({"question": question, "answer": answer})
        stats["generated"] += 1

    if not 3 <= len(items) <= 6:
        raise RuntimeError(f"{ctx.path}: produced {len(items)} FAQ items")
    return items


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> None:
    dataset = json.loads((RESEARCH / "dataset.json").read_text(encoding="utf-8"))
    pages_data = dataset["pages"]

    files: list[tuple[Path, dict]] = []
    for file in sorted(METADATA_ROOT.rglob("*.json")):
        files.append((file, json.loads(file.read_text(encoding="utf-8"))))

    # Priority order: pages with the strongest query data claim shared
    # questions and keywords first.
    def page_weight(record: dict) -> float:
        data = pages_data.get(str(record["path"]), {})
        total = 0.0
        for item in data.get("queries", [])[:10]:
            ads = item.get("ads") or {}
            gsc = item.get("gsc") or {}
            total += ads.get("clicks", 0) * 10 + ads.get("impressions", 0) * 0.5
            total += gsc.get("clicks", 0) * 20 + gsc.get("impressions", 0) * 0.2
        return total

    ordered = sorted(files, key=lambda pair: page_weight(pair[1]), reverse=True)

    titles = UniquePool()
    descriptions = UniquePool()
    h1s = UniquePool()
    primaries = UniquePool()
    questions_pool = UniquePool()
    answers_pool = UniquePool()
    stats = {"paa": 0, "bank": 0, "generated": 0}

    results: dict[str, dict] = {}
    for file, record in ordered:
        path = str(record["path"])
        ctx = PageContext(record, pages_data.get(path, {"queries": [], "paa": [], "facts": []}))
        seo_update = build_seo(ctx, titles, descriptions, h1s, primaries)
        faq = build_faq(ctx, questions_pool, answers_pool, stats)
        results[path] = {"seo": seo_update, "faq": faq, "file": file, "record": record}

    changed = 0
    for path, payload in results.items():
        record = payload["record"]
        schema_before = json.dumps(record.get("schema"), sort_keys=True, ensure_ascii=False)
        seo = record["seo"]
        update = payload["seo"]

        og = seo.get("og") if isinstance(seo.get("og"), dict) else {}
        twitter = seo.get("twitter") if isinstance(seo.get("twitter"), dict) else {}

        seo["title"] = update["title"]
        seo["description"] = update["description"]
        seo["h1"] = update["h1"]
        seo["primary_keyword"] = update["primary_keyword"]
        seo["target_keywords"] = update["target_keywords"]
        og["title"] = update["title"]
        og["description"] = update["description"]
        twitter["title"] = update["title"]
        twitter["description"] = update["description"]
        seo["og"] = og
        seo["twitter"] = twitter
        record["faq"] = payload["faq"]

        if json.dumps(record.get("schema"), sort_keys=True, ensure_ascii=False) != schema_before:
            raise RuntimeError(f"{path}: schema changed unexpectedly")

        payload["file"].write_text(
            json.dumps(record, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
        )
        changed += 1

    faq_pages = sum(1 for payload in results.values() if payload["faq"])
    faq_items = sum(len(payload["faq"]) for payload in results.values())
    print(json.dumps({
        "pages_updated": changed,
        "pages_with_faq": faq_pages,
        "faq_items": faq_items,
        "faq_from_paa": stats["paa"],
        "faq_from_bank": stats["bank"],
        "faq_generated": stats["generated"],
    }, indent=2))


if __name__ == "__main__":
    main()
