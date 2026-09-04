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
hand-edited. It leads with web application development and the AI-assisted
delivery workflow, and groups Selected Projects into Web Applications and
Business Systems. The site profile in `index.php` says the same thing, so
change both together. Rebuild after any résumé edit:

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

The whole site now needs PHP and MySQL: the home page is `index.php` and it
reads the project cards from the database. Static hosting such as GitHub
Pages can no longer serve it, so the Pages workflow was removed.

All asset paths are relative, so the site runs from a domain root or a
subpath. Nothing is built or bundled; the repository root is the site.

Requirements: PHP 8.1+ with `pdo_mysql`, and MySQL 8 or MariaDB 10.4+.
Point the web root at the repository root and make sure `.env` sits above
it -- see Backend below.

For the dedicated Ubuntu VPS at `172.16.0.209`, the repository includes an
idempotent provisioning script. After cloning to `/home/dad/dad`, run:

```bash
cd /home/dad/dad
sudo bash tools/provision-ubuntu.sh
```

It deploys to `/var/www/dad`, creates a least-privilege MySQL user, writes the
protected production environment, configures Nginx for
`dennisadizon.online`, seeds the systems, and creates the administrator.

Because the home page reads the database, a database outage takes the
portfolio down with it, which was not true of the old static page. If that
matters, put a cache in front of it.

`404.html` is styled to match and is picked up automatically by Apache,
Netlify and Cloudflare Pages.

Once the site has a real domain, prefix `og:image` and `twitter:image` in
`index.php` with the origin and add an `og:url`, since Facebook's scraper
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
`database/schema.sql` describes: sign-in, sign-out, an account page and a
password change.

There is no public sign-up. This is a single-administrator site, so accounts
are created from the command line with `tools/create_admin.php`;
`app/register.php` only explains that.

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

4. Create the administrator:

   ```powershell
   php tools/create_admin.php you@example.com "Dennis Dizon"
   ```

   It prints a generated password once, or takes one as a third argument.
   Re-running it resets that account's password and signs out its sessions.

5. Point the browser at `app/` (`app/login.php`).

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

### Content studio

`app/studio.php` is the admin-only publishing interface. Sign in and it
lists everything grouped by where it appears, with a form to publish or edit.

`app/settings.php` is the admin Settings area. It manages the public profile,
contact details, career metrics and technology summary from `site_settings`.
The same page includes a systems management table; each row opens the complete
entry editor, where every card, detail, link, media, ordering and publication
field can be changed.

Published project entries appear in the keyboard-accessible carousel on the
home page. Arrow buttons move one card at a time, native horizontal scrolling
remains available for touch and trackpads, and reduced-motion preferences are
respected.

An entry has a title, short introduction, category, publication date,
draft/published status, a cover picture with a description, an optional
audio or video recording, label/value detail facts, and a body in simple
HTML. Entries whose type is placed on the home page also carry the card
fields: heading, colour, small and corner labels, order and a link.

**Content types are managed, not hardcoded** (`app/types.php`). Each type
has a *placement* that decides where its entries render:

| Placement | Where it appears |
|---|---|
| Project card | The home page grid, with a detail panel |
| Writing | `writing.php`, each entry at `entry.php?slug=...` |

So a new type such as "Case study" or "Transliteration" can be added at any
time. A type cannot be deleted while it still has entries. The `Writing`
link only appears in the site header once something is published there.

The card heading accepts a vertical bar to force a line break
(`Resort|Booking`), which is how the cards keep their two-line rhythm
without putting markup in the title.

Uploads land in `output/uploads/` under a random name with an extension
chosen from the file's actual contents, never from the name it arrived
with. `output/uploads/.htaccess` disables script execution there. Bodies
are stored as written and reduced to a tag allowlist when rendered.

### Drafts and sharing

An entry saved as a draft is invisible to the public: it is not listed and
its URL returns 404. A signed-in administrator opening the same URL sees it
with a "Draft preview" banner and `noindex`, so you can check a piece before
publishing it. The studio links straight to it after saving.

Each published entry carries its own Open Graph and Twitter tags, using its
cover picture when it has one and the site card otherwise, so a shared link
previews properly.

`sitemap.php` lists the home page and every published writing entry. It
builds absolute URLs from the request host; set `SITE_ORIGIN` in `.env` once
the site has a real domain so the URLs are right regardless of how the
request arrives.

### Tests

```powershell
php tools/test_auth.php
php tools/test_content.php
```

`test_auth.php` covers registration, sign-in, sessions, throttling,
password change, CSRF and SQL injection. `test_content.php` covers content
types, entries, facts, placement, the writable-column whitelist, the HTML
sanitiser, uploads, and checks that `schema.sql` and `schema.sqlite.sql`
still declare the same tables and columns -- they are maintained by hand, so
that guard exists to catch a drift before a deploy does. Runs against in-memory SQLite using the same
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
