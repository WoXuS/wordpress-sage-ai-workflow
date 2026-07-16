/**
 * Clamp excerpts to whole lines.
 *
 * The card CSS stretches every card in a row to the tallest one and lets the
 * excerpt box grow to fill the leftover space. That leftover is a fractional
 * number of lines, so plain `overflow: hidden` cuts the last line in half. Here
 * we snap the visible text down to whole lines: line-clamp = floor(box / line).
 *
 * `[data-clamp-fill]` is the flex item (grow/basis). The clamp is applied to its
 * inner `<p>`, because a flex item's `display` is blockified and would kill the
 * `-webkit-box` that `-webkit-line-clamp` needs.
 */
const MIN_LINES = 2;
const MOBILE_MAX = 767; // excerpt is hidden below md

export default function initClampFill() {
  const boxes = Array.from(document.querySelectorAll('[data-clamp-fill]'));
  if (!boxes.length) return;

  const reset = (p) => {
    p.style.removeProperty('display');
    p.style.removeProperty('-webkit-line-clamp');
    p.style.removeProperty('-webkit-box-orient');
    p.style.removeProperty('overflow');
  };

  const apply = () => {
    const inners = boxes.map((b) => b.firstElementChild).filter(Boolean);
    inners.forEach(reset);

    if (window.innerWidth <= MOBILE_MAX) return;

    // Read all heights, then write — avoids layout thrash.
    const plans = boxes.map((b) => {
      const p = b.firstElementChild;
      if (!p) return null;
      const lh = parseFloat(getComputedStyle(p).lineHeight);
      if (!lh) return {p, lines: MIN_LINES};
      const lines = Math.max(MIN_LINES, Math.floor(b.clientHeight / lh + 0.01));
      return {p, lines};
    });
    plans.forEach((plan) => {
      if (!plan) return;
      const {p, lines} = plan;
      p.style.display = '-webkit-box';
      p.style.webkitBoxOrient = 'vertical';
      p.style.webkitLineClamp = String(lines);
      p.style.overflow = 'hidden';
    });
  };

  let ticking = false;
  const onResize = () => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(() => {
      ticking = false;
      apply();
    });
  };

  apply();
  window.addEventListener('resize', onResize, {passive: true});
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(apply);
  }
}
