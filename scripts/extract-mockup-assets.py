#!/usr/bin/env python3
"""Extract the logo, hero photo, map preview and demo car photos embedded in the
approved site mockup (HTML with base64 images).

Usage: python3 scripts/extract-mockup-assets.py "/path/to/Mario's Car Dealer.html"

Demo car photos go to database/seeders/demo-photos/ and are used only by the
local DemoCarSeeder. They are samples and must never ship to production.
"""
import base64
import re
import sys
from pathlib import Path

root = Path(__file__).resolve().parent.parent
html = Path(sys.argv[1]).read_text(encoding="utf-8")

def decode(b64):
    return base64.b64decode(b64)

# 1) Logo, hero and map preview come from <img src="data:..."> tags in order of appearance.
tags = list(re.finditer(r'<img[^>]*?src="data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)"', html))
by_class = {}
for m in tags:
    tag = m.group(0)
    if 'id="hImg"' in tag:
        by_class["hero"] = (m.group(1), m.group(2))
    elif 'class="flogo"' in tag:
        by_class["footer_logo"] = (m.group(1), m.group(2))
    elif m.group(1) == "png" and "logo" not in by_class:
        by_class["logo"] = (m.group(1), m.group(2))
    elif m.group(1) == "jpeg" and "map" not in by_class:
        by_class["map"] = (m.group(1), m.group(2))

out = root / "public" / "images"
out.mkdir(parents=True, exist_ok=True)
(out / "logo.png").write_bytes(decode(by_class["logo"][1]))
(out / "hero.jpg").write_bytes(decode(by_class["hero"][1]))
(out / "map.jpg").write_bytes(decode(by_class["map"][1]))

# 2) Demo car photos live in a JS object: IMG = { name: "data:image/jpeg;base64,..." }
demo = root / "database" / "seeders" / "demo-photos"
demo.mkdir(parents=True, exist_ok=True)
count = 0
for m in re.finditer(r'(\w+):\s*"data:image/jpeg;base64,([A-Za-z0-9+/=]+)"', html):
    (demo / f"{m.group(1)}.jpg").write_bytes(decode(m.group(2)))
    count += 1

print(f"logo.png, hero.jpg, map.jpg and {count} demo photos written")
