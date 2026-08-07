// Scope tiles default to 2/3/4 columns (basis set in the template). A label that wraps
// past MAX_LINES at that column width gets its tile widened — but only enough to bring it
// back to ~MAX_LINES (≈ half its single-line width), not to a full line. So a genuinely
// long label takes a wider column (its own row if needed) while medium ones still pair up.
// Capped at the container width. Recomputed on resize and after fonts load.
const MAX_LINES = 2;

function lineCount(el) {
  const range = document.createRange();
  range.selectNodeContents(el);
  return range.getClientRects().length;
}

function fit(list) {
  const tiles = [...list.querySelectorAll('[data-scope-tile]')];
  tiles.forEach((tile) => {
    tile.style.flexBasis = '';
    tile.style.maxWidth = '';
    tile.style.flexShrink = '';
  });

  const avail = list.clientWidth;

  tiles.forEach((tile) => {
    const label = tile.querySelector('p');
    if (lineCount(label) <= MAX_LINES) return;

    // Single-line width of the label → aim for ~MAX_LINES lines, not one long line.
    const prev = label.style.whiteSpace;
    label.style.whiteSpace = 'nowrap';
    const oneLine = label.scrollWidth;
    label.style.whiteSpace = prev;

    const target = Math.min(Math.ceil(oneLine / MAX_LINES) + 24, avail);
    tile.style.flexBasis = `${target}px`;
    tile.style.maxWidth = '100%';
    tile.style.flexShrink = '0';
  });
}

export default function initScopeFit() {
  const lists = [...document.querySelectorAll('[data-scope-list]')];
  if (!lists.length) return;

  const run = () => lists.forEach(fit);
  run();

  let raf;
  window.addEventListener('resize', () => {
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(run);
  });
  if (document.fonts?.ready) document.fonts.ready.then(run);
}
