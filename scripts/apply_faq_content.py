#!/usr/bin/env python3
"""Generate unique, file-backed FAQ content for public pages."""

from __future__ import annotations

import argparse
import json
import pathlib
import re
from typing import Any


ROOT = pathlib.Path(__file__).resolve().parents[1]
METADATA_ROOT = ROOT / "database/data/page-metadata"

EXCLUDED_PATHS = {
    "/about",
    "/blog",
    "/brand",
    "/gallery",
    "/glossary",
    "/testimonials",
}

PRESERVED_FAMILIES = {"door-brands", "window-replacement"}

MATERIALS = {
    "aluminum-clad": {
        "label": "aluminum-clad",
        "benefit": "a warm interior appearance with a protective aluminum exterior",
        "efficiency": "performance depends on the interior frame, glazing package, spacers, and installation quality",
        "maintenance": "the exterior cladding limits repainting while the interior finish should be cared for as specified by the manufacturer",
        "consideration": "They are distinct from all-aluminum products because cladding protects another frame material rather than forming the entire frame.",
    },
    "wood-clad": {
        "label": "wood-clad",
        "benefit": "a real-wood interior with a lower-maintenance protective exterior",
        "efficiency": "the wood core, exterior cladding, glass package, and weather sealing all influence whole-window performance",
        "maintenance": "the exterior needs less upkeep than exposed wood, while interior wood still requires appropriate finish care",
        "consideration": "Wood-clad is a broad category; aluminum-clad is the specific version that uses aluminum as the exterior covering.",
    },
    "aluminum": {
        "label": "aluminum",
        "benefit": "slim sightlines, structural strength, and a clean contemporary appearance",
        "efficiency": "thermally improved frames and the selected glass package are especially important because aluminum conducts heat",
        "maintenance": "factory-finished aluminum is low maintenance, but tracks, drainage paths, seals, and hardware should remain clean",
        "consideration": "Confirm thermal performance, frame depth, finish, and condensation resistance for the exact product.",
    },
    "fiberglass": {
        "label": "fiberglass",
        "benefit": "dimensional stability, strength, and low-maintenance performance",
        "efficiency": "stable frames and insulated glass can provide strong thermal performance when the unit is correctly specified and installed",
        "maintenance": "routine cleaning and inspection of seals and hardware are normally the main upkeep requirements",
        "consideration": "Compare finish choices, frame profiles, glass packages, and installed cost rather than material alone.",
    },
    "steel": {
        "label": "steel",
        "benefit": "narrow architectural profiles, strength, and a distinctive premium appearance",
        "efficiency": "thermal breaks, glazing, weather seals, and installation details determine energy performance",
        "maintenance": "protective finishes should be inspected and damaged areas addressed promptly to prevent corrosion",
        "consideration": "Steel products are typically selected for architectural design goals rather than as a direct substitute for economy vinyl windows.",
    },
    "vinyl": {
        "label": "vinyl",
        "benefit": "low maintenance, useful insulation, and broad value-oriented product selection",
        "efficiency": "multi-chamber frames, Low-E glass, spacers, and professional installation can reduce heat transfer and air leakage",
        "maintenance": "vinyl does not require painting; cleaning frames, tracks, drainage openings, and hardware is usually sufficient",
        "consideration": "Compare frame construction, reinforcement, glass packages, warranties, and installation details between brands.",
    },
    "wood": {
        "label": "wood",
        "benefit": "natural interior character, design flexibility, and compatibility with traditional architecture",
        "efficiency": "wood frames insulate naturally, while glass, weatherstripping, and installation determine the completed unit's performance",
        "maintenance": "exposed wood needs periodic inspection and finish maintenance to control moisture and weathering",
        "consideration": "Unlike wood-clad products, exposed wood requires more exterior care unless another protective system is specified.",
    },
}

BRAND_PROFILES = {
    "All Weather Architectural Aluminum": "architectural aluminum windows with slim profiles and options for large contemporary openings",
    "Alside": "value-focused replacement windows with low-maintenance vinyl options",
    "Andersen": "window collections spanning several materials, styles, performance levels, and design approaches",
    "Anlin": "California-focused vinyl replacement windows designed around comfort and low maintenance",
    "Italwindows": "contemporary window systems for projects that prioritize clean profiles and modern design",
    "JELD-WEN": "a broad window portfolio with multiple materials, styles, and price levels",
    "Jeld-Wen": "a broad window portfolio with multiple materials, styles, and price levels",
    "Marvin": "premium window collections with wood, fiberglass, and clad options for varied architectural needs",
    "Milgard": "replacement window choices across vinyl, fiberglass, and aluminum product families",
    "Ply Gem": "practical window collections across several materials and value levels",
    "Simonton": "low-maintenance vinyl window collections for replacement projects",
    "Western Window Systems": "architectural aluminum window systems designed for contemporary homes and larger openings",
}

KNOWN_BRANDS = sorted(BRAND_PROFILES, key=len, reverse=True)


def faq(question: str, answer: str) -> dict[str, str]:
    return {"question": question.strip(), "answer": answer.strip()}


def family_for(record: dict[str, Any]) -> str:
    return str(record["key"]).split("/", 1)[0]


def page_entity(record: dict[str, Any]) -> str:
    seo = record["seo"]
    title = str(seo["title"]).split("|", 1)[0].strip()
    schema_name = str(record.get("schema", {}).get("data", {}).get("name", "")).strip()
    return title or schema_name or str(seo["h1"]).strip()


def detect_brand(text: str) -> str:
    normalized = text.casefold()
    for brand in KNOWN_BRANDS:
        if brand.casefold() in normalized:
            return brand
    return "the manufacturer"


def detect_material(text: str) -> tuple[str, dict[str, str]]:
    normalized = text.casefold().replace("aluminium", "aluminum")
    for key in ("aluminum-clad", "wood-clad", "fiberglass", "aluminum", "steel", "vinyl", "wood"):
        if key in normalized or key.replace("-", " ") in normalized:
            return key, MATERIALS[key]
    return "window", {
        "label": "window",
        "benefit": "a balance of appearance, comfort, durability, and project value",
        "efficiency": "frame construction, glass, spacers, weather seals, and installation all affect performance",
        "maintenance": "maintenance depends on the selected frame, finish, hardware, and exposure",
        "consideration": "Compare verified specifications and the complete installed system before choosing.",
    }


def material_page_faq(record: dict[str, Any], product: str) -> list[dict[str, str]]:
    entity = str(record.get("schema", {}).get("data", {}).get("name") or page_entity(record))
    _, profile = detect_material(entity)
    material = profile["label"]
    product_singular = product.rstrip("s")
    efficiency = profile["efficiency"].replace(
        "whole-window",
        f"whole-{product_singular}",
    )
    return [
        faq(
            f"What are the main benefits of {entity}?",
            f"{entity} offer {profile['benefit']}. The best fit depends on the home's architecture, opening sizes, performance goals, and budget.",
        ),
        faq(
            f"How energy efficient are {material} {product} for Bay Area homes?",
            f"For {entity}, {efficiency}. Deluxe Windows compares complete {product_singular} ratings and installation requirements for the specific opening.",
        ),
        faq(
            f"What maintenance do {entity} require?",
            f"For {entity}, {profile['maintenance']}. Product-specific care instructions and warranty requirements should also be followed.",
        ),
        faq(
            f"What should I compare before choosing {entity}?",
            f"When evaluating {entity}, compare frame construction, glass, operating style, finish, warranty, and installed price. {profile['consideration']}",
        ),
    ]


def brand_page_faq(record: dict[str, Any]) -> list[dict[str, str]]:
    entity = page_entity(record).replace(" Windows Bay Area", "").strip()
    brand = detect_brand(entity)
    if brand == "the manufacturer":
        brand = entity.replace(" Windows", "").strip()
    profile = BRAND_PROFILES.get(brand, f"window products suited to different home styles and project requirements")
    return [
        faq(
            f"What types of {brand} windows does Deluxe Windows offer?",
            f"Deluxe Windows helps Bay Area homeowners compare {profile}. Available series, styles, and configurations are confirmed for each project.",
        ),
        faq(
            f"Are {brand} windows a good fit for Bay Area homes?",
            f"{brand} windows can be a good fit when their material, glass, size limits, and design match the property. Deluxe Windows evaluates those details before recommending a collection.",
        ),
        faq(
            f"How should I compare {brand} window collections?",
            f"Compare {brand} collections by frame material, operating style, energy ratings, glass options, finish choices, warranty, and total installed price.",
        ),
        faq(
            f"Does Deluxe Windows install {brand} windows?",
            f"Yes. Deluxe Windows measures, specifies, and installs {brand} windows for qualifying Bay Area projects, then verifies operation and fit after installation.",
        ),
    ]


def window_type_faq(record: dict[str, Any]) -> list[dict[str, str]]:
    entity = str(record.get("schema", {}).get("data", {}).get("name") or page_entity(record))
    brand = detect_brand(entity)
    _, profile = detect_material(entity)
    material = profile["label"]
    return [
        faq(
            f"What makes {entity} suitable for a Bay Area replacement project?",
            f"{entity} combine {brand}'s product approach with {profile['benefit']}. Suitability still depends on the home's openings, exposure, design, and budget.",
        ),
        faq(
            f"How do {entity} perform for energy efficiency?",
            f"For {entity}, {profile['efficiency']}. The exact glass package and verified whole-window ratings should be reviewed before ordering.",
        ),
        faq(
            f"What maintenance should I expect with {entity}?",
            f"Maintenance for {entity} follows the needs of {material} frames: {profile['maintenance']}. Hardware and weather seals should also be inspected periodically.",
        ),
        faq(
            f"Can Deluxe Windows provide installed pricing for {entity}?",
            f"Yes. Installed pricing for {entity} is prepared after measuring the openings and confirming style, glass, finish, hardware, access, and installation conditions.",
        ),
    ]


def collection_faq(record: dict[str, Any]) -> list[dict[str, str]]:
    entity = page_entity(record)
    brand = detect_brand(entity)
    description = str(record["seo"]["description"]).strip()
    return [
        faq(
            f"What should homeowners know about {entity}?",
            description,
        ),
        faq(
            f"Which configurations are available in {entity}?",
            f"Available {entity} styles, sizes, glass packages, colors, and hardware depend on the exact product and opening. Deluxe Windows confirms current options before an order is prepared.",
        ),
        faq(
            f"How should I compare {entity} with other {brand} collections?",
            f"Compare {entity} with other {brand} choices by frame material, style availability, energy ratings, design options, warranty, and installed project cost.",
        ),
        faq(
            f"How is installed pricing for {entity} calculated?",
            f"Installed pricing for {entity} depends on opening dimensions, configuration, glass, finish, hardware, quantity, access, and any required preparation or permit work.",
        ),
    ]


def county_faq(record: dict[str, Any]) -> list[dict[str, str]]:
    county = str(record.get("schema", {}).get("data", {}).get("name") or page_entity(record))
    return [
        faq(
            f"Which cities does Deluxe Windows serve in {county}?",
            f"Deluxe Windows serves window and door replacement projects across {county}. Confirm the property address when requesting an estimate so travel, permitting, and installation details can be checked.",
        ),
        faq(
            f"Do replacement windows require permits in {county}?",
            f"Permit requirements in {county} are set by the city or local building authority and can vary by project scope, property type, and historic status. Requirements are reviewed for the specific address.",
        ),
        faq(
            f"Which window features matter for homes in {county}?",
            f"For homes in {county}, compare solar control, insulation, air leakage, frame durability, ventilation, and exposure. The right balance depends on the property's microclimate and orientation.",
        ),
        faq(
            f"How do I request a window replacement estimate in {county}?",
            f"Provide the {county} property address, approximate window count, preferred materials, and any known access or HOA requirements. Deluxe Windows can then schedule measurements and prepare project-specific options.",
        ),
    ]


def blog_faq(record: dict[str, Any]) -> list[dict[str, str]]:
    topic = str(record["seo"]["h1"]).strip()
    topic_label = topic.rstrip("?!.\u2026")
    description = str(record["seo"]["description"]).strip()
    return [
        faq(
            f"What is the main takeaway from “{topic_label}”?",
            description,
        ),
        faq(
            f"How should a Bay Area homeowner apply the advice in “{topic_label}”?",
            f"Use the guidance in “{topic_label}” as a starting point, then account for the home's age, openings, exposure, existing damage, comfort goals, and budget before selecting a solution.",
        ),
        faq(
            f"Does “{topic_label}” apply to every window or door project?",
            f"No. The considerations in “{topic_label}” can change with frame material, product condition, installation method, local code, and the surrounding wall or waterproofing.",
        ),
        faq(
            f"When should I request professional help after reading “{topic_label}”?",
            f"Request an evaluation when the issue described in “{topic_label}” involves safety, water intrusion, damaged framing, uncertain measurements, code requirements, or a full replacement decision.",
        ),
    ]


def static_faq(path: str) -> list[dict[str, str]]:
    content = {
        "/": [
            faq("What window and door services does Deluxe Windows provide?", "Deluxe Windows helps Bay Area homeowners compare, measure, and install replacement windows and doors across multiple materials, brands, styles, and price levels."),
            faq("Which parts of the Bay Area does Deluxe Windows serve?", "Service is available across the core Bay Area counties and cities represented in the Deluxe Windows service-area pages. Availability is confirmed for the project address."),
            faq("How does a Deluxe Windows project begin?", "A project begins with a consultation and site measurements, followed by product selection, an installation proposal, ordering, professional installation, and a final operating check."),
            faq("Can Deluxe Windows help compare window and door brands?", "Yes. Recommendations consider the home's architecture, opening sizes, material preferences, energy goals, design requirements, warranties, and project budget."),
        ],
        "/contacts": [
            faq("How can I request an estimate from Deluxe Windows?", "Use the contact form or call Deluxe Windows with the project address, product type, approximate quantity, and preferred appointment timing."),
            faq("What information should I provide before a window consultation?", "Share the property address, number of openings, current problems, preferred materials or brands, target schedule, and any known HOA or permit requirements."),
            faq("Can I discuss both windows and doors during one consultation?", "Yes. A consultation can cover window and door needs together so measurements, styles, materials, priorities, and project sequencing can be reviewed as one plan."),
            faq("Does Deluxe Windows offer in-home project measurements?", "Yes. In-home measurements can be scheduled for qualifying Bay Area projects after the initial project details and service address are confirmed."),
        ],
        "/windows": [
            faq("Which replacement window materials can I compare at Deluxe Windows?", "Homeowners can compare vinyl, wood, wood-clad, aluminum, aluminum-clad, fiberglass, and steel window options, subject to current manufacturer availability."),
            faq("How do I choose between window styles?", "Choose an operating style by considering ventilation, egress, cleaning access, view area, furniture placement, architectural fit, and the dimensions of each opening."),
            faq("What determines replacement window energy performance?", "Whole-window ratings depend on the frame, glass coatings, pane configuration, gas fill, spacers, weather seals, product size, and installation quality."),
            faq("How is installed window pricing calculated?", "Installed window pricing depends on material, brand, style, dimensions, glass, finish, hardware, quantity, access, removal work, and permit or repair requirements."),
        ],
        "/doors": [
            faq("Which replacement door materials can I compare at Deluxe Windows?", "Deluxe Windows offers access to wood, wood-clad, fiberglass, steel, vinyl, and aluminum door options across entry, patio, sliding, folding, and multi-panel configurations."),
            faq("How do I choose between sliding, hinged, and folding doors?", "Compare opening width, usable floor space, ventilation, accessibility, panel size, threshold details, weather exposure, security, and the desired indoor-outdoor connection."),
            faq("What affects a replacement door's energy efficiency?", "Door performance depends on the frame and panel material, glazing, spacers, weather seals, threshold, locking system, exposure, and installation accuracy."),
            faq("What measurements are needed for an installed door estimate?", "An estimate requires the structural opening, frame condition, wall depth, swing or panel direction, threshold, exterior exposure, access, finish details, and selected configuration."),
        ],
        "/financing": [
            faq("What project information is needed to discuss financing?", "Provide the project address, estimated scope, selected products if known, and target schedule. Financing options can then be reviewed alongside the installation proposal."),
            faq("Does checking window and door financing change the product estimate?", "The product and installation scope should be priced first. Financing changes how an approved project is paid for, not the measurements or required installation work."),
            faq("When are financing terms confirmed?", "Rates, term length, payment amount, eligibility, and required disclosures are confirmed through the applicable financing provider before an agreement is accepted."),
            faq("Can financing cover both windows and doors in one project?", "A combined project may be considered, subject to the selected program, approved amount, project proposal, and financing provider's current terms."),
        ],
        "/special-offers": [
            faq("How do I confirm whether a Deluxe Windows offer is still active?", "Use the dates and conditions shown with the offer, then confirm availability when requesting an estimate because promotions can change or expire."),
            faq("Which products qualify for current Deluxe Windows promotions?", "Eligibility depends on the specific offer, product, quantity, project location, and installation scope. The written proposal should identify any applied promotion."),
            faq("Can multiple window or door offers be combined?", "Offers should be treated as non-combinable unless their written terms explicitly allow combination. Confirm the applicable discount before approving the project."),
            faq("Can a promotion be used with financing?", "Compatibility between a promotion and financing depends on the current written terms of both programs and must be confirmed before the agreement is signed."),
        ],
    }
    return content[path]


def generated_faq(record: dict[str, Any]) -> list[dict[str, str]]:
    path = str(record["path"])
    family = family_for(record)

    if path in EXCLUDED_PATHS:
        return []
    if path == "/faq" or family in PRESERVED_FAMILIES:
        return list(record.get("faq") or [])
    if family == "static":
        return static_faq(path)
    if family == "windows":
        return material_page_faq(record, "windows")
    if family == "doors":
        return material_page_faq(record, "doors")
    if family == "brands":
        return brand_page_faq(record)
    if family == "window-type":
        return window_type_faq(record)
    if family == "brand-collections":
        return collection_faq(record)
    if family == "county-hub-pages":
        return county_faq(record)
    if family == "blog":
        return blog_faq(record)

    raise ValueError(f"Unhandled page family for {path}: {family}")


def validate(records: list[dict[str, Any]]) -> None:
    questions: dict[str, str] = {}
    answers: dict[str, str] = {}
    pages_with_faq = 0
    faq_items = 0

    for record in records:
        path = str(record["path"])
        items = record.get("faq")
        if not isinstance(items, list):
            raise ValueError(f"{path}: faq must be a list")
        if path in EXCLUDED_PATHS:
            if items:
                raise ValueError(f"{path}: FAQ is not expected on this page")
            continue
        if not 3 <= len(items) <= 6:
            raise ValueError(f"{path}: expected 3-6 FAQ items, got {len(items)}")

        pages_with_faq += 1
        faq_items += len(items)
        for item in items:
            question = str(item.get("question", "")).strip()
            answer = str(item.get("answer", "")).strip()
            if not question.endswith("?"):
                raise ValueError(f"{path}: FAQ question must end with ?: {question}")
            if len(answer) < 80:
                raise ValueError(f"{path}: FAQ answer is too short: {answer}")
            if re.search(r"[А-Яа-яЁё]", question + answer):
                raise ValueError(f"{path}: FAQ must be English only")

            question_key = question.casefold()
            answer_key = answer.casefold()
            if question_key in questions:
                raise ValueError(
                    f"Duplicate question on {path} and {questions[question_key]}: {question}"
                )
            if answer_key in answers:
                raise ValueError(
                    f"Duplicate answer on {path} and {answers[answer_key]}"
                )
            questions[question_key] = path
            answers[answer_key] = path

    if len(records) != 204:
        raise ValueError(f"Expected 204 metadata files, got {len(records)}")
    if pages_with_faq != 198:
        raise ValueError(f"Expected FAQ on 198 pages, got {pages_with_faq}")

    print(
        f"Validated {len(records)} pages: {pages_with_faq} with FAQ, "
        f"{faq_items} unique questions and answers."
    )


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--check",
        action="store_true",
        help="Validate that committed files match generated FAQ content.",
    )
    args = parser.parse_args()

    loaded: list[tuple[pathlib.Path, dict[str, Any], str]] = []
    for path in sorted(METADATA_ROOT.rglob("*.json")):
        record = json.loads(path.read_text(encoding="utf-8"))
        schema_before = json.dumps(record.get("schema"), sort_keys=True, ensure_ascii=False)
        loaded.append((path, record, schema_before))

    changed = 0
    for path, record, schema_before in loaded:
        expected = generated_faq(record)
        if args.check:
            if record.get("faq") != expected:
                raise ValueError(f"{record['path']}: committed FAQ differs from generator")
        elif record.get("faq") != expected:
            record["faq"] = expected
            if json.dumps(record.get("schema"), sort_keys=True, ensure_ascii=False) != schema_before:
                raise ValueError(f"{record['path']}: schema changed during FAQ generation")
            path.write_text(
                json.dumps(record, indent=2, ensure_ascii=False) + "\n",
                encoding="utf-8",
            )
            changed += 1

    validate([record for _, record, _ in loaded])
    if not args.check:
        print(f"Updated FAQ content in {changed} metadata files.")


if __name__ == "__main__":
    main()
