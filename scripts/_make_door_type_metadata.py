"""One-off: create page-metadata skeletons for /door-types/{slug} pages."""
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ITEMS = ROOT / "webflow-data" / "current" / "collections" / "door-types" / "items.json"
OUT = ROOT / "database" / "data" / "page-metadata" / "door-types"
IMAGES = ROOT / "public" / "webflow-assets" / "images"

data = json.loads(ITEMS.read_text(encoding="utf-8"))
items = next(v for v in data.values() if isinstance(v, list)) if isinstance(data, dict) else data

OUT.mkdir(parents=True, exist_ok=True)
created = 0
for item in items:
    fd = item.get("fieldData", {})
    slug = fd.get("slug")
    if not slug or item.get("isArchived") or item.get("isDraft"):
        continue

    image = ""
    for field in ("property-listing---featured-image", "property-listing---thumbnail-image-v1"):
        ref = fd.get(field)
        if not isinstance(ref, dict):
            continue
        url = ref.get("url") or ""
        basename = url.rsplit("/", 1)[-1]
        if basename and (IMAGES / basename).is_file():
            image = f"/webflow-assets/images/{basename}"
            break
        # Local exports sanitize special characters, so match by fileId prefix.
        file_id = ref.get("fileId") or basename.split("_", 1)[0]
        if file_id:
            matches = sorted(IMAGES.glob(f"{file_id}_*"))
            if matches:
                image = f"/webflow-assets/images/{matches[0].name}"
                break

    name = fd.get("name") or slug
    record = {
        "version": 1,
        "key": f"door-types/{slug}",
        "path": f"/door-types/{slug}",
        "seo": {
            "title": "",
            "description": "",
            "canonical": f"https://www.deluxewindows.com/door-types/{slug}",
            "og": {
                "title": "",
                "description": "",
                "image": image,
                "type": "website",
            },
            "h1": "",
            "primary_keyword": "",
            "target_keywords": [],
            "search_intent": "brand-material",
            "priority": "P2",
            "robots": "index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1",
            "twitter": {
                "title": "",
                "description": "",
                "image": image,
                "card": "summary_large_image",
            },
        },
        "faq": [],
        "schema": {
            "primary_type": "Product",
            "data": {"name": name},
            "extra": [],
            "replace": [],
        },
    }
    file = OUT / f"{slug}.json"
    file.write_text(json.dumps(record, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    created += 1

print(f"created {created} files in {OUT}")
