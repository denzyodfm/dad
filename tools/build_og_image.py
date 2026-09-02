"""Render the social preview card to output/og-image.png.

Uses headless Edge so the card reuses the site's real fonts and palette,
then flattens the capture to exactly 1200x630 for the social scrapers.
"""
import subprocess, sys, tempfile
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
CARD = ROOT / "tools" / "og-card.html"
OUT = ROOT / "output" / "og-image.png"
SIZE = (1200, 630)

EDGE_CANDIDATES = [
    Path(r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"),
    Path(r"C:\Program Files\Microsoft\Edge\Application\msedge.exe"),
]

def find_browser() -> Path:
    for path in EDGE_CANDIDATES:
        if path.exists():
            return path
    sys.exit("Microsoft Edge not found; install it or edit EDGE_CANDIDATES.")

def main() -> None:
    OUT.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory() as tmp:
        shot = Path(tmp) / "card.png"
        subprocess.run([
            str(find_browser()), "--headless=new", "--disable-gpu", "--hide-scrollbars",
            f"--window-size={SIZE[0]},{SIZE[1]}", "--virtual-time-budget=6000",
            f"--screenshot={shot}", CARD.as_uri(),
        ], check=True, capture_output=True)
        if not shot.exists():
            sys.exit("Edge produced no screenshot.")
        image = Image.open(shot).convert("RGB")
        if image.size != SIZE:
            image = image.resize(SIZE, Image.LANCZOS)
        image.save(OUT, format="PNG", optimize=True)
    print(f"{OUT} ({OUT.stat().st_size:,} bytes)")

if __name__ == "__main__":
    main()
