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

## Rollback

The project is a git repository. `git log` lists the checkpoints and
`git checkout -- .` restores the last committed state. Binary assets are
marked in `.gitattributes` so the PDF and PNGs survive a checkout intact.

## Future users

The site is currently static. `database/schema.sql` provides a MySQL
foundation for future users and server-side sessions. Copy `.env.example`
into the future backend environment. Never expose database credentials in
browser JavaScript, and hash passwords with Argon2id or bcrypt.
