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

- **Bedrock (Roots)** — WordPress zarządzany Composerem, `.env` per środowisko (parytet dev/staging/prod),
  wtyczki jako zależności, całość w Git.
  - *Warunek:* hosting musi pozwolić na docroot = `web/` (potwierdzone dla cyberFolks, patrz §5).
  - *Fallback:* jeśli docroot na wybranym planie okaże się problematyczny — standardowy layout WP
    + Composer do wtyczek + `.env` + Sage. Bedrock jest nice-to-have, nie fundamentem.
- **Sage 10 (Roots)** na froncie: Blade + Acorn (view composers) + **Bud.js (HMR)** + Tailwind + PostCSS.
- **ACF PRO** legalny (klucz w `.env`, instalacja przez Composer z repo Roots/ACF).

### Model treści

- **CPT + taksonomie przez ACF PRO 6.1** (nie CPT UI): rejestrowane w UI ACF-a, edytowalne w adminie,
  **wersjonowane w Local JSON** (`acf-json/`). Import istniejących typów z CPT UI (ACF 6.1 to wspiera).
- **Pola ACF** również w **Local JSON** — widoczne/edytowalne w adminie i wersjonowane w repo (sync dwukierunkowy).
- **Klucze CPT i nazwy pól MUSZĄ być identyczne** ze starymi, żeby migrowany post meta (WXR) trafił we właściwe pola.
- Typy do odtworzenia: `dbip-chapters` (+ `chapter-name`), `oferty`, `uslugi`, `service` — do weryfikacji które są żywe.

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
| **Dev (lokalny)** | DDEV (Docker, wersje PHP/MySQL zgodne z prod) + `bud dev` = **HMR**. Import zanonimizowanej kopii danych. |
| **Staging** | cyberFolks (osobne konto/subdomena + osobna baza), aktywny nowy motyw, **HTTP Basic Auth + `noindex`**. Klient ogląda postęp. Odświeżany danymi z prod. |
| **Prod (docelowy)** | cyberFolks. Migrowany na końcu; **stary prod na nazwa.pl działa aż do cutoveru**. |

- **Git flow (solo):** `main` → prod, `staging` → staging. Kod/motyw w Git; zbudowanego `public/` nie commitujemy.
- **Deploy:** **GitHub Actions** → `bud build` → **rsync/SSH** na cyberFolks (ew. PHP Deployer albo bare repo + `post-receive` hook). CI/CD nie jest wbudowane w hosting — dopisujemy sami (trywialne).
- **Klon prod → staging/dev bez WP-CLI na starym prodzie:** `all-in-one-wp-migration` (export/import) lub dostarczony zrzut bazy + `rsync` uploads.

## 5. Hosting — cyberFolks (zweryfikowane)

- **Docroot pod Bedrock (`web/`):** ✅ możliwy — domenę/subdomenę można wskazać na dowolny katalog konta
  (DirectAdmin: „Przypisz katalog domeny"; Server_Panel: „Lista adresów www"). **TBD:** potwierdzić dla konkretnego wybranego planu.
- **SSH / Composer / WP-CLI:** ✅ dostępne (WP-CLI na wszystkich planach, wymaga SSH).
- **Git-synced CI/CD out-of-the-box:** ❌ brak wbudowanej integracji GitHub/webhooków/UI auto-deploy;
  jest ręczny git przez SSH. CD dopisujemy przez GitHub Actions + rsync/SSH.
- **Wydajność:** LiteSpeed + LSCache. **Managed** (brak administracji serwerem — pasuje do solo).
- **Domena + poczta zostają na nazwa.pl** (ta sama grupa cyber_Folks → przeniesienie hostingu bezproblemowe).

Alternatywa odrzucona: VPS (Hetzner) — tańszy i elastyczniejszy, ale przenosi na dev cały narzut sysadmina
(patche, LEMP, backupy, uptime) — niepożądane przy pracy solo.

## 6. Fazy realizacji (wysoki poziom)

1. **Setup:** Bedrock + Sage + DDEV; ACF PRO legalny; CPT/pola w ACF Local JSON; tokeny z Figmy → Tailwind config.
2. **Infra:** konta cyberFolks (staging + prod), docroot, SSH; pipeline GitHub Actions + rsync; Basic Auth na staging.
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
- **Docroot Bedrock niedostępny na wybranym planie cyberFolks.** *Mit.:* fallback do standardowego layoutu WP + Composer.
- **Ukryte żywe trasy/Page Templates** przeoczone w audycie. *Mit.:* pełna inwentaryzacja z panelu prod (po uzyskaniu dostępu).
- **Content drift** (klient edytuje prod w trakcie). *Mit.:* finalny sync przy cutoverze; ustalić ewentualny content freeze.

## 8. Punkty otwarte (TBD)

- **Docroot `web/`** na konkretnym wybranym planie cyberFolks — potwierdzić u supportu.
- **Dostęp do hostingu nazwa.pl** (klient miał odpowiedzieć) — potrzebny do finalnego eksportu WXR + zrzutu MailPoet.
  (Zrzut bazy MailPoet będzie dostarczony jako plik.)
- **Dostęp edytora do Figmy** dla `dev@example.test` (MCP wymaga edit access, nie tylko view).
- **`custom-post-type-ui` vs `post-type-manager`** — potwierdzić czy redundancja i które typy są żywe.
- **Ewentualny content freeze** treści na czas cutoveru.
- **Compliance sygnalisty** — czy formularz/whistleblowing ma wymogi prawne (dyrektywa o ochronie sygnalistów).
