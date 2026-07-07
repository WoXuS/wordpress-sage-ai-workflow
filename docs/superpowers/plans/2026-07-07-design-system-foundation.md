# Design System Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire the extracted ARPI Figma tokens into the Sage theme as a scalable CSS/JS foundation — design tokens, fluid typography, `c-btn`/`o-wrap` components, and an icon system.

**Architecture:** Tailwind v4 CSS-first (`@theme` in `theme.css`), `app.css` a thin index importing single-purpose partials across cascade layers (`@layer base`/`components`). Reusable components are BEM-lite CSS classes wrapped by thin Blade components. Icons are inline SVG via Blade Icons with a custom local set.

**Tech Stack:** Sage 11, Acorn 6 (Blade), Tailwind CSS v4 (`@tailwindcss/vite`), Vite 8, `@roots/vite-plugin` (generates `theme.json`), `blade-ui-kit/blade-icons`. Docker stack (`php`/`nginx`/`db`), site at `http://localhost:8080`, Node/Vite on host.

## Global Constraints

- **Tailwind v4 CSS-first only** — no `tailwind.config.js`; config lives in `@theme`/`@layer`/`@utility`.
- **Units:** `rem` for `font-size` **only** (px in a comment); `px` for all spacing/padding/border/radius/layout tokens.
- **Naming:** ITCSS-lite prefixes `c-` (component), `o-` (layout object), `u-` (utility); BEM **block + `--modifier`** only, no `__element`.
- **Palette cleared:** `--color-*: initial`, then brand-only `--color-red #942d58`, `--color-black #19191c`, `--color-white #ffffff`, `--color-cream #f5f4ee`; transparency via Tailwind `/30`.
- **Fluid type:** `clamp()` endpoints at viewport **375px → 1280px**, 1:1 with Figma mobile/desktop pairs.
- **Font:** Geomanist **Regular / 400 only** (`geomanist-regular-webfont.woff2` + `.woff`).
- **No fixed width/height on components** — dimensions compose from padding + line-height.
- **`.o-wrap` has no vertical padding** — top/bottom spacing is per-section.
- **Theme dir:** `public_html/wp-content/themes/arpi` (abbreviated `THEME/` below).
- **Commits:** no Claude co-author trailer (private repo, personal identity).
- **Env gotchas (from prior sessions):** run `yarn` on the **host** in `THEME/` (or via `make build-assets`/`make dev`); run `wp`/`composer` in the **`php` container**; after container writes into `wp-content`, files become root-owned — re-`chown` theme files to host uid 1000 if git/yarn then choke.

---

## File Structure

```
THEME/resources/css/
  app.css                     # MODIFY — entry index
  editor.css                  # MODIFY — block-editor parity
  theme.css                   # CREATE — @theme tokens
  base/fonts.css              # CREATE — @font-face Geomanist
  base/typography.css         # CREATE — @layer base element defaults
  components/button.css       # CREATE — .c-btn
  components/wrap.css         # CREATE — .o-wrap
THEME/resources/js/
  app.js                      # MODIFY — entry
  modules/.gitkeep            # CREATE — module dir marker
THEME/resources/fonts/
  geomanist-regular-webfont.woff2/.woff   # CREATE (copied)
THEME/resources/icons/
  arrow-right.svg             # CREATE — sample icon
THEME/resources/views/
  components/button.blade.php     # CREATE — <x-button>
  components/container.blade.php  # CREATE — <x-container>
  template-sandbox.blade.php      # CREATE — verification page (removed in Task 7)
THEME/config/blade-icons.php   # CREATE — custom icon set
THEME/composer.json            # MODIFY — via `composer require`
```

---

### Task 1: Design tokens + CSS/JS entry scaffold

**Files:**
- Create: `THEME/resources/css/theme.css`
- Modify: `THEME/resources/css/app.css`, `THEME/resources/css/editor.css`
- Modify: `THEME/resources/js/app.js`; Create: `THEME/resources/js/modules/.gitkeep`

**Interfaces:**
- Produces: CSS custom properties `--color-{red,black,white,cream}`, `--font-sans`, `--text-{display,h2,h3,body-lg,body,body-sm}`, `--wrap-padding-inline`; consumed by every later task.

- [ ] **Step 1: Create `theme.css` with all tokens**

```css
@theme static {
  /* `static` emits every token to :root even if no scanned template references it yet —
     required so our var()/color-mix() component CSS and the generated theme.json see the
     tokens (Tailwind v4 tree-shakes unreferenced @theme vars without it). */
  /* wipe Tailwind's default palette so bg-red etc. mean *our* red */
  --color-*: initial;
  --color-transparent: transparent;
  --color-current: currentColor;
  --color-inherit: inherit;

  --color-white: #ffffff;
  --color-black: #19191c;   /* soft near-black, not #000 */
  --color-red:   #942d58;   /* transparency via Tailwind: bg-red/30 */
  --color-cream: #f5f4ee;

  --font-sans: "Geomanist", ui-sans-serif, system-ui, sans-serif;

  /* fluid type — endpoints 375→1280px, 1:1 with Figma; rem for font-size (a11y) */
  --text-display: clamp(2.25rem, 1.5rem  + 3.2vw,  4.0625rem); /* H1 36→65 */
  --text-h2:      clamp(1.5rem,  1.19rem + 1.33vw, 2.25rem);   /* H2 24→36 */
  --text-h3:      clamp(1rem,    0.79rem + 0.88vw, 1.5rem);    /* H3 16→24 */
  --text-body-lg: clamp(1.25rem, 1.15rem + 0.44vw, 1.5rem);   /* 20→24 */
  --text-body:    clamp(1rem,    0.9rem  + 0.44vw, 1.25rem);   /* 16→20 */
  --text-body-sm: 1rem;                                        /* 16 fixed */

  /* layout: container horizontal padding, fluid 375→1280 (px — spacing, not type) */
  --wrap-padding-inline: clamp(30px, 9.28px + 5.52vw, 80px);
}
```

- [ ] **Step 2: Replace `app.css` with the index**

```css
@import "tailwindcss";
@import "./theme.css";

@source "../../app/**/*.php";
@source "../**/*.blade.php";
@source "../**/*.js";
```

(Base/component partials get added by later tasks. Note: the scaffold's `@import "tailwindcss" theme(static);` becomes a plain `@import "tailwindcss";` — the emit-everything behaviour now lives on the `@theme static` block in `theme.css`.)

- [ ] **Step 3: Replace `editor.css`**

```css
@import "tailwindcss";
@import "./theme.css";
```

- [ ] **Step 4: Set up the JS entry pattern**

`THEME/resources/js/app.js` (replace contents):

```js
/**
 * Theme entry. Import feature modules here as they are added, e.g.:
 *   import './modules/navigation';
 */
```

Create empty `THEME/resources/js/modules/.gitkeep`.

- [ ] **Step 5: Build and verify tokens reach the generated `theme.json`**

Run: `make build-assets` (or `cd THEME && yarn build`)
Expected: build completes with no Vite/Tailwind error.

Run: `grep -o '#942d58' THEME/public/build/assets/theme.json`
Expected: prints `#942d58` (brand red registered → block-editor parity).

Run: `grep -c 'red-500\|blue-500\|slate-' THEME/public/build/assets/theme.json`
Expected: `0` (default Tailwind palette cleared).

- [ ] **Step 6: Commit**

```bash
git add THEME/resources/css/theme.css THEME/resources/css/app.css THEME/resources/css/editor.css THEME/resources/js/app.js THEME/resources/js/modules/.gitkeep
git commit -m "feat(theme): design tokens + CSS/JS entry scaffold"
```

---

### Task 2: Geomanist font

**Files:**
- Create: `THEME/resources/fonts/geomanist-regular-webfont.woff2`, `…woff` (copied)
- Create: `THEME/resources/css/base/fonts.css`
- Modify: `THEME/resources/css/app.css`, `THEME/resources/css/editor.css`

**Interfaces:**
- Produces: the `"Geomanist"` `@font-face` backing `--font-sans`.

- [ ] **Step 1: Copy the Regular weight into the theme**

```bash
cp reference/legacy-theme/assets/font/geomanist-regular-webfont.woff2 THEME/resources/fonts/
cp reference/legacy-theme/assets/font/geomanist-regular-webfont.woff  THEME/resources/fonts/
```

- [ ] **Step 2: Create `base/fonts.css`**

```css
@font-face {
  font-family: "Geomanist";
  src: url("../../fonts/geomanist-regular-webfont.woff2") format("woff2"),
       url("../../fonts/geomanist-regular-webfont.woff")  format("woff");
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
```

- [ ] **Step 3: Import it (both entries), after `theme.css`**

In `app.css` and `editor.css` add `@import "./base/fonts.css";` immediately below `@import "./theme.css";`.

- [ ] **Step 4: Build and verify the font is emitted**

Run: `make build-assets`
Expected: build passes.

Run: `ls THEME/public/build/assets/ | grep -i geomanist`
Expected: a hashed `geomanist-regular-webfont-*.woff2` (Vite copied + fingerprinted the font).

- [ ] **Step 5: Commit**

```bash
git add THEME/resources/fonts/ THEME/resources/css/base/fonts.css THEME/resources/css/app.css THEME/resources/css/editor.css
git commit -m "feat(theme): Geomanist Regular @font-face"
```

> **Licensing note:** the font is committed here (private repo). If licensing forbids storing the webfont in git, instead add `resources/fonts/*.woff*` to `THEME/.gitignore`, keep the copied files locally for build, and provision them at deploy — the rest of the task is unchanged.

---

### Task 3: Base typography + sandbox verification page

**Files:**
- Create: `THEME/resources/css/base/typography.css`
- Modify: `THEME/resources/css/app.css`, `THEME/resources/css/editor.css`
- Create: `THEME/resources/views/template-sandbox.blade.php`

**Interfaces:**
- Consumes: tokens from Task 1, font from Task 2.
- Produces: styled semantic `h1/h2/h3`, `body`, `a`; a `Sandbox` WP page used to DOM-verify Tasks 3–6.

- [ ] **Step 1: Create `base/typography.css`**

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

- [ ] **Step 2: Import it (both entries), after `fonts.css`**

Add `@import "./base/typography.css";` below the fonts import in `app.css` and `editor.css`.

- [ ] **Step 3: Create the sandbox template**

`THEME/resources/views/template-sandbox.blade.php`:

```blade
{{--
  Template Name: Sandbox
--}}
@extends('layouts.app')
@section('content')
  <main class="o-wrap" style="padding-block: 40px;">
    <h1 data-test="h1">Rozliczamy ARPI</h1>
    <h2 data-test="h2">Księgowość bez stresu</h2>
    <h3 data-test="h3">Doradztwo podatkowe</h3>
    <p data-test="body">Treść bazowa w foncie Geomanist, fluid między 375 a 1280px.</p>

    {{-- component demos are appended by later tasks --}}
  </main>
@endsection
```

- [ ] **Step 4: Build, then create the Sandbox WP page**

```bash
make build-assets
docker compose exec -T php wp post create \
  --post_type=page --post_title='Sandbox' --post_status=publish \
  --post_name=sandbox --porcelain
```

Note the printed page ID as `<PID>`, then assign the template:

```bash
docker compose exec -T php wp post meta update <PID> _wp_page_template template-sandbox.blade.php
```

- [ ] **Step 5: Verify the page renders the type scale**

Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'data-test="h1"'`
Expected: prints `data-test="h1"` (page resolves and the template renders).

Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'Geomanist'` (in the emitted CSS/inline) — optional visual confirm: open the URL in a browser and check H1 scales when narrowing the window from 1280→375px and renders in Geomanist.
Expected: headings visibly shrink fluidly; font is Geomanist.

- [ ] **Step 6: Commit**

```bash
git add THEME/resources/css/base/typography.css THEME/resources/css/app.css THEME/resources/css/editor.css THEME/resources/views/template-sandbox.blade.php
git commit -m "feat(theme): base typography + sandbox verification page"
```

---

### Task 4: Button — CSS (`.c-btn`)

**Files:**
- Create: `THEME/resources/css/components/button.css`
- Modify: `THEME/resources/css/app.css`

**Interfaces:**
- Produces: classes `.c-btn`, `.c-btn--solid`, `.c-btn--outline`, `.c-btn--ghost` (consumed by Task 5's Blade component).

- [ ] **Step 1: Create `components/button.css`**

```css
@layer components {
  .c-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;                            /* label ↔ icon (only visible with an icon) */
    padding-block: 16px;                 /* Figma py-16 → composes 48px height */
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

  /* ghost / text link — no box, larger label. Icon is OPTIONAL (Blade slot), never in CSS. */
  .c-btn--ghost {
    padding: 0;
    gap: 16px;
    border-color: transparent;
    background-color: transparent;
    color: var(--color-red);
    font-size: 1.25rem;                  /* 20px, Button Big */
  }
  .c-btn--ghost:hover {
    color: color-mix(in oklab, var(--color-red) 30%, transparent);
  }
}
```

- [ ] **Step 2: Import it, after the base imports**

Add `@import "./components/button.css";` in `app.css` (below `base/typography.css`). Editor does not need components.

- [ ] **Step 3: Build and verify the classes compile**

Run: `make build-assets`
Expected: build passes (validates `color-mix`, tokens resolve).

Run: `grep -o 'c-btn--solid' THEME/public/build/assets/*.css | head -1`
Expected: prints `c-btn--solid` (component emitted).

> Note: Tailwind only keeps component CSS it sees referenced via `@source`; the sandbox gains a `c-btn` in Task 5, so if this grep is empty, proceed — Step 3 of Task 5 re-verifies after the markup exists. (Component classes authored in `@layer components` are preserved regardless, so the grep should pass here.)

- [ ] **Step 4: Commit**

```bash
git add THEME/resources/css/components/button.css THEME/resources/css/app.css
git commit -m "feat(theme): c-btn component (solid/outline/ghost)"
```

---

### Task 5: Button — Blade `<x-button>`

**Files:**
- Create: `THEME/resources/views/components/button.blade.php`
- Modify: `THEME/resources/views/template-sandbox.blade.php`

**Interfaces:**
- Consumes: `.c-btn*` classes (Task 4).
- Produces: `<x-button variant href type>` rendering `<a>`/`<button>` with `c-btn c-btn--{variant}`.

- [ ] **Step 1: Create the Blade component**

```blade
@props(['variant' => 'solid', 'href' => null, 'type' => 'button'])
@php $tag = $href ? 'a' : 'button'; @endphp
<{{ $tag }}
  @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
  {{ $attributes->merge(['class' => "c-btn c-btn--{$variant}"]) }}>
  {{ $slot }}
</{{ $tag }}>
```

- [ ] **Step 2: Add button demos to the sandbox**

Replace the `{{-- component demos … --}}` line in `template-sandbox.blade.php` with:

```blade
<div style="display:flex; gap:16px; align-items:center; margin-top:40px;" data-test="buttons">
  <x-button variant="solid" href="/kontakt">Wyślij</x-button>
  <x-button variant="outline">Wszystkie artykuły</x-button>
  <x-button variant="ghost">Więcej informacji</x-button>
</div>
```

- [ ] **Step 3: Rebuild and verify rendered DOM**

Run: `make build-assets`
Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'class="c-btn c-btn--solid"'`
Expected: prints `class="c-btn c-btn--solid"` (solid renders as `<a>` with correct classes).

Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'c-btn--outline\|c-btn--ghost' | sort -u`
Expected: both `c-btn--ghost` and `c-btn--outline` present.

Visual: open the URL — solid/outline/ghost match Figma; hover on solid/outline dims to the transparent-red/inverted state.

- [ ] **Step 4: Commit**

```bash
git add THEME/resources/views/components/button.blade.php THEME/resources/views/template-sandbox.blade.php
git commit -m "feat(theme): <x-button> Blade component"
```

---

### Task 6: Container — `.o-wrap` + `<x-container>`

**Files:**
- Create: `THEME/resources/css/components/wrap.css`
- Create: `THEME/resources/views/components/container.blade.php`
- Modify: `THEME/resources/css/app.css`, `THEME/resources/views/template-sandbox.blade.php`

**Interfaces:**
- Consumes: `--wrap-padding-inline` (Task 1).
- Produces: `.o-wrap`, `.o-wrap--header`; `<x-container as variant>`.

- [ ] **Step 1: Create `components/wrap.css`**

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

- [ ] **Step 2: Import it**

Add `@import "./components/wrap.css";` in `app.css` (below `components/button.css`).

- [ ] **Step 3: Create the Blade component**

```blade
@props(['as' => 'div', 'variant' => null])
<{{ $as }} {{ $attributes->merge(['class' => 'o-wrap' . ($variant === 'header' ? ' o-wrap--header' : '')]) }}>
  {{ $slot }}
</{{ $as }}>
```

- [ ] **Step 4: Use it in the sandbox**

In `template-sandbox.blade.php`, change the opening `<main class="o-wrap" style="padding-block: 40px;">` to:

```blade
<x-container as="main" style="padding-block: 40px;" data-test="wrap">
```

and its closing `</main>` to `</x-container>`.

- [ ] **Step 5: Rebuild and verify**

Run: `make build-assets`
Run: `grep -o 'max-width: *1280px' THEME/public/build/assets/*.css | head -1`
Expected: prints the `max-width:1280px` rule (`.o-wrap` compiled).

Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'class="o-wrap"'`
Expected: prints `class="o-wrap"` (container renders as `<main>`).

Visual: content is centered, capped at 1280px, side padding grows 30→80px as the viewport widens, and there is **no** wrapper-imposed vertical padding.

- [ ] **Step 6: Commit**

```bash
git add THEME/resources/css/components/wrap.css THEME/resources/views/components/container.blade.php THEME/resources/css/app.css THEME/resources/views/template-sandbox.blade.php
git commit -m "feat(theme): o-wrap container + <x-container>"
```

---

### Task 7: Icon system + final verification

**Files:**
- Modify: `THEME/composer.json` (via `composer require`)
- Create: `THEME/config/blade-icons.php`
- Create: `THEME/resources/icons/arrow-right.svg`
- Modify: `THEME/resources/views/template-sandbox.blade.php` (then delete it + the WP page)

**Interfaces:**
- Produces: `<x-icon-arrow-right />` inline SVG using `currentColor`.

- [ ] **Step 1: Require Blade Icons in the theme (php container)**

```bash
docker compose exec -T php composer require blade-ui-kit/blade-icons -d wp-content/themes/arpi
docker compose exec -T php chown -R 1000:1000 wp-content/themes/arpi/vendor wp-content/themes/arpi/composer.json wp-content/themes/arpi/composer.lock
```

- [ ] **Step 2: Register the custom set**

`THEME/config/blade-icons.php`:

```php
<?php

return [
    'sets' => [
        'default' => [
            'path' => 'resources/icons',
            'prefix' => 'icon',
            'class' => 'size-6',
        ],
    ],
];
```

- [ ] **Step 3: Add the `arrow-right` icon (currentColor, 24 grid)**

`THEME/resources/icons/arrow-right.svg`:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M5 12h14M13 6l6 6-6 6"/>
</svg>
```

- [ ] **Step 4: Put the icon in the ghost button demo**

In `template-sandbox.blade.php`, change the ghost demo line to:

```blade
<x-button variant="ghost">Więcej <x-icon-arrow-right class="size-6" /></x-button>
```

- [ ] **Step 5: Clear Acorn cache, rebuild, verify inline SVG**

```bash
docker compose exec -T php wp acorn view:clear
make build-assets
```

Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'stroke="currentColor"'`
Expected: prints `stroke="currentColor"` (icon inlined as SVG, colorable via `text-*`).

Run: `curl -s "http://localhost:8080/?page_id=<PID>" | grep -o 'viewBox="0 0 24 24"'`
Expected: prints the viewBox (SVG rendered inside the ghost button).

Visual: ghost button shows the arrow in brand red; hover fades label+icon to transparent-red.

- [ ] **Step 6: Remove the sandbox (verification artifact) and commit**

```bash
docker compose exec -T php wp post delete <PID> --force
rm THEME/resources/views/template-sandbox.blade.php
make build-assets   # confirm build still green after removal
git add THEME/composer.json THEME/composer.lock THEME/config/blade-icons.php THEME/resources/icons/arrow-right.svg THEME/resources/views/template-sandbox.blade.php
git commit -m "feat(theme): Blade Icons custom set + arrow-right; drop sandbox"
```

> The `git add` of the deleted `template-sandbox.blade.php` stages its removal. Keep `vendor/` handling consistent with the theme's existing `.gitignore` (Sage ignores theme `vendor/`; only `composer.json`/`composer.lock` are tracked).

---

## Self-Review

**Spec coverage:** tokens/cleared palette (T1) · fluid type (T1/T3) · multi-file CSS + layers + entry (T1–T6) · fonts (T2) · anti-boilerplate base typography (T3) · `.c-btn` no-fixed-dims (T4) · `<x-button>` (T5) · `.o-wrap`/`<x-container>` no vertical padding (T6) · icon system + `arrow-right` (T7) · `theme.json` parity (T1 Step 5) · JS entry scaffold (T1 Step 4). All spec sections mapped.

**Placeholders:** none — every file has complete contents; `<PID>` is a runtime value the executor captures in T3 Step 4 and reuses (documented), not a plan gap.

**Type/name consistency:** class names `c-btn`, `c-btn--{solid,outline,ghost}`, `o-wrap`, `o-wrap--header`; tokens `--color-red/black/white/cream`, `--text-*`, `--wrap-padding-inline`; components `<x-button>`/`<x-container>`/`<x-icon-arrow-right>` — used identically across tasks.

**Provisional (from spec, flagged):** `.o-wrap` padding endpoints (30/80/16px) and body fluid mins confirm against real Figma page frames later; they live in tokens for one-line tuning.
