# ARPI Accounting — custom motyw WordPress (case study AI-first)

Kompletne przepisanie strony biura rachunkowego z motywu opartego na Twenty Seventeen
na własny motyw w Roots Sage 11: 8 typów podstron, dwa języki, panel edycyjny dla klienta,
kanał zgłoszeń sygnalisty i deploy na staging z GitHub Actions.

**160 commitów · 13 PR-ów · 1 lip – 17 sie 2026 · jednoosobowo**

To repozytorium jest publiczną, zredagowaną kopią prywatnego repo klienckiego. Powstało jako
przykład **workflow AI-first** — nie tylko efektu końcowego, ale sposobu pracy: jak dzieliłem
zadania między siebie a agenta, jak weryfikowałem jego output i gdzie go poprawiałem.
Zakres redakcji opisuję [na dole](#co-zostało-zredagowane).

---

## Stack i uzasadnienie wyboru

| Warstwa | Wybór | Dlaczego |
|---|---|---|
| Motyw | Roots Sage 11 + Acorn | Blade + kontener DI + view composery — logika prezentacji wychodzi z `functions.php` do klas |
| CSS | Tailwind v4 (CSS-first) + `fluid-tailwindcss` | `@theme static` jako jedno źródło tokenów; `fl-*` daje płynne skalowanie 375→1280 bez drabinki breakpointów |
| Build | Vite | HMR w dev, manifest w prod |
| Treść | ACF (Local JSON) + Polylang | klient edytuje w wp-admin; pola wersjonowane w repo, nie w bazie |
| Infra | Docker (nginx + PHP 8.3 + MariaDB), GitHub Actions → rsync/SSH | staging na współdzielonym hostingu bez Dockera, więc deploy buduje w CI i wysyła gotowy motyw |

Świadome **nie**-wybory: brak Reacta (strona treściowa, SSR z Blade wystarcza — ~800 linii
wanilii na interakcje), brak Bedrocka (hosting klienta nie dawał kontroli nad docrootem).

## Co jest w tym repo moje

Cały motyw `public_html/wp-content/themes/arpi/` — ok. 7000 linii:

- **8 service providerów** — m.in. `WhistleblowerServiceProvider` (anonimowy kanał zgłoszeń:
  walidacja uploadów, losowe nazwy plików, deny na Apache **i** nginx, autoryzowany download,
  mail do komisji + potwierdzenie odbioru w 7 dni zgodnie z ustawą), `ContactServiceProvider`
  (endpointy REST + integracja z MailPoet), `AdminMenuServiceProvider` (grupowanie customowych
  CPT w wp-admin).
- **12 view composerów** — mapowanie pól ACF na dane widoku, z fallbackami.
- **19 komponentów Blade** + 60+ widoków, dwujęzycznie (PL domyślny, EN pod `/en/`).
- **Migracja treści z legacy** — importery z produkcyjnego dumpa (blog, DBiP, leady MailPoet)
  przez WP-CLI i API Polylang, w `scripts/`.
- **CI/CD** — `.github/workflows/deploy-staging.yml`.

Dokumentacja architektury: **[`docs/design-system.md`](docs/design-system.md)** (543 linie) —
to jest jednocześnie dokumentacja projektu i główny plik kontekstu dla agenta.

---

## Jak dzieliłem pracę z AI

Pracowałem w Claude Code w pętli **spec → plan → implementacja → review**, z artefaktami
w repo na każdym etapie.

**1. Spec (ja prowadzę, AI spisuje).** Ustalam zakres i decyzje projektowe; agent zapisuje je
jako dokument. → [`docs/superpowers/specs/`](docs/superpowers/specs/)

**2. Plan (AI proponuje, ja tnę).** Agent rozbija spec na zadania z krokami weryfikacji.
Tu najczęściej ingeruję — plany bywają przeinżynierowane. → [`docs/superpowers/plans/`](docs/superpowers/plans/)

**3. Implementacja (AI pisze, ja pilnuję konwencji).** Jedna gałąź na feature. Konwencje są
zapisane, nie powtarzane w promptach — patrz niżej.

**4. Review przed mergem (ja).** Każdy feature szedł przez PR z opisem *co / dlaczego / jak
zweryfikowane*. Opisy PR-ów: **[`docs/pull-requests/`](docs/pull-requests/)**.

Commity współtworzone z agentem mają trailer `Co-Authored-By: Claude` — **93 ze 160**.
Reszta to moje poprawki, decyzje projektowe i sprzątanie po agencie.

### Konfiguracja agenta

| Plik | Rola |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | cienki wskaźnik — każe przeczytać design-system przed pracą |
| [`docs/design-system.md`](docs/design-system.md) | **kręgosłup kontekstu**: stack, model treści, tokeny, konwencje (§14), gotchas |
| [`.claude/rules/`](.claude/rules/) | reguły egzekwowane w każdej sesji: konwencje + procedura weryfikacji |

Świadoma decyzja: kontekst mieszka w **wersjonowanym dokumencie**, nie w promptach.
`CLAUDE.md` ma 13 linii i tylko wskazuje — dzięki temu jest jedno źródło prawdy, które
aktualizuje się razem z kodem. Wymusza to reguła „document as you build" (§14.11): feature
i jego opis lecą w tym samym commicie. Bez tego dokument rozjeżdża się z kodem po tygodniu
i agent zaczyna pracować na nieaktualnym obrazie projektu.

---

## Jak weryfikowałem output AI

Projekt nie ma testów jednostkowych (motyw prezentacyjny — koszt utrzymania suite'a
przewyższał zysk), więc weryfikacja opierała się na czterech warstwach:

1. **`yarn build` bez ostrzeżeń** — łapie błędy Vite/Tailwind i nieistniejące assety.
2. **Sprawdzenie wyrenderowanego DOM-u przez `curl`**, osobno dla PL i EN — bo agent regularnie
   „poprawiał" jedną wersję językową i gubił drugą.
3. **Porównanie wizualne z projektem** przy ~375px i ~1440px.
4. **Review diffa przed mergem** — sekcja `## Verification` w każdym PR-ze opisuje, co konkretnie
   sprawdziłem ([przykład: PR #13](docs/pull-requests/pr-13.md)).

### Konkret: błąd, który przeszedł przez review kodu i wyłożył się dopiero na produkcji

Agent pisał wszędzie idiom `$group['items'] ?? []` przed `array_map()`. Poprawny PHP, poprawny
w code review, przechodził build. Ale **ACF Pro dla pustego repeatera zwraca `false`, nie `null`**
— więc `??` nie łapie, a `array_map()` dostaje `false` i rzuca fatal error. Ujawniło się dopiero,
gdy grupy pól poszły na żywo i strona usługi się wyłożyła.

Poprawka: `($group['items'] ?? []) ?: []` w czterech composerach —
[`107f37e`](../../commit/107f37e).

Wniosek, który wyniosłem z tego projektu: **agent jest mocny w idiomach języka, a słaby
w kontraktach konkretnych bibliotek.** Kod wygląda na poprawny, bo w czystym PHP *jest*
poprawny — łapie to dopiero uruchomienie na realnych danych, nie czytanie diffa.
Dlatego weryfikacja przez wyrenderowany DOM, a nie przez samo przeczytanie kodu.

Inne rzeczy tej klasy, które musiałem złapać ręcznie i opisać w
[`docs/design-system.md` §15](docs/design-system.md):
- nieaktualny `public/hot` serwujący CSS z martwego dev-servera (build wyglądał OK, strona nie),
- Polylang zwracający nazwy terminów z encjami HTML — wymaga `html_entity_decode()`,
- brakujący plik Geomanist Light 300 → cichy fallback do systemowego sans.

---

## Uruchomienie lokalnie

```bash
make setup      # .env + salts, kontenery, WP core, motyw, instalacja
make up         # start
make dev        # Vite + HMR
```

Strona: `http://localhost:8080`. Pełna lista poleceń w `Makefile`.

> Repo nie zawiera bazy ani uploadów, więc świeża instalacja wstaje pusta.
> Skrypty `scripts/seed-*.php` zasiewają strukturę treści (menu, strony, domyślne pola ACF).

## Mapa repo

```
public_html/wp-content/themes/arpi/   motyw (jedyny śledzony katalog WP)
  app/Providers/                      8 service providerów
  app/View/Composers/                 12 composerów ACF → widok
  resources/views/                    Blade: 19 komponentów + 60+ widoków
  resources/css/                      tokeny (@theme static) + komponenty
  resources/acf-json/                 definicje pól ACF (Local JSON)
docs/design-system.md                 kręgosłup kontekstu projektu i AI
docs/pull-requests/                   archiwum opisów PR-ów
docs/superpowers/                     specyfikacje, plany, runbooki
scripts/                              importery z legacy + seedy + deploy
.claude/                              konfiguracja agenta
```

---

## Co zostało zredagowane

To jest kopia repo klienckiego, więc historia została przepisana (`git filter-repo`) przed
upublicznieniem. Usunięte **z całej historii**, nie tylko z HEAD-a:

- **`reference/legacy-theme/`** — źródła poprzedniego motywu klienta (cudzy kod, trzymany
  lokalnie do audytu nazw pól). Stąd odwołania do `reference/` w starszych planach, które
  w tym repo nie prowadzą już do niczego.
- **Adresy e-mail imienne** — członkowie komisji ds. zgłoszeń sygnalisty i kontakty klienta,
  podmienione na `committee-N@example.test`. Publiczne adresy rolowe
  (`contact@`, `rekrutacja@`) zostały.
- **Konfiguracja narzędziowa innego pracodawcy**, która wcześniej leżała w `.claude/` —
  nie moja własność, więc nie moja do publikowania. `.claude/rules/` w tym repo napisałem
  od nowa pod ten projekt.
- **Moje prywatne adresy e-mail** → `…@users.noreply.github.com`.

Cztery commity dotykające wyłącznie usuniętych plików zniknęły przy przepisywaniu
(164 → 160). Cała reszta historii, wraz z merge commitami PR-ów i topologią gałęzi,
została zachowana — `git log --graph` pokazuje oryginalny przebieg pracy.

<!-- TODO PRZED UPUBLICZNIENIEM: potwierdź zgodę klienta na publikację kodu i wpisz tu
     jedno zdanie o podstawie publikacji — albo usuń ten akapit razem z komentarzem. -->

