# Dennis Dizon Portfolio

Compact portfolio site with accessible native dialogs for project details, a
generated resume PDF, and a PHP users-and-sessions backend in `app/`.

## Run

```powershell
php tools/serve.php
```

Serves the static portfolio and the PHP account pages together on
<http://127.0.0.1:8000>:

| | |
|---|---|
| Portfolio | <http://127.0.0.1:8000/> |
| Accounts | <http://127.0.0.1:8000/app/> |

The first run creates a SQLite database in `.dev/` and writes a development
`.env`, both git-ignored. Nothing to install beyond PHP -- MySQL is only
needed for production. `php tools/serve.php 8080` picks another port and
`php tools/serve.php --reset` starts from an empty database.

It runs through `tools/router.php`, which applies the same file denials that
`.htaccess` and `web.config` apply in production, so `.env`, the schema and
the build scripts are unreachable locally too.

There is no link from the portfolio to the account pages; the portfolio is
public and `app/` is reached directly. Add one to the header when you decide
what the accounts are for.

For the static site on its own, any file server works:

```powershell
python -m http.server 4173 --bind 127.0.0.1
```

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

## Backend

`app/` is a PHP implementation of the users and sessions layer that
`database/schema.sql` describes: registration, sign-in, sign-out, an account
page and a password change.

PHP was chosen because it drops in beside the static files with no build
step or process manager, it matches the MySQL schema already written, and it
runs on ordinary shared hosting.

### Important: the static host cannot run this

GitHub Pages, and any other static host, serves files only. The portfolio
page and the account pages therefore cannot live on the same host unless
that host runs PHP and MySQL. Either put the whole site on PHP hosting, or
keep the portfolio static and put `app/` on a separate host.

### Setup

For local work use `php tools/serve.php`, which needs none of this. For
production:

1. Import `database/schema.sql` into MySQL 8 (or MariaDB 10.4+).
2. Copy `.env.example` to `.env` and fill it in.
3. **Put `.env` one directory above the site root.** The app looks there
   first and only falls back to the project root. A `.env` inside the web
   root is served as plain text by default and hands over your database
   password -- `.htaccess` and `web.config` block it on Apache and IIS, but
   nginx needs its own rule:

   ```nginx
   location ~ /\. { deny all; }
   location ~ ^/(database|tools|tmp)/ { deny all; }
   ```

4. Point the browser at `app/` (`app/login.php`).

### What it does

- Passwords hashed with Argon2id, falling back to bcrypt at cost 12 where
  the host lacks Argon2. Stored hashes are upgraded on the next sign-in.
- Sessions are server-side rows in `user_sessions`. The cookie holds a
  random 256-bit token and the table stores only its SHA-256 hash, so a
  database dump cannot be replayed as a live session. Cookies are HttpOnly,
  SameSite=Lax, and Secure over HTTPS.
- Failed sign-ins are throttled per email and per IP. The IP budget is six
  times the email budget, because schools, offices and mobile networks put
  many people behind one address. Registration deliberately bypasses the
  sign-in throttle, so a stranger's failures cannot block a new account.
- CSRF via a double-submit HttpOnly cookie; sign-out is POST-only.
- Sign-in failures are indistinguishable whether the account is unknown,
  the password is wrong, or the account is disabled, and an unknown address
  still costs a password hash so timing does not leak either.
- Changing a password revokes every existing session.
- Every page sends CSP, X-Frame-Options, X-Content-Type-Options and
  Referrer-Policy, and `noindex`.

### Tests

```powershell
php tools/test_auth.php
```

Covers registration, sign-in, sessions, throttling, password change, CSRF
and SQL injection. Runs against in-memory SQLite using the same
`database/schema.sqlite.sql` the dev server uses, so the tests and the
running app cannot drift apart. The app's SQL is plain portable statements
with PHP-side timestamps, so MySQL behaves the same.

Not built yet: email verification (the `email_verified_at` column is
waiting for it), password reset, and an admin view of the `admin`/`editor`
roles the schema allows.

## Future users

`database/schema.sql` also allows `admin` and `editor` roles and an
`email_verified_at` timestamp; neither is used by the current screens.
Never expose database credentials to browser JavaScript.
