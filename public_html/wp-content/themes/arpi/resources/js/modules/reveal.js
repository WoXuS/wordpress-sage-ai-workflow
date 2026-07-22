/**
 * Scroll-reveal.
 *
 * Adds `.reveal-ready` to <html> so the CSS only hides elements when JS is present
 * (no-JS visitors see everything). Elements marked `[data-reveal]` fade+rise in when
 * they enter the viewport. A `[data-reveal-group]` container staggers its own
 * `[data-reveal]` descendants into a wave. Bails out on prefers-reduced-motion.
 */
const STEP = 90; // ms between staggered items

export default function initReveal() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const root = document.documentElement;
  root.classList.add('reveal-ready'); // usually already set by the inline head gate

  if (!('IntersectionObserver' in window)) return; // graceful: head failsafe unhides everything

  // Tell the head failsafe that reveal is running, so it keeps the gate.
  window.__revealReady = true;

  const show = (el, delay = 0) => {
    if (delay) el.style.transitionDelay = `${delay}ms`;
    el.classList.add('is-visible');
  };

  const io = new IntersectionObserver(
    (entries, obs) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        const el = entry.target;
        obs.unobserve(el);

        if (el.hasAttribute('data-reveal-group')) {
          el.querySelectorAll('[data-reveal]').forEach((item, i) => show(item, i * STEP));
        } else {
          show(el);
        }
      }
    },
    // Fire later — element must reach ~75% of the viewport height before it
    // animates, so on a slow scroll the motion lands where the eye already is
    // instead of flashing near the bottom edge and finishing unseen.
    { rootMargin: '0px 0px -25% 0px', threshold: 0.15 },
  );

  // Observe group containers, plus standalone reveals not owned by a group.
  document.querySelectorAll('[data-reveal-group]').forEach((g) => io.observe(g));
  document.querySelectorAll('[data-reveal]').forEach((el) => {
    if (!el.closest('[data-reveal-group]')) io.observe(el);
  });
}
