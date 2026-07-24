"""Geocode previous-jobs addresses (TSV) into public/data/previous-jobs.json.

Uses Photon (komoot) with a Nominatim fallback, caches results in
scripts/jobs-geocode-cache.json so re-runs only hit the API for new rows.
"""

import io
import json
import os
import sys
import time
import urllib.parse
import urllib.request

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TSV_PATH = os.path.join(BASE, "scripts", "jobs-addresses.tsv")
CACHE_PATH = os.path.join(BASE, "scripts", "jobs-geocode-cache.json")
OUT_PATH = os.path.join(BASE, "public", "data", "previous-jobs.json")

PHOTON_URL = "https://photon.komoot.io/api/"
NOMINATIM_URL = "https://nominatim.openstreetmap.org/search"
USER_AGENT = "DeluxeWindowsJobsMap/1.0 (contact: info@deluxewindows.com)"

# Nine-county Bay Area bounds (exclude Sacramento, Fresno, Santa Cruz, etc.)
LAT_MIN, LAT_MAX = 37.05, 38.85
LNG_MIN, LNG_MAX = -123.15, -121.45

# Cities clearly outside the Bay Area service footprint (even if geocode drifts)
NON_BAY_AREA_CITIES = {
    "fresno",
    "sacramento",
    "stockton",
    "chicago",
    "tigard",
    "beaverton",
    "west hollywood",
    "glendora",
    "groveland",
    "rancho cordova",
    "north highlands",
    "marina",
    "scotts valley",
    "aptos",
    "boulder creek",
    "la selva beach",
    "santa cruz",
    "south lake tahoe",
}


def read_rows():
    rows = []
    with io.open(TSV_PATH, encoding="utf-8") as f:
        lines = f.read().splitlines()
    for line in lines[1:]:  # skip header
        parts = line.split("\t")
        street = (parts[0] if len(parts) > 0 else "").strip()
        city = (parts[2] if len(parts) > 2 else "").strip()
        if not street or not city:
            continue
        rows.append((street, city))
    return rows


def cache_key(street, city):
    return f"{street.lower()}|{city.lower()}"


def http_json(url):
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    with urllib.request.urlopen(req, timeout=20) as resp:
        return json.loads(resp.read().decode("utf-8"))


def in_bounds(lat, lng):
    return LAT_MIN <= lat <= LAT_MAX and LNG_MIN <= lng <= LNG_MAX


def geocode_photon(street, city):
    q = urllib.parse.quote(f"{street}, {city}, California, USA")
    url = f"{PHOTON_URL}?q={q}&limit=1&lang=en"
    data = http_json(url)
    feats = data.get("features") or []
    if not feats:
        return None
    coords = feats[0].get("geometry", {}).get("coordinates")
    if not coords or len(coords) < 2:
        return None
    lng, lat = float(coords[0]), float(coords[1])
    return (lat, lng) if in_bounds(lat, lng) else None


def geocode_nominatim(street, city):
    q = urllib.parse.urlencode(
        {"q": f"{street}, {city}, California, USA", "format": "json", "limit": 1}
    )
    url = f"{NOMINATIM_URL}?{q}"
    data = http_json(url)
    if not data:
        return None
    lat, lng = float(data[0]["lat"]), float(data[0]["lon"])
    return (lat, lng) if in_bounds(lat, lng) else None


def main():
    rows = read_rows()
    print(f"input rows: {len(rows)}")

    cache = {}
    if os.path.exists(CACHE_PATH):
        with io.open(CACHE_PATH, encoding="utf-8") as f:
            cache = json.load(f)
    print(f"cache entries: {len(cache)}")

    pending = [(s, c) for (s, c) in dict.fromkeys(rows) if cache_key(s, c) not in cache]
    print(f"to geocode: {len(pending)}")

    for i, (street, city) in enumerate(pending, 1):
        key = cache_key(street, city)
        coords = None
        try:
            coords = geocode_photon(street, city)
        except Exception as e:
            print(f"  photon error for {key}: {e}")
        if coords is None:
            time.sleep(1.1)  # Nominatim rate limit
            try:
                coords = geocode_nominatim(street, city)
            except Exception as e:
                print(f"  nominatim error for {key}: {e}")
        cache[key] = list(coords) if coords else None
        print(f"[{i}/{len(pending)}] {key} -> {cache[key]}")
        # persist cache incrementally so a crash never loses progress
        with io.open(CACHE_PATH, "w", encoding="utf-8") as f:
            json.dump(cache, f, ensure_ascii=False)
        time.sleep(0.6)

    # Build output: aggregate duplicate addresses into one point with count
    agg = {}
    missed = 0
    for street, city in rows:
        if city.strip().lower() in NON_BAY_AREA_CITIES:
            missed += 1
            continue
        coords = cache.get(cache_key(street, city))
        # Older cache entries were stored without bounds validation — filter here too.
        if not coords or not in_bounds(coords[0], coords[1]):
            missed += 1
            continue
        k = cache_key(street, city)
        if k in agg:
            agg[k]["count"] += 1
        else:
            agg[k] = {
                "lat": round(coords[0], 6),
                "lng": round(coords[1], 6),
                "street": street,
                "city": city,
                "count": 1,
            }

    points = list(agg.values())
    out = {
        "generatedAt": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "source": "photon+nominatim",
        "totalInput": len(rows),
        "geocoded": len(rows) - missed,
        "missed": missed,
        "points": points,
    }
    os.makedirs(os.path.dirname(OUT_PATH), exist_ok=True)
    with io.open(OUT_PATH, "w", encoding="utf-8") as f:
        json.dump(out, f, ensure_ascii=False)
    print(f"done: {len(points)} unique points, geocoded {out['geocoded']}, missed {missed}")
    print(f"wrote {OUT_PATH}")


if __name__ == "__main__":
    sys.exit(main())
