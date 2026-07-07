# ARPI — Design Tokens (source of truth)

Extracted from Figma **"ARPI WEB (Copy)"** — file key `edYIMqOQ7pHPrP880IzgR9`
(readable copy of the original `KFG3sWtSSluZQMGmhE3IrW`, which needs editor access).

Reference frames:
- **Typography** — node `95:1582` (Frame 61)
- **Colors** — node `373:907` (Frame 62)
- **Buttons** — node `436:1746` (BUTTONS)

Extracted: 2026-07-07. Values are the exact Figma properties; unit notes inline.

---

## 1. Font

Single family across the whole system:

- **Geomanist**, style Regular, weight **400**.

> Licensed webfont — brand asset already on disk at `reference/legacy-theme/assets/font/` (gitignored). No other weights appear in the design-system frames.

---

## 2. Typography scale

`lh` = line-height, `ls` = letter-spacing (tracking). Unitless `lh` = multiplier of font-size; px `lh` = absolute.

### Desktop

| Token         | size (px) | lh        | ls (px) | Figma name         |
|---------------|-----------|-----------|---------|--------------------|
| Display / H1  | 65        | 64px      | -0.25   | Arpi H1            |
| H2            | 36        | 44px      | 0       | Arpi H2            |
| H3            | 24        | 30px      | 0       | ARPI H3            |
| Body Big      | 24        | 1.4       | 0       | Arpi Body big      |
| Body Base     | 20        | 1.4       | 0       | Arpi Body Base     |
| Body Small    | 16        | 1.4       | 0       | Arpi Body small    |
| Button Big    | 20        | 1 (100%)  | 0.5     | Arpi Butoon Big    |
| Button Small  | 16        | 1 (100%)  | 0.5     | Arpi Button Small  |

### Mobile

| Token    | size (px) | lh    | ls (px) | Figma name     |
|----------|-----------|-------|---------|----------------|
| H1       | 36        | 1.1   | 0       | ARPI H1 Mobile |
| H2       | 24        | 1.1   | 0       | ARPI H2 Mobile |
| H3       | 16        | 20px  | 0       | ARPI H3 Mobile |

> Body/Button sizes are shared desktop↔mobile (no mobile-specific variants in the frame).

---

## 3. Colors

| Token                 | Value                     | Notes                                   | Figma name           |
|-----------------------|---------------------------|-----------------------------------------|----------------------|
| ARPI Red              | `#942D58`                 | primary brand                           | ARPI RED             |
| ARPI Red Transparent  | `rgba(148,45,88,0.30)`    | `#942D58` @ 30% — hover/muted accent    | ARPI RED TRANSPARENT |
| White                 | `#FFFFFF`                 | backgrounds / primary surface           | WHITE / Default/White|
| Black                 | `#19191C`                 | soft near-black text (NOT pure #000)    | BLACK                |
| Cream                 | `#F5F4EE`                 | warm off-white surface                  | CREAM                |

Variable-bound in Figma: `ARPI RED = #942d58`, `Backgrounds/Primary = #ffffff`.
Black / Cream / Red-Transparent were drawn as swatch vectors — hex read from the swatch SVG fills.

---

## 4. Buttons

Shared geometry (all pill buttons):

- height **48px**, padding `px-12 py-16`, inner text container `px-16`
- radius **30px** (full pill), border **2px solid**
- text: Geomanist 400, **16px**, tracking **0.5**, line-height 1 (`leading-none`), `white-space: nowrap`
- hover (filled/outline variants): whole button `opacity: 0.91`

### 4a. Outline button — "light" (on light bg)  · `436:1751` / hover `436:1753`

| State   | bg                      | border          | text      |
|---------|-------------------------|-----------------|-----------|
| Normal  | White `#FFFFFF`         | 2px ARPI Red    | ARPI Red  |
| Hover   | ARPI Red `#942D58`      | 2px ARPI Red    | White     |

### 4b. Solid button — "dark" (on light bg)  · `479:2243` / hover `479:2245`

| State   | bg                          | border    | text   |
|---------|-----------------------------|-----------|--------|
| Normal  | ARPI Red `#942D58`          | 2px White | White  |
| Hover   | ARPI Red Transparent (30%)  | 2px ARPI Red | White |

### 4c. Text / link button ("Buttons Group") · `436:1756` / hover `436:1759`

Ghost link — no bg, no border. Label + trailing `arrow-right` icon (jam-icons, 24px).

| State   | text                        | icon        |
|---------|-----------------------------|-------------|
| Normal  | ARPI Red `#942D58`, **20px** (Button Big) | arrow-right |
| Hover   | ARPI Red Transparent (30%)  | arrow-right |

Layout: `flex gap-16`, `pr-8`, height 48px.

> Also present in the BUTTONS frame but **not a token**: a "Content Box" card component (`667:1987`, variants Default / Variant2) — a layout component, extract separately when building that section.

---

## 5. Suggested Tailwind mapping (not yet applied)

Sage 11 uses Tailwind v4 (`@theme` in `app.css`). Draft for when we wire tokens in
(chosen next step is extraction-only; this is a starting point, not committed to the theme):

```css
@theme {
  --font-sans: "Geomanist", ui-sans-serif, system-ui, sans-serif;

  --color-arpi-red: #942d58;
  --color-arpi-red-30: rgb(148 45 88 / 0.30);
  --color-arpi-black: #19191c;
  --color-arpi-cream: #f5f4ee;
  --color-white: #ffffff;

  --text-h1: 65px;      --text-h1--line-height: 64px;  --text-h1--letter-spacing: -0.25px;
  --text-h2: 36px;      --text-h2--line-height: 44px;
  --text-h3: 24px;      --text-h3--line-height: 30px;
  --text-body-lg: 24px; --text-body-lg--line-height: 1.4;
  --text-body: 20px;    --text-body--line-height: 1.4;
  --text-body-sm: 16px; --text-body-sm--line-height: 1.4;
  --text-btn-lg: 20px;  --text-btn-lg--line-height: 1; --text-btn-lg--letter-spacing: 0.5px;
  --text-btn-sm: 16px;  --text-btn-sm--line-height: 1; --text-btn-sm--letter-spacing: 0.5px;
}
```

Mobile H1/H2/H3 (36/24/16, lh 1.1/1.1/20px) apply via responsive utilities at the type-scale
breakpoint, not separate tokens.
