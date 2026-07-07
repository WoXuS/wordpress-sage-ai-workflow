# ARPI — Design System Foundation (spec)

**Date:** 2026-07-07
**Status:** approved design, ready for implementation plan
**Depends on:** `docs/design-tokens.md` (extracted Figma tokens)

## Goal

Wire the extracted Figma design tokens into the Sage theme as a **professional, scalable
CSS/JS foundation**: design tokens, fluid typography, reusable components (button, container),
an icon system, and a folder/naming architecture that stays clean as the project grows.

This is the styling/scripting foundation only. It does **not** build page sections, ACF, or
migrate content.

## Stack context (already in place)

- **Sage 11** theme at `public_html/wp-content/themes/arpi`, **Acorn v6** (Laravel/Blade components).
- **Tailwind CSS v4** via `@tailwindcss/vite` — CSS-first config (`@theme`, `@layer`, `@utility`),
  **no `tailwind.config.js`**.
- **Vite 8** with `@roots/vite-plugin`. `wordpressThemeJson` generates `theme.json` from the
  Tailwind theme, so tokens defined here also reach the block editor.
- Entry points (from `vite.config.js`): `resources/css/app.css`, `resources/js/app.js`,
  `resources/css/editor.css`, `resources/js/editor.js`.

## Key principles (agreed)

1. **No hardcoded width/height on components** — dimensions compose from font-size, line-height,
   and padding.
2. **Anti-boilerplate typography** — font *size* is a shared token; *line-height* and *tracking*
   are applied contextually (element defaults + component overrides), never duplicated per style.
3. **Multi-file CSS** — `app.css` is a thin index; real styles live in small, single-purpose
   partials pulled in via CSS `@import`. Cascade layers merge across files.
4. **Stay with CSS, not Sass** — Tailwind v4 runs on native CSS (custom properties, nesting,
   `@layer`, `color-mix()`); Sass would only add a redundant compile step and fight the pipeline.
5. **ITCSS-lite naming** — prefixes distinguish our classes from Tailwind utilities:
   `c-` component, `o-` layout object, `u-` custom utility. BEM **block + `--modifier`** only
   (no `__element`) — Blade owns component markup, so internal elements aren't a public API.
6. **Units policy** — **`rem` for font-size only** (the one place it earns its keep: respects the
   user's browser default-font-size setting for a11y; px shown in comments). **`px` for
   everything else** — padding, spacing, borders, radii, layout tokens — for unambiguous
   readability. (Tailwind's own utility spacing scale is rem-based; that's fine, it only affects
   utilities, not our authored component CSS.)

---

## 1. Folder structure

```
resources/
├── css/
│   ├── app.css              # entry: @import tailwindcss + partials, @source globs
│   ├── theme.css            # @theme { colors, fluid type, layout tokens }
│   ├── base/
│   │   ├── fonts.css        # @font-face Geomanist (Regular 400)
│   │   └── typography.css   # @layer base: h1–h3, body, a — semantic element defaults
│   └── components/
│       ├── button.css       # @layer components: .c-btn + modifiers
│       └── wrap.css         # @layer components: .o-wrap + modifiers
├── js/
│   ├── app.js               # entry — feature modules imported here as added
│   └── modules/.gitkeep     # feature modules (created when the first one exists)
├── fonts/                   # geomanist-regular-webfont.woff2 + .woff
├── icons/                   # *.svg (currentColor, viewBox 24) — Blade Icons source
└── views/
    └── components/
        ├── button.blade.php     # <x-button variant href>
        └── container.blade.php  # <x-container as variant>
config/
└── blade-icons.php          # custom icon set registration (new; no config/ dir today)
```

`editor.css` imports `theme.css` + `base/fonts.css` + `base/typography.css` so the block editor
matches the front end.

---

## 2. Design tokens — `resources/css/theme.css`

Clear Tailwind's default color palette (brand-locked site → short, unambiguous names), then define
only ARPI tokens plus the CSS keyword colors we still need.

```css
@theme static {
  /* `static` = emit every token as a real :root custom property even when no
     scanned template references it yet. Without it, Tailwind v4 tree-shakes
     unused theme vars, so our hand-authored var(--color-red)/color-mix() in
     component CSS (and the generated theme.json) would be missing the tokens. */
  /* wipe Tailwind's default palette so bg-red etc. mean *our* red */
  --color-*: initial;
  --color-transparent: transparent;
  --color-current: currentColor;
  --color-inherit: inherit;

  --color-white: #ffffff;
  --color-black: #19191c;   /* soft near-black, not #000 */
  --color-red:   #942d58;   /* primary brand; transparent variant via Tailwind: bg-red/30 */
  --color-cream: #f5f4ee;

  --font-sans: "Geomanist", ui-sans-serif, system-ui, sans-serif;

  /* fluid type — endpoints 375→1280px, 1:1 with Figma mobile/desktop pairs */
  --text-display: clamp(2.25rem, 1.5rem  + 3.2vw,  4.0625rem); /* H1 36→65 */
  --text-h2:      clamp(1.5rem,  1.19rem + 1.33vw, 2.25rem);   /* H2 24→36 */
  --text-h3:      clamp(1rem,    0.79rem + 0.88vw, 1.5rem);    /* H3 16→24 */
  --text-body-lg: clamp(1.25rem, 1.15rem + 0.44vw, 1.5rem);   /* 20→24 */
  --text-body:    clamp(1rem,    0.9rem  + 0.44vw, 1.25rem);   /* 16→20 */
  --text-body-sm: 1rem;                                        /* 16 fixed (a11y floor) */

  /* layout: container horizontal padding, fluid 375→1280 (px — spacing, not type) */
  --wrap-padding-inline: clamp(30px, 9.28px + 5.52vw, 80px);
}
```

Notes:
- `--text-*` tokens set font-*size* only (no companion `--text-*--line-height`), so `text-*`
  utilities and element defaults control line-height independently. This is the anti-boilerplate
  mechanism: one size token, line-height applied where it belongs.
- No separate transparent-red token — use Tailwind's `/30` opacity modifier (`bg-red/30`,
  `text-red/30`) in utilities and `color-mix()` in component CSS.
- `--text-body-sm` stays fixed at 1rem; `--text-body` floor is 1rem (16px) on mobile — revisit
  bumping to 18px once seen next to body-sm (per design discussion).
- Keep the `wordpressThemeJson` `disable*` flags `false` so colors/fonts/sizes flow to `theme.json`.

---

## 3. Typography — `resources/css/base/typography.css`

Style semantic elements directly so plain HTML/prose needs **no utility classes**. Geomanist ships
Regular only → all weights are 400. Line-heights and tracking are the Figma exact values.

```css
@layer base {
  body {
    font-family: var(--font-sans);
    font-size: var(--text-body);      /* 16→20 fluid */
    line-height: 1.4;
    color: var(--color-black);
    background-color: var(--color-white);
    -webkit-font-smoothing: antialiased;
  }
  h1, h2, h3 { font-weight: 400; text-wrap: balance; }
  h1 { font-size: var(--text-display); line-height: 0.98; letter-spacing: -0.25px; } /* 64/65 */
  h2 { font-size: var(--text-h2); line-height: 1.22; }  /* 44/36 */
  h3 { font-size: var(--text-h3); line-height: 1.25; }  /* 30/24 */
  a  { color: inherit; text-decoration: none; }
}
```

Ad-hoc sizes remain available as Tailwind utilities generated from the tokens
(`text-display`, `text-h2`, `text-body-lg`, …) for cases that aren't semantic elements.

---

## 4. Fonts — `resources/css/base/fonts.css`

Copy `geomanist-regular-webfont.woff2` and `.woff` from
`reference/legacy-theme/assets/font/` into `resources/fonts/` (Vite hashes them into
`public/build`). Only Regular/400 is used by the design system.

```css
@font-face {
  font-family: "Geomanist";
  src: url("@fonts/geomanist-regular-webfont.woff2") format("woff2"),
       url("@fonts/geomanist-regular-webfont.woff")  format("woff");
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
```

(Use the Vite `@fonts` alias, or a relative `../../fonts/...` path — whichever the Vite asset
pipeline resolves cleanly; confirm during implementation.)

---

## 5. Component: Button — `resources/css/components/button.css` + `views/components/button.blade.php`

**No fixed height/width** — size composes from padding + line-height. Figma pill: 16px text,
line-height 1, `padding-block: 16px` → 48px tall naturally. Horizontal padding uses the pill's own
`px-12` (12px); the nested text-container's `px-16` in Figma is not reproduced.

```css
@layer components {
  .c-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;                            /* label ↔ icon (only visible when an icon is present) */
    padding-block: 16px;                 /* Figma py-16 → composes the 48px height (16 text + 2×16) */
    padding-inline: 12px;                /* Figma px-12 (pill's own padding) */
    border: 2px solid transparent;
    border-radius: 9999px;               /* pill */
    font-size: var(--text-body-sm);      /* 16px, fixed */
    line-height: 1;
    letter-spacing: 0.5px;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color .2s, border-color .2s, color .2s, opacity .2s;
  }

  /* solid (Figma "dark") */
  .c-btn--solid {
    background-color: var(--color-red);
    border-color: var(--color-white);
    color: var(--color-white);
  }
  .c-btn--solid:hover {
    background-color: color-mix(in oklab, var(--color-red) 30%, transparent);
    border-color: var(--color-red);
    opacity: 0.91;
  }

  /* outline (Figma "light") */
  .c-btn--outline {
    background-color: var(--color-white);
    border-color: var(--color-red);
    color: var(--color-red);
  }
  .c-btn--outline:hover {
    background-color: var(--color-red);
    color: var(--color-white);
    opacity: 0.91;
  }

  /* ghost / text link — no box, larger label. Icon is OPTIONAL: passed via the Blade slot,
     never baked into the CSS; gap only takes effect when a second (icon) child exists. */
  .c-btn--ghost {
    padding: 0;
    gap: 16px;
    border-color: transparent;
    background-color: transparent;
    color: var(--color-red);
    font-size: 1.25rem;                  /* 20px, Button Big (type stays rem) */
  }
  .c-btn--ghost:hover {
    color: color-mix(in oklab, var(--color-red) 30%, transparent);
  }
}
```

Blade — renders `<a>` when `href` is set, else `<button>`; merges extra classes/attributes:

```blade
@props(['variant' => 'solid', 'href' => null, 'type' => 'button'])
@php $tag = $href ? 'a' : 'button'; @endphp
<{{ $tag }}
  @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
  {{ $attributes->merge(['class' => "c-btn c-btn--{$variant}"]) }}>
  {{ $slot }}
</{{ $tag }}>
```

Usage:

```blade
<x-button variant="solid" href="/kontakt">Wyślij</x-button>
<x-button variant="ghost">More <x-icon-arrow-right class="size-6" /></x-button>
```

> **Provisional — solid-button hover legibility (deferred 2026-07-07):** the `--solid` hover
> (`color-mix(red 30%, transparent)` bg + white text) is 1:1 with Figma's "DARK" frame, but on a
> white background that's ~1.4:1 text contrast (illegible). Kept Figma-faithful for now; **decide
> legibility once the real page layouts show what background the solid button actually sits on**
> (dark/colored section → fine as-is; on white → change hover to keep white text readable, e.g.
> an opaque/darker red bg).

---

## 6. Component: Container — `resources/css/components/wrap.css` + `views/components/container.blade.php`

Sets **only** max-width, horizontal centering, and horizontal padding. **No vertical padding** —
top/bottom spacing is per-section, not the wrapper's job.

```css
@layer components {
  .o-wrap {
    width: 100%;
    max-width: 1280px;
    margin-inline: auto;
    padding-inline: var(--wrap-padding-inline);    /* 30→80px, from theme.css */
  }
  .o-wrap--header {
    --wrap-padding-inline: clamp(16px, -10.5px + 7.07vw, 80px);  /* 16→80px */
  }
}
```

```blade
@props(['as' => 'div', 'variant' => null])
<{{ $as }} {{ $attributes->merge(['class' => 'o-wrap' . ($variant === 'header' ? ' o-wrap--header' : '')]) }}>
  {{ $slot }}
</{{ $as }}>
```

Usage: `<x-container>…</x-container>`, `<x-container variant="header" as="header">…</x-container>`.

> Exact padding values (30 / 80 / 16 px) are provisional from the discussion; confirm/adjust
> against the real page frames in Figma when they arrive. They live in tokens for one-line tuning.

---

## 7. Icon system — `blade-ui-kit/blade-icons` custom set

The ~40 project icons are monochrome line icons (mixed Tabler/Ionicons + custom-drawn), treated as
one curated set. Inline SVG via Blade Icons → colorable with `currentColor` (`text-red`), sized with
Tailwind (`size-*`), no extra requests.

**Scope of this spec: the icon *system* only** — dependency, config, folder, convention, and the
single `arrow-right` icon needed by the ghost button. Bulk export of the full library is a separate
task the user will do.

- `composer require blade-ui-kit/blade-icons` in the theme.
- Register a custom set in **`config/blade-icons.php`** (new `config/` dir): path
  `resources/icons`, prefix `icon`, default class `size-6` (24px). Verify Acorn discovers the
  config during implementation.
- Add `resources/icons/arrow-right.svg` — optimized (SVGO), `viewBox="0 0 24 24"`, strokes/fills set
  to `currentColor`, no hardcoded colors. May be taken from Tabler's source or exported from Figma.
- Result: `<x-icon-arrow-right class="size-6 text-red" />` (and `@svg('arrow-right', '…')`).

---

## 8. CSS entry — `resources/css/app.css`

```css
@import "tailwindcss";
@import "./theme.css";
@import "./base/fonts.css";
@import "./base/typography.css";
@import "./components/button.css";
@import "./components/wrap.css";

@source "../../app/**/*.php";
@source "../../config/**/*.php";
@source "../**/*.blade.php";
@source "../**/*.js";
```

Replace the scaffold's `@import "tailwindcss" theme(static);` with a plain `@import "tailwindcss";`
— the emit-everything behaviour we need is now expressed on the `@theme static` block in `theme.css`
(§2), which guarantees every token lands in `:root` for our `var(--color-red)` / `color-mix()`
references and the generated `theme.json`.

`editor.css`:

```css
@import "tailwindcss";
@import "./theme.css";
@import "./base/fonts.css";
@import "./base/typography.css";
```

---

## 9. JS entry — `resources/js/app.js`

Keep lean — no features yet. Establish the module pattern: `app.js` is the boot entry that imports
feature modules from `modules/` as they appear. Create `resources/js/modules/.gitkeep`.

---

## Out of scope (follow-up work)

- Bulk export/optimization of the full ~40-icon library (**user will do this**).
- Page sections, header/footer markup, ACF fields, view composers.
- Any JS feature modules (nav, carousels, maps) — only the entry scaffold here.
- Real page container widths / gutters if Figma page frames contradict the provisional values.

## Success criteria

- `yarn build` succeeds; `yarn dev` HMR works.
- Cleared palette in effect: `bg-red` = `#942d58`, no Tailwind default color scales leak.
- A test page renders: an `<h1>`/`<h2>`/`<p>` scale fluidly 375↔1280px with Geomanist Regular; all
  three `<x-button>` variants match Figma (incl. hover); ghost button shows the `arrow-right` icon
  colored via `currentColor`; `<x-container>` centers at max 1280px with the fluid gutter and no
  vertical padding.
- `theme.json` (generated) contains the ARPI colors and font sizes (block editor parity).
```
