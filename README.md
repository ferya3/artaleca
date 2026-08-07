# ARTA LECA — industrial corporate website

Trilingual (Persian / English / Arabic) corporate site for a producer of
lightweight expanded clay aggregate (LECA), built on Laravel 13 with
server-rendered Blade, Tailwind CSS v4 and effectively no client-side framework.

---

## Stack

| | |
|---|---|
| Framework | Laravel 13 (PHP 8.4) |
| Database | MySQL / MariaDB in production, SQLite for local work and tests |
| Views | Blade — server-rendered, no SPA |
| Styling | Tailwind CSS v4 via `@theme` design tokens |
| Build | Vite 8 |
| JavaScript | ~2 KB of vanilla progressive enhancement. No React/Vue/Alpine. |
| Fonts | One self-hosted Vazirmatn variable font (111 KB) covering all three scripts |

**Production bundle:** ~8.5 KB CSS and ~0.6 KB JS, gzipped, plus one font request.

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
behind the mobile menu, header elevation on scroll, gallery thumbnails, and
auto-submitting filters. The site is fully usable with JavaScript disabled.

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
vendor/bin/phpunit    # 87 tests
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
/{locale}/products/{category}         Category
/{locale}/products/{category}/{grade} Product detail
/{locale}/applications                Industries & applications
/{locale}/applications/{slug}         Application detail
/{locale}/projects                    Reference projects
/{locale}/projects/{slug}             Project detail
/{locale}/news                        News & technical articles
/{locale}/news/{slug}                 Article
/{locale}/downloads                   Catalogues, datasheets, certificates
/{locale}/faq                         FAQ
/{locale}/contact                     Contact form
/{locale}/quote                       RFQ form                       (noindex)
/{locale}/search                      Search                         (noindex)
/{locale}/privacy  /{locale}/terms    Legal
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
- **Structured data** — Organization and WebSite site-wide, plus Product,
  Article, FAQPage, BreadcrumbList and ItemList where relevant, emitted as a
  single `@graph`.
- **Sitemap** — one `sitemap.xml` covering all three locales, each URL carrying
  its complete alternate set. Cached for six hours, invalidated on content save.
- **robots.txt** — blocks the admin, search and RFQ pages; blocks everything
  outside production so a staging copy cannot be indexed.
- Pagination beyond page 1, search results and form pages are `noindex, follow`.

---

## Performance

- Server-rendered HTML; no hydration step, no client-side routing.
- One self-hosted variable font covering Persian, Arabic and Latin, preloaded.
  No CDN origin on the critical path.
- Below-the-fold images are `loading="lazy"`; the LCP image is `eager` with
  `fetchpriority="high"`.
- Navigation and site settings — read on every request — are cached; the caches
  are invalidated whenever content is saved (`Navigation::flush()`).
- Products expose their filterable properties (grain size, bulk density) as real
  indexed columns rather than JSON, so the catalogue filters and sorts in SQL.
- Where a product has no photograph yet, the page renders a deterministic
  "granule field" SVG derived from the record's slug: on-brand, stable per
  record, and zero extra requests.

---

## Security

- **CSP is nonce-based**, not `unsafe-inline`. A fresh nonce per request is
  shared with Blade and stamped on the few inline blocks the layout emits, so
  anything injected later simply does not execute.
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
