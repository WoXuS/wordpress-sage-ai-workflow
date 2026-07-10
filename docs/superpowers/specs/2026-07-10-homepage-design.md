# Homepage (front-page) — design spec

**Data:** 2026-07-10 · **Branch:** `feat/homepage` · **Status:** zatwierdzony, do implementacji

Homepage PL dla nowego motywu ARPI, budowany 1:1 z Figmy. Zastępuje tymczasowy
design-system showcase w `front-page.blade.php`.

## Źródła

- **Figma desktop (PL):** `2QUIxJAqMGrvChL7X4aIdr` node `2:2396` ("POLSKA", 1440×8029).
- **Figma mobile (PL):** node `395:2110` ("Mobile POLSKI", 393×7126).
- **Assety** (dostarczone przez Kamila, w `resources/images/`): `hp-hero.png`,
  `about-us.png`, `companies.png`, `experience.png`, `logo.png`, `logo--white.png`,
  `forbes-*.png`.
- Nawiązuje do design-systemu (`docs/.../2026-07-07-design-system-foundation.md`) i
  wzorca header/footer (`2026-07-08-header-footer-design.md`).

## Decyzje (zatwierdzone)

1. **Treść: statyczna teraz, gotowa pod ACF.** Nagłówki/akapity/kafelki z composera
   `App\View\Composers\Home` (tablice PHP, jak `Footer`; `// TODO: get_field` w fazie ACF).
   Wyjątek: blog = realne dane WP.
2. **Kategorie bloga = linki do archiwów kategorii** (nie filtr JS). SEO-first, działa bez JS.
3. **Linki usług/DBiP = slugi legacy** (zachowujemy stare URL-e — zero utraty SEO, zero
   301). Usługi pod `/uslugi/<slug>`, DBiP pod `/dbip-chapters/`. Dokładne per-slug
   weryfikujemy przy migracji; URL-e single-source w composerze.
4. **Sekcje graficzne = PNG.** Diagram hero, kolaż „O nas", honeycomb logotypów partnerów
   to obrazy. Jako indeksowalny HTML robimy **tylko hexagony „Dlaczego ARPI"**.
5. **Ikony usług** (z istniejącego zestawu): księgowość→`file-stack`, kadry i płace→`people`,
   doradztwo podatkowe→`bulb`, budżetowanie i raporty→`three-bar-graph`, spółki
   handlowe→`people-and-buildings`, prawo→`scales`.

## Architektura

```
front-page.blade.php  (cienki index; @extends layouts.app; @includy sekcji)
 └─ partials/home/{hero,about,memberships,why-arpi,services,blog,dbip}.blade.php
components/
 ├─ hexagon.blade.php      (<x-hexagon variant=solid|outline> — clip-path)
 ├─ service-tile.blade.php (ikona + nazwa + strzałka, link)
 └─ post-card.blade.php    (miniatura + tytuł + zajawka)
App/View/Composers/Home.php  (statyczne tablice + zapytania bloga; $views=['front-page'])
css/components/hexagon.css   (clip-path + honeycomb; @layer components)
```

- Jeden partial = jedna sekcja, jedno zadanie. `front-page` tylko składa.
- Composer `Home` dostarcza: `$hero`, `$about`, `$memberships`, `$why` (nagłówki + tablica
  hexów), `$services` (tablica: name/icon/url), `$dbip`, oraz `$blogCategories`, `$latestPosts`.
- Responsywność: **single-source** — render raz, przełączenie mobile/desktop przez
  utility/breakpointy (patrz [[theme-single-source-responsive-css]]). Wysokości z paddingu +
  treści, bez `h-[Npx]` ([[theme-no-hardcoded-heights]]). Płynne odstępy przez `fl-*`
  ([[theme-fluid-utilities-and-clamps]]).

## Sekcje

Kolejność = kolejność w DOM. Każda w `<x-container>` (o-wrap, max 1440, płynny gutter),
poza pełnotłowymi (memberships/blog) gdzie tło jest full-bleed a treść w kontenerze.

### 1. Hero
- **Treść:** H1 „Twoja księgowość pod kontrolą." (ostatnie słowo w `text-red`) + akapit
  „Dbamy o Twoją księgowość…". Przycisk w Figmie ukryty → **pomijamy**.
- **Desktop:** tekst lewa (~412px), diagram `hp-hero.png` prawa. **Mobile:** tekst, pod nim obraz.
- Obraz z `alt` opisowym (diagram procesu KSeF→InFlow→ARPI→US). Tło białe.

### 2. O nas
- **Treść:** kolaż `about-us.png` (hexagony ze zdjęciami) + „O nas" (H2) + akapit
  „Jesteśmy częścią ARPI Group…" + akapit ze statystykami (obrót 586 553 123 PLN, 3 530
  pracowników mies., OC 2,2 mln / 2,2 mln / 1,1 mln PLN) — full-width pod spodem.
- **Desktop:** kolaż lewa, tekst prawa, statystyki pełna szerokość. **Mobile:** stack. Tło białe.
- Statystyki = zwykły akapit (w Figmie warianty „Stats-card" są ukryte).

### 3. Jesteśmy częścią (tło red, full-bleed)
- **Treść:** H2 „Jesteśmy częścią" + akapit (biały tekst) + honeycomb logotypów partnerów
  `companies.png` (ARPI, Scandinavian-Polish Chamber, Krajowa Izba Doradców Podatkowych,
  Szwedzko-Polska / Polsko-Kanadyjska / Polsko-Ukraińska Izba Gospodarcza).
- **Desktop:** tekst lewa, honeycomb prawa. **Mobile:** stack. Tło `bg-red`, tekst biały.
- Honeycomb = obraz (logotypy, nie tekst → obraz OK).

### 4. Dlaczego ARPI?  ← hexagony HTML
- **Treść:** H2 „Dlaczego ARPI?" + intro akapit + **5 hexagonów z tekstem**: SPRAWNA
  KOMUNIKACJA, DOŚWIADCZENIE I SPECJALIZACJA, MIĘDZYNARODOWY ZAKRES, KOMPLEKSOWE WSPARCIE,
  APLIKACJE WSPIERAJĄCE BIZNES · pod spodem duże wyśrodkowane zdanie („Od 2001 roku…") +
  mały caption („Każdy klient otrzymuje swojego indywidualnego opiekuna.").
- **Hexagony:** `<x-hexagon variant="solid">TEKST</x-hexagon>` — `bg-red`, biały wyśrodkowany
  tekst, `clip-path` (pointy-top). Tekst realny (indeksowalny), nie obraz.
- **Honeycomb desktop:** rząd 5 szt. z naprzemiennym offsetem pionowym (parzyste podniesione),
  lekki ujemny odstęp poziomy. **Mobile:** klaster 2-1-2 (górny rząd 2, środek 1, dół 2).
  Realizacja: flex/grid + offset, jedno źródło, reflow przez breakpoint. Tło białe.

### 5. Nasze usługi
- **Treść:** H2 „Nasze usługi" + 6 kafelków: Księgowość, Kadry i płace, Doradztwo podatkowe,
  Budżetowanie i raporty, Spółki handlowe, Prawo.
- **Kafelek** `<x-service-tile>`: `bg-red`, zaokrąglony, ikona (outline biała) + nazwa +
  strzałka (`arrow-right` w kółku, prawy-dół). Cały kafelek = link do `/uslugi/<slug>`.
- **Desktop:** grid 3×2. **Mobile:** grid 2×3. Tło sekcji białe.
- Ikony wg mapowania z „Decyzje" p.5.

### 6. Blog (tło szare, full-bleed)  ← dynamiczne
- **Treść:** H2 „Blog" + intro akapit + pigułki kategorii + 3 najnowsze wpisy + „Więcej".
- **Pigułki:** „Wszystkie artykuły" (link do strony wpisów) + `get_categories(['hide_empty'
  => false])` → każda linkuje do `get_category_link()`. Styl: pill (aktywna = solid red,
  reszta = outline). Zawijają się (mobile: wrap).
- **Wpisy:** `WP_Query` `post_type=post`, `posts_per_page=3`, najnowsze. Karta
  `<x-post-card>`: miniatura (`the_post_thumbnail` — brandowana grafika ARPI z tytułem
  „na obrazku") + pod nią tytuł wpisu (`the_title`) + zajawka (`get_the_excerpt`), całość
  linkuje do wpisu. **Desktop:** 3 w rzędzie. **Mobile:** stack.
- **„Więcej"** → strona wpisów (blog index).
- **Pusty blog:** brak wpisów → sekcja renderuje nagłówek + pigułki (kategorie mogą być
  puste → sam „Wszystkie artykuły") + komunikat pustego stanu zamiast kart. Bez błędów.
- Tło sekcji: jasnoszare. **Do ustalenia przy implementacji:** czy to `--color-cream` czy
  osobny chłodny szary token — sprawdzić zmienną w Figmie; jeśli różny od cream, dodać
  token `--color-grey` (albo najbliższy istniejący).

### 7. Doing business in Poland
- **Treść:** hexagony dekoracyjne (obrys) + H2 „Doing business in Poland" + 2 akapity +
  przycisk „Poznaj naszą publikację".
- **Desktop:** hexagony lewa, tekst+przycisk prawa. **Mobile:** nagłówek → akapit → hexagony
  → akapit → przycisk (stack). Tło białe.
- **Hexagony:** `<x-hexagon variant="outline">` (3 szt., dekoracyjne, `aria-hidden`).
- **Przycisk** → `/dbip-chapters/` (baza CPT DBiP; dokładny landing potwierdzić przy migracji).

## Nowe komponenty

- **`<x-hexagon variant="solid|outline" size="…">`** — jeden hexagon (clip-path pointy-top).
  `solid` = `bg-red` + biały tekst (slot, wyśrodkowany); `outline` = obrys, dekoracyjny.
  Używany w sekcji 4 (solid, tekst) i 7 (outline). CSS w `css/components/hexagon.css`.
- **`<x-service-tile :service="…">`** — kafelek usługi (ikona/nazwa/strzałka, link, `bg-red`).
- **`<x-post-card :post="…">`** — karta wpisu (miniatura/tytuł/zajawka, link).

Honeycomb (rozkład wielu hexów) = kontener CSS w partialu/`hexagon.css`, nie w samym
komponencie (komponent = pojedynczy hex).

## Poza zakresem (świadomie)

- **ACF** — treść statyczna, podmiana na `get_field` w osobnej fazie (composer izoluje zmianę).
- **Podstrony usług** — nie istnieją; linkujemy do docelowych `/uslugi/*`, kodujemy później.
- **CPT `dbip-chapters`** — nie zmigrowany; link do bazy działa po migracji.
- **Wersje EN** — Polylang doda tłumaczenia później; homepage EN to osobny frame.
- **Newsletter/stopka** — już zbudowane, nie dotykamy.
- **Brak sekcji testimoniali** — mobilny „Testimonial-box" to reużyty komponent trzymający
  akapit DBiP, nie osobna sekcja opinii.

## Weryfikacja

- Build (`yarn build`) czysty; `front-page` renderuje wszystkie 7 sekcji (curl DOM 200).
- Hexagony „Dlaczego ARPI": tekst obecny w DOM (indeksowalny, nie obraz) — grep po treści.
- Kafelki usług linkują do `/uslugi/*`; DBiP do `/dbip-chapters/`.
- Blog: przy braku wpisów sekcja nie wywala się; pigułki kategorii linkują do archiwów.
- Responsywność: sekcje składają się poprawnie na ~375px i ~1440px (przegląd wizualny).
- Zgodność z Figmą desktop + mobile (odstępy z `fl-*`, bez hardcoded heights).
