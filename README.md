# ARTA LECA — industrial corporate website

Trilingual (Persian / English / Arabic) corporate site for a producer of
lightweight expanded clay aggregate (LECA), built on Laravel 13 with
server-rendered Blade, Tailwind CSS v4 and effectively no client-side framework.

---

## Stack

| | |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Database | MySQL / MariaDB in production, SQLite for local work and tests |
| Views | Blade — server-rendered, no SPA |
| Styling | Tailwind CSS v4 via `@theme` design tokens |
| Build | Vite 8 |
| JavaScript | ~2 KB of vanilla progressive enhancement. No React/Vue/Alpine. |
| Fonts | One self-hosted Vazirmatn variable font (111 KB) covering all three scripts |

**Production bundle:** ~10.2 KB CSS and ~0.5 KB JS, gzipped, plus one font request.

### Why no JavaScript framework

Every page is a document: a catalogue, a datasheet, an article, a form. There is
no client-side state worth synchronising, so a framework would add payload and a
hydration step without changing what the user can do. The interactions that
*are* needed are expressed in HTML instead:

- the mobile menu and the FAQ accordion are `<details>` elements,
- catalogue filters are a plain `GET` form (so every filter combination is a
  real, shareable, crawlable URL),
- forms are ordinary `POST`s.

`resources/js/app.js` only adds what HTML cannot express: a body-scroll lock
behind the mobile menu, the header's elevation and hide-on-scroll, gallery
thumbnails, and auto-submitting filters. The site is fully usable with JavaScript disabled.

---

## Design system

Everything visual comes from tokens declared once in `resources/css/app.css`
under `@theme`; templates reference the semantic names, never raw values.

- **Two colour ramps.** `ink` is a warm-cast neutral that reads as concrete
  rather than as blue-grey UI chrome, and `clay` is the fired-clay accent of the
  product itself. Both ramps stop short of pure black and pure white, so nothing
  on the page meets at maximum contrast.
- **One radius scale.** `md` is the workhorse for buttons, inputs, chips and
  small tiles; `lg` is for cards, media and panels, so the curve stays
  proportional to the box it sits on. Form controls get their radius from a base
  rule rather than a utility, which is what keeps the public forms, the
  catalogue filters and the admin panel identical without repeating a class.
- **Elevation, not just hairlines.** Two shadows — `soft` at rest and `lift` on
  hover — both wide, shallow and tinted with the warm ink rather than pure
  black. The point is a soft edge, not a floating card; a hairline is still
  there underneath as the quiet fallback.
- **Three promoted classes.** `.panel`, `.panel-muted` and `.panel-interactive`
  carry the one surface treatment the site repeats ~40 times. Anything used once
  stays as utilities in the template.

### On a phone

The mobile header is a three-column grid — menu button, logo, spacer — so the
logo is optically centred rather than merely placed after the button. Grid
columns follow the writing direction on their own, which puts the button at the
start of the line in every language: on the right in Persian and Arabic, on the
left in English, with no direction-specific classes anywhere.

The hero image runs edge to edge with no frame, so a photograph reads as part of
the band rather than as a card dropped into it, and the headline drops to 30px
because at 36px a three-word Persian line wraps to four rows and pushes the
actions off the first screen. The actions themselves become full-width bars.

The figures strip is `display: none` below `md`. Five statistics become five
stacked rows on a phone, and someone there is looking for a product or a phone
number rather than reading company numbers — hiding it rather than shrinking it
also keeps it out of the accessibility tree.

No gradients outside the hero's kiln glow and the faint blueprint grid behind
the dark bands, and no glassmorphism — the header is deliberately opaque rather
than blurred, which also avoids `backdrop-filter` making it a containing block
for the fixed mobile menu inside it.

### Motion

Motion is confined to two places, and both animate **only `transform` and
`opacity`**, so they run on the compositor and never contend with the main
thread for the LCP text:

- **The hero background.** A rotary-kiln glow breathing behind two granule
  fields drifting at different speeds over the blueprint grid — the material
  and the process, not a decorative gradient. Each layer translates by exactly
  one pattern tile, which is what makes the loop seamless rather than visibly
  snapping back.
- **The header.** It starts hidden so the hero is met without a bar across it,
  and slides in once the page scrolls. The thresholds are asymmetric on purpose
  — revealing at 140px but hiding again only below 40px — so a trackpad
  hovering near the boundary cannot strobe it. It is `fixed` rather than
  `sticky`, because a sticky header keeps its space in the flow even while
  translated away and left a blank band above the hero.

  **It fails open, and that is the whole design.** The header is the only
  navigation on a phone and it is hidden until scrolled, so anything that stops
  the reveal running would take the navigation away for good — no amount of
  scrolling brings back a bar that nothing can unhide. So the hiding rule is
  gated on `data-autohide`, which the inline head script sets and nothing else
  does: no script, no attribute, no hiding.

  That script is inline in `<head>` rather than in the bundle, precisely so a
  blocked, slow or broken bundle cannot cost a visitor their navigation — the
  header keeps working with the bundle aborted entirely. It is also why the
  bundle's own enhancements are each wrapped individually: a throw in one used
  to take out every one that had not run yet.

  `:focus-within` reveals it too, so a keyboard user tabbing out of the
  skip-link lands on visible navigation — which is why the hidden state uses
  `opacity` and not `visibility`, since an invisible element is still focusable
  and a hidden one is not. It also stays put whenever the mobile menu is open,
  since the close button lives inside it.

  Switching language restores the scroll offset rather than forcing the bar
  open. A language link is clicked *from inside the header*, so landing at the
  top of the new page put the fixed bar over the first 118px of content the
  visitor had not asked to be taken to. Keeping their place reveals the header
  on its own, because the page is scrolled — the three translations set the
  same content at different lengths, so the offset is close rather than exact,
  which is the right trade against being thrown back to the top.

`prefers-reduced-motion` freezes all of it through one base rule, and the
header's hide-on-scroll does not bind at all under that setting.

---

## Getting started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite     # or point DB_* at MySQL
php artisan migrate --seed         # seeds the full catalogue in all 3 languages
php artisan storage:link

npm run build                      # or: npm run dev
php artisan serve
```

The seeder creates an administrator at `ADMIN_EMAIL` (default
`admin@artaleca.com`). In `local` the password is `password`; in any other
environment it is randomly generated and printed once unless `ADMIN_PASSWORD` is
set. Sign in at `/admin`.

```bash
vendor/bin/phpunit    # 168 tests
vendor/bin/pint       # code style
```

---

## Information architecture

The URL structure carries the hierarchy, so breadcrumbs, `BreadcrumbList`
structured data and the navigation are all describing the same tree.

```
/                                     → redirects to the visitor's best locale
/{locale}/                            Home
/{locale}/about                       About  ├─ /quality  ├─ /plant
/{locale}/products                    Catalogue (filterable)
/{locale}/products/category/{slug}    Category
/{locale}/products/{slug}             Product detail
/{locale}/applications                Industries & applications
/{locale}/applications/{slug}         Application detail
/{locale}/projects                    Reference projects
/{locale}/projects/{slug}             Project detail
/{locale}/articles                    News & technical articles
/{locale}/articles/{slug}             Article
/{locale}/projects-gallery            Image gallery
/{locale}/downloads                   Catalogues, datasheets, certificates
/{locale}/faq                         FAQ
/{locale}/contact                     Contact form
/{locale}/quote                       RFQ form                       (noindex)
/{locale}/search                      Search                         (noindex)
/{locale}/privacy  /{locale}/terms    Legal
/{locale}/{slug}                      Editor-created pages (catch-all, last)
/sitemap.xml  /robots.txt             Root-level, all locales in one sitemap
/admin/…                              Back office (outside the locale prefix)
```

### Conversion path

A specifier arrives on a product or application page from search, checks two
headline numbers (grain size, bulk density) without scrolling, and reaches the
RFQ form pre-filled with that grade. Every content page ends with the same
single conversion band — one primary action (request a quote) and one secondary
(talk to an engineer). That band is the only place on a page where two calls to
action sit together.

---

## Three languages

Locales are declared once in `config/site.php` and read through
`App\Support\Locales`. Persian is the default; Persian and Arabic render RTL,
English LTR.

**Slugs are shared across locales.** A product is `leca-structure-4-10` in all
three languages, so alternate-language URLs differ only by their prefix. That
makes `hreflang` and `canonical` trivial to generate and impossible to point at
a page that does not exist — and it keeps URLs stable if a title is retranslated.

**Content is translated in JSON columns.** `App\Concerns\HasTranslations` stores
`{"fa": …, "en": …, "ar": …}` and resolves the active locale on read, falling
back to Persian and then to any non-empty value, so a partly translated record
still renders. Writing a plain string only touches the active locale — an editor
working in one language can never wipe the other two.

One implementation note worth knowing: controller actions receive route
parameters *positionally*, so the `{locale}` prefix would land in the first
argument of every action. `SetLocale` removes it from the route after applying
it to the app and the URL generator, which is what lets controllers be written
as `show(Product $product)`.

---

## SEO

Handled centrally rather than sprinkled through templates. Controllers describe
the page; the layout renders it.

```php
seo()->title($product->name)
     ->description($product->summary)
     ->image($product->primaryImage())
     ->type('product')
     ->breadcrumbs(...)
     ->schema(Schema::product($product));
```

- **hreflang** — every page emits the full reciprocal set plus `x-default`.
- **Canonical** — query strings are dropped except the ones that genuinely
  change the content (`page`, `category`, `q`, `grain`…), so tracking tags
  cannot split ranking signals across duplicate URLs.
- **Structured data** — Organization and WebSite site-wide, plus LocalBusiness,
  Product, Article, FAQPage, BreadcrumbList and ItemList where relevant,
  emitted as a single `@graph`. LocalBusiness stays unpublished until an
  administrator switches it on *and* supplies a real street and locality —
  placeholder geodata is worse than none, because search engines display it.
- **Redirects** — an editor-managed 301/302 table, applied by global middleware
  only when a request would otherwise 404, so the lookup costs nothing on URLs
  that resolve.
- **Sitemap** — one `sitemap.xml` covering all three locales, each URL carrying
  its complete alternate set. Cached for six hours, invalidated on content save.
- **robots.txt** — blocks the admin, search and RFQ pages; blocks everything
  outside production so a staging copy cannot be indexed.
- Pagination beyond page 1, search results and form pages are `noindex, follow`.

---

## Performance

Targets: LCP < 2.5 s, INP < 200 ms, CLS < 0.1. The structural decisions that
get there:

- Server-rendered HTML; no hydration step, no client-side routing. INP is
  bounded by there being almost no main-thread JavaScript to block it.
- One self-hosted variable font covering Persian, Arabic and Latin, preloaded.
  No CDN origin on the critical path.
- Navigation and site settings — read on every request — are cached; the caches
  are invalidated whenever content is saved (`Navigation::flush()`).
- Products expose their filterable properties (grain size, bulk density) as real
  indexed columns rather than JSON, so the catalogue filters and sorts in SQL.
- Where a product has no photograph yet, the page renders a deterministic
  "granule field" SVG derived from the record's slug: on-brand, stable per
  record, and zero extra requests.

### Images

No large image reaches the site unoptimised, because optimisation happens on
upload rather than being left to whoever uploads it. `App\Support\Image` runs on
PHP's bundled GD — no image library is added for three operations GD already
does — and every upload is:

1. **capped** to a 2400px longest edge, since nothing on the site displays
   larger and the original costs megabytes;
2. **re-encoded**, which strips EXIF — that removes the GPS coordinates phones
   embed as well as a payload shipped to every visitor for nothing;
3. **given WebP derivatives** at 480 / 768 / 1024 / 1440 / 1920.

The intrinsic size is written into the filename (`<random>-1600x1200.jpg`).
That single convention is what lets a template emit `width`/`height` — holding
the layout against CLS — *and* build the complete `srcset`, without one
filesystem call per image on the render path. `<x-picture>` is the only place
that markup lives.

Preload is reserved for the one image likely to be the LCP element: the first
`eager` image on the page, and nothing else. A second preload would not make the
page faster, it would make both compete for the same bandwidth.

An animation, an unsupported format, or an image too large to decode safely is
stored untouched instead — an editor never loses an upload to an optimisation
that could not run — and images stored before the pipeline existed degrade to a
plain `<img>` rather than pointing at derivatives that were never written.

### Query budgets

Every public page costs between 0 and 7 queries, and `PagePerformanceTest`
keeps it that way. Alongside per-page ceilings it asserts the property that
cannot go stale: adding twenty products must not change what the catalogue
costs. A lazily-loaded relation in a card shows up there immediately, however
the ceilings are tuned.

Compression, cache headers and CDN setup belong to the web server —
see [docs/deploy-ubuntu.md](docs/deploy-ubuntu.md), which also lists the `curl`
commands to verify each one actually took effect.

---

## Security

Most of this is verified rather than asserted: `SecurityHardeningTest` checks
the headers, the nonce, the JSON-LD escaping, the admin's closure to anonymous
visitors, the login's refusal to disclose accounts, its rate limit, and the
upload and traversal defences.

- **CSP is nonce-based**, not `unsafe-inline`. A fresh nonce per request is
  shared with Blade and stamped on the few inline blocks the layout emits, so
  anything injected later simply does not execute. Setting `ASSET_URL` adds that
  origin to the asset-serving directives only — never to `form-action` or
  `frame-ancestors`.
- **The structured-data block is escaped with `JSON_HEX_TAG`.** That one is
  load-bearing: the `@graph` is built from editor-supplied text, and inside a
  `<script>` the HTML parser finds `</script` before the JSON parser runs — so
  a product name containing `</script><script>…` would otherwise break out and
  execute.
- Uploads cannot execute. The stored name and extension come from the sniffed
  MIME type, so a `.php` cannot be written in the first place; the web server
  additionally refuses to run anything under the upload tree, and directory
  listing is off unconditionally rather than only when `mod_negotiation`
  happens to be loaded.
- The session cookie defaults to `secure` whenever `APP_ENV=production`, rather
  than to whatever `.env` remembers to say. A flag that must be remembered is
  one that eventually is not, and that failure is silent.
- Baseline headers: `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy`, COOP/CORP, and HSTS over HTTPS.
- **Forms use a honeypot plus a signed render timestamp** instead of a
  third-party CAPTCHA: no external request, no cookie, no accessibility
  penalty, and nothing for a visitor to solve. A submission that arrives in
  under three seconds, or with the hidden field filled, or with an unsigned
  timestamp, is rejected before validation.
- Public forms are rate-limited per IP (3/minute, 20/day); login is 5/minute per
  IP and per address.
- Sender IPs are stored only as a keyed hash — enough to spot abuse, not enough
  to retain a plain-text identifier for every enquiry.
- Documents are served from a **private** disk through a controller, so an
  unpublished file is genuinely unreachable and an editor's path cannot be
  turned into a traversal.
- The login form returns one message for both a wrong password and an unknown
  address, so it cannot be used to enumerate accounts.

---

## Admin

`/admin` is a single interface outside the locale prefix, with per-user language.

Everything the site renders is editable: pages, products, categories,
applications, projects, articles, FAQs, certifications, partners, the gallery,
documents, enquiries, redirects, the headline numbers, and both the site-wide
copy and SEO defaults. Nothing that an editor might reasonably want to change
is hard-coded in a template.

**Files are uploaded, never typed as paths.** The stored filename is generated
and the extension comes from the file's sniffed MIME type, so an editor cannot
overwrite an existing asset or store something executable. Images go to the
public media disk; documents go to the private one and are streamed by a
controller.

Content is managed through one **schema-driven CRUD layer**
(`Admin\ResourceController`): each resource declares its model, a field schema
and its list columns; validation, translation handling, slug generation and both
Blade views are shared. Adding a resource is a ~40-line class, and every resource
behaves identically for the editor. Translatable fields render one input per
locale, each tagged with its own `lang` and `dir`, so an Arabic field types
right-to-left even while the panel is in Persian.

JSON columns are never shown as JSON: lists are edited one item per line, and
spec rows as `label | value`.

### Roles

Coarse role on the user, real decisions in Policies:

| | read | write | delete | users & settings |
|---|---|---|---|---|
| `viewer` | ✓ | | | |
| `editor` | ✓ | ✓ | | |
| `admin`  | ✓ | ✓ | ✓ | ✓ |

Eight content models share one `ContentPolicy`, so a new content type cannot
ship with its permissions accidentally left open. An administrator cannot demote
or delete their own account — that is the classic way to lock the last admin out.

---

## Layout of the code

```
app/
  Concerns/HasTranslations.php   per-locale JSON attributes
  Concerns/Publishable.php       shared active/ordered scopes
  Http/Middleware/               SetLocale, SecurityHeaders, ResetScopedState…
  Http/Controllers/Admin/        schema-driven CRUD
  Policies/                      ContentPolicy, ContactMessagePolicy, UserPolicy
  Support/                       Locales, Seo, Schema, Url, Navigation, Search
config/site.php                  company identity, locales, contact details
lang/{fa,en,ar}/                 interface copy
resources/views/
  components/                    layout + design-system components
  pages/                         one file per page
database/seeders/                the full catalogue, in three languages
```

---

## Deployment notes

- Set `DB_CONNECTION=mysql` and the `DB_*` credentials; the schema is portable.
- `APP_ENV=production` forces HTTPS URL generation and switches `robots.txt`
  from "block everything" to the real policy.
- Run `php artisan config:cache route:cache view:cache` and `npm run build`.
- `php artisan storage:link` is required for editor-uploaded media.
- Documents belong on the private `documents` disk
  (`storage/app/private/documents`), never in `public/`.
