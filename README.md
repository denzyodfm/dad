# Dennis Dizon Portfolio

Compact static portfolio with accessible native dialogs for project details.

## Run

```powershell
python -m http.server 4173 --bind 127.0.0.1
```

Open `http://127.0.0.1:4173`. Serve from the project root — the résumé link
is root-absolute (`/output/pdf/...`).

## Palette

One restrained palette lives entirely in `styles.css`: warm paper
(`--paper`), near-black (`--ink`), and cobalt (`--blue`). The four project
cards use `cobalt`, `ink`, `outline`, and `outline-cobalt` alongside the
`project` class. Every card heading and label clears WCAG AA (4.5:1);
cobalt has little headroom on paper, so labels on cobalt fills are not
dimmed with opacity.

## Résumé PDF

`output/pdf/Dennis-Dizon-Resume-Professional.pdf` is generated, not
hand-edited. Rebuild it after changing any résumé content:

```powershell
pip install reportlab
python tmp/pdfs/build_professional_resume.py
```

The builder is the only source for that PDF. It currently sits under
`tmp/`, so do not clear that directory without moving the script first;
it resolves its output path with `Path(__file__).parents[2]` and needs
updating if it moves. `tmp/pdfs/professional/*.png` are page renders kept
for visual review.

`Dennis-Dizon-Resume.pdf` in the project root is the original source
document and is not referenced by the site.

## Fonts

Manrope and DM Mono are self-hosted in `fonts/`, so the site makes no
third-party requests. Refresh them with:

```powershell
python tools/fetch_fonts.py
```

Only the latin subsets are kept. The OFL licence files in `fonts/` must
ship with the site.

## Accessibility

Every text element on the page and in all six dialogs meets WCAG AA
(4.5:1); the lowest measured ratio is 4.84:1. `--muted` is tuned to sit
just above the threshold on paper, so darken rather than lighten it if it
ever changes. The micro-typography does render as small as 7.2px, which
is legible but tight -- worth revisiting if you ever want the site to read
more comfortably on small screens.

## Sharing card and favicon

`output/og-image.png` is the 1200x630 social preview. It is generated from
`tools/og-card.html`, which reuses the site palette and fonts:

```powershell
pip install pillow
python tools/build_og_image.py
```

Regenerate it whenever the headline or the metrics change. `favicon.svg`
inverts under `prefers-color-scheme: dark` so it stays legible on dark
browser chrome.

The `og:image` and `twitter:image` paths in `index.html` are root-absolute
(`/output/og-image.png`). X, LinkedIn, Slack, and Discord resolve those
against the page URL, but Facebook's scraper wants a full origin — once the
site has a domain, prefix those two tags (and add `og:url`) with it.

## Deploying

All asset paths are relative, so the site runs unchanged from a domain
root or a subpath such as a GitHub project page. Nothing is built or
bundled; the repository root is the site.

- **GitHub Pages** - `.github/workflows/pages.yml` publishes on every push
  to `main`. Enable it once under Settings -> Pages -> Source -> GitHub
  Actions. `.nojekyll` stops Jekyll from filtering files.
- **Netlify / Cloudflare Pages / Vercel** - no build command, publish
  directory `.`.

`404.html` is styled to match and is picked up automatically by GitHub
Pages, Netlify and Cloudflare Pages.

Once the site has a real domain, prefix `og:image` and `twitter:image` in
`index.html` with the origin and add an `og:url`, since Facebook's scraper
will not resolve relative image paths.

## Printing

`styles.css` ends with a print block: the page prints to a single page with
no filled backgrounds, the dialogs and interactive controls removed, and the
project cards drawn as outlined boxes. The detailed document for sending to
people is the generated resume PDF, not the printed page.

## Rollback

The project is a git repository. `git log` lists the checkpoints and
`git checkout -- .` restores the last committed state. Binary assets are
marked in `.gitattributes` so the PDF and PNGs survive a checkout intact.

## Future users

The site is currently static. `database/schema.sql` provides a MySQL
foundation for future users and server-side sessions. Copy `.env.example`
into the future backend environment. Never expose database credentials in
browser JavaScript, and hash passwords with Argon2id or bcrypt.
