# ARPI Rewrite — Faza 1: Fundament lokalny (Implementation Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Postawić działający lokalny fundament projektu: Bedrock + Sage 10 w DDEV, z HMR, Tailwindem podpiętym pod tokeny z Figmy, ACF PRO, modelem treści (CPT + pierwsza grupa pól) w Local JSON oraz wzorcem sekcji sterowanej view composerem.

**Architecture:** Green-field WordPress zarządzany Composerem (Bedrock, docroot `web/`), motyw Sage 10 (Blade + Acorn + Bud/HMR + Tailwind) w `web/app/themes/arpi`. Model treści (CPT, taksonomie, pola) definiowany w ACF PRO i wersjonowany jako Local JSON w motywie. Sekcje stron dostają dane przez view composery — dziś zwracają treść zahardkodowaną, później podmiana na `get_field()` bez zmiany szablonu.

**Tech Stack:** DDEV (Docker), Bedrock (roots/bedrock), Sage 10 (roots/sage), Acorn, Bud.js, Tailwind CSS, ACF PRO 6.3+, PHP 8.2, Node 20+, Composer 2, Polylang (konfiguracja w kolejnej fazie).

## Prerequisites (zależności zewnętrzne — potwierdzić PRZED wykonaniem)

- **Klucz licencyjny ACF PRO** (klient wykupuje subskrypcję) — potrzebny w Task 6/7. Bez niego Task 6–8 są zablokowane.
- **Dostęp edytora do Figmy** dla `dev@example.test` (plik `KFG3sWtSSluZQMGmhE3IrW`) — potrzebny w Task 5 do pobrania tokenów. Bez niego Task 5 stawia tylko strukturę, wartości uzupełniamy po uzyskaniu dostępu.
- **Zainstalowane lokalnie:** DDEV + Docker, Composer 2, Node 20+ z Yarn. (PHP dostarcza DDEV; Node/Yarn działają na hoście.)

## Global Constraints

- **Katalog projektu:** `/home/kamil/Repos/arpiaccounting-theme` (istniejące repo git). Bedrock w root, docroot = `web/`.
- **PHP = 8.2**, **Node >= 20**, **Composer 2**.
- **Motyw:** `web/app/themes/arpi` (slug `arpi`, text domain `arpi`).
- **Local JSON:** wszystkie grupy pól, CPT i taksonomie zapisywane do `web/app/themes/arpi/acf-json/` i commitowane.
- **Zgodność nazw (krytyczne dla późniejszej migracji WXR):** klucze CPT i nazwy pól MUSZĄ być identyczne z legacy. Realny CPT do odtworzenia w tej fazie: `dbip-chapters` + taksonomia `chapter-name`, pola: `dbip-date`, `dbip-version`, `chapter-introduction`.
- **Prezentacyjne CPT-y legacy** (`footer`, `menu`, `popup`, `slider`, `map`, `cta`, `section_title`, `parallax`, `whyarpi`, `about`, `branze`, `rada`, `meta`, `cookies`) **NIE** są odtwarzane jako CPT — staną się grupami pól ACF na stronach; klasyfikacja w fazie audytu/migracji.
- **Sekcje stron** konsumują dane wyłącznie przez **view composer** — w Blade **nigdy** nie wołamy `get_field()` bezpośrednio.
- **i18n:** wszystkie stringi w szablonach owinięte w `__()/_e()` z text domain `arpi`.
- **Nie commitujemy:** `web/wp/`, `vendor/`, `web/app/uploads/`, `.env`, `public/` motywu, `node_modules/` (obsłużone przez `.gitignore` Bedrocka i Sage).

---

### Task 1: Scaffold Bedrock w repo, legacy do reference/

**Files:**
- Create: `composer.json`, `config/application.php`, `config/environments/*`, `web/index.php`, `web/wp-config.php`, `.env.example`, `.gitignore` (z scaffoldu Bedrock)
- Move: `twentyseventeen/` → `reference/legacy-theme/`

**Interfaces:**
- Produces: struktura Bedrock w root repo; katalog `web/app/themes/` gotowy na motyw; legacy motyw zachowany w `reference/legacy-theme/` do audytu parytetu i weryfikacji nazw pól.

- [ ] **Step 1: Przenieś legacy motyw do reference/**

```bash
cd /home/kamil/Repos/arpiaccounting-theme
mkdir -p reference
git mv twentyseventeen reference/legacy-theme 2>/dev/null || mv twentyseventeen reference/legacy-theme
```

- [ ] **Step 2: Zescaffolduj Bedrock do katalogu tymczasowego i wsyp do root**

```bash
composer create-project roots/bedrock /tmp/arpi-bedrock
rsync -a --exclude='.git' /tmp/arpi-bedrock/ /home/kamil/Repos/arpiaccounting-theme/
rm -rf /tmp/arpi-bedrock
```

- [ ] **Step 3: Zweryfikuj strukturę**

Run:
```bash
ls -d web config composer.json .env.example && ls web/app/themes
```
Expected: wypisane `web config composer.json .env.example` oraz katalog `web/app/themes` (pusty lub z domyślnymi motywami WP).

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore: scaffold Bedrock, move legacy theme to reference/"
```

---

### Task 2: DDEV + lokalna instalacja WordPress

**Files:**
- Create: `.ddev/config.yaml` (przez `ddev config`)
- Create: `.env` (Bedrock, gitignored)

**Interfaces:**
- Consumes: struktura Bedrock z Task 1.
- Produces: działająca lokalna instalacja WP pod `https://arpiaccounting-theme.ddev.site`; WP-CLI dostępne przez `ddev wp`.

- [ ] **Step 1: Skonfiguruj DDEV pod Bedrock**

```bash
ddev config --project-type=wordpress --docroot=web --php-version=8.2
```

- [ ] **Step 2: Utwórz .env Bedrocka z danymi DDEV + solami**

```bash
cat > .env <<'EOF'
DB_NAME=db
DB_USER=db
DB_PASSWORD=db
DB_HOST=db
WP_ENV=development
WP_HOME=https://arpiaccounting-theme.ddev.site
WP_SITEURL=${WP_HOME}/wp
EOF
for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
  echo "$k='$(openssl rand -base64 48 | tr -d '\n')'" >> .env
done
```

- [ ] **Step 3: Uruchom DDEV, zainstaluj zależności, zainstaluj WP**

```bash
ddev start
ddev composer install
ddev wp core install \
  --url=https://arpiaccounting-theme.ddev.site \
  --title="ARPI Accounting" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=dev@example.com
```

- [ ] **Step 4: Zweryfikuj, że WP działa**

Run:
```bash
ddev wp core version && curl -sk https://arpiaccounting-theme.ddev.site/wp/wp-login.php | grep -o "<title>[^<]*</title>" | head -1
```
Expected: numer wersji WP (np. `6.x`) i tytuł strony logowania (zawiera „Log In").

- [ ] **Step 5: Commit**

```bash
git add .ddev/config.yaml .env.example
git commit -m "chore: DDEV config for Bedrock (docroot web, php 8.2)"
```

---

### Task 3: Instalacja i aktywacja motywu Sage 10

**Files:**
- Create: `web/app/themes/arpi/` (scaffold Sage)

**Interfaces:**
- Consumes: działający WP z Task 2.
- Produces: aktywny motyw `arpi` (Sage 10) z Acornem; katalog `resources/views` z Blade.

- [ ] **Step 1: Utwórz projekt Sage w katalogu motywu (wewnątrz DDEV — zgodność PHP)**

```bash
ddev composer create-project roots/sage web/app/themes/arpi
```

- [ ] **Step 2: Aktywuj motyw**

```bash
ddev wp theme activate arpi
```

- [ ] **Step 3: Zweryfikuj aktywację i render Blade**

Run:
```bash
ddev wp theme list --status=active --field=name && curl -sk https://arpiaccounting-theme.ddev.site/ -o /dev/null -w "%{http_code}\n"
```
Expected: `arpi` oraz kod HTTP `200`.

- [ ] **Step 4: Commit**

```bash
git add web/app/themes/arpi
git commit -m "feat: install and activate Sage 10 theme (arpi)"
```

---

### Task 4: Build assetów + HMR (Bud) działające

**Files:**
- Modify: `web/app/themes/arpi/bud.config.js`
- Modify: `web/app/themes/arpi/resources/css/app.css`

**Interfaces:**
- Consumes: motyw z Task 3.
- Produces: działający `yarn dev` z HMR proxującym stronę DDEV; produkcyjny `yarn build` generujący manifest w `public/`.

- [ ] **Step 1: Zainstaluj zależności front-endu (na hoście)**

```bash
cd web/app/themes/arpi
yarn
```

- [ ] **Step 2: Ustaw proxy Bud na stronę DDEV**

W `web/app/themes/arpi/bud.config.js` dodaj konfigurację dev-servera (wewnątrz głównej funkcji `app => { ... }`):

```js
app
  .serve('http://0.0.0.0:3000')
  .proxy('https://arpiaccounting-theme.ddev.site')
  .watch(['resources/views/**/*', 'app/**/*']);
```

- [ ] **Step 3: Zbuduj produkcyjnie i sprawdź manifest**

Run:
```bash
yarn build && ls public/ && test -f public/manifest.json && echo MANIFEST_OK
```
Expected: lista plików w `public/` oraz `MANIFEST_OK`.

- [ ] **Step 4: Uruchom dev server i zweryfikuj HMR ręcznie**

Run w osobnym terminalu: `yarn dev`
Otwórz URL wypisany przez Bud (np. `http://localhost:3000`).
W `resources/css/app.css` dodaj `body { background: rgb(255 0 0); }`, zapisz.
Expected: tło strony w przeglądarce zmienia się na czerwone **bez pełnego przeładowania** (HMR). Cofnij zmianę po weryfikacji.

- [ ] **Step 5: Commit**

```bash
cd /home/kamil/Repos/arpiaccounting-theme
git add web/app/themes/arpi/bud.config.js
git commit -m "feat: bud dev-server proxy + HMR for DDEV site"
```

---

### Task 5: Architektura tokenów Tailwind + import z Figmy

**Files:**
- Create: `web/app/themes/arpi/resources/tokens.js`
- Modify: `web/app/themes/arpi/tailwind.config.js`

**Interfaces:**
- Consumes: działający Tailwind z Task 4.
- Produces: `tokens` (obiekt JS: `colors`, `fontFamily`, `spacing`, `borderRadius`) wpięte w `theme.extend` Tailwinda; klasy oparte na tokenach renderują wartości z Figmy.

- [ ] **Step 1: Pobierz tokeny z Figmy (wymaga dostępu edytora)**

Użyj narzędzia Figma MCP `get_variable_defs` dla pliku `KFG3sWtSSluZQMGmhE3IrW`, aby pobrać zmienne (kolory, typografia, spacing). Jeśli plik ma zdefiniowane node'y stron, najpierw `get_metadata` bez `nodeId` po listę stron, potem `get_variable_defs` dla właściwego węzła.
Jeśli dostęp jeszcze nie przyznany: utwórz `tokens.js` ze strukturą i wartościami tymczasowymi z projektu (do podmiany), i oznacz commit jako `wip`.

- [ ] **Step 2: Zapisz tokeny jako moduł JS**

W `web/app/themes/arpi/resources/tokens.js` (przykład struktury — wartości z kroku 1):

```js
module.exports = {
  colors: {
    // np. z Figma variables:
    brand: { DEFAULT: '#0B4F6C', dark: '#073B50' },
    accent: '#F5A623',
  },
  fontFamily: {
    sans: ['Inter', 'system-ui', 'sans-serif'],
  },
  spacing: {
    section: '6rem',
  },
  borderRadius: {
    card: '1rem',
  },
};
```

- [ ] **Step 3: Wepnij tokeny w Tailwind**

W `web/app/themes/arpi/tailwind.config.js`:

```js
const tokens = require('./resources/tokens');

module.exports = {
  content: ['./resources/views/**/*.blade.php', './app/**/*.php'],
  theme: {
    extend: {
      colors: tokens.colors,
      fontFamily: tokens.fontFamily,
      spacing: tokens.spacing,
      borderRadius: tokens.borderRadius,
    },
  },
  plugins: [],
};
```

- [ ] **Step 4: Zweryfikuj, że token daje klasę CSS**

Run:
```bash
cd web/app/themes/arpi
yarn build && grep -R "bg-brand" public/ 2>/dev/null; npx tailwindcss -i resources/css/app.css -o /tmp/tw-check.css --content <(echo '<div class="bg-brand text-accent"></div>') && grep -q "background-color" /tmp/tw-check.css && echo TOKENS_OK
```
Expected: `TOKENS_OK` (klasa `bg-brand` generuje `background-color` z wartością tokenu).

- [ ] **Step 5: Commit**

```bash
cd /home/kamil/Repos/arpiaccounting-theme
git add web/app/themes/arpi/resources/tokens.js web/app/themes/arpi/tailwind.config.js
git commit -m "feat: Tailwind design tokens from Figma"
```

---

### Task 6: ACF PRO przez Composer (wymaga licencji)

**Files:**
- Modify: `composer.json` (repozytorium + require)
- Modify: `.env` (klucz `ACF_PRO_KEY`)
- Modify: `web/app/themes/arpi/app/setup.php` (punkty save/load Local JSON)

**Interfaces:**
- Consumes: WP z Task 2, motyw z Task 3.
- Produces: aktywna wtyczka `advanced-custom-fields-pro`; katalog `acf-json/` w motywie jako punkt zapisu/odczytu Local JSON.

- [ ] **Step 1: Dodaj repozytorium ACF PRO do composer.json**

W `composer.json` dodaj do tablicy `repositories` (obok istniejącego wpackagist):

```json
{
  "type": "package",
  "package": {
    "name": "advanced-custom-fields/advanced-custom-fields-pro",
    "version": "6.3.11",
    "type": "wordpress-plugin",
    "dist": {
      "type": "zip",
      "url": "https://connect.advancedcustomfields.com/index.php?p=pro&a=download&t=6.3.11"
    },
    "require": {
      "pivvenit/acf-pro-installer": "^4.0",
      "composer/installers": "^1.4 || ^2.0"
    }
  }
}
```

- [ ] **Step 2: Zapisz klucz licencyjny i zainstaluj (klucz wstrzyknięty do kontenera)**

```bash
echo "ACF_PRO_KEY='<KLUCZ_LICENCYJNY_ACF>'" >> .env
export ACF_PRO_KEY='<KLUCZ_LICENCYJNY_ACF>'
ddev exec bash -c "ACF_PRO_KEY='$ACF_PRO_KEY' composer require pivvenit/acf-pro-installer:^4.0 advanced-custom-fields/advanced-custom-fields-pro:6.3.11"
ddev wp plugin activate advanced-custom-fields-pro
```

- [ ] **Step 3: Ustaw punkty Local JSON w motywie**

W `web/app/themes/arpi/app/setup.php` dodaj:

```php
add_filter('acf/settings/save_json', function () {
    return get_theme_file_path('acf-json');
});
add_filter('acf/settings/load_json', function ($paths) {
    $paths[0] = get_theme_file_path('acf-json');
    return $paths;
});
```

Utwórz katalog:
```bash
mkdir -p web/app/themes/arpi/acf-json
```

- [ ] **Step 4: Zweryfikuj instalację i punkt Local JSON**

Run:
```bash
ddev wp plugin list --name=advanced-custom-fields-pro --field=status && test -d web/app/themes/arpi/acf-json && echo ACFJSON_OK
```
Expected: `active` oraz `ACFJSON_OK`.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock web/app/themes/arpi/app/setup.php web/app/themes/arpi/acf-json/.gitkeep
git commit -m "feat: ACF PRO via Composer + Local JSON in theme"
```

---

### Task 7: Model treści — CPT `dbip-chapters` + taksonomia w Local JSON

**Files:**
- Create (przez ACF UI → Local JSON): `web/app/themes/arpi/acf-json/post_type_*.json`, `taxonomy_*.json`, `group_*.json`

**Interfaces:**
- Consumes: ACF PRO + Local JSON z Task 6.
- Produces: zarejestrowany CPT `dbip-chapters`, taksonomia `chapter-name`, grupa pól `dbip` z polami `dbip-date` (Date Picker), `dbip-version` (Text), `chapter-introduction` (Wysiwyg) — wszystko wersjonowane w `acf-json/`.

- [ ] **Step 1: Zweryfikuj nazwy pól legacy (muszą się zgadzać)**

Run:
```bash
grep -rhoE "get_field\(\s*'[^']+'|the_field\(\s*'[^']+'" reference/legacy-theme/single-dbip-chapters.php reference/legacy-theme/archive-dbip-chapters.php reference/legacy-theme/taxonomy-chapter-name.php 2>/dev/null | grep -oE "'[^']+'" | sort -u
```
Expected: lista zawierająca `'dbip-date'`, `'dbip-version'`, `'chapter-introduction'` (i ew. inne — użyj dokładnie tych nazw przy tworzeniu pól).

- [ ] **Step 2: Utwórz CPT i taksonomię w ACF UI**

W `https://arpiaccounting-theme.ddev.site/wp/wp-admin/` → ACF → Post Types → Add New:
- Post Type Key: `dbip-chapters`, Plural: „DBIP Chapters", Single: „DBIP Chapter".
ACF → Taxonomies → Add New:
- Taxonomy Key: `chapter-name`, powiązana z post type `dbip-chapters`.

- [ ] **Step 3: Utwórz grupę pól `dbip`**

ACF → Field Groups → Add New: nazwa grupy `dbip`, lokalizacja: Post Type == `dbip-chapters`. Pola:
- `dbip-date` — typ Date Picker
- `dbip-version` — typ Text
- `chapter-introduction` — typ Wysiwyg Editor

Zapisz każdy element (ACF wygeneruje pliki JSON w `acf-json/`).

- [ ] **Step 4: Zweryfikuj rejestrację i Local JSON**

Run:
```bash
ddev wp post-type list --field=name | grep -x dbip-chapters && ddev wp taxonomy list --field=name | grep -x chapter-name && ls web/app/themes/arpi/acf-json/*.json && echo MODEL_OK
```
Expected: `dbip-chapters`, `chapter-name`, pliki `.json` w `acf-json/`, `MODEL_OK`.

- [ ] **Step 5: Commit**

```bash
git add web/app/themes/arpi/acf-json/
git commit -m "feat: dbip-chapters CPT + chapter-name taxonomy + fields (Local JSON)"
```

---

### Task 8: Wzorzec sekcji sterowanej view composerem (+ test helpera)

**Files:**
- Create: `web/app/themes/arpi/app/helpers.php`
- Create: `web/app/themes/arpi/app/View/Composers/FrontPage.php`
- Create: `web/app/themes/arpi/resources/views/front-page.blade.php`
- Create: `web/app/themes/arpi/resources/views/sections/hero.blade.php`
- Create: `web/app/themes/arpi/tests/Unit/FieldOrTest.php`
- Modify: `web/app/themes/arpi/composer.json` (autoload helpers.php + skrypt testów)

**Interfaces:**
- Consumes: ACF z Task 6, Blade/Sage z Task 3.
- Produces: pure-funkcja `Arpi\field_or(mixed $value, mixed $default): mixed`; view composer `FrontPage` udostępniający `$hero` do widoku; sekcja hero renderująca treść z fallbackiem.

- [ ] **Step 1: Napisz failing test dla `field_or`**

`web/app/themes/arpi/tests/Unit/FieldOrTest.php`:

```php
<?php
use function Arpi\field_or;

it('returns the value when it is non-empty', function () {
    expect(field_or('Witaj', 'domyślne'))->toBe('Witaj');
});

it('returns the default when the value is empty', function () {
    expect(field_or('', 'domyślne'))->toBe('domyślne');
    expect(field_or(null, 'domyślne'))->toBe('domyślne');
    expect(field_or(false, 'domyślne'))->toBe('domyślne');
});
```

- [ ] **Step 2: Uruchom test — ma FAILować**

Run:
```bash
cd web/app/themes/arpi && ./vendor/bin/pest tests/Unit/FieldOrTest.php
```
Expected: FAIL — `function Arpi\field_or not found` (Pest jest zależnością Sage 10; jeśli brak: `ddev composer require --dev pestphp/pest`).

- [ ] **Step 3: Zaimplementuj `field_or` i podepnij autoload**

`web/app/themes/arpi/app/helpers.php`:

```php
<?php

namespace Arpi;

/**
 * Zwraca wartość pola albo fallback, gdy wartość jest pusta.
 * Pozwala budować sekcje z hardcode teraz i podmienić na get_field() później.
 */
function field_or($value, $default)
{
    return ($value === null || $value === false || $value === '') ? $default : $value;
}
```

W `web/app/themes/arpi/composer.json` w sekcji `autoload.files` dodaj `"app/helpers.php"` (jeśli sekcja nie istnieje, utwórz):

```json
"autoload": {
  "psr-4": { "App\\": "app/" },
  "files": ["app/helpers.php"]
}
```

Przeładuj autoload:
```bash
ddev composer dump-autoload -d web/app/themes/arpi
```

- [ ] **Step 4: Uruchom test — ma PRZEJŚĆ**

Run:
```bash
cd web/app/themes/arpi && ./vendor/bin/pest tests/Unit/FieldOrTest.php
```
Expected: PASS (3 asercje zielone).

- [ ] **Step 5: Napisz view composer strony głównej**

`web/app/themes/arpi/app/View/Composers/FrontPage.php`:

```php
<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use function Arpi\field_or;

class FrontPage extends Composer
{
    protected static $views = ['front-page'];

    public function with()
    {
        return [
            'hero' => [
                // Dziś: hardcode. Później podmiana wartości na get_field('hero_text') itd.
                'title' => field_or(get_field('hero_title') ?: null, 'ARPI Accounting'),
                'text'  => field_or(get_field('hero_text') ?: null, 'Nowoczesna księgowość dla Twojej firmy.'),
            ],
        ];
    }
}
```

- [ ] **Step 6: Utwórz widoki front-page i sekcję hero**

`web/app/themes/arpi/resources/views/front-page.blade.php`:

```blade
@extends('layouts.app')
@section('content')
  @include('sections.hero', ['hero' => $hero])
@endsection
```

`web/app/themes/arpi/resources/views/sections/hero.blade.php`:

```blade
<section class="bg-brand text-white py-section">
  <div class="container mx-auto">
    <h1 class="text-4xl font-sans">{{ $hero['title'] }}</h1>
    <p class="mt-4">{{ $hero['text'] }}</p>
  </div>
</section>
```

- [ ] **Step 7: Zweryfikuj render i fallback**

Run:
```bash
ddev wp option update show_on_front posts >/dev/null; curl -sk https://arpiaccounting-theme.ddev.site/ | grep -q "Nowoczesna księgowość" && echo FALLBACK_OK
```
Expected: `FALLBACK_OK` — sekcja renderuje treść fallback (brak wartości ACF ⇒ hardcode).
(Opcjonalnie: utwórz grupę pól `hero_title`/`hero_text` na Front Page w ACF, ustaw wartość, odśwież — pojawia się treść z pola zamiast fallbacku.)

- [ ] **Step 8: Commit**

```bash
cd /home/kamil/Repos/arpiaccounting-theme
git add web/app/themes/arpi/app web/app/themes/arpi/resources web/app/themes/arpi/tests web/app/themes/arpi/composer.json
git commit -m "feat: view-composer-driven section pattern with field_or fallback + test"
```

---

## Self-Review

**Spec coverage (§ z dokumentu projektowego):**
- Bedrock + docroot `web/` → Task 1, 2. ✅
- Sage 10 (Blade/Acorn/Bud/Tailwind) → Task 3, 4. ✅
- HMR → Task 4. ✅
- ACF PRO legalny → Task 6 (gated na licencję). ✅
- CPT + pola w Local JSON, klucze/nazwy 1:1 → Task 7 (`dbip-chapters`, `chapter-name`, pola z legacy). ✅
- Sekcje sterowane ACF przez view composer, hardcode-then-`get_field` → Task 8. ✅
- Tokeny z Figmy → Tailwind → Task 5 (gated na dostęp Figma). ✅
- Prezentacyjne CPT-y jako ACF (nie CPT) → ujęte w Global Constraints; pełna klasyfikacja to faza audytu (poza fazą 1). ✅
- Polylang, staging/prod/cyberFolks, deploy CI, migracja WXR/MailPoet → **poza fazą 1** (fazy 2–6). ✅ (świadomie poza zakresem)

**Placeholder scan:** wartości tokenów (Task 5) i klucz ACF/nazwy pól (Task 6/7) to dane pozyskiwane wykonalnymi krokami (Figma MCP / grep legacy / .env), nie luki planu. Wersja ACF 6.3.11 pinowana (podnieś do najnowszej jeśli dostępna).

**Type consistency:** `field_or` — sygnatura i nazwa spójne między Task 8 Step 1/3/5. View composer produkuje `$hero` (klucze `title`, `text`) konsumowane w `front-page.blade.php` i `sections/hero.blade.php`. Punkty Local JSON (Task 6) i pliki JSON (Task 7) używają tego samego katalogu `acf-json/`.
