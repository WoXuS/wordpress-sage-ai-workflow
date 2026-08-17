# Konwencje kodu

**Pełna lista: [`docs/design-system.md`](../../docs/design-system.md) §14.** Przeczytaj ją
przed pisaniem kodu — poniżej tylko te, które są najczęściej łamane.

Ten plik celowo **nie kopiuje** treści §14. Jedno źródło prawdy: skopiowana lista
rozjeżdża się z oryginałem po kilku commitach i agent zaczyna pracować na nieaktualnej wersji.

## Najczęściej łamane

- **Tokeny, nigdy surowe wartości.** Zamiast `#7f1d46` / `text-4xl` / `24px` → `bg-red-dark`,
  `text-display`, token spacingu. Wszystkie w `resources/css/theme.css` (`@theme static`).
- **Hooki w klasach** (provider lub dedykowana klasa), nie proceduralnie w `setup.php` /
  `filters.php`. Te pliki trzymają legacy — nowa logika jest klasowa.
- **Flexbox domyślnie; grid tylko na realne siatki N-kolumnowe.** Układ dwuelementowy
  (tytuł + ikona) to `flex` + `gap` + `justify-between`.
- **Element po semantyce.** Kontener blokowy/wizualny → `<div>`; tekst inline → `<span>`.
  Nie defaultuj do `<span>` dlatego, że Tailwind i tak ustawi `display`.
- **Minimum komentarzy.** Komentuj tylko genuinely nieoczywiste (trik CSS, obejście quirku).
  Nie powtarzaj tego, co mówi kod.
- **Zaokrąglaj spacing.** Nie przepisuj pikseli z projektu 1:1 — jedna wartość na powtarzalny
  element. Dokładny px to szum designerski, nie intencja.

## Nazewnictwo

Wewnętrzne identyfikatory (klasy PHP, katalogi partiali, moduły JS, klasy CSS) → **angielski**.
Powierzchnia publiczna (slugi URL, etykiety wp-admin, `Template Name`) → **język odbiorcy**
(polski dla treści PL, angielski dla DBiP).

Wyjątek, którego nie ruszamy: machine name CPT (`usluga`, `zgloszenie`) i nazwy plików
`template-*.blade.php` są zapisane w bazie (`post_type`, `_wp_page_template`) —
zmiana nazwy cicho psuje żywą treść.

## Document as you build

Nowy feature, podstrona, provider albo zasada projektowa → opis w `docs/design-system.md`
**w tym samym commicie**. Reguła, która żyje tylko w kodzie, zostanie pominięta następnym razem.
