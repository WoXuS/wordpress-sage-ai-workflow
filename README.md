# WordPress + Sage 11 — workflow AI-first

Zapis **sposobu pracy** przy przepisaniu strony biura rachunkowego (ARPI Accounting)
z motywu opartego na Twenty Seventeen na własny motyw w Roots Sage 11:
8 typów podstron, dwa języki, panel edycyjny dla klienta, kanał zgłoszeń sygnalisty,
deploy z GitHub Actions.

**145 commitów · 13 PR-ów · 1 lip – 17 sie 2026 · jednoosobowo**

> **Czym to repo jest, a czym nie.** To nie jest kompletny, uruchamialny motyw — to
> **wycinek prywatnego repo klienckiego**, przygotowany jako próbka pracy AI-first.
> Zostały: cała warstwa PHP, tokeny CSS, moduły JS, komponenty Blade, model treści ACF,
> infrastruktura i **komplet artefaktów procesu**. Wypadły: assety brandowe klienta,
> większość markupu podstron i dane produkcyjne. Historia gita dla tego, co zostało,
> jest oryginalna — nie jest to świeży commit z wrzuconym kodem.
> Zakres redakcji: [na dole](#co-zostało-usunięte-i-dlaczego).
>
> Efekt końcowy na żywo: [arpiaccounting.com](https://arpiaccounting.com)

---

## Jak dzieliłem pracę z AI

Praca w Claude Code w pętli **spec → plan → implementacja → review**, z artefaktem
w repo na każdym etapie. Nie „wygenerowane i wrzucone" — każdy etap ma ślad.

**1. Spec — ja prowadzę, agent spisuje.**
Ustalam zakres i decyzje projektowe, agent zapisuje je jako dokument.
→ [`docs/superpowers/specs/`](docs/superpowers/specs/)

**2. Plan — agent proponuje, ja tnę.**
Agent rozbija spec na zadania z krokami weryfikacji. To etap, na którym ingeruję
najczęściej: plany wychodzą przeinżynierowane, z abstrakcjami na zapas.
→ [`docs/superpowers/plans/`](docs/superpowers/plans/)

**3. Implementacja — agent pisze, ja pilnuję konwencji.**
Jedna gałąź na feature. Konwencje są **zapisane, nie powtarzane w promptach** — patrz niżej.

**4. Review przed mergem — ja.**
Każdy feature przez PR z opisem *co / dlaczego / jak zweryfikowane*.
→ **[`docs/pull-requests/`](docs/pull-requests/)** — 13 opisów, m.in.
[#13](docs/pull-requests/pr-13.md) (sekcja `## Verification`),
[#11](docs/pull-requests/pr-11.md) (kanał sygnalisty),
[#12](docs/pull-requests/pr-12.md) (przebudowa menu wp-admin).

Commity współtworzone z agentem mają trailer `Co-Authored-By: Claude` — **86 ze 145**.
Reszta to moje poprawki, decyzje projektowe i sprzątanie po agencie.

## Konfiguracja agenta

| Plik | Rola |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | cienki wskaźnik — każe przeczytać design-system przed pracą |
| [`docs/design-system.md`](docs/design-system.md) | **kręgosłup kontekstu** (543 linie): stack, model treści, tokeny, konwencje (§14), gotchas (§15) |
| [`.claude/rules/conventions.md`](.claude/rules/conventions.md) | najczęściej łamane konwencje + zasada nazewnictwa |
| [`.claude/rules/verification.md`](.claude/rules/verification.md) | procedura weryfikacji — co znaczy „zrobione" |

Kluczowa decyzja: **kontekst mieszka w wersjonowanym dokumencie, nie w promptach.**
`CLAUDE.md` ma 13 linii i tylko wskazuje. Dzięki temu jest jedno źródło prawdy, które
aktualizuje się razem z kodem — wymusza to reguła „document as you build" (§14.11):
feature i jego opis lecą w tym samym commicie.

Ta sama zasada w drugą stronę: `conventions.md` **nie kopiuje** listy konwencji z §14,
tylko na nią wskazuje. Skopiowana lista rozjeżdża się z oryginałem po kilku commitach
i agent zaczyna pracować na nieaktualnym obrazie projektu. Dryf między dwiema wersjami
tego samego dokumentu to najczęstsza przyczyna „agent nagle zaczął pisać bez sensu".

---

## Jak weryfikowałem output AI

Brak testów jednostkowych (motyw prezentacyjny — koszt utrzymania suite'a przewyższał
zysk), więc weryfikacja opierała się na czterech warstwach — opisane w
[`.claude/rules/verification.md`](.claude/rules/verification.md):

1. **`yarn build` bez ostrzeżeń** — łapie błędy Vite/Tailwind i nieistniejące assety.
2. **Sprawdzenie wyrenderowanego DOM-u przez `curl`, osobno PL i EN** — bo agent regularnie
   „poprawiał" jedną wersję językową i cicho gubił drugą.
3. **Dane brzegowe**, nie tylko happy path: puste pole ACF, brak obrazka, pusty repeater.
4. **Review diffa przed mergem** — sekcja `## Verification` w każdym PR-ze.

### Konkret: błąd, który przeszedł code review i wyłożył się dopiero na produkcji

Agent pisał wszędzie idiom `$group['items'] ?? []` przed `array_map()`. Poprawny PHP,
przechodzi build, wygląda dobrze w diffie. Ale **ACF Pro dla pustego repeatera zwraca
`false`, nie `null`** — więc `??` nie łapie, `array_map()` dostaje `false` i rzuca fatal error.
Ujawniło się dopiero, gdy grupy pól poszły na żywo i podstrona usługi się wyłożyła.

Poprawka: `($group['items'] ?? []) ?: []` w czterech composerach — commit `107f37e`,
widoczny m.in. w [`app/View/Composers/Usluga.php`](public_html/wp-content/themes/arpi/app/View/Composers/Usluga.php).

Wniosek, który wyniosłem z tego projektu: **agent jest mocny w idiomach języka, a słaby
w kontraktach konkretnych bibliotek.** Kod wygląda poprawnie, bo w czystym PHP *jest*
poprawny — łapie to dopiero uruchomienie na realnych danych, nie czytanie diffa.
Dlatego weryfikacja przez wyrenderowany DOM, a nie przez sam przegląd kodu.

Inne rzeczy tej samej klasy, złapane ręcznie i opisane w
[`docs/design-system.md` §15](docs/design-system.md):
- nieaktualny `public/hot` serwujący CSS z martwego dev-servera — build czysty, strona rozjechana,
- Polylang zwracający nazwy terminów z encjami HTML — wymaga `html_entity_decode()`,
- brakujący plik Geomanist Light 300 → cichy fallback do systemowego sans.

---

## Co jest w kodzie

Warstwa PHP w całości — 24 pliki, 3532 linie:

- **8 service providerów.** Najciekawszy:
  [`WhistleblowerServiceProvider`](public_html/wp-content/themes/arpi/app/Providers/WhistleblowerServiceProvider.php)
  — anonimowy kanał zgłoszeń sygnalisty: walidacja uploadów (typ, rozmiar, liczba),
  losowe nazwy plików, deny na poziomie Apache **i** nginx, autoryzowany download przez
  `admin_post`, mail do komisji + potwierdzenie odbioru w 7 dni zgodnie z ustawą.
  Dalej: [`ContactServiceProvider`](public_html/wp-content/themes/arpi/app/Providers/ContactServiceProvider.php)
  (endpointy REST + integracja z MailPoet),
  [`AdminMenuServiceProvider`](public_html/wp-content/themes/arpi/app/Providers/AdminMenuServiceProvider.php)
  (grupowanie customowych CPT w wp-admin wokół obejścia dwupoziomowego menu WP).
- **12 view composerów** — mapowanie pól ACF na dane widoku, z fallbackami.
- **Model treści** — [`resources/acf-json/`](public_html/wp-content/themes/arpi/resources/acf-json/):
  pola wersjonowane w repo, nie w bazie.
- **Tokeny** — [`resources/css/theme.css`](public_html/wp-content/themes/arpi/resources/css/theme.css)
  (`@theme static`, wygaszone domyślne kolory Tailwinda) + `fluid-tailwindcss` (`fl-*`),
  płynne skalowanie 375→1280 bez drabinki breakpointów.
- **JS** — ~800 linii wanilii w modułach ES: walidacja formularzy, custom select, scroll-reveal.
- **Migracja z legacy** — [`scripts/`](scripts/): importery treści z produkcyjnego dumpa
  (blog, DBiP, leady MailPoet) przez WP-CLI i API Polylang.
- **CI/CD** — [`deploy-staging.yml`](.github/workflows/deploy-staging.yml): build w CI
  (staging na współdzielonym hostingu bez Dockera) → rsync po SSH → czyszczenie cache Acorna.

Świadome **nie**-wybory: brak Reacta (strona treściowa, SSR z Blade wystarcza),
brak Bedrocka (hosting klienta nie dawał kontroli nad docrootem).

---

## Co zostało usunięte i dlaczego

Historia została przepisana (`git filter-repo`) przed upublicznieniem — usunięcia
objęły **całą historię**, nie tylko HEAD:

| Usunięte | Dlaczego |
|---|---|
| `reference/legacy-theme/` — źródła poprzedniego motywu | cudzy kod, trzymany lokalnie tylko do audytu nazw pól |
| Assety brandowe: obrazy, fonty, ikony (3,6 MB) | własność klienta, zero wartości jako próbka kodu |
| Większość markupu podstron | powtarzalny Blade; zostały komponenty, layout i jeden komplet partiali (`partials/usluga/`) |
| Adresy e-mail imienne | komisja ds. zgłoszeń sygnalisty i kontakty klienta → `committee-N@example.test`. Publiczne adresy rolowe (`contact@`, `rekrutacja@`) zostały |
| Konfiguracja narzędziowa innego pracodawcy z `.claude/` | nie moja własność. `.claude/rules/` w tym repo napisałem od nowa pod ten projekt |
| Moje prywatne adresy e-mail | → `…@users.noreply.github.com` |
| Lockfile'y, dane produkcyjne, dumpy bazy | szum i PII subskrybentów |

Commity dotykające wyłącznie usuniętych plików zniknęły przy przepisywaniu (164 → 145).
**Zachowane:** cała reszta historii, wszystkie 12 merge commitów PR-ów i topologia gałęzi
— `git log --graph` pokazuje oryginalny przebieg pracy.

Same PR-y nie przeniosły się, bo to metadane GitHuba, a nie część repo — ich opisy
wyeksportowałem do [`docs/pull-requests/`](docs/pull-requests/) przed przepisaniem historii.

<!-- TODO PRZED UPUBLICZNIENIEM: potwierdź zgodę klienta na publikację tego wycinka
     i wpisz tu jedno zdanie o podstawie publikacji — albo usuń ten komentarz. -->
