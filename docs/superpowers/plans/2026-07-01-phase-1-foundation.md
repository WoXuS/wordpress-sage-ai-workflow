# ARPI Rewrite — Faza 1: Fundament lokalny (Implementation Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Postawić działający lokalny fundament: standardowy WordPress (`public_html`) w multi-container docker-compose + Makefile, z konfiguracją przez `.env`, motywem Sage + Vite (HMR), Tailwindem na tokenach z Figmy, ACF PRO, modelem treści (CPT + pierwsza grupa pól) w Local JSON oraz wzorcem sekcji sterowanej view composerem.

**Architecture:** Standardowy layout WP (`public_html`) — BEZ Bedrocka. Sekrety w `.env` nad `public_html` (poza docrootem), czytane przez env-aware `wp-config.php` (phpdotenv). Lokalny stack: osobne kontenery `php` (php-fpm 8.3 + wp-cli + composer), `nginx`, `db` (MariaDB 10.11); Node/Vite na hoście. W git tylko motyw `wp-content/themes/arpi` + `wp-config.php`; core, wtyczki, uploads ignorowane. Motyw Sage: Blade + Acorn (view composers) + Vite (HMR 5173) + Tailwind. Model treści (CPT/taksonomie/pola) w ACF PRO, wersjonowany jako Local JSON. Sekcje stron dostają dane przez view composery (dziś hardcode, później `get_field()` bez zmiany szablonu).

**Tech Stack:** docker-compose + Makefile, PHP 8.3-fpm, nginx, MariaDB 10.11, WP-CLI, Composer 2, vlucas/phpdotenv, Sage (Vite + Tailwind), ACF PRO 6.x, Node 20+ (host) + Yarn, Polylang (konfiguracja w kolejnej fazie).

## Prerequisites (zależności zewnętrzne — potwierdzić PRZED wykonaniem)

- **Klucz licencyjny ACF PRO** (klient wykupuje subskrypcję) — Task 7. Bez niego Task 7–9 zablokowane.
- **Dostęp edytora do Figmy** dla `dev@example.test` (plik `KFG3sWtSSluZQMGmhE3IrW`) — Task 6. Bez niego Task 6 stawia tylko strukturę tokenów, wartości uzupełnia się po dostępie.
- **Narzędzia na hoście:** Docker + `docker compose`, Node 20+ z Yarn (jest — corepack). Composer/PHP na hoście opcjonalne (Composer uruchamiamy w kontenerze).

## Global Constraints

- **Katalog repo:** `/home/kamil/Repos/arpiaccounting-theme` (remote `origin` = prywatne GitHub `WoXuS/arpiaccounting-web`, tożsamość `Kamil Woźniak <91374040+WoXuS@users.noreply.github.com>`).
- **Layout:** standardowy WP w `public_html/`; `.env`, `vendor/`, `composer.json` w **root repo** (nad `public_html`).
- **PHP = 8.3**, **MariaDB = 10.11**, **Node >= 20** (host), **Composer 2** (w kontenerze).
- **Dostęp lokalny:** `http://localhost:8080` (nginx). Vite HMR na `http://localhost:5173`.
- **Motyw:** `public_html/wp-content/themes/arpi` (slug `arpi`, text domain `arpi`).
- **Local JSON:** wszystkie grupy pól, CPT i taksonomie w `public_html/wp-content/themes/arpi/acf-json/`, commitowane.
- **Zgodność nazw (krytyczne dla późniejszej migracji WXR):** klucze CPT i nazwy pól identyczne z legacy. Realny CPT w tej fazie: `dbip-chapters` + taksonomia `chapter-name`, pola: `dbip-date`, `dbip-version`, `chapter-introduction`.
- **Prezentacyjne CPT-y legacy** (`footer`, `menu`, `popup`, `slider`, `map`, `cta`, `section_title`, `parallax`, `whyarpi`, `about`, `branze`, `rada`, `meta`, `cookies`) **NIE** odtwarzane jako CPT — staną się grupami pól ACF; klasyfikacja w fazie audytu.
- **Sekcje stron** konsumują dane wyłącznie przez **view composer** — w Blade **nigdy** `get_field()` bezpośrednio.
- **i18n:** stringi w szablonach w `__()/_e()` z text domain `arpi`.
- **W git NIE trafiają:** `vendor/`, `.env`, `certs/`, core WP, `wp-content/plugins`, `wp-content/uploads`, `node_modules/`, `public/` motywu (obsłużone przez `.gitignore` z Task 1).
- **Deploy dotyka tylko motywu** — nigdy `wp-content/plugins` (klient zarządza wtyczkami w adminie).

---

### Task 1: Layout repo, legacy do reference/, .gitignore

**Files:**
- Move: `twentyseventeen/` → `reference/legacy-theme/`
- Create: `public_html/wp-content/` (katalogi), `etc/nginx/`, `etc/php/`
- Create: `.gitignore`

**Interfaces:**
- Produces: szkielet katalogów projektu; legacy motyw w `reference/legacy-theme/` do audytu i weryfikacji nazw pól; `.gitignore` chroniący core/wtyczki/sekrety.

- [ ] **Step 1: Przenieś legacy i utwórz katalogi**

```bash
cd /home/kamil/Repos/arpiaccounting-theme
mkdir -p reference
git mv twentyseventeen reference/legacy-theme 2>/dev/null || mv twentyseventeen reference/legacy-theme
mkdir -p public_html/wp-content/themes etc/nginx etc/php
```

- [ ] **Step 2: Napisz .gitignore**

`.gitignore`:

```gitignore
# deps / secrets
/vendor/
/.env
/certs/

# WordPress: trzymamy tylko wp-config.php i motyw arpi
/public_html/*
!/public_html/wp-config.php
!/public_html/wp-content/
/public_html/wp-content/*
!/public_html/wp-content/themes/
/public_html/wp-content/themes/*
!/public_html/wp-content/themes/arpi/

# artefakty buildu motywu
/public_html/wp-content/themes/arpi/node_modules/
/public_html/wp-content/themes/arpi/vendor/
/public_html/wp-content/themes/arpi/public/
```

- [ ] **Step 3: Zweryfikuj strukturę**

Run:
```bash
ls -d reference/legacy-theme public_html/wp-content/themes etc/nginx etc/php && echo STRUCT_OK
```
Expected: wypisane ścieżki + `STRUCT_OK`.

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "chore: repo layout (public_html), legacy -> reference, gitignore"
```

---

### Task 2: Stack Docker (Dockerfile + compose + nginx + Makefile)

**Files:**
- Create: `Dockerfile`, `docker-compose.yml`, `docker-compose.override.yml`
- Create: `etc/nginx/default.conf`, `etc/php/php.ini`
- Create: `Makefile`

**Interfaces:**
- Consumes: layout z Task 1.
- Produces: `make up` uruchamia `php` + `nginx` + `db`; nginx nasłuchuje na `localhost:8080`; Makefile z targetami `up/down/build/rebuild/logs/shell/wp/composer`.

- [ ] **Step 1: Dockerfile (php-fpm + rozszerzenia + wp-cli + composer)**

`Dockerfile`:

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
      curl ca-certificates git unzip less default-mysql-client \
      libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev \
  && docker-php-ext-configure gd --with-jpeg --with-freetype \
  && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli gd zip exif intl opcache \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
  && chmod +x /usr/local/bin/wp

COPY etc/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/app/public_html
```

- [ ] **Step 2: php.ini + nginx config**

`etc/php/php.ini`:

```ini
upload_max_filesize = 128M
post_max_size = 128M
memory_limit = 512M
max_execution_time = 300
```

`etc/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/app/public_html;
    index index.php;
    client_max_body_size 128M;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* /\.(?!well-known) { deny all; }
}
```

- [ ] **Step 3: docker-compose.yml + override**

`docker-compose.yml`:

```yaml
services:
  db:
    image: mariadb:10.11
    command: --max_allowed_packet=256M
    environment:
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-root}
      MARIADB_DATABASE: ${DB_NAME:-arpi}
      MARIADB_USER: ${DB_USER:-arpi}
      MARIADB_PASSWORD: ${DB_PASSWORD:-arpi}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 10

  php:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - ./:/var/app
    working_dir: /var/app/public_html
    depends_on:
      db:
        condition: service_healthy

  nginx:
    image: nginx:1.27-alpine
    volumes:
      - ./:/var/app:ro
      - ./etc/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    ports:
      - "8080:80"
    depends_on:
      - php

volumes:
  db_data:
```

`docker-compose.override.yml` (dev — wystawia port bazy dla klientów GUI):

```yaml
services:
  db:
    ports:
      - "3307:3306"
```

- [ ] **Step 4: Makefile**

`Makefile`:

```makefile
DC    = docker compose
PHP   = $(DC) exec php
THEME = wp-content/themes/arpi

.DEFAULT_GOAL := help

help: ## Ten ekran pomocy
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "\033[32m%-16s\033[0m %s\n",$$1,$$2}'

up: ## Start kontenerów
	$(DC) up -d

down: ## Stop kontenerów
	$(DC) down --remove-orphans

build: ## Zbuduj obraz php
	$(DC) build

rebuild: ## Przebuduj i wystartuj
	$(DC) down --remove-orphans
	$(DC) build
	$(DC) up -d

logs: ## Podgląd logów
	$(DC) logs -f

shell: ## Wejście do kontenera php
	$(PHP) bash

wp: ## WP-CLI, np. make wp ARGS="plugin list"
	$(PHP) wp --allow-root $(ARGS)

composer: ## Composer w motywie, np. make composer ARGS="require x"
	$(PHP) sh -c "cd $(THEME) && composer $(ARGS)"

theme-install: ## Zależności motywu (composer w kontenerze + yarn na hoście)
	$(PHP) sh -c "cd $(THEME) && composer install"
	cd $(THEME) && yarn install

dev: ## Vite dev server + HMR (host)
	cd $(THEME) && yarn dev

build-assets: ## Build assetów motywu (host)
	cd $(THEME) && yarn build

import-db: ## Import zrzutu: make import-db FILE=dump.sql.gz
	gzip -cd $(FILE) | $(DC) exec -T db sh -c 'exec mariadb -u root -p"$$MARIADB_ROOT_PASSWORD" "$$MARIADB_DATABASE"'

dump-db: ## Zrzut bazy: make dump-db FILE=dump.sql.gz
	$(DC) exec -T db sh -c 'exec mariadb-dump -u root -p"$$MARIADB_ROOT_PASSWORD" "$$MARIADB_DATABASE"' | gzip > $(FILE)
```

- [ ] **Step 5: Zbuduj i uruchom, zweryfikuj kontenery**

Run:
```bash
make build && make up && sleep 8
docker compose ps --format '{{.Service}} {{.State}}'
make wp ARGS="--info" 2>&1 | grep -i "PHP version" || docker compose exec php php -v | head -1
```
Expected: `db running`, `php running`, `nginx running`; PHP 8.3.x. (nginx zwróci 403/404 dopóki nie ma WP — to OK.)

- [ ] **Step 6: Commit**

```bash
git add Dockerfile docker-compose.yml docker-compose.override.yml etc Makefile
git commit -m "feat: multi-container docker stack (php/nginx/mariadb) + Makefile"
```

---

### Task 3: Konfiguracja przez .env + env-aware wp-config + instalacja WP

**Files:**
- Create: `composer.json` (root — phpdotenv), `.env.example`, `.env`
- Create: `public_html/wp-config.php`

**Interfaces:**
- Consumes: stack z Task 2.
- Produces: działający WP pod `http://localhost:8080`; `wp-config.php` czytający konfigurację z `.env`; WP-CLI przez `make wp`.

- [ ] **Step 1: Root composer.json + instalacja phpdotenv**

`composer.json`:

```json
{
    "name": "arpi/site",
    "description": "ARPI Accounting website",
    "require": {
        "vlucas/phpdotenv": "^5.6"
    },
    "config": {
        "optimize-autoloader": true
    }
}
```

Zainstaluj (w kontenerze, katalog root repo → `/var/app`):
```bash
docker compose exec php sh -c "cd /var/app && composer install"
```

- [ ] **Step 2: .env.example + .env (z solami)**

`.env.example`:

```dotenv
DB_NAME=arpi
DB_USER=arpi
DB_PASSWORD=arpi
DB_ROOT_PASSWORD=root
DB_HOST=db
DB_PREFIX=wp_

WP_ENV=development
WP_HOME=http://localhost:8080
WP_DEBUG=true

ACF_PRO_KEY=

# Sole — wygenerowane per instalacja (patrz niżej)
AUTH_KEY=
SECURE_AUTH_KEY=
LOGGED_IN_KEY=
NONCE_KEY=
AUTH_SALT=
SECURE_AUTH_SALT=
LOGGED_IN_SALT=
NONCE_SALT=
```

Utwórz `.env` z example i dogeneruj sole (base64 nie zawiera `$`, więc jest bezpieczne dla docker-compose):
```bash
cp .env.example .env
for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
  sed -i "s|^$k=.*|$k='$(openssl rand -base64 48 | tr -d '\n')'|" .env
done
```

- [ ] **Step 3: env-aware wp-config.php**

`public_html/wp-config.php`:

```php
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

function arpi_env($key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($v === false || $v === null) ? $default : $v;
}

define('DB_NAME', arpi_env('DB_NAME'));
define('DB_USER', arpi_env('DB_USER'));
define('DB_PASSWORD', arpi_env('DB_PASSWORD'));
define('DB_HOST', arpi_env('DB_HOST', 'db'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $k) {
    define($k, arpi_env($k, 'change-me'));
}

$table_prefix = arpi_env('DB_PREFIX', 'wp_');

define('WP_HOME', arpi_env('WP_HOME', 'http://localhost:8080'));
define('WP_SITEURL', WP_HOME);

define('WP_DEBUG', filter_var(arpi_env('WP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG', WP_DEBUG);
define('WP_DEBUG_DISPLAY', false);
define('WP_ENVIRONMENT_TYPE', arpi_env('WP_ENV', 'production'));
define('DISALLOW_FILE_EDIT', true);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
```

- [ ] **Step 4: Pobierz core WP i zainstaluj**

```bash
make wp ARGS="core download --force"
make wp ARGS="core install --url=http://localhost:8080 --title='ARPI Accounting' --admin_user=admin --admin_password=admin --admin_email=dev@example.com --skip-email"
```

- [ ] **Step 5: Zweryfikuj, że strona wstaje z konfiguracją z .env**

Run:
```bash
make wp ARGS="core version" && curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/ && make wp ARGS="option get home"
```
Expected: wersja WP; kod `200`; `http://localhost:8080`.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock .env.example public_html/wp-config.php
git commit -m "feat: env-aware wp-config (phpdotenv) + standard WP install"
```

---

### Task 4: Motyw Sage + aktywacja + zależności

**Files:**
- Create: `public_html/wp-content/themes/arpi/` (scaffold Sage)

**Interfaces:**
- Consumes: WP z Task 3.
- Produces: aktywny motyw `arpi` (Sage z Acornem, build Vite); `resources/views` (Blade).

- [ ] **Step 1: Utwórz projekt Sage (w kontenerze — zgodność PHP)**

```bash
docker compose exec php sh -c "cd wp-content/themes && composer create-project roots/sage arpi"
```

- [ ] **Step 2: Zainstaluj zależności motywu i aktywuj**

```bash
make theme-install
make wp ARGS="theme activate arpi"
```

- [ ] **Step 3: Zweryfikuj build tool (ma być Vite) i aktywację**

Run:
```bash
test -f public_html/wp-content/themes/arpi/vite.config.js && echo VITE_OK || echo "UWAGA: brak vite.config.js — sprawdź czy starter Sage używa Vite"
make wp ARGS="theme list --status=active --field=name"
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/
```
Expected: `VITE_OK`; `arpi`; kod `200`.

- [ ] **Step 4: Commit**

```bash
git add public_html/wp-content/themes/arpi
git commit -m "feat: install and activate Sage theme (arpi, Vite)"
```

---

### Task 5: Vite HMR działające

**Files:**
- Modify: `public_html/wp-content/themes/arpi/vite.config.js`

**Interfaces:**
- Consumes: motyw z Task 4.
- Produces: `make dev` uruchamia Vite na `localhost:5173`; edycja stylu → HMR bez pełnego przeładowania.

- [ ] **Step 1: Ustaw host/port/origin dev-servera Vite**

W `public_html/wp-content/themes/arpi/vite.config.js` w konfiguracji upewnij się, że `server` wskazuje na `localhost:5173` (WP renderuje się w kontenerze, ale przeglądarka na hoście ładuje assety z hosta):

```js
server: {
  host: 'localhost',
  port: 5173,
  strictPort: true,
  origin: 'http://localhost:5173',
},
```

- [ ] **Step 2: Zbuduj produkcyjnie (sanity manifestu)**

Run:
```bash
make build-assets && ls public_html/wp-content/themes/arpi/public && echo BUILD_OK
```
Expected: pliki w `public/` (m.in. `manifest.json` / katalog `build`) + `BUILD_OK`.

- [ ] **Step 3: Uruchom dev i zweryfikuj HMR ręcznie**

W osobnym terminalu: `make dev` (Vite na `http://localhost:5173`).
Otwórz `http://localhost:8080/`. W `resources/css/app.css` dodaj `body{background:rgb(255 0 0)}`, zapisz.
Expected: tło zmienia się na czerwone **bez pełnego przeładowania** (HMR działa — WP czyta plik `hot` z dev-servera). Cofnij zmianę.

- [ ] **Step 4: Commit**

```bash
git add public_html/wp-content/themes/arpi/vite.config.js
git commit -m "feat: Vite dev-server host/port for HMR"
```

---

### Task 6: Tokeny Tailwind z Figmy

**Files:**
- Create: `public_html/wp-content/themes/arpi/resources/tokens.js`
- Modify: `public_html/wp-content/themes/arpi/tailwind.config.js`

**Interfaces:**
- Consumes: Tailwind (default Sage) z Task 4/5.
- Produces: `tokens` (colors, fontFamily, spacing, borderRadius) w `theme.extend`; klasy z tokenów renderują wartości z Figmy.

- [ ] **Step 1: Pobierz zmienne z Figmy (wymaga dostępu edytora)**

Użyj Figma MCP: `get_metadata` (bez `nodeId`) po listę stron, potem `get_variable_defs` dla `KFG3sWtSSluZQMGmhE3IrW` po kolory/typografię/spacing.
Jeśli dostęp jeszcze nie przyznany: utwórz `tokens.js` ze strukturą i wartościami tymczasowymi z projektu graficznego (do podmiany), commit oznacz `wip`.

- [ ] **Step 2: tokens.js (wartości z kroku 1)**

`public_html/wp-content/themes/arpi/resources/tokens.js`:

```js
module.exports = {
  colors: {
    brand: { DEFAULT: '#0B4F6C', dark: '#073B50' },
    accent: '#F5A623',
  },
  fontFamily: {
    sans: ['Inter', 'system-ui', 'sans-serif'],
  },
  spacing: { section: '6rem' },
  borderRadius: { card: '1rem' },
};
```

- [ ] **Step 3: Wepnij tokeny w tailwind.config.js**

W `public_html/wp-content/themes/arpi/tailwind.config.js`:

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

- [ ] **Step 4: Zweryfikuj, że token daje klasę**

Run:
```bash
cd public_html/wp-content/themes/arpi
npx tailwindcss -i resources/css/app.css -o /tmp/tw.css --content <(echo '<div class="bg-brand text-accent p-section rounded-card"></div>') >/dev/null 2>&1 && grep -q "background-color" /tmp/tw.css && echo TOKENS_OK
```
Expected: `TOKENS_OK`.

- [ ] **Step 5: Commit**

```bash
cd /home/kamil/Repos/arpiaccounting-theme
git add public_html/wp-content/themes/arpi/resources/tokens.js public_html/wp-content/themes/arpi/tailwind.config.js
git commit -m "feat: Tailwind design tokens from Figma"
```

---

### Task 7: ACF PRO + Local JSON (wymaga licencji)

**Files:**
- Modify: `.env` (`ACF_PRO_KEY`)
- Modify: `public_html/wp-content/themes/arpi/app/setup.php` (punkty Local JSON)

**Interfaces:**
- Consumes: WP z Task 3, motyw z Task 4.
- Produces: aktywna wtyczka `advanced-custom-fields-pro`; `acf-json/` jako punkt zapisu/odczytu Local JSON.

- [ ] **Step 1: Zapisz klucz i zainstaluj ACF PRO przez WP-CLI**

```bash
sed -i "s|^ACF_PRO_KEY=.*|ACF_PRO_KEY='<KLUCZ_LICENCYJNY_ACF>'|" .env
make wp ARGS="plugin install 'https://connect.advancedcustomfields.com/index.php?a=download&p=pro&k=<KLUCZ_LICENCYJNY_ACF>' --activate"
```
(Instalujemy przez wp-cli, bo wtyczki zarządzamy normalnie — nie przez Composer. `wp-content/plugins` jest gitignored.)

- [ ] **Step 2: Punkty Local JSON w motywie**

W `public_html/wp-content/themes/arpi/app/setup.php` dodaj:

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
mkdir -p public_html/wp-content/themes/arpi/acf-json && touch public_html/wp-content/themes/arpi/acf-json/.gitkeep
```

- [ ] **Step 3: Zweryfikuj**

Run:
```bash
make wp ARGS="plugin list --name=advanced-custom-fields-pro --field=status" && test -d public_html/wp-content/themes/arpi/acf-json && echo ACF_OK
```
Expected: `active` + `ACF_OK`.

- [ ] **Step 4: Commit**

```bash
git add public_html/wp-content/themes/arpi/app/setup.php public_html/wp-content/themes/arpi/acf-json/.gitkeep
git commit -m "feat: ACF PRO (wp-cli install) + Local JSON in theme"
```

---

### Task 8: Model treści — CPT dbip-chapters + taksonomia w Local JSON

**Files:**
- Create (przez ACF UI → Local JSON): `.../acf-json/post_type_*.json`, `taxonomy_*.json`, `group_*.json`

**Interfaces:**
- Consumes: ACF PRO + Local JSON z Task 7.
- Produces: CPT `dbip-chapters`, taksonomia `chapter-name`, grupa pól `dbip` (`dbip-date` Date Picker, `dbip-version` Text, `chapter-introduction` Wysiwyg) — w `acf-json/`.

- [ ] **Step 1: Zweryfikuj nazwy pól legacy (muszą się zgadzać)**

Run:
```bash
grep -rhoE "get_field\(\s*'[^']+'|the_field\(\s*'[^']+'" reference/legacy-theme/single-dbip-chapters.php reference/legacy-theme/archive-dbip-chapters.php reference/legacy-theme/taxonomy-chapter-name.php 2>/dev/null | grep -oE "'[^']+'" | sort -u
```
Expected: lista zawierająca `'dbip-date'`, `'dbip-version'`, `'chapter-introduction'` — użyj dokładnie tych nazw.

- [ ] **Step 2: Utwórz CPT i taksonomię w ACF UI**

`http://localhost:8080/wp-admin/` → ACF → Post Types → Add New: klucz `dbip-chapters` (Plural „DBIP Chapters", Single „DBIP Chapter").
ACF → Taxonomies → Add New: klucz `chapter-name`, powiązana z `dbip-chapters`.

- [ ] **Step 3: Utwórz grupę pól `dbip`**

ACF → Field Groups → Add New: nazwa `dbip`, lokalizacja Post Type == `dbip-chapters`. Pola:
- `dbip-date` — Date Picker
- `dbip-version` — Text
- `chapter-introduction` — Wysiwyg Editor

Zapisz każdy element (ACF wygeneruje pliki w `acf-json/`).

- [ ] **Step 4: Zweryfikuj rejestrację i Local JSON**

Run:
```bash
make wp ARGS="post-type list --field=name" | grep -x dbip-chapters && make wp ARGS="taxonomy list --field=name" | grep -x chapter-name && ls public_html/wp-content/themes/arpi/acf-json/*.json && echo MODEL_OK
```
Expected: `dbip-chapters`, `chapter-name`, pliki `.json`, `MODEL_OK`.

- [ ] **Step 5: Commit**

```bash
git add public_html/wp-content/themes/arpi/acf-json/
git commit -m "feat: dbip-chapters CPT + chapter-name taxonomy + fields (Local JSON)"
```

---

### Task 9: Wzorzec sekcji sterowanej view composerem (+ test helpera)

**Files:**
- Create: `.../arpi/app/helpers.php`, `.../arpi/app/View/Composers/FrontPage.php`
- Create: `.../arpi/resources/views/front-page.blade.php`, `.../arpi/resources/views/sections/hero.blade.php`
- Create: `.../arpi/tests/Unit/FieldOrTest.php`
- Modify: `.../arpi/composer.json` (autoload `app/helpers.php`)

**Interfaces:**
- Consumes: ACF z Task 7, Blade/Sage z Task 4.
- Produces: pure-funkcja `Arpi\field_or(mixed $value, mixed $default): mixed`; composer `FrontPage` udostępniający `$hero` do widoku; sekcja hero z fallbackiem.

- [ ] **Step 1: Failing test dla `field_or` (Pest)**

`public_html/wp-content/themes/arpi/tests/Unit/FieldOrTest.php`:

```php
<?php
use function Arpi\field_or;

it('zwraca wartość gdy niepusta', function () {
    expect(field_or('Witaj', 'domyślne'))->toBe('Witaj');
});

it('zwraca fallback gdy pusto', function () {
    expect(field_or('', 'd'))->toBe('d');
    expect(field_or(null, 'd'))->toBe('d');
    expect(field_or(false, 'd'))->toBe('d');
});
```

- [ ] **Step 2: Uruchom test — ma FAILować**

Run:
```bash
docker compose exec php sh -c "cd $THEME 2>/dev/null; cd /var/app/public_html/wp-content/themes/arpi && ./vendor/bin/pest tests/Unit/FieldOrTest.php"
```
Expected: FAIL — `function Arpi\field_or not found`. (Jeśli brak Pest: `make composer ARGS='require --dev pestphp/pest'`.)

- [ ] **Step 3: Zaimplementuj `field_or` + autoload**

`public_html/wp-content/themes/arpi/app/helpers.php`:

```php
<?php

namespace Arpi;

/**
 * Wartość pola albo fallback gdy pusta.
 * Pozwala budować sekcje z hardcode i podmienić na get_field() później.
 */
function field_or($value, $default)
{
    return ($value === null || $value === false || $value === '') ? $default : $value;
}
```

W `public_html/wp-content/themes/arpi/composer.json`, w `autoload` dodaj `files`:

```json
"autoload": {
  "psr-4": { "App\\": "app/" },
  "files": ["app/helpers.php"]
}
```

Przeładuj autoload:
```bash
make composer ARGS="dump-autoload"
```

- [ ] **Step 4: Uruchom test — ma PRZEJŚĆ**

Run:
```bash
docker compose exec php sh -c "cd /var/app/public_html/wp-content/themes/arpi && ./vendor/bin/pest tests/Unit/FieldOrTest.php"
```
Expected: PASS (3 asercje).

- [ ] **Step 5: View composer strony głównej**

`public_html/wp-content/themes/arpi/app/View/Composers/FrontPage.php`:

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
                // Dziś hardcode w fallbacku; później podmiana na get_field('hero_*').
                'title' => field_or(get_field('hero_title') ?: null, 'ARPI Accounting'),
                'text'  => field_or(get_field('hero_text') ?: null, 'Nowoczesna księgowość dla Twojej firmy.'),
            ],
        ];
    }
}
```

- [ ] **Step 6: Widoki front-page + sekcja hero**

`public_html/wp-content/themes/arpi/resources/views/front-page.blade.php`:

```blade
@extends('layouts.app')
@section('content')
  @include('sections.hero', ['hero' => $hero])
@endsection
```

`public_html/wp-content/themes/arpi/resources/views/sections/hero.blade.php`:

```blade
<section class="bg-brand text-white py-section">
  <div class="container mx-auto">
    <h1 class="text-4xl font-sans">{{ $hero['title'] }}</h1>
    <p class="mt-4">{{ $hero['text'] }}</p>
  </div>
</section>
```

- [ ] **Step 7: Zweryfikuj render + fallback**

Run:
```bash
make wp ARGS="option update show_on_front posts" >/dev/null
make build-assets >/dev/null 2>&1
curl -s http://localhost:8080/ | grep -q "Nowoczesna księgowość" && echo FALLBACK_OK
```
Expected: `FALLBACK_OK` — sekcja renderuje fallback (brak wartości ACF ⇒ hardcode). (Opcjonalnie: dodaj pola `hero_title`/`hero_text` na Front Page w ACF, ustaw wartość → pojawia się treść z pola.)

- [ ] **Step 8: Commit**

```bash
git add public_html/wp-content/themes/arpi/app public_html/wp-content/themes/arpi/resources public_html/wp-content/themes/arpi/tests public_html/wp-content/themes/arpi/composer.json
git commit -m "feat: view-composer-driven section pattern with field_or fallback + test"
```

---

## Self-Review

**Spec coverage:**
- Standard `public_html` + `.env` (phpdotenv) + env-aware wp-config → Task 1, 3. ✅
- Multi-container docker-compose + Makefile (php/nginx/mariadb 10.11) → Task 2. ✅
- Sage + Vite HMR → Task 4, 5. ✅
- Tokeny z Figmy → Tailwind → Task 6 (gated: dostęp Figma). ✅
- ACF PRO legalny (nie przez Composer — wtyczki zarządzane normalnie) + Local JSON → Task 7 (gated: licencja). ✅
- CPT + pola w Local JSON, nazwy 1:1 z legacy → Task 8. ✅
- Sekcje przez view composer, hardcode→get_field → Task 9. ✅
- Deploy tylko motywu / git tylko motyw → Global Constraints + `.gitignore` (Task 1). ✅
- cyberFolks, staging, migracja WXR/MailPoet, Polylang → **poza fazą 1** (fazy 2–6). ✅

**Placeholder scan:** `<KLUCZ_LICENCYJNY_ACF>` (Task 7) i wartości tokenów (Task 6) to dane zewnętrzne pozyskiwane wykonalnymi krokami (licencja / Figma MCP), nie luki planu. Nazwy pól (Task 8) weryfikowane grepem legacy.

**Type consistency:** `field_or` — nazwa/sygnatura spójne w Task 9 (Step 1/3/5). `$hero` (klucze `title`,`text`) produkowany przez `FrontPage::with()` i konsumowany w `front-page.blade.php` → `sections/hero.blade.php`. `THEME=wp-content/themes/arpi` spójne w Makefile i ścieżkach. Punkty Local JSON (Task 7) i pliki (Task 8) → ten sam katalog `acf-json/`.
