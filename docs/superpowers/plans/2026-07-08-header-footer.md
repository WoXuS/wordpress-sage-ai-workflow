# Header + Footer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the site-wide sticky header and red footer from the Figma frames, plus the small reusable primitives they need.

**Architecture:** Blade sections (`sections/header`, `sections/footer`) rendered by the app layout on every page. Reusable design-system primitives get dedicated `c-` CSS (`c-btn--white`, `c-input`); all one-off layout (header, footer, nav, overlay, social, switcher) uses Tailwind utilities inline. Footer content comes from a Sage view composer holding hardcoded, ACF-ready data. Mobile menu is a full-screen overlay toggled by a small vanilla-JS module; sticky is pure CSS.

**Tech Stack:** WordPress + Sage 11/Acorn, Blade, Tailwind v4 (CSS-first), Vite, blade-ui-kit/blade-icons.

## Global Constraints

- **CSS naming:** ITCSS-lite `c-`/`o-`/`u-` + BEM block + `--modifier`. Native CSS nesting for states (`&:hover`, `&:focus`) only — Sass-style `&--modifier` concatenation does NOT work; modifiers are flat top-level classes.
- **Dedicated CSS only for repeating design-system primitives.** One-off compositions use Tailwind utilities in Blade.
- **Colors:** brand tokens only — `--color-red #942d58`, `--color-black #19191c`, `--color-white`, `--color-cream #f5f4ee`. Utilities `bg-red`, `text-white`, `text-red`, `bg-cream`, opacity via `/NN` (e.g. `text-black/40`).
- **Type:** fluid tokens as utilities (`text-h2`, `text-body`, `text-body-sm`). Font = Geomanist Regular (400) only.
- **Spacing:** px units (design-system policy). Use Tailwind scale for internal spacing; arbitrary px (`px-[80px]`) or the `o-wrap` gutter where exact Figma parity matters.
- **Blade component prefix:** icons via `<x-icon-*>` / `@svg('icon-name', 'classes')` (set prefix `icon`).
- **Composers auto-register** from `App\View\Composers\*`; public methods become view variables (`company()` → `$company`).
- **Assets:** reference images with `Vite::asset('resources/images/…')` (`use Illuminate\Support\Facades\Vite;`).
- **Verification** (no unit suite — mirror the design-system phase): CSS presence via `yarn build` then grep `public/build/assets/*.css`; DOM via `curl -s http://localhost:8080/` then grep (header/footer render on every page — the showcase front-page is the homepage). `make dev` is assumed running. After adding new Blade views run `docker compose exec -T php wp acorn view:clear --allow-root` so Acorn recompiles.
- **Commits:** repo-local identity, NO `Co-Authored-By` trailer. Conventional commit messages.
- **Theme path shorthand below:** `T = public_html/wp-content/themes/arpi`. Run `yarn`/`git` from repo root; run `wp` via `docker compose exec -T php wp … --allow-root`.

---

### Task 1: Primitives — white button variant + input component

**Files:**
- Modify: `T/resources/css/components/button.css` (append `.c-btn--white`)
- Create: `T/resources/css/components/input.css`
- Modify: `T/resources/css/app.css` (add `@import "./components/input.css";`)
- Create: `T/resources/views/components/input.blade.php`

**Interfaces:**
- Produces: `<x-button variant="white">` (white bg, red text). `<x-input type="…" variant="on-red|null" name placeholder />` → `.c-input` / `.c-input--on-red`.

- [ ] **Step 1: Add the white button modifier**

Append inside the `@layer components { … }` block of `T/resources/css/components/button.css`, after `.c-btn--ghost`:

```css
  .c-btn--white {
    background-color: var(--color-white);
    border-color: var(--color-white);
    color: var(--color-red);

    &:hover {
      opacity: 0.9; /* provisional — no hover in Figma */
    }
  }
```

- [ ] **Step 2: Create the input component CSS**

Create `T/resources/css/components/input.css`:

```css
@layer components {
  .c-input {
    display: block;
    width: 100%;
    padding: 14px 24px;
    border: 2px solid var(--color-black);
    border-radius: 30px;
    background-color: transparent;
    color: var(--color-black);
    font-size: var(--text-body-sm);
    line-height: 1;

    &::placeholder {
      color: color-mix(in oklab, currentColor 55%, transparent);
    }
    &:focus {
      outline: none;
      border-color: var(--color-red);
    }
  }

  /* On the red footer: white outline + white text. */
  .c-input--on-red {
    border-color: var(--color-white);
    color: var(--color-white);

    &:focus {
      border-color: var(--color-white);
    }
  }
}
```

- [ ] **Step 3: Import the partial**

In `T/resources/css/app.css`, add after the `button.css` import line:

```css
@import "./components/input.css";
```

- [ ] **Step 4: Create the Blade input component**

Create `T/resources/views/components/input.blade.php`:

```blade
@props(['type' => 'text', 'variant' => null])
<input
  type="{{ $type }}"
  {{ $attributes->merge(['class' => 'c-input' . ($variant === 'on-red' ? ' c-input--on-red' : '')]) }}
/>
```

- [ ] **Step 5: Build and verify the CSS exists**

Run: `yarn --cwd public_html/wp-content/themes/arpi build`
Then: `grep -oE '\.c-btn--white|\.c-input(--on-red)?' public_html/wp-content/themes/arpi/public/build/assets/*.css | sort -u`
Expected: `.c-btn--white`, `.c-input`, `.c-input--on-red` all present; build exits 0. (Component DOM is exercised in Task 3.)

- [ ] **Step 6: Commit**

```bash
git add public_html/wp-content/themes/arpi/resources/css public_html/wp-content/themes/arpi/resources/views/components/input.blade.php
git commit -m "feat(theme): c-btn--white variant + c-input/<x-input> primitive"
```

---

### Task 2: Footer composer + footer skeleton (logo, legal, offices, badges)

**Files:**
- Create: `T/app/View/Composers/Footer.php`
- Modify: `T/resources/views/sections/footer.blade.php` (replace stub)
- Create: `T/resources/views/partials/footer-office.blade.php`

**Interfaces:**
- Consumes: `Vite::asset()`, `<x-container>`.
- Produces: `App\View\Composers\Footer` exposing `$company`, `$offices`, `$newsletter`, `$socials`, `$badges` to `sections.footer`. Shapes:
  - `$company`: `['name'=>string,'krs'=>string,'nip'=>string,'address'=>string[]]`
  - `$offices`: `array<['name'=>string,'address'=>string[],'maps_url'=>string,'phone'=>string,'email'=>string]>`
  - `$newsletter`: `['heading'=>string,'copy'=>string,'submit'=>string,'action'=>string]`
  - `$socials`: `array<['network'=>string,'url'=>string,'icon'=>string]>`
  - `$badges`: `array<['src'=>string,'alt'=>string]>`

- [ ] **Step 1: Create the Footer composer**

Create `T/app/View/Composers/Footer.php`:

```php
<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Vite;
use Roots\Acorn\View\Composer;

class Footer extends Composer
{
    // NOTE (post-impl): Acorn 6.2 wraps zero-arg composer methods in
    // InvokableComponentVariable, which breaks $company['name'] array access in
    // Blade. Add a with() override returning ['company'=>$this->company(), …] for
    // all five keys so the arrays reach the view as plain arrays.
    /**
     * Views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'sections.footer',
    ];

    /**
     * Company registration details.
     * TODO: swap to get_field(..., 'option') in the ACF phase.
     */
    public function company(): array
    {
        return [
            'name' => 'ARPI & Partners Sp. z o.o.',
            'krs' => '0000255170',
            'nip' => '5213382100',
            'address' => ['Puławska 182', '02-670 Warszawa'],
        ];
    }

    /**
     * Office locations.
     */
    public function offices(): array
    {
        return [
            [
                'name' => 'Rzeszów',
                'address' => ['Juliusza Słowackiego 6/12', '35-060'],
                'maps_url' => '#',
                'phone' => '+48 538 235 852',
                'email' => 'contact@arpiaccounting.com',
            ],
            [
                'name' => 'Warszawa',
                'address' => ['Puławska 182', '02-670'],
                'maps_url' => '#',
                'phone' => '+48 22 559 00 55',
                'email' => 'contact@arpiaccounting.com',
            ],
        ];
    }

    /**
     * Newsletter block copy (markup-only form; MailPoet wired later).
     */
    public function newsletter(): array
    {
        return [
            'heading' => 'Newsletter',
            'copy' => 'Zapisz się do naszego newslettera i bądź na bieżąco z najważniejszymi zmianami w polskim prawie',
            'submit' => 'Subskrybuj',
            'action' => '#',
        ];
    }

    /**
     * Social links (URLs are placeholders).
     */
    public function socials(): array
    {
        return [
            ['network' => 'Facebook', 'url' => '#', 'icon' => 'facebook'],
            ['network' => 'LinkedIn', 'url' => '#', 'icon' => 'linkedin'],
        ];
    }

    /**
     * Forbes badges.
     */
    public function badges(): array
    {
        return [
            ['src' => Vite::asset('resources/images/forbes-diament.png'), 'alt' => 'Diamenty Forbes 2026'],
            ['src' => Vite::asset('resources/images/forbes-laureat.png'), 'alt' => 'Forbes Laureat 2026'],
        ];
    }
}
```

- [ ] **Step 2: Replace the footer stub with the skeleton**

Replace the entire contents of `T/resources/views/sections/footer.blade.php`:

```blade
<footer class="c-footer bg-red text-white">
  <x-container class="grid grid-cols-1 gap-x-16 gap-y-12 py-16 md:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_auto_auto]">

    {{-- Left column (newsletter) is added in Task 3 --}}
    <div class="flex flex-col gap-8">
      <img src="{{ Vite::asset('resources/images/logo--white.svg') }}" alt="{{ get_bloginfo('name') }}" class="h-16 w-auto" width="197" height="132">
      {{-- newsletter partial slot (Task 3) --}}
      @includeWhen(view()->exists('partials.newsletter'), 'partials.newsletter')
    </div>

    {{-- Company legal + badges --}}
    <div class="flex flex-col gap-10">
      <address class="not-italic leading-relaxed text-body-sm">
        {{ $company['name'] }}<br>
        KRS: {{ $company['krs'] }}<br>
        NIP: {{ $company['nip'] }}<br>
        @foreach ($company['address'] as $line){{ $line }}<br>@endforeach
      </address>
      <div class="flex flex-wrap items-center gap-6">
        @foreach ($badges as $badge)
          <img src="{{ $badge['src'] }}" alt="{{ $badge['alt'] }}" class="h-28 w-auto rounded-sm">
        @endforeach
      </div>
    </div>

    {{-- Offices --}}
    @foreach ($offices as $office)
      @include('partials.footer-office', ['office' => $office])
    @endforeach

  </x-container>
</footer>
```

Note: `use Illuminate\Support\Facades\Vite;` is not needed in Blade — the `Vite` facade is globally aliased.

- [ ] **Step 3: Create the office partial**

Create `T/resources/views/partials/footer-office.blade.php`:

```blade
<div class="flex flex-col gap-2 text-body-sm">
  <p class="font-medium">{{ $office['name'] }}</p>
  <address class="not-italic leading-relaxed">
    @foreach ($office['address'] as $line){{ $line }}<br>@endforeach
  </address>
  <a href="{{ $office['maps_url'] }}" class="underline underline-offset-2 hover:no-underline">Pokaż w Google Maps</a>
  <a href="tel:{{ preg_replace('/\s+/', '', $office['phone']) }}" class="hover:underline">{{ $office['phone'] }}</a>
  <a href="mailto:{{ $office['email'] }}" class="hover:underline">{{ $office['email'] }}</a>
</div>
```

- [ ] **Step 4: Clear view cache and verify the footer renders with data**

Run: `docker compose exec -T php wp acorn view:clear --allow-root`
Then: `curl -s http://localhost:8080/ | grep -oE 'KRS: 0000255170|NIP: 5213382100|Rzeszów|Warszawa|538 235 852|Diamenty Forbes 2026'`
Expected: all six strings present (company legal, both offices, a phone, a badge alt).

- [ ] **Step 5: Visual check**

Run: `curl -s http://localhost:8080/ -o /dev/null -w "%{http_code}\n"` → expect `200`. Optionally screenshot to confirm red footer + white logo + legal + offices + badges layout.

- [ ] **Step 6: Commit**

```bash
git add public_html/wp-content/themes/arpi/app/View/Composers/Footer.php public_html/wp-content/themes/arpi/resources/views/sections/footer.blade.php public_html/wp-content/themes/arpi/resources/views/partials/footer-office.blade.php
git commit -m "feat(theme): footer skeleton + Footer composer (logo, legal, offices, badges)"
```

---

### Task 3: Newsletter block + social links (into footer)

**Files:**
- Create: `T/resources/views/partials/newsletter.blade.php`
- Create: `T/resources/views/components/social-links.blade.php`

**Interfaces:**
- Consumes: `$newsletter`, `$socials` (Task 2 composer); `<x-input variant="on-red">`, `<x-button variant="white">` (Task 1).
- Produces: `partials.newsletter` (rendered by the footer's `@includeWhen`); `<x-social-links :links="$socials" />`.

- [ ] **Step 1: Create the newsletter partial**

Create `T/resources/views/partials/newsletter.blade.php`:

```blade
<div class="flex flex-col gap-6">
  <h2 class="text-h2">{{ $newsletter['heading'] }}</h2>

  <form action="{{ $newsletter['action'] }}" method="post" class="flex flex-col gap-5">
    {{-- TODO: wire to MailPoet in the migration phase --}}
    <x-input type="email" name="email" variant="on-red" required placeholder="Adres e-mail" aria-label="Adres e-mail" />
    <p class="text-body-sm leading-relaxed">{{ $newsletter['copy'] }}</p>
    <div>
      <x-button type="submit" variant="white">{{ $newsletter['submit'] }}</x-button>
    </div>
  </form>

  <x-social-links :links="$socials" />
</div>
```

- [ ] **Step 2: Create the social-links component**

Create `T/resources/views/components/social-links.blade.php`:

```blade
@props(['links' => []])
<ul class="flex items-center gap-3">
  @foreach ($links as $link)
    <li>
      <a href="{{ $link['url'] }}" aria-label="{{ $link['network'] }}"
         class="flex size-10 items-center justify-center rounded-full border-2 border-white/70 text-white transition-colors hover:bg-white hover:text-red">
        @svg('icon-' . $link['icon'], 'size-4')
      </a>
    </li>
  @endforeach
</ul>
```

- [ ] **Step 3: Clear view cache and verify newsletter + socials render**

Run: `docker compose exec -T php wp acorn view:clear --allow-root`
Then: `curl -s http://localhost:8080/ | grep -oE 'c-input--on-red|c-btn--white|Subskrybuj|aria-label="Facebook"|aria-label="LinkedIn"|Adres e-mail'`
Expected: input (on-red), white button, "Subskrybuj", both social aria-labels, placeholder — all present.

- [ ] **Step 4: Verify the social SVGs are inlined**

Run: `curl -s http://localhost:8080/ | grep -c '<svg'`
Expected: count increased vs before (facebook + linkedin icons now inlined in the footer).

- [ ] **Step 5: Commit**

```bash
git add public_html/wp-content/themes/arpi/resources/views/partials/newsletter.blade.php public_html/wp-content/themes/arpi/resources/views/components/social-links.blade.php
git commit -m "feat(theme): footer newsletter block + social links"
```

---

### Task 4: Header — logo, nav, language switcher, sticky (desktop) + dev menu

**Files:**
- Modify: `T/resources/views/sections/header.blade.php` (replace stub)
- Create: `T/resources/views/components/lang-switcher.blade.php`
- Modify: `T/app/filters.php` (add nav link classes filter)

**Interfaces:**
- Consumes: `primary_navigation` menu location (registered in `app/setup.php`); `<x-container variant="header">`.
- Produces: `<x-lang-switcher />`; a `nav_menu_link_attributes` filter that adds utility classes to primary-nav links, switchable via a custom `arpi_variant` wp_nav_menu arg (`'desktop'` default | `'mobile'`).

- [ ] **Step 1: Create a dev WP menu and assign it**

```bash
docker compose exec -T php wp menu create "Primary" --allow-root 2>/dev/null || true
for item in "Dlaczego ARPI?" "Usługi" "Blog" "Kariera" "Proces" "Kontakt"; do
  docker compose exec -T php wp menu item add-custom Primary "$item" "#" --allow-root
done
docker compose exec -T php wp menu location assign Primary primary_navigation --allow-root
docker compose exec -T php wp menu list --allow-root
```
Expected: `primary` menu listed with location `primary_navigation` and count 6.

- [ ] **Step 2: Add the nav-link classes filter**

Append to `T/app/filters.php` (inside the file, top-level):

```php
/**
 * Utility classes for primary-navigation links. `arpi_variant` (a custom
 * wp_nav_menu arg) picks the colour set: red on the white desktop bar,
 * white in the mobile overlay.
 */
add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
    if (($args->theme_location ?? '') !== 'primary_navigation') {
        return $atts;
    }
    $base = 'uppercase tracking-wide text-body-sm transition-opacity hover:opacity-70';
    $colour = (($args->arpi_variant ?? 'desktop') === 'mobile') ? 'text-white' : 'text-red';
    $atts['class'] = trim(($atts['class'] ?? '') . " {$base} {$colour}");
    return $atts;
}, 10, 3);
```

- [ ] **Step 3: Create the language switcher component**

Create `T/resources/views/components/lang-switcher.blade.php`:

```blade
@php
  $langs = function_exists('pll_the_languages')
    ? pll_the_languages(['raw' => 1, 'hide_if_empty' => 0])
    : null;
@endphp
<div class="flex items-center gap-1.5 text-body-sm uppercase tracking-wide">
  @if ($langs)
    @foreach ($langs as $i => $lang)
      @if ($i > 0)<span class="text-black/40">/</span>@endif
      <a href="{{ $lang['url'] }}"
         @class(['text-red' => $lang['current_lang'], 'text-black/40 hover:text-red' => ! $lang['current_lang']])
         @if ($lang['current_lang']) aria-current="true" @endif>{{ strtoupper($lang['slug']) }}</a>
    @endforeach
  @else
    <span class="text-red" aria-current="true">PL</span>
    <span class="text-black/40">/</span>
    <a href="#" class="text-black/40 hover:text-red">EN</a>
  @endif
</div>
```

- [ ] **Step 4: Replace the header stub (desktop; mobile trigger added in Task 5)**

Replace the entire contents of `T/resources/views/sections/header.blade.php`:

```blade
<header class="c-header sticky top-0 z-50 bg-white shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
  <x-container variant="header" class="flex h-[132px] items-center justify-between gap-8">

    <a href="{{ home_url('/') }}" class="shrink-0" rel="home" aria-label="{{ get_bloginfo('name') }}">
      <img src="{{ Vite::asset('resources/images/logo.svg') }}" alt="{{ get_bloginfo('name') }}" class="h-16 w-auto" width="197" height="132">
    </a>

    @if (has_nav_menu('primary_navigation'))
      <nav class="hidden lg:block" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'container' => false,
          'menu_class' => 'flex items-center gap-8',
          'echo' => false,
        ]) !!}
      </nav>
    @endif

    <div class="hidden lg:block">
      <x-lang-switcher />
    </div>

    {{-- mobile hamburger added in Task 5 --}}

  </x-container>
</header>
```

- [ ] **Step 5: Clear view cache and verify the header renders**

Run: `docker compose exec -T php wp acorn view:clear --allow-root`
Then: `curl -s http://localhost:8080/ | grep -oE 'c-header|logo\.svg|Dlaczego ARPI\?|Kontakt|text-red[^"]*">PL|aria-current="true">PL'`
Expected: header class, logo, nav items ("Dlaczego ARPI?", "Kontakt"), and the PL switcher fallback present.

- [ ] **Step 6: Verify sticky is applied**

Run: `yarn --cwd public_html/wp-content/themes/arpi build && grep -oE 'position:\s*sticky' public_html/wp-content/themes/arpi/public/build/assets/*.css | head -1`
Expected: `position: sticky` present (from the `sticky` utility). Optionally screenshot the header.

- [ ] **Step 7: Commit**

```bash
git add public_html/wp-content/themes/arpi/resources/views/sections/header.blade.php public_html/wp-content/themes/arpi/resources/views/components/lang-switcher.blade.php public_html/wp-content/themes/arpi/app/filters.php
git commit -m "feat(theme): sticky header — logo, primary nav, PL/EN switcher"
```

---

### Task 5: Mobile menu — hamburger trigger + full-screen overlay + JS

**Files:**
- Modify: `T/resources/views/sections/header.blade.php` (add trigger + overlay)
- Create: `T/resources/js/modules/menu.js`
- Modify: `T/resources/js/app.js` (import the module)

**Interfaces:**
- Consumes: `primary_navigation` (rendered again with `arpi_variant => 'mobile'`); the Task 2 nav filter.
- Produces: a `[data-menu-toggle]` button and a `[data-menu-overlay]` element toggled by `menu.js` via a `data-open` attribute.

- [ ] **Step 1: Add the hamburger trigger + overlay to the header**

In `T/resources/views/sections/header.blade.php`, replace the comment `{{-- mobile hamburger added in Task 5 --}}` with:

```blade
    <button type="button" class="lg:hidden text-red" data-menu-toggle
            aria-expanded="false" aria-controls="mobile-menu" aria-label="Menu">
      @svg('icon-menu-2', 'size-8')
    </button>
```

Then add the overlay as the last child inside `<header>`, right before the closing `</header>` tag (after `</x-container>`):

```blade
  <div id="mobile-menu" data-menu-overlay
       class="invisible fixed inset-0 z-50 flex flex-col bg-red opacity-0 transition-opacity duration-200 data-[open]:visible data-[open]:opacity-100 lg:hidden">
    <div class="flex h-[82px] items-center justify-between px-6">
      <img src="{{ Vite::asset('resources/images/logo--white.svg') }}" alt="{{ get_bloginfo('name') }}" class="h-12 w-auto">
      <button type="button" class="text-white" data-menu-close aria-label="Zamknij menu">
        @svg('icon-x', 'size-8')
      </button>
    </div>
    @if (has_nav_menu('primary_navigation'))
      <nav class="flex flex-1 flex-col items-center justify-center gap-8 text-center" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'container' => false,
          'menu_class' => 'flex flex-col items-center gap-8',
          'arpi_variant' => 'mobile',
          'echo' => false,
        ]) !!}
      </nav>
    @endif
  </div>
```

Note: this needs icons `menu-2` and `x` in `T/resources/icons/`. If either is missing, add a minimal `currentColor` SVG (24×24, no width/height) — hamburger (`menu-2.svg`: three horizontal lines) and close (`x.svg`: an ✕). Verify with `ls T/resources/icons/ | grep -E '^(menu-2|x)\.svg'` before Step 4.

- [ ] **Step 2: Create the menu JS module**

Create `T/resources/js/modules/menu.js`:

```js
/**
 * Mobile menu: toggle a full-screen overlay via a `data-open` attribute.
 */
export default function initMenu() {
  const toggle = document.querySelector('[data-menu-toggle]');
  const overlay = document.querySelector('[data-menu-overlay]');
  if (!toggle || !overlay) return;

  const close = () => {
    delete overlay.dataset.open;
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  };
  const open = () => {
    overlay.dataset.open = 'true';
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  };

  toggle.addEventListener('click', () => (overlay.dataset.open ? close() : open()));
  overlay.querySelector('[data-menu-close]')?.addEventListener('click', close);
  document.addEventListener('keydown', (e) => e.key === 'Escape' && close());
}
```

- [ ] **Step 3: Import the module in the entry**

Replace the contents of `T/resources/js/app.js`:

```js
/**
 * Theme entry.
 */
import initMenu from './modules/menu';

initMenu();
```

- [ ] **Step 4: Clear view cache, build, and verify markup + JS**

Run: `docker compose exec -T php wp acorn view:clear --allow-root`
Then: `curl -s http://localhost:8080/ | grep -oE 'data-menu-toggle|data-menu-overlay|aria-controls="mobile-menu"|data-\[open\]:opacity-100'`
Expected: toggle, overlay, aria-controls, and the `data-[open]` variant class all present.
Then: `yarn --cwd public_html/wp-content/themes/arpi build && grep -rl 'data-menu-overlay\|dataset.open' public_html/wp-content/themes/arpi/public/build/assets/*.js`
Expected: the menu module code is bundled (a matching JS file).

- [ ] **Step 5: Visual/interaction check**

Load `http://localhost:8080/` in a narrow viewport (< 1024px): hamburger shows, click opens the red overlay with white logo + X + centered nav, X and Escape close it, body scroll locks while open.

- [ ] **Step 6: Commit**

```bash
git add public_html/wp-content/themes/arpi/resources/views/sections/header.blade.php public_html/wp-content/themes/arpi/resources/js
git commit -m "feat(theme): mobile menu — hamburger + full-screen overlay + toggle JS"
```

---

## Self-Review

**Spec coverage:**
- Header desktop (logo, nav, switcher, sticky) → Task 4. Mobile header + overlay → Task 5. ✓
- Footer (newsletter, legal, offices, badges, socials, mobile stack) → Tasks 2–3 (responsive grid in Task 2 skeleton). ✓
- Primitives `c-input`/`<x-input>`, `c-btn--white` → Task 1. ✓
- `<x-lang-switcher>` fallback → Task 4. `<x-social-links>` → Task 3. ✓
- Footer composer (hardcoded, ACF-ready) → Task 2. ✓
- Mobile menu JS + sticky CSS → Task 5 / Task 4. ✓
- Dev WP menu → Task 4 Step 1. ✓
- CSS approach (dedicated only for primitives; utilities elsewhere) → enforced throughout. ✓
- Provisional items (white hover, logo raster, breakpoints) → carried in Global Constraints / spec; no code owed. ✓

**Placeholder scan:** No "TBD/TODO-implement-later" left as work items. The two literal `TODO:` comments (MailPoet wiring, ACF swap) are intentional out-of-scope markers required by the spec, with real code around them. ✓

**Type consistency:** `$company/$offices/$newsletter/$socials/$badges` shapes defined in Task 2 match their use in Tasks 2–3. `variant="on-red"` (input) and `variant="white"` (button) consistent between Task 1 definitions and Task 3 usage. `data-menu-toggle`/`data-menu-overlay`/`data-menu-close`/`data-open` consistent between Task 5 markup and JS. `arpi_variant` arg consistent between Task 4 filter and Task 5 nav call. ✓
