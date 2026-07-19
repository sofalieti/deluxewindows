import json
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

for coll in ["door-types", "window-type"]:
    p = Path(f"webflow-data/current/collections/{coll}/items.json")
    items = json.loads(p.read_text(encoding="utf-8"))
    if isinstance(items, dict):
        items = items.get("items", items)
    print("=" * 20, coll, len(items))
    for it in items:
        fd = it.get("fieldData", {})
        print(f"  {fd.get('slug', '?'):55} draft={it.get('isDraft')} arch={it.get('isArchived')}")

print()
print("page-metadata door-types files:")
for f in sorted(Path("database/data/page-metadata/door-types").glob("*.json")):
    print(" ", f.stem)
print()
print("page-metadata window-type files:")
for f in sorted(Path("database/data/page-metadata/window-type").glob("*.json")):
    print(" ", f.stem)
