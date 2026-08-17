# Weryfikacja zmian

Projekt nie ma suite'a testów jednostkowych. Zmiana jest zweryfikowana dopiero, gdy
przeszła poniższe kroki — **przeczytanie diffa nie jest weryfikacją**.

## Zawsze, dla każdej zmiany w motywie

1. **Build bez ostrzeżeń**
   ```
   cd public_html/wp-content/themes/arpi && yarn build
   ```
   Jeśli lokalnie działa dev-server, sprawdź najpierw `public/hot` — nieaktualny plik
   serwuje assety z martwego dev-servera i build wygląda na poprawny, mimo że strona nie działa.

2. **Sprawdź wyrenderowany DOM, nie kod**
   ```
   curl -s http://localhost:8080/     | grep -o 'szukany-fragment'
   curl -s http://localhost:8080/en/  | grep -o 'szukany-fragment'
   ```
   **Zawsze obie wersje językowe.** Najczęstszy błąd w tym repo: poprawka wchodzi tylko
   do PL, EN cicho się rozjeżdża.

3. **Dane brzegowe, nie tylko happy path.** Puste pole ACF, brak obrazka, pusty repeater.
   ACF Pro zwraca `false` (nie `null`) dla pustego repeatera — `??` tego nie łapie,
   `array_map()` dostaje `false` i rzuca fatal. Idiom w tym repo: `(… ?? []) ?: []`.

4. **Widok się nie odświeża?** `make wp ARGS="acorn view:clear"` — cache Blade.

## Przed mergem

PR musi mieć sekcję `## Verification` opisującą, co konkretnie zostało sprawdzone —
nie „przetestowane", tylko które URL-e, które breakpointy, które przypadki brzegowe.

## Zmiany wizualne

Porównanie z projektem przy ~375px i ~1440px. Layout ma być płynny między tymi
wartościami (tokeny `fl-*`), więc sprawdzaj też szerokości pośrednie — regresje
lubią siedzieć dokładnie pomiędzy breakpointami.
