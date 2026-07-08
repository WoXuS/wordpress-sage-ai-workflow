# Spec: Header + Footer

**Date:** 2026-07-08
**Branch:** `feat/header-footer` (off `main`, after PR #1 / design-system foundation merged)
**Depends on:** design-system foundation (tokens, `c-btn`, `o-wrap`/`x-container`, Blade Icons, fluid type)
**Figma (copy "test"):** header desktop `2:2397`, header mobile `395:2851`, mobile menu `499:1549`, footer desktop `645:1741`, footer mobile `479:2276`

Build the site-wide header and footer from the Figma frames, plus the small reusable primitives they need. This is a layout/composition phase — not i18n, not newsletter backend, not ACF.

## 1. Design summary (from Figma)

**Header (desktop, 132px):** white, subtle bottom shadow + rounded bottom corners. Logo left → primary nav (red, uppercase, letter-spaced: *Dlaczego ARPI? · Usługi · Blog · Kariera · Proces · Kontakt*) → language switcher **PL/EN** (PL active red, EN grey). No buttons in this variant.

**Header (mobile, 82px):** white, logo left + hamburger (red) right. Tap → full-screen red overlay: white logo + X (top), nav centered vertically, white uppercase links, same menu items.

**Footer (red `#942d58`):**
- Left: white logo → "Newsletter" heading → email input (pill, white outline, white placeholder) → helper copy → **white "Subskrybuj" button** → social icons (fb, in — circular, white outline).
- Middle: company legal — *ARPI & Partners Sp. z o.o. / KRS: 0000255170 / NIP: 5213382100 / Puławska 182 / 02-670 Warszawa*.
- Right: two office blocks (Rzeszów, Warszawa) — address, "Pokaż w Google Maps", phone, email.
- Below middle: two Forbes badges (Diamenty Forbes 2026, Forbes Laureat 2026).
- Mobile: same content, single column, sections separated by hairline dividers.

Content values (offices, phones, emails, copy, KRS/NIP) are read straight from the Figma frames and captured in the Footer composer (below).

## 2. Architecture

### Sections (replace existing stubs)
- `resources/views/sections/header.blade.php` — sticky header: logo, desktop nav, `<x-lang-switcher>`, hamburger trigger, and the mobile overlay menu markup. Desktop/mobile handled by Tailwind responsive utilities + `data-[open]` state; single file.
- `resources/views/sections/footer.blade.php` — red footer composing the newsletter partial, company legal, office partials (loop), Forbes badges.

### New reusable primitives (design-system `c-` components)
- `<x-input>` → **`c-input`** (new `resources/css/components/input.css`). Pill text/email input. Base + `c-input--on-red` modifier (transparent bg, white border/text/placeholder). Props: `type` (default `text`), `name`, `placeholder`, `variant` (`null` | `on-red`), plus `$attributes` merge. Reusable — forms recur (contact page later).
- **`c-btn--white`** → added to existing `resources/css/components/button.css`. White bg, red text, pill (same base as `c-btn`). Exposed via existing `<x-button variant="white">`.

### Blade components (utilities-only, no dedicated CSS)
- `<x-lang-switcher>` — Polylang-aware with static fallback. If `function_exists('pll_the_languages')`, render Polylang's list; else static `PL` (active) / `EN`. `aria-current="true"` on active. Markup styled with Tailwind utilities.
- `<x-social-links>` — circular outlined icon links (blade-icons `<x-icon-facebook>`, `<x-icon-linkedin>`); URLs from Footer composer data. Utilities inline (the component is the reuse boundary — no `c-social` class).

### Partials (utilities-only)
- `resources/views/partials/newsletter.blade.php` — "Newsletter" heading + `<x-input variant="on-red">` + helper copy + `<x-button variant="white">` inside a `<form>` (markup only). Used by the footer.
- `resources/views/partials/footer-office.blade.php` — one office block (name, address, "Pokaż w Google Maps" link, phone, email). Rendered in a loop over composer data.

### Data — view composer
- `app/View/Composers/Footer.php` (Sage/Acorn composer, matches existing `App.php` pattern) bound to `sections.footer` + footer partials. Returns **hardcoded data, ACF-ready** (swap each to `get_field(..., 'option')` in the i18n/ACF phase — one line each):
  - `company`: name, KRS, NIP, address lines.
  - `offices`: array of `{ name, address_lines[], maps_url, phone, email }` (Rzeszów, Warszawa).
  - `newsletter`: heading, helper copy, submit label, form action placeholder.
  - `socials`: array of `{ network, url, icon }`.
  - `badges`: Forbes image paths (`forbes-diament.png`, `forbes-laureat.png`).
- Header needs no composer (nav = WP menu; switcher self-contained).

## 3. CSS approach

**Dedicated `c-` CSS only for repeating design-system primitives:** `c-btn--white` (button.css), `c-input` (new input.css). ITCSS-lite naming, BEM block + `--modifier`, native CSS nesting for `&:hover`/`&:focus` (Sass-style `&--x` concatenation does NOT work — flat modifier classes). Both `@import`ed into `app.css`.

**Everything else = Tailwind utilities inline in Blade** (header, nav, overlay, footer grid, office, social wrapper, switcher). Brand-color utilities from tokens (`bg-red`, `text-white`, `text-red`, `bg-cream`, `bg-red/30`), fluid type utilities (`text-h2`, `text-body`, …), `o-wrap`/`<x-container>` for gutters. Mobile-menu open state via a `data-[open]` variant toggled by JS. No `header.css`/`footer.css`/`nav.css`/`social.css`.

## 4. JavaScript
- `resources/js/modules/menu.js` — mobile menu toggle: open/close, set `aria-expanded` on the trigger, `aria-controls` → overlay id, lock body scroll while open, close on X click and on `Escape`. Imported from `resources/js/app.js`.
- **Sticky header = pure CSS** (`position: sticky; top: 0; z-index`). No JS; the Figma shadow stays constant (no scroll-shrink state in the design).

## 5. Responsive / interaction
- **Header:** inline nav down to **1024px** (Tailwind `lg`); below → hamburger + full-screen red overlay rendering the same WP menu items. (Breakpoint provisional — 6 items + logo + switcher need the room.)
- **Footer:** desktop multi-column grid (newsletter | company legal + badges | Rzeszów | Warszawa) collapsing to a single column below **768px** (`md`) with hairline dividers between sections, matching the mobile frame.
- **Spacing:** match Figma with Tailwind scale for internal spacing; arbitrary px values (`px-[80px]`) or the `o-wrap` gutter where exact Figma parity matters (px units policy from the design-system spec).

## 6. Data flow
- **Nav:** `wp_nav_menu(['theme_location' => 'primary_navigation', …])`. `primary_navigation` is already registered (`app/setup.php`). A dev menu (Dlaczego ARPI?, Usługi, Blog, Kariera, Proces, Kontakt) is created via wp-cli and assigned to the location so the header shows real items (links are `#` until subpages exist). Same menu renders in both the desktop bar and the mobile overlay.
- **Footer:** Footer composer → hardcoded data → footer view + office partial loop.
- **Newsletter:** `<form>` with HTML5 validation (`type="email" required`); no backend. `action` is a placeholder + `TODO: wire to MailPoet` (migration phase).
- **Switcher:** component-internal; renders the static fallback now (Polylang not installed).

## 7. Accessibility
- Mobile toggle: `aria-expanded`, `aria-controls`, `aria-label`; overlay dismissible via Escape and the X. Focus moves into the overlay on open and returns to the trigger on close (basic; full focus-trap optional).
- `<nav aria-label>` on both nav instances. Language switcher active item gets `aria-current`. Decorative icons `aria-hidden`; social links have accessible names (`aria-label="Facebook"` etc.). Skip-link already in the layout.

## 8. Scope

**IN:** header (desktop + mobile + overlay + sticky), footer (all blocks), `<x-input>`/`c-input`, `c-btn--white`, `<x-lang-switcher>`, `<x-social-links>`, newsletter + office partials, `Footer` composer (hardcoded data), mobile-menu JS, the two CSS additions, dev WP menu.

**OUT (separate phases):** Polylang install/config + EN content + per-language menus; MailPoet newsletter wiring; ACF options (swap composer to `get_field`); homepage/subpage sections; logo re-export.

## 9. Provisional / follow-ups
- **`c-btn--white` hover** — not in Figma. Provisional: subtle (slight opacity or cream tint); revisit with page layouts, like the deferred solid-button hover.
- **Logo assets** — `logo.svg` (64KB) / `logo--white.svg` (58KB) are Figma exports with an **embedded raster** (won't scale crisply, heavy). Used as-is now; flagged for re-export to true vector as a follow-up.
- **Nav labels** — Figma's mobile frame shows placeholder "CAREER"/"INFLOW"; real labels come from the WP menu, so ignored.
- **Header breakpoint** (1024px) and **footer breakpoint** (768px) — tune against real content while building.
