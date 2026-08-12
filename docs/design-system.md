# ARPI Accounting — Design System & Project Context

> **Purpose.** This is the single-file, ground-truth reference for the ARPI theme —
> written to give an AI (or a new developer) full project context in one read: what the
> project is, how it's built, the design tokens, the component/CSS/JS conventions, and the
> "how we work here" rules. It reflects the **as-built** state of the code, not just design
> intent. Companion docs: `docs/design-tokens.md` (raw Figma token extraction) and
> `docs/superpowers/{specs,plans,runbooks}` (per-phase design specs & implementation plans).
>
> When these ever disagree, **the code wins** — verify against the theme before asserting.

---

## 1. What this project is

A complete **rewrite of arpiaccounting.com** (a bilingual PL/EN accounting-firm site) from a
heavily-modified legacy Twenty Seventeen theme into a clean **WordPress + Roots Sage 11** theme.
Green-field WordPress install (not a theme swap); content is rewritten and selectively migrated
from the legacy prod DB. The client keeps editing content in wp-admin (hard requirement → stays
on WordPress, not headless).

- **Bilingual**: Polish default (`/`) + English (`/en/`) via **Polylang** (directory mode).
- **Design source**: Figma (desktop + mobile frames), built 1:1 but with rounded, systematised
  spacing/type (see conventions).
- Legacy prod: WordPress on nazwa.pl; new site deploys to **cyberFolks** (staging now, prod at cutover).

---

## 2. Stack

| Layer | Choice |
|---|---|
| CMS | WordPress, **standard `public_html` layout** (no Bedrock — client manages plugins in wp-admin) |
| Theme framework | **Roots Sage 11** + **Acorn** (Laravel container, Blade, view composers) |
| CSS | **Tailwind CSS v4, CSS-first** (`@theme`/`@layer`/`@utility` in CSS — **no `tailwind.config.js`**) + `fluid-tailwindcss` plugin (`fl-*` utilities) |
| Build | **Vite** (`@tailwindcss/vite`, `laravel-vite-plugin`, `@roots/vite-plugin`; `wordpressThemeJson` emits `theme.json`) |
| Icons | **blade-ui-kit/blade-icons**, custom set (`@svg('icon-<slug>')`) |
| i18n | **Polylang** (Pro) — PL default + EN |
| Content model | **ACF** — CPTs, taxonomies & field groups as **Local JSON** in the theme |
| Secrets | `.env` (phpdotenv) **above** `public_html` (outside docroot); `wp-config.php` reads it |
| Repo | git tracks **only the theme** (+ scripts); WP core, plugins, uploads are gitignored |

Active theme: `public_html/wp-content/themes/arpi/`.

---

## 3. Environments & deploy

| Env | Setup |
|---|---|
| **Dev** | Docker Compose + Makefile: `php`, `db` (MariaDB 10.11), `nginx`. Containers: `arpiaccounting-web-{php,db,nginx}-1`. Repo mounts at `/var/app` (WP root `/var/app/public_html`). Dev URL **http://localhost:8080**. Vite HMR via `make dev`. |
| **Staging** | cyberFolks subdomain, HTTP Basic Auth + `noindex`. Client previews progress. |
| **Prod** | cyberFolks; migrated at cutover; legacy prod stays live until then. |

**Git flow — trunk-based, solo.** `main` is the source of truth; short `feat/*` branches merge in
(via PR). **A push to `main` that touches the theme triggers `.github/workflows/deploy-staging.yml`**:
CI runs `yarn build` + `composer install --no-dev`, then rsyncs the built theme to staging over SSH
and clears Acorn caches. **Only the theme ships** — WP core & `wp-content/plugins` on staging are
never touched. Prod deploy is gated (tag `v*` / manual dispatch with environment approval).

> **Deploy ships CODE only, never DB/uploads.** Content (posts, terms, ACF values, media) lives in
> the database + uploads. To mirror **data** dev→staging use `make sync-staging` (= `push-db-staging`
> + `sync-plugins-staging` + `sync-uploads-staging`). **`push-db-staging` OVERWRITES the entire
> staging DB** — destructive, confirm before running.

### Everyday commands
```
make up / make down            # start / stop containers
make dev                       # Vite dev server + HMR (host)
make build-assets              # build theme assets (host)  — or: cd theme && yarn build
make shell                     # shell into php container
make wp ARGS="…"               # WP-CLI, e.g. make wp ARGS="plugin list"
make wp ARGS="acorn view:clear"   # clear compiled Blade views when a view won't refresh
make wp ARGS="eval-file /var/app/scripts/<seed>.php"   # run a seed script (idempotent)
make import-db FILE=dump.sql.gz / make dump-db FILE=…
```
No unit-test suite. **Verification = `yarn build` clean + `curl` DOM checks + visual compare vs
Figma at ~375px and ~1440px.**

---

## 4. Content model

- **CPTs / taxonomies / field groups are ACF Local JSON** in `resources/acf-json/` (loaded via
  `acf/settings/load_json` + `save_json` in `app/setup.php`). ACF auto-registers types from local
  JSON — no `register_post_type` in the theme. Editable in wp-admin → ACF; versioned in git.
- **Every Blade section gets its data from a view composer** (`app/View/Composers/*`). Even when a
  composer returns hardcoded content today, switching it to `get_field()` is a one-line change with
  **no template rewrite**. This is a first-day architectural rule.
- **ACF content is per-post (post-per-language)**, not global Options Pages — cleaner for Polylang.
  (Exception: genuinely global settings like the DBiP version/date use an options page.)
- Hardcoded-vs-ACF status today: **Home**, **Usluga**, **DBiP**, **Footer**, **Careers** & **Proces**
  composers read ACF with hardcoded fallbacks (`fromAcf() ?? fromHardcoded()`); each fallback branches
  PL/EN (or is per-language via Polylang) so every page renders even before ACF is filled. **Contact**
  is the only composer still fully hardcoded (deferred to a dedicated future task). Because the dev box
  runs **ACF free** (options pages, `group`/`repeater` fields are Pro-only), the ACF paths are inert
  locally and every page falls back — the wiring is exercised on the client's Pro environment.
- **Global theme settings** (footer company/offices/socials/badges/newsletter copy) live on a dedicated
  **"Ustawienia motywu" options page** (`ThemeSettingsServiceProvider`), made **per-language** by a
  filter that appends the current Polylang language to its option id (`theme_settings_{lang}`), scoped
  strictly to that page so the DBiP options page stays language-neutral. Read with
  `get_field('…', ThemeSettingsServiceProvider::OPTIONS_ID)`.
- **Home ACF lives on the front-page Page** (per-post, not an options page). The EN home is a
  linked Polylang translation of the PL front page, so each language edits its own content.
  `HomeServiceProvider` extends ACF's "Front Page" location rule to also match the translation of
  the front page — keeping the field group free of hardcoded post IDs.

### CPTs in play
- **`usluga`** (slug `uslugi`) — service subpages `/uslugi/{slug}` (PL+EN via Polylang). Single
  template `single-usluga`.
- **`dbip-chapters`** (+ taxonomy `chapter-name`) — "Doing Business in Poland" publication.
  **English-only** (no Polylang). Custom permalink `dbip-chapters/%chapter-name%`.
- Blog: standard `post` + `category` (PL+EN).

---

## 5. Internationalisation (Polylang)

- Language from **`pll_current_language()`**, always behind a `function_exists()` guard (Polylang may
  be absent on CLI / a staging DB without the plugin):
  ```php
  $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
  'all_label' => $isEn ? 'All articles' : 'Wszystkie artykuły',
  ```
- **UI strings are hardcoded PL** in Blade/composers (no gettext infra yet). EN variants are added by
  branching in the composer as above. Scalable target (not yet done): `pll_register_string()` + `pll__()`.
- **Term names come HTML-entity-encoded** from WP (`HR &amp; OHS`); Blade `{{ }}` escapes again →
  double-encode. Fix: `{{ html_entity_decode($cat->name, ENT_QUOTES) }}` (composers expose a
  `decode()`/`title()` helper that does this).
- **DBiP is intentionally EN-only** — its templates/labels are English; no i18n branching there.

---

## 6. Design tokens  (`resources/css/theme.css` — `@theme static`)

`@theme static` emits every token as a real `:root` custom property (so hand-authored
`var(--color-red)`/`color-mix()` in component CSS and the generated `theme.json` always have them).
Tailwind's default palette is wiped (`--color-*: initial`) so `bg-red` means **our** red.

### Colors
| Token | Value | Use |
|---|---|---|
| `--color-white` | `#ffffff` | surfaces |
| `--color-black` | `#19191c` | text (soft near-black, **not** `#000`) |
| `--color-red` | `#942d58` | primary brand |
| `--color-red-dark` | `#7b2549` | hover |
| `--color-red-darker` | `#651f3c` | active / press |
| `--color-cream` | `#f5f4ee` | warm off-white surface / callouts |

Transparent brand tint via Tailwind opacity: `bg-red/30`, `text-red/60`; in CSS use
`color-mix(in oklab, var(--color-red) 30%, transparent)`.

### Typography — Geomanist **Light 300** (body copy) + **Regular 400** (headings/UI)
One family, five declared weights in `base/fonts.css`: **Light 300**, **Book 350**, **Regular 400**,
**Bold 700**, **Black 900** (only Regular ships woff2 — `geomanist-regular-webfont.woff2/woff`; Book/Bold/Black
are woff-only; Book is 350 to sit between Light and Regular without colliding at 400). **Body copy is
`font-weight: 300`** (Body Big/Base/Small inherit it); **headings** (`h1/h2/h3/.h1`) and **buttons/inputs**
(`.c-btn`, `.c-input`) pin **400** (Regular).

> ⚠️ **Known font gotcha (kept intentionally — user-confirmed 2026-08-07). DON'T "fix" by deleting the 300
> `@font-face`.** The Light source files (`geomanist-light-webfont.woff2/woff`) were **never committed**, so
> the weight-300 `@font-face` **errors** (`document.fonts` → status `error`; the built CSS even leaves the url
> un-fingerprinted). Result: **body copy (300) falls back to the system sans** (`ui-sans-serif, system-ui` —
> San Francisco on macOS), **not** Geomanist Light. The user *wants* this visible body-vs-headings distinction
> for now. To render body in true Geomanist Light instead, drop `geomanist-light-webfont.woff2/woff` into
> `resources/fonts/` — the existing 300 rule then just works.

Fluid type, endpoints **640→1280px**, `rem` for font-size
(a11y). Font-size tokens carry **no** line-height (line-height is applied per element/component — the
anti-boilerplate rule).

| Token | clamp | px range | Notes |
|---|---|---|---|
| `--text-display` | `clamp(2.25rem, 0.4375rem + 4.531vw, 4.0625rem)` | 36→65 | H1; `.h1` utility applies this to any element |
| `--text-h2` | `clamp(1.5rem, 0.75rem + 1.875vw, 2.25rem)` | 24→36 | |
| `--text-subtitle` | `clamp(1.5rem, 1rem + 1.1vw, 2rem)` | 24→32 | section subtitle; `--line-height: 1.25` |
| `--text-h3` | `clamp(1rem, 0.5rem + 1.25vw, 1.5rem)` | 16→24 | |
| `--text-body-lg` | `clamp(1.25rem, 1rem + 0.625vw, 1.5rem)` | 20→24 | |
| `--text-body` | `1.25rem` | 20 (fixed) | body default (fluid version commented out) |
| `--text-body-sm` | `1rem` | 16 (fixed) | a11y floor |

Element defaults (`base/typography.css`): `body` = `--text-body`/lh 1.4/black on white; `h1/.h1` lh
0.98 ls −0.25px; `h2` lh 1.22; `h3` lh 1.25; all headings weight 400 + `text-wrap: balance`;
`a` = inherit color, no underline. `html,body { overflow-x: clip }`.

### Spacing (fluid) — `app.css` `@plugin "fluid-tailwindcss"` (viewports 640→1280)
Generates fluid utilities (`py-section`, `gap-stack`, `fl-gap-8/16`, `fl-size-30/60`, …). Named scale:
| Token | min/max |
|---|---|
| `--spacing-section` | 48px / 80px |
| `--spacing-stack` | 32px / 48px |
| `--spacing-head` | 16px / 24px |
| `--spacing-cols` | 32px / 64px |
| `--spacing-hex-dbip` | 140px / 290px |

Ad-hoc fluid pairs use the `fl-` prefix directly: `fl-py-12/24`, `fl-gap-6/12`, `fl-size-10/16`.

### Layout / breakpoints
- `--wrap-padding-inline: clamp(30px, -20px + 7.813vw, 80px)` (container gutter, 30→80px).
- `--breakpoint-xs: 30rem` → adds an `xs:` variant (on top of Tailwind's `sm md lg xl`).
- `theme.json`: `layout.contentSize: 48rem`, `wideSize: 70rem` (Gutenberg constrained-layout widths;
  `alignwide` blocks get 70rem — see DBiP tables).

---

## 7. CSS architecture & conventions

- **ITCSS-lite prefixes** to separate authored classes from Tailwind utilities: `c-` component,
  `o-` layout object, `u-` custom utility. **BEM block + `--modifier` only** (no `__element` — Blade
  owns internal markup). Native CSS nesting for `&:hover` etc. (Sass-style `&--x` concatenation does
  **not** work — modifiers are flat classes).
- **Multi-file CSS**: `app.css` is a thin index that `@import`s single-purpose partials; cascade
  `@layer`s merge across files. Stay on **native CSS** (custom props, nesting, `@layer`,
  `color-mix()`) — no Sass.
- **Units policy**: `rem` for **font-size only** (respects user's browser font-size). `px` for
  everything else (spacing, borders, radii, layout) for readability. (Tailwind's own utility scale is
  rem-based; fine — that's utilities, not authored CSS.)
- `@source` globs in `app.css` make Tailwind scan `app/**/*.php`, `config/**/*.php`, `**/*.blade.php`,
  `**/*.js`.
- `editor.css` imports theme + fonts + typography so the block editor matches the front end.

### Component / object / utility classes
| Class | File | What |
|---|---|---|
| `.c-btn` + `--solid/--outline/--ghost/--white/--underline` | `components/button.css` | Pill button. base: inline-flex, gap 16, pad 14/24, 2px border, radius 30, `--text-body-sm`, ls .5, nowrap. solid=red/white text (hover red-dark, active red-darker); outline=white/red (hover fills red-dark); ghost=no box, `--text-body`, trailing svg translates +3px on hover (reacts to `.group/card`); white=white/red (hover cream); underline=animated transparent→currentColor underline. |
| `.c-input` + `--on-red` / `--area` | `components/input.css` | Pill input, 2px black border radius 30, focus→red border; `--on-red` = white border/text (footer); `--area` = textarea variant (auto height, leading 1.4, softer radius). Also styles `select.c-input` and `.c-checkbox` (20px, `accent-color: red`). |
| `.o-wrap` + `--header/--wide` | `components/wrap.css` | Container. base max-width **1440px** + fluid gutter (**horizontal only, no vertical padding**); `--header` tighter gutter (16→80); `--wide` max **1600px** + tighter gutter (DBiP archive). |
| `.c-hex*`, `.c-honeycomb*`, `.c-hex-triad` | `components/hexagon.css` | Flat-top hexagon (`aspect-ratio 417/372`, clip via inline SVG path). solid=red fill/white text; outline=stroke, fills on hover. Honeycomb = responsive layout (`max-md` 2-2-1, `md` 5-across interlocked). `c-hex-triad` = DBiP 3-hex cluster. `--hex-w` drives sizing. |
| `.c-dbip-num`, `.c-dbip-photo(--mobile)`, `.dbip-content` | `components/dbip.css` | DBiP: number-tab & slanted-photo clip-paths; `.dbip-content` = Gutenberg prose (red headings, red list markers, smooth-underline links, `.double-column` 2-col ≥1024, `.highlighted-paragraph` cream callout, responsive tables `.top-row-highlighted`/`.left-column-highlighted`). |
| `.c-prose` | `components/prose.css` | Long-form article body for classic-editor **blog** posts: red h2/h3/h4, red list markers, animated-underline links, blockquote, rounded images, cream-header tables. Simpler than `.dbip-content` (no Gutenberg block variants). Used by `content-single-post` with a `max-w-[70ch]` measure. |
| `.c-pagination` | `components/pagination.css` | Styles WordPress `paginate_links()` output (`.page-numbers`) as pill buttons matching the button system (red outline, solid = current, `.dots` = ellipsis). |
| `#primary-nav …current-menu-*`, `[data-contact-highlight]` | `components/nav.css` | Two header-driven interactions. **Active nav state**: the current item's `<li>` (WP `current-menu-item`/`-ancestor`/`current_page_*`) gets a persistent underline; `> a:not([href*="#"])` skips in-page section anchors (O nas/Usługi/Kontakt → `/#…`) that WP falsely flags "current" on the home page they point to. Desktop hover itself is the ghost button's red→red-dark (see header note in §12). **Contact nudge**: `contact-nudge` keyframes scale/wiggle the footer phone+email links (`[data-contact-highlight]`, `inline-block`) when `.is-nudging` is toggled by JS; reduced-motion safe. |
| `section-head` (`@utility`) | `app.css` | flex column, `gap: --fluid-spacing-head` — title+subtitle block. |
| `top-admin-safe` (`@utility`) | `utilities/admin-bar.css` | pins sticky elements below the WP admin bar (≥601px). |
| reveal | `utilities/reveal.css` | scroll-reveal + hero animation; **all gated by `.reveal-ready` + `prefers-reduced-motion: no-preference`**. `[data-reveal]` fades/rises in; `.c-hero` has a blurred plum glow + drift. **`.reveal-ready` is set by an inline `<head>` script _before first paint_** (see §12) — not by `reveal.js` — so above-the-fold `[data-reveal]` (blog archive/single) animates reliably even when the JS module loads late in Vite dev. A failsafe unhides everything if reveal JS never runs. |

---

## 8. Blade components (`resources/views/components/`)

| Component | Props | Purpose |
|---|---|---|
| `<x-button>` | `variant='solid', href=null, type='button'` | `<a>` if href else `<button>`; classes `c-btn c-btn--{variant}`. **Use this instead of hand-rolling button styles.** |
| `<x-container>` | `as='div', variant=null` | `.o-wrap`; `variant='header'|'wide'`. |
| `<x-hexagon>` | `variant='solid', href=null` | flat-top hex SVG + slot; outline non-link is `aria-hidden`. |
| `<x-input>` | `type='text', variant=null` | `.c-input`; `variant='on-red'`. |
| `<x-dynamic-icon>` | `icon` (`{type,name}` or `{type:'file',url}`) | renders an uploaded SVG file **or** `@svg('icon-'.name)`. Used by ACF icon-picker. |
| `<x-service-tile>` | `service` | red rounded homepage service tile (icon + name + circular arrow), links to `/uslugi/{slug}`. |
| `<x-post-card>` | `post` | blog card (thumb + title + `[data-clamp-fill]` excerpt + "Więcej"). Title & excerpt run through `html_entity_decode()` (WP returns entity-encoded text → Blade would double-encode). No thumbnails exist yet → the rounded `bg-red` block shows as a solid plum placeholder (intended). |
| `<x-social-links>` | `links=[], variant='light'` | circular outlined social icon links; `variant='dark'` for light backgrounds (black outline → red-on-hover, used on the contact page). |
| `<x-lang-switcher>` | — | Polylang PL/EN switcher (`pll_the_languages`) with hardcoded fallback. |
| `<x-alert>` | `type, message` | Sage default alert box (unused by content). |
| `<x-dbip.breadcrumb>` | `items` | breadcrumb `<nav>` with arrow separators + optional prefix. |
| `<x-dbip.nav-link>` | `link, dir` | DBiP prev/next as `<x-button variant="outline">`. |
| `<x-dbip.post-nav>` | `nav` | prev/next footer wrapper. |
| `<x-dbip.floating-logo>` | — | fixed bottom-left watermark logo (`lg:block`). |

---

## 9. View composers (`app/View/Composers/`)

Pattern: `class X extends Roots\Acorn\View\Composer`, `protected static $views = [...]`, `with(): array`.
Content hardcoded now, ACF-ready. Titles pass through `html_entity_decode(..., ENT_QUOTES)`.

| Composer | `$views` | Exposes / notes |
|---|---|---|
| `App` | `['*']` | `siteName`. |
| `Home` | `['front-page']` | `hero, about, memberships, why, services, dbip, blog, blogCategories, latestPosts`. `fromAcf()` (reads `group_home` on the current-language front page) → fallback `fromHardcoded()` which branches PL/EN via `pll_current_language()`. ACF image sub-fields resolve to a URL, falling back to the bundled theme asset; `services` items carry an icon slug + link. `blogMeta` merges ACF text overrides onto EN/PL defaults; `blogCategories` filtered to current language, excl. default. |
| `Footer` | `['sections.footer']` | `company, offices, newsletter, socials, badges` (eager `with()`). `fromAcf()` reads the per-language **theme-settings** options page (company legal info, offices, socials, Forbes badges, newsletter copy) → fallback `fromHardcoded()`. `newsletter()` always keeps the REST endpoint/nonce/status messages theme-owned; ACF only overrides its copy. Textarea address fields split on newlines into lines. |
| `Post` | `['partials.page-header','partials.content*']` | `title`, `pagination`. Feeds the stock `search`/`page-header` partials. |
| `Blog` | `['index','partials.content-single-post']` | Dispatches by context. **Archive** (`index` = blog home + category/tag/author/date): `heading, intro, categories, activeTermId, allUrl, allLabel, empty, pagination` (styled `paginate_links` array). **Single**: `crumbs, category, meta{date,author}, nav{prev,next}, labels`. EN/PL via `pll_current_language()`; categories filtered to current language, default excluded. Prev/next use `get_{previous,next}_post` (Polylang keeps them in-language). |
| `Comments` | `['partials.comments']` | Sage defaults. |
| `Contact` | `['template-contact']` | `hero, general, offices, socials, form`. Hardcoded, EN/PL branched via `pll_current_language()`. `form` exposes the REST `endpoint`, a `wp_rest` nonce, topic options and all labels/messages for the AJAX contact form. Offices mirror `Footer::offices()` + opening hours. |
| `Careers` | `['template-careers']` | `hero, intro, mosaic, benefits, positions, cta`. `fromAcf()` (group_careers, located by page template so PL/EN each edit their own page) → fallback `fromHardcoded()` (EN/PL branched). Improvised page (no Figma): text block + photo/stat `mosaic` (dense-flow grid; tiles carry an optional advanced `span`, else a per-kind default), benefit cards, open-role list, red CTA. A single `recruitment_email` ACF field drives every `mailto:` (roles/open/CTA). |
| `Proces` | `['template-proces']` | `hero, stages`. `fromAcf()` (group_proces, located by page template) → fallback `pl()`/`en()`. `stages` repeater carries `tone` (hex variant), icon, `hex_label`, optional `logo`, tags, paras, and an optional benefits `list` — the composer collapses the ACF `{lead,desc}` sub-fields into the `[$lead,$desc]` pairs the Blade destructures. |
| `Usluga` | `['single-usluga']` | `hero, scope_intro, scope, body, reports, others, labels`. `fromAcf()` (icon-picker resolves source/name/file) → fallback `fromHardcoded()` (PL map keyed by slug). `others()` queries sibling `usluga` (current Polylang lang, `tile_excerpt`). `labels()` EN/PL branch. |
| `Whistleblower` | `['template-sygnalista']` | `hero, info, form` for the "Sygnalista" page (PL slug `sygnalista`, EN `whistleblower`). Hardcoded, EN/PL branched; `form` posts to `arpi/v1/report` (`WhistleblowerServiceProvider` → private `zgloszenie` CPT). Code stem is English (`Whistleblower` composer, `partials/whistleblower/`); the `template-sygnalista` filename + `zgloszenie` CPT stay Polish (DB-coupled), the page slug/labels stay Polish (public). |
| `Dbip` | `['archive-dbip-chapters','taxonomy-chapter-name','single-dbip-chapters']` | dispatches by `is_post_type_archive`/`is_tax`/single. Chapters ordered by termmeta `dbip_chapter_order` (excl. `no-chapter`); version/date from options; hardcoded EN CEO/About; prev/next crosses chapter boundaries; glossary = `no-chapter` term. On a **locked** chapter the single branch also exposes `access` (endpoint/nonce/`post` id/TOS/messages) for the "receive a password" request form. |

---

## 10. Providers · setup · filters · icons · assets

Providers (`functions.php` → `Application::configure()->withProviders`): `ThemeServiceProvider`
(Sage base), `AcfServiceProvider`, `DbipServiceProvider`, `HomeServiceProvider`,
`ContactServiceProvider`, `ThemeSettingsServiceProvider`, `AdminMenuServiceProvider`,
`WhistleblowerServiceProvider`. Then loads `app/setup.php` + `app/filters.php`.

- **Hooks live in classes** (providers), not in `setup.php`/`filters.php` for new logic — see conventions.
- **`AdminMenuServiceProvider`** — registers the top-level **"ARPI"** hub (slug `arpi-admin`, custom
  "A" CSS-mask icon, landing page = link cards to every custom section) and visually groups the custom
  block at the top of the sidebar: a `custom_menu_order`/`menu_order` filter clusters the slugs listed in
  `AdminMenuServiceProvider::GROUP` right below the Dashboard, styled as one brand-tinted card (left
  accent bar `#952d58` + gap) in `groupStyle`. **RULE — every future custom CPT or options page MUST
  join this group:** register it as its **own top-level** menu (CPT → ACF `admin_menu_parent` empty /
  `show_in_menu => true`; options page → no `parent_slug`, unless it belongs to a domain, then
  `parent_slug` = that domain's CPT menu, e.g. `edit.php?post_type=dbip-chapters`) **and add its menu
  slug to `AdminMenuServiceProvider::GROUP`** so the clustering + styling pick it up. WP admin has only
  **2 menu levels** — never nest a 3rd; domain sub-screens (taxonomy/reorder/settings) hang off the
  domain's own CPT menu.
- **`AcfServiceProvider`** — `acf/load_field/name=icon_name` → choices from `Icons::choices()`.
- **`DbipServiceProvider`** — registers the DBiP options page (`acf_add_options_page`, slug
  `dbip-settings`); `protected_title_format` strips WP's "Zabezpieczone:" prefix for `dbip-chapters`.
- **`HomeServiceProvider`** — `acf/location/match_rule` filter: makes ACF's `page_type == front_page`
  rule also match the Polylang translation of the front page (so `group_home` shows on the EN home).
- **`ThemeSettingsServiceProvider`** — registers the top-level **"Ustawienia motywu"** options page
  (`acf_add_options_page`, slug `theme-settings`, `post_id` `theme_settings`) and an
  `acf/validate_post_id` filter scoped strictly to that `post_id` that suffixes it with the current
  Polylang language (`theme_settings_{lang}`) — making the one options field group per-language without
  touching the DBiP options page or any post/term ids. Falls back to the default language (or no suffix
  if Polylang is inactive). Options-page + `group`/`repeater` fields are ACF **Pro**; on free ACF the
  page never registers and the Footer composer falls back to hardcoded.
- **`ContactServiceProvider`** — registers three public REST routes under `arpi/v1` (`/contact`,
  `/newsletter`, `/dbip-access`) backing the front-end forms. Guarded by the `wp_rest` nonce + a
  `website` honeypot (bots get a fake 200). Contact submissions email the office (`wp_mail`,
  best-effort) and push the lead into MailPoet; the footer newsletter subscribes to Newsletter PL/EN.
  **`/dbip-access`** is the DBiP "receive a password" form (replaces the legacy `[mailpoet_form id="7"]`):
  it adds the requester to the *Dostęp do DBiP* + *Pomocnicza: Dostęp do DBiP* lists and `wp_mail`s them
  the shared chapter post-password so they can unlock the (English-only) publication. Lists
  resolved/created by name via **`app/Support/Mailpoet.php`** (thin wrapper over MailPoet's `MP('v1')` API — no-ops
  gracefully when the plugin is inactive). Newsletter opt-ins use double opt-in (`send_confirmation_email`);
  plain enquiries land in the Kontakt list without an email. The office recipient is env-based via
  `wp_get_environment_type()` — `contact@arpiaccounting.com` on production, else `dev@example.test`
  (dev + staging test inbox). Mail transport: **FluentSMTP** carries `wp_mail`; MailPoet sends via its
  own SMTP config — both point at **Brevo** (From = ARPI Accounting `<contact@arpiaccounting.com>`).
- **`app/setup.php`** — ACF local-JSON load/save (`resources/acf-json`); `theme.json` served from
  `public/build/assets/theme.json`; editor.css/editor.js injection; `after_setup_theme` supports
  (title-tag, post-thumbnails, html5, …); registers `primary_navigation` menu + two sidebars.
- **`app/filters.php`** — `excerpt_more`; `nav_menu_link_attributes` adds `c-btn c-btn--ghost uppercase …`
  to primary-nav links (ghost = desktop red→red-dark hover) and, for MVP, rewrites any nav item whose
  URL contains `#footer` (the **Contact** item) to a pure `#footer` hash + `data-contact-footer="true"`
  so it same-page-scrolls to the footer contact block on every URL and triggers the `contact-highlight`
  nudge; **`post_type_link`** replaces `%chapter-name%` in `dbip-chapters` permalinks
  with the assigned term slug (Yoast primary → first → `uncategorized`); `wp_robots` no-index off-prod.
- **`app/Support/Icons.php`** — `Icons::choices()` globs `resources/icons/*.svg` → ACF select options.
- **Assets**: `@vite(['resources/css/app.css','resources/js/app.js'])` in the layout;
  **`Vite::asset('resources/images/…')`** for images in Blade; **`@svg('icon-<slug>')`** for icons
  (blade-icons set `default`, path `resources/icons`, prefix `icon`, class `size-5`).

---

## 11. JavaScript (`resources/js/`)

Vanilla JS, **no Stimulus/Alpine**. `app.js` imports & calls: `initReveal, initMenu, initHeader,
initClampFill`. `editor.js` is a separate block-editor entry.

| Module | Behavior | Data attrs |
|---|---|---|
| `reveal.js` | IntersectionObserver adds `.is-visible`; `[data-reveal-group]` staggers children (90ms); bails on reduced-motion; sets `window.__revealReady` so the head gate's failsafe keeps `.reveal-ready`. (The `.reveal-ready` class itself is set earlier by an inline head script — §12.) | `data-reveal`, `data-reveal-group`, `--reveal-delay`, `data-hero` |
| `menu.js` | mobile menu toggle: `data-open`, `aria-expanded`, body scroll-lock, Escape/X close | `data-menu-toggle/-overlay/-close`, `data-open` |
| `header.js` | sticky shrink: `data-scrolled="true"` past 100px scroll | `data-header`, `data-scrolled` |
| `clamp-fill.js` | snaps `[data-clamp-fill]` excerpts to whole lines (`-webkit-line-clamp`); off ≤767px | `data-clamp-fill` |
| `scope-fit.js` | usluga scope tiles: a `[data-scope-tile]` whose label wraps past 2 lines at its base column width gets widened just enough (≈ half its single-line width, capped at the container) to fall back to ~2 lines — so long labels take a wider column / their own row instead of stacking 3-4 lines. Recomputed on resize + `fonts.ready` | `data-scope-list`, `data-scope-tile` |
| `forms.js` | progressive enhancement for `[data-ajax-form]` (contact + footer newsletter): posts the form as JSON to `data-endpoint` with the `X-WP-Nonce` header, then shows `data-success`/`data-error` (or the server message) in `[data-form-status]` and resets on success | `data-ajax-form`, `data-endpoint`, `data-nonce`, `data-success`, `data-error`, `data-form-status` |
| `contact-highlight.js` | MVP has no contact page — the header **Contact** link scrolls to the footer (`anchor-scroll` handles the scroll); this nudges the footer phone/email (`[data-contact-highlight]`) ~550ms later by toggling `.is-nudging` (`contact-nudge` keyframes), clearing it on `animationend` so it replays | `data-contact-footer` (the link), `data-contact-highlight` (footer tel/mailto) |

---

## 12. Templates & partials (`resources/views/`)

- **`layouts/app.blade.php`** — shell: an **inline `<head>` reveal gate** (adds `.reveal-ready` before
  first paint, with a `load`+1200ms failsafe that unhides if `window.__revealReady` was never set),
  `wp_head`, `@vite`, skip-link, header, `<main>@yield('content')`, optional sidebar, footer, `wp_footer`.
- **`sections/`** — `header` (sticky `.c-header`, `top-admin-safe`, scroll-shrink, mobile red overlay
  nav + desktop bar via `wp_nav_menu(primary_navigation)`, lang-switcher, hamburger), `footer` (red,
  newsletter + company `<address>` + offices + Forbes badges + socials), `sidebar`.
- **Top-level templates** (all `@extends('layouts.app')`): `front-page`, `index` (**styled blog
  archive** — heading + category pills + `<x-post-card>` grid + `.c-pagination`; serves blog home &
  category/tag/author/date), `page`, `single` (`@includeFirst content-single-{type}` → `post` resolves
  to the styled `content-single-post`), `search`, `404`, `template-custom`, `single-usluga`,
  `archive-dbip-chapters`, `taxonomy-chapter-name`, `single-dbip-chapters`.
- **Contact page**: `template-contact.blade.php` ("Template Name: Contact") assigned to the PL
  **Kontakt** + EN **Contact** pages (seeded via `scripts/seed-contact-pages.php`, linked as Polylang
  translations). Composes `partials/contact/{hero,details,form}`; the form posts to `arpi/v1/contact`.
- **Careers page**: `template-careers.blade.php` ("Template Name: Careers") assigned to the PL
  **Kariera** + EN **Careers** pages (seeded via `scripts/seed-careers-pages.php`, Polylang-linked).
  Improvised (no design) from existing patterns; composes `partials/careers/{hero,intro,mosaic,benefits,positions,cta}`.
- **Partials**: `partials/home/{hero,about,memberships,why-arpi,services,blog,dbip}`,
  `partials/contact/{hero,details,form}`, `partials/careers/{hero,intro,mosaic,benefits,positions,cta}`,
  `partials/usluga/{hero,scope,body,reports,others}`,
  `partials/dbip/{archive-hero,ceo,about,chapter-hero,chapters}`, plus `content-single-post` (styled
  blog single: breadcrumb + category pill + `.c-prose` body + prev/next via `<x-dbip.post-nav>`),
  `page-header`, `content*` (stock, still used by `search`), `comments`, `entry-meta`,
  `partials/footer/{office,newsletter}` (footer section's own partials), `forms/search`.
- Section conventions: one partial = one section; `front-page`/`single-*` just compose includes.
  Full-bleed sections (memberships, blog) put the background full-width but content in `<x-container>`.

---

## 13. ACF (`resources/acf-json/`)

| File | Defines |
|---|---|
| `post_type_67af6de4b7d48.json` | CPT `dbip-chapters` (archive, permalink `dbip-chapters/%chapter-name%`) |
| `post_type_689e2a1b3c4d5.json` | CPT `usluga` (slug `uslugi`, no archive, page-attributes) |
| `taxonomy_67b5a2074399c.json` | taxonomy `chapter-name` (hierarchical, for `dbip-chapters`) |
| `group_usluga_tresc.json` | `usluga` fields: `hero` (group + icon-picker), `scope_intro`, `scope` (repeater), `body` (wysiwyg), `reports` (group), `tile_excerpt` |
| `group_dbip_chapter.json` | `chapter-name` term fields: `title_image` (image), `chapter_introduction` (wysiwyg) |
| `group_dbip_settings.json` | options page `dbip-settings`: `dbip_version`, `dbip_date` |
| `group_home.json` | front-page fields (`page_type == front_page`): `hero`, `about`, `memberships`, `why` (+ `hexes` repeater), `services` (+ `items` repeater w/ icon select & link), `dbip` (+ `paragraphs` repeater, `cta` link), `blog` text. Groups/repeaters need ACF **Pro**; free ACF ignores them → composer falls back to hardcoded. |
| `group_theme_settings.json` | global theme settings (`options_page == theme-settings`, per-language): `company` (group), `offices`/`socials`/`badges` (repeaters), `newsletter` (group). Feeds the Footer composer. |
| `group_careers.json` | Careers page (`page_template == template-careers.blade.php`): `hero`, `intro` (+paragraphs), `mosaic` (+tiles repeater w/ `kind` select & conditional sub-fields), `benefits` (+items w/ icon select), `positions` (+items repeater, `open` group), `cta`, `recruitment_email`. |
| `group_proces.json` | Proces page (`page_template == template-proces.blade.php`): `hero` (group), `stages` (repeater: `tone` select, icon, `hex_label`, `logo` group, `tags`/`paras` repeaters, `list_intro`, `list` repeater of lead+desc). |

Icon-picker pattern (usluga): sub-fields `icon_source` (library/custom), `icon_name` (select fed by
`Icons::choices()`), `icon_file` (SVG upload — needs the **Safe SVG** plugin). Composer normalises to
`{type:'svg',name}` or `{type:'file',url}` for `<x-dynamic-icon>`.

---

## 14. Coding conventions & preferences  ← read before writing code

These are firm, user-confirmed rules for this repo:

1. **Hooks go in classes** (a provider or dedicated class), **not** procedurally in
   `app/setup.php`/`filters.php`. Those files hold legacy procedural hooks; new logic is class-based.
2. **Flexbox by default; grid only for real N-column grids** (service tiles, "other services",
   subchapter cards). Two-element layouts (title + icon) use `flex` + `gap` + `justify-between`, not grid.
3. **Use theme tokens, never raw hex/px.** If you'd write `#7f1d46` / `text-4xl` / `#f6f6f6`, use the
   nearest token instead (`bg-red-dark`, `text-display`, `var(--color-cream)`, `color-mix(... var(--color-black) …)`).
4. **Reuse the button component** (`<x-button variant="…">`) instead of hand-rolling button visuals.
   If a case doesn't map cleanly, ask rather than force it.
5. **Prefer Gutenberg block options over CSS overrides.** Don't fight block styles. If a block option
   gives the effect (e.g. `align:wide`/`align:full` to escape `contentSize` on `is-layout-constrained`
   content), use it — edit the block content (even programmatically in a seed) rather than adding
   override CSS. (`wideSize` is set in `theme.json` for `align:wide`.)
6. **Round spacing, don't copy Figma px exactly.** Snap gaps/paddings to a small consistent scale
   (fluid tokens reused everywhere), one value per repeated element — the exact Figma px is designer
   noise, not intent.
7. **No hardcoded component heights.** Dimensions compose from font-size + line-height + padding.
8. **Single-source responsive.** Render once; switch mobile/desktop with utilities/breakpoints, not
   duplicated markup.
9. **Minimal code comments.** Only comment genuinely non-obvious solutions (a CSS trick, a workaround
   for a specific quirk). Don't restate what the code says.
10. **Anti-boilerplate typography.** Font-*size* is a token; line-height/tracking applied per
    element/component, never duplicated per style.
11. **Document as you build — keep this doc the ground truth.** Whenever you add a feature, an
    admin/front subpage, a provider, or a design principle, record it **in the same change** — in the
    relevant § here (and as a memory when it's a firm preference), so this file stays the single
    reference future AI prompts read first. Don't ship undocumented conventions; a rule that only lives
    in code will be missed next time.
12. **Element by semantics, not habit.** Block/visual container (icon badge, card box, wrapper) →
    `<div>`; inline text/decoration inside a phrasing context → `<span>`. Don't default to `<span>` just
    because it's phrasing-valid everywhere or because Tailwind sets `display` via a utility class.
13. **Naming language — internal English, public = audience language.** Two buckets, decided by
    visibility:
    - **Public / user- or client-visible** (URL slugs & permalinks, wp-admin `Template Name` +
      menu/section labels, CPT admin labels) → **the audience's language**: Polish for PL-default
      content (`uslugi`, page slug `sygnalista`, menu "Sygnalista", "Usługi"), English for EN-only
      content (DBiP). These are SEO/UX surface — do **not** anglicise them.
    - **Internal code identifiers** (PHP classes — providers, composers, helpers; Blade partial
      **directory** names; JS modules; CSS classes) → **always English** (`WhistleblowerServiceProvider`,
      `Whistleblower` composer, `partials/whistleblower/`). Providers are the reference — they were
      English from day one.
    - **Grey zone — leave as-is, don't churn:** a CPT machine name (`usluga`, `zgloszenie`) is baked
      into the DB `post_type` column + ACF JSON key; a `template-*.blade.php` filename is stored in
      `_wp_page_template` postmeta. Renaming either silently breaks live content, so keep the existing
      (Polish) name even though it's "code" — the coupling outweighs the cosmetics. The `Usluga`/`Proces`
      composers keep their Polish stem to mirror their Polish-slug CPT/page; that's coherent, not a bug.
    - **One feature, one code stem.** The whistleblower feature must not spread across three words
      (was: `Whistleblower` provider + `Sygnalista` composer/partials + `zgloszenie` CPT). Code layer is
      now uniformly `whistleblower`; only the DB-coupled CPT (`zgloszenie`) and public/label surface
      (`sygnalista`) stay Polish, by the rule above.

---

## 15. Gotchas

- **Stale Vite `public/hot`**: if `public/hot` exists (left by a past `make dev`), `@vite` loads assets
  from a dead dev-server URL and the built CSS/images silently don't apply. After `yarn build`, if
  serving the built site, move/remove `public/hot`.
- **New Tailwind classes need `yarn build`**, not just `acorn view:clear`. `view:clear` only clears
  compiled Blade; utilities/`theme.json` changes require a rebuild.
- **`theme.json` is a build artifact** (`public/build/assets/theme.json`) — edit the source
  `theme.json` then `yarn build` for layout (contentSize/wideSize) changes to take effect.
- **Never commit secrets.** Real credentials (e.g. content passwords) go through env/`.env`, never
  inlined in scripts (`getenv('…')`).
- **Polylang absent on CLI/some staging** → always `function_exists()`-guard `pll_*` calls.

---

## 16. Key file map

```
public_html/wp-content/themes/arpi/
├── functions.php                 # provider registration + Sage boot
├── theme.json                    # Gutenberg layout (contentSize 48rem, wideSize 70rem)
├── vite.config.js                # entries: app.css, app.js, editor.css, editor.js
├── config/blade-icons.php        # icon set (path resources/icons, prefix "icon")
├── app/
│   ├── setup.php                 # theme supports, ACF local-json, menus, sidebars
│   ├── filters.php               # excerpt, nav classes, dbip permalink, robots
│   ├── Support/Icons.php         # icon choices for ACF
│   ├── Providers/{Theme,Acf,Dbip,Home,Contact,ThemeSettings}ServiceProvider.php
│   └── View/Composers/{App,Home,Post,Blog,Footer,Comments,Contact,Careers,Proces,Usluga,Dbip}.php
└── resources/
    ├── css/{app,theme,editor}.css + base/{fonts,typography}.css
    │   + components/{button,input,wrap,hexagon,dbip,prose,pagination}.css
    │   + utilities/{admin-bar,reveal}.css
    ├── js/{app,editor}.js + modules/{reveal,menu,header,clamp-fill}.js
    ├── fonts/  # Geomanist: regular-webfont.{woff2,woff} (400) + book/bold/black.woff (350/700/900);
    │           # NO light file → 300 @font-face errors, body falls back to system sans (see §6 gotcha)
    ├── icons/*.svg               # 43 icons, kebab-case → @svg('icon-<slug>')
    ├── images/                   # Vite::asset(...) — logo, forbes, home, dbip/*
    ├── acf-json/*.json           # CPTs, taxonomy, field groups
    └── views/                    # layouts, sections, components, partials, templates
scripts/                          # WP-CLI seed/import scripts (make wp ARGS="eval-file …")
  seed-contact-pages.php          #   creates PL Kontakt + EN Contact pages (template-contact)
  seed-careers-pages.php          #   creates PL Kariera + EN Careers pages (template-careers)
  extract-mailpoet-stage.py       #   stage MailPoet/CF7 tables from the prod dump → /tmp/mp-stage.sql
  migrate-mailpoet-leads.sql      #   import legacy subscribers/lists/CF7 leads into MailPoet (idempotent)
  import-mailpoet-settings.php    #   import portable MailPoet settings from prod (skips secrets/version/prod page IDs)
docs/design-tokens.md             # raw Figma token extraction
docs/superpowers/{specs,plans,runbooks}/   # per-phase design + implementation docs
```

---

*Last synced with the code: 2026-08-03. If you touch tokens, components, composers, providers, JS
modules, or ACF, update the relevant section here so this stays the reliable single source.*

> **Forms & MailPoet (added 2026-07-23).** The **MailPoet** plugin is now a dependency (installed in
> dev; the client manages it in wp-admin like ACF/Polylang). Both the contact form and the footer
> newsletter POST to `ContactServiceProvider`'s REST routes and push leads into MailPoet lists
> (Kontakt PL/EN, Newsletter PL/EN). Legacy subscribers (~1.3k) + their lists and 122 Contact-Form-7
> leads were migrated from `reference/prod-db.sql` via the two `scripts/` migration files (staging
> tables `zimp_*` → live `wp_mailpoet_*`, matched by email/list-name, statuses preserved). MailPoet's
> API ignores an explicit subscriber `status`; it's governed by the signup-confirmation setting.
>
> **Settings & forms migration (added 2026-08-07).** Portable MailPoet **settings** were then imported
> from the same dump via `scripts/import-mailpoet-settings.php` (`sender`, `reply_to`, `bounce`,
> `tracking`, `subscribe`, `signup_confirmation`) — deliberately **skipping** install-specific / secret
> settings: the `mta` MailPoet-Sending-Service **API key**, prod `subscription` **page IDs**, and the
> older `db_version`/`version` (dev's MailPoet is newer). Sending (Brevo) stays a wp-admin task for the
> client. The legacy MailPoet **forms** were re-implemented as the theme's own custom forms rather than
> imported as (orphaned) `wp_mailpoet_forms` records: the **newsletter** lives in the footer
> (`/newsletter`), and the **DBiP "receive a password"** form (legacy `[mailpoet_form id="7"]`) is the
> new `/dbip-access` endpoint + the locked-chapter form in `single-dbip-chapters`.
