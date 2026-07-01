# ARPI Accounting — Rewrite strony: dokument projektowy

- **Data:** 2026-07-01
- **Autor:** Kamil (solo dev)
- **Status:** zatwierdzony do planu implementacji
- **Produkcja obecna:** https://arpiaccounting.com/ (WordPress, motyw oparty na Twenty Seventeen, hosting nazwa.pl)

## 1. Cel i kontekst

Całkowity rewrite strony klienta z nowym projektem w Figmie (gotowym).
Wymagania nadrzędne:

- Obecna produkcja działa **nieprzerwanie aż do momentu wypuszczenia** nowej strony.
- Potrzebny **serwer stagingowy** do pokazywania klientowi bieżącego postępu.
- Potrzebny **dev z HMR**.
- Klient dalej zarządza treścią przez **panel WordPress** (wymóg — przesądza o pozostaniu przy WP).
- Strona **dwujęzyczna PL/EN** (wielojęzyczność bardzo ważna).

### Stan zastanego motywu (ustalenia z analizy kodu)

- Mocno zmodyfikowany **Twenty Seventeen** rozwijany organicznie: dużo martwego kodu i duplikatów
  (`foter.php`, `old_front-page.php`, `old_front_page.php`, `navigation-small.php1`,
  `template-blog.php` + `template-blog20.php`), gigantyczne pliki inline (`front-page.php` 54 KB,
  `functions.php` 811 linii), CodeKit jako build (`config.codekit3`).
- **W motywie NIE ma** `register_post_type` / `register_taxonomy` ani `acf_add_local_field_group`
  → model treści jest odseparowany od motywu (CPT-y w CPT UI, pola ACF w bazie).
- `database.php` mimo nazwy to zwykły partial `WP_Query` — **brak własnych tabel** poza MailPoetem.
- `get_field()` używany w 14 plikach — ACF jest wyłącznie warstwą prezentacji.
- Custom post types: `dbip-chapters` (+ taksonomia `chapter-name`), `oferty`, `uslugi`, `service`;
  strony specjalne: `page-sygnalista.php` (sygnalista/whistleblowing).

### Lista wtyczek (ze snapshotu prod, niepełnego)

- **Model treści:** `custom-post-type-ui`, `post-type-manager` (możliwa redundancja — do przeglądu).
- **ACF PRO:** obecny na prodzie, ale **crackowany** → w folderze go nie ma (expected). Zgoda na legalną subskrypcję.
- **Formularze:** `contact-form-7` + `contact-form-cfdb7` (zgłoszenia w bazie).
- **Newsletter:** `mailpoet`, `mailpoet-premium`, `easy-wp-smtp`.
- **Wielojęzyczność:** `polylang`.
- **SEO/analytics:** `wordpress-seo` (Yoast), `redirection`, `google-analytics-for-wordpress`, `gtm-kit`.
- **Cache/perf:** `w3-total-cache_`, `autoptimize_` (oba wyłączone), `webp-converter-for-media`.
- **Klonowanie danych:** `all-in-one-wp-migration`, `wp-import-export-lite`, `wordpress-importer`.
- **Ryzyka:** `file-manager-advanced` (dziura bezpieczeństwa — usunąć), crackowany ACF (→ legalizacja).
- **Utility:** `ag-custom-admin`, `classic-editor`, `duplicate-page`, `enable-media-replace`,
  `string-locator`, `what-the-file`, `post-types-order`, `taxonomy-terms-order`, `flexible-table-block`,
  `wp-maintenance-mode`, `wp-simple-firewall`, `akismet`, `yet-another-related-posts-plugin`.

## 2. Decyzja architektoniczna

**Świeża instalacja WordPress (green field), nie theme swap.**

Uzasadnienie: treści pisane od nowa (niewielki wolumen do migracji), projekt solo utrzymywany
długoterminowo, chęć czystego fundamentu bez odziedziczonego cruftu wtyczek/bazy i dziur bezpieczeństwa.
Headless odrzucony jako przerost formy nad treścią dla strony-wizytówki, przy wymogu edycji w WP adminie.

### Stos

- **Standardowy layout WordPress (`public_html`), BEZ Bedrocka.** Uzasadnienie: klient
  edytuje i instaluje/aktualizuje wtyczki w wp-adminie — composer-managed wtyczki Bedrocka by to
  cofały przy deployu; do tego docroot `web/` + Composer na shared hostingu cyberFolks = zbędne tarcie.
  Benefity Bedrocka bierzemy selektywnie (niżej).
- **Sekrety przez `.env`** (nie w `wp-config.php`): root-owy `composer.json` z `vlucas/phpdotenv`,
  plik `.env` **nad** `public_html` (poza docrootem → niedostępny z sieci), `wp-config.php` czyta z niego
  DB, salty, `WP_HOME/WP_SITEURL`, klucze (ACF, SMTP).
- **Czysty git:** w repo tylko motyw (+ ew. mu-plugin); core WP, wtyczki, `uploads` w `.gitignore`.
- **Sage (Roots)** na froncie: Blade + Acorn (view composers) + **Vite (HMR, port 5173, default startera Sage)**
  + Tailwind + PostCSS.
- **ACF PRO** legalny (klucz w `.env`; instalacja przez Composer w motywie lub przez wp-admin).

### Model treści

- **CPT + taksonomie przez ACF PRO 6.1** (nie CPT UI): rejestrowane w UI ACF-a, edytowalne w adminie,
  **wersjonowane w Local JSON** (`acf-json/`). Import istniejących typów z CPT UI (ACF 6.1 to wspiera).
- **Pola ACF** również w **Local JSON** — widoczne/edytowalne w adminie i wersjonowane w repo (sync dwukierunkowy).
- **Klucze CPT i nazwy pól MUSZĄ być identyczne** ze starymi, żeby migrowany post meta (WXR) trafił we właściwe pola.
- Typy do odtworzenia: `dbip-chapters` (+ `chapter-name`), `oferty`, `uslugi`, `service` — do weryfikacji które są żywe.

### Sekcje sterowane ACF (treści konfigurowalne)

Część podstron — **zwłaszcza strona główna** — będzie miała sekcje generowane z **grup pól ACF**
(np. grupa „Homepage" z polami textarea itd.). Klient wypełnia pola w adminie, a treść renderuje się w sekcji.

- **Stan początkowy:** sekcje budujemy z **zahardkodowaną treścią** — nie ma jeszcze informacji, które sekcje
  mają być konfigurowalne.
- **Wymóg architektoniczny (od pierwszego dnia):** każda sekcja Blade dostaje dane przez **view composer**.
  Nawet gdy composer zwraca teraz treść zahardkodowaną, przełączenie na `get_field()` będzie zmianą jednej linii,
  **bez przebudowy szablonu**. Dzięki temu „które sekcje konfigurowalne" można doprecyzować później bez kosztu.
- **i18n treści ACF:** pola konfigurowalne trzymamy **na stronie (post per język)**, a **nie** w globalnym
  ACF Options Page — opcje globalne nie są per-post i komplikują wielojęzyczność (patrz niżej).

### Wielojęzyczność (i18n) — Polylang (Pro)

Wybór: **Polylang (Pro)**. Budujemy od zera, tłumaczeń nie migrujemy → wybór otwarty; Polylang wygrywa dla tego
profilu (wizytówka + sekcje na ACF, Sage): model „osobny post per język = osobne wartości pól ACF" jest czysty
i naturalny, lekki, idiomatyczny ze standardowymi funkcjami WP + `pll_` API, tańszy. Polylang Pro dokłada
duplikację/sync struktury (szybsze stawianie wersji EN).

- **Ścieżka eskalacji → WPML + ACFML:** jeśli grupy pól ACF urosną i będą miały dużo pól nietekstowych
  (obrazy, linki, liczby), których nie chcemy wpisywać dwa razy — WPML+ACFML daje kontrolę per pole
  („translate / copy / copy-once") i jeden edytor tłumaczeń. Drożej i ciężej — tylko jeśli zajdzie potrzeba.
- **TranslatePress odrzucony** — tłumaczenie po renderze słabo pasuje do strukturalnych sekcji ACF.
- Stringi w szablonach owinięte w `__()`/`_e()`; przełącznik przez `pll_the_languages()`; hreflang przez Yoast+Polylang.

## 3. Migracja danych

Zasada: definicje (pola, typy) piszemy od nowa; migrujemy **wartości i wpisy**.

| Zakres | Metoda | Uwagi |
|---|---|---|
| Posty bloga + wpisy dbip + media + meta Yoast | **WXR export/import** (`wordpress-importer`) | WXR niesie posty, CPT, post meta (= wartości ACF), taksonomie, media |
| Definicje pól/typów ACF | pisane od nowa (Local JSON) | nie migrujemy; nazwy pól zgodne ze starymi |
| **MailPoet — 1:1 z historią** | import tabel `wp_mailpoet_*` + wierszy `mailpoet_*` z `wp_options` ze **zrzutu bazy** (plik dostępny) | ta sama wersja MailPoet po obu stronach, zgodny prefiks tabel, przeniesienie klucza wysyłki (MSS/SMTP) |
| Formularze (kontakt, sygnalista) | odtworzenie w CF7 | drobna konfiguracja |
| Redirecty | eksport/import konfiguracji `redirection` | |
| Tłumaczenia PL/EN | **brak migracji** — EN budowane na bieżąco | treści pisane od nowa |

**Warunki krytyczne migracji:**
- Zgodność kluczy CPT i nazw pól ACF (inaczej meta nie trafi).
- **Permalinki 1:1** ze starą strukturą (SEO). Każda zmiana slug → 301 przez `redirection`.
- **Anonimizacja PII** (maile subskrybentów MailPoet) na dev/staging + wyłączenie realnej wysyłki (RODO).
- SPF/DKIM w DNS zostają nietknięte (domena/poczta zostają na nazwa.pl) → reputacja wysyłki MailPoet zachowana.

## 4. Środowiska

| Środowisko | Setup |
|---|---|
| **Dev (lokalny)** | **docker-compose + Makefile** (własny stack: php-fpm + nginx + wp-cli, MariaDB 10.11, wersje zgodne z prod) + **Vite (HMR, 5173)**. Import zanonimizowanej kopii danych przez `make import-db`. |
| **Staging** | cyberFolks (osobne konto/subdomena + osobna baza), aktywny nowy motyw, **HTTP Basic Auth + `noindex`**. Klient ogląda postęp. Odświeżany danymi z prod. |
| **Prod (docelowy)** | cyberFolks. Migrowany na końcu; **stary prod na nazwa.pl działa aż do cutoveru**. |

- **Git flow (solo, trunk-based):** jeden long-lived branch **`main`** = źródło prawdy. Feature work na krótkich `feat/*` → merge do `main`. Merge do `main` → CI build → **auto-deploy na staging**. **Prod = bramkowany**: deploy odpalany **tagiem `v*`** lub ręcznym `workflow_dispatch` z GitHub Environment approval (realizuje „prod żyje do ostatniej chwili"). **Bez osobnych branchy `staging`/`dev`** — to środowiska, nie gałęzie; dev = lokalny Docker.
- **Deploy:** motyw budowany lokalnie/w **GitHub Actions** (`vite build`) → **rsync/SSH** na cyberFolks (ew. bare repo + `post-receive` hook). Deployujemy **tylko motyw** (+ ew. mu-plugin) — `wp-content/plugins` i core zostają nietknięte (klient zarządza wtyczkami w adminie). CI/CD nie jest wbudowane w hosting — dopisujemy sami (trywialne).
- **Klon prod → staging/dev bez WP-CLI na starym prodzie:** `all-in-one-wp-migration` (export/import) lub dostarczony zrzut bazy + `rsync` uploads.

## 5. Hosting — cyberFolks (zweryfikowane)

- **Standardowy layout (`public_html`):** docroot = standardowy katalog konta, **bez specjalnej konfiguracji**
  (odpadła kwestia docroot `web/` po rezygnacji z Bedrocka). `.env` i `vendor/` trzymamy **nad** `public_html`
  (poza docrootem → niedostępne z sieci).
- **SSH / Composer / WP-CLI:** ✅ dostępne (WP-CLI na wszystkich planach, wymaga SSH).
- **Git-synced CI/CD out-of-the-box:** ❌ brak wbudowanej integracji GitHub/webhooków/UI auto-deploy;
  jest ręczny git przez SSH. CD dopisujemy przez GitHub Actions + rsync/SSH (deploy tylko motywu).
- **Wydajność:** LiteSpeed + LSCache. **Managed** (brak administracji serwerem — pasuje do solo).
- **Domena + poczta zostają na nazwa.pl** (ta sama grupa cyber_Folks → przeniesienie hostingu bezproblemowe).

Alternatywy odrzucone: **Coolify na VPS** (znany z innych projektów — git-deploy + staging + parytet z lokalnym
Dockerem, ale niepotrzebny narzut sysadmina i przerost dla wizytówki; klient woli managed) oraz **Bedrock**
(composer-managed wtyczki kolidują z edycją wtyczek w wp-adminie przez klienta).

## 6. Fazy realizacji (wysoki poziom)

1. **Setup:** lokalny stack (docker-compose + Makefile: php-fpm+nginx+wp-cli, MariaDB 10.11), standard WP `public_html`,
   `.env` (phpdotenv) + env-aware `wp-config.php`, Sage + Vite (HMR); ACF PRO legalny; CPT/pola w ACF Local JSON; tokeny z Figmy → Tailwind config.
2. **Infra:** konta cyberFolks (staging + prod), SSH; pipeline GitHub Actions + rsync (deploy motywu); Basic Auth na staging.
3. **Budowa szablonów z Figmy:** front, blog (lista/single), dbip (archiwum/single/taksonomia), oferty/usługi,
   kontakt, sygnalista; przełącznik i obsługa PL/EN (Polylang, stringi w `__()`/`_e()`).
4. **Migracja na staging:** WXR (blog + dbip + media) + MailPoet (tabele) + formularze + redirecty; weryfikacja.
5. **Audyt przedwdrożeniowy:** parytet tras/szablonów, permalinki + 301, formularze, SEO/hreflang, wydajność,
   inwentaryzacja rzeczy „w motywie" (shortcode'y, image sizes, menu, widgety, Customizer), usunięcie file-managera.
6. **Cutover:** finalny zrzut danych z prod → import; przepięcie rekordu A na cyberFolks; końcowy sync;
   stary prod pozostaje jako backup przez okres przejściowy.

## 7. Ryzyka i mitygacje

- **Niezgodność nazw pól/CPT** → meta nie trafi. *Mit.:* zmapować starą strukturę przed pisaniem Local JSON.
- **Treść z hardcodowanym markupem starego motywu** (zwł. dbip) rozjeżdża się w nowych stylach.
  *Mit.:* przegląd treści pod kątem inline HTML/klas przed migracją.
- **MailPoet — rozjazd wersji / prefiksu tabel** przy imporcie. *Mit.:* zrównać wersję MailPoet, zweryfikować prefiks, przenieść ustawienia wysyłki.
- **Rozjazd wtyczek** (klient instaluje/aktualizuje wtyczki w adminie, repo o tym nie wie). *Mit.:* deploy dotyka **tylko motywu**, nigdy `wp-content/plugins`; okresowo zrzucamy listę wtyczek (`wp plugin list`) do repo dla odtwarzalności.
- **`.env`/`wp-config` env-aware na shared hostingu** (ścieżka do `.env` nad `public_html`, autoload `vendor`). *Mit.:* zweryfikować układ katalogów na cyberFolks przy pierwszym deployu stagingu.
- **Ukryte żywe trasy/Page Templates** przeoczone w audycie. *Mit.:* pełna inwentaryzacja z panelu prod (po uzyskaniu dostępu).
- **Content drift** (klient edytuje prod w trakcie). *Mit.:* finalny sync przy cutoverze; ustalić ewentualny content freeze.

## 8. Punkty otwarte (TBD)

- **Układ katalogów na cyberFolks** — potwierdzić, że da się trzymać `.env`/`vendor` nad `public_html` i że SSH/rsync działa na wybranym planie.
- **Dostęp do hostingu nazwa.pl** (klient miał odpowiedzieć) — potrzebny do finalnego eksportu WXR + zrzutu MailPoet.
  (Zrzut bazy MailPoet będzie dostarczony jako plik.)
- **Dostęp edytora do Figmy** dla `dev@example.test` (MCP wymaga edit access, nie tylko view).
- **`custom-post-type-ui` vs `post-type-manager`** — potwierdzić czy redundancja i które typy są żywe.
- **Które sekcje** (homepage i podstrony) mają być **konfigurowalne przez ACF** vs zahardkodowane — do doprecyzowania
  z klientem. Do tego czasu sekcje budowane przez view composer (podmiana na `get_field()` bez przebudowy).
- **Ewentualny content freeze** treści na czas cutoveru.
- **Compliance sygnalisty** — czy formularz/whistleblowing ma wymogi prawne (dyrektywa o ochronie sygnalistów).
