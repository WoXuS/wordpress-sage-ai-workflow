/**
 * Smooth-scroll same-page anchor links and center the target in the viewport
 * (instead of the browser's default top-alignment). Honors prefers-reduced-motion
 * and re-centers on initial load when the URL already carries a hash.
 *
 * The theme sets `overflow: clip` on the root, which makes Chromium silently
 * no-op native smooth scrolling (`scrollIntoView`/`scrollTo` with
 * `behavior: 'smooth'`). So the animation is hand-rolled with requestAnimationFrame,
 * which scrolls reliably; reduced-motion falls back to an instant jump.
 */
export default function initAnchorScroll() {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');

  // Absolute scroll position that puts the element's center at the viewport
  // center, clamped to the scrollable range.
  const centerY = (el) => {
    const rect = el.getBoundingClientRect();
    const y = window.scrollY + rect.top + rect.height / 2 - window.innerHeight / 2;
    const max = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
    return Math.max(0, Math.min(y, max));
  };

  const easeInOutQuad = (t) => (t < 0.5 ? 2 * t * t : 1 - (-2 * t + 2) ** 2 / 2);

  const animateTo = (to, duration = 500) => {
    const start = window.scrollY;
    const dist = to - start;
    if (Math.abs(dist) < 2) return;

    let startedAt = null;
    const step = (now) => {
      if (startedAt === null) startedAt = now;
      const p = Math.min((now - startedAt) / duration, 1);
      window.scrollTo(0, start + dist * easeInOutQuad(p));
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  };

  const scrollToHash = (hash, { animate = true, updateUrl = false } = {}) => {
    let target = null;
    try {
      target = hash.length > 1 ? document.querySelector(hash) : null;
    } catch {
      return false; // not a valid selector (e.g. "#!/foo")
    }
    if (!target) return false;

    const to = centerY(target);
    if (animate && !prefersReduced.matches) animateTo(to);
    else window.scrollTo(0, to);

    if (updateUrl) history.pushState(null, '', hash);
    return true;
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href*="#"]');
    if (!link || link.target === '_blank') return;

    // Only handle links pointing to the current document. Normalize trailing
    // slashes so e.g. `/en#about-us` still matches when the page is at `/en/`.
    const url = new URL(link.href, location.href);
    const samePath = url.pathname.replace(/\/+$/, '') === location.pathname.replace(/\/+$/, '');
    if (url.origin !== location.origin || !samePath || url.search !== location.search || !url.hash) return;

    // Close the mobile menu overlay first, otherwise body overflow is locked.
    document.querySelector('[data-menu-overlay][data-open] [data-menu-close]')?.click();

    if (scrollToHash(url.hash, { updateUrl: true })) event.preventDefault();
  });

  // The browser top-aligns a hash target on load; re-center once layout settles.
  if (location.hash) {
    window.addEventListener('load', () =>
      requestAnimationFrame(() => scrollToHash(location.hash, { animate: false }))
    );
  }
}
