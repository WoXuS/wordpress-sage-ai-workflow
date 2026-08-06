// Scroll-reveal. `.reveal-ready` on <html> gates the hide-until-revealed CSS to
// JS-present visitors (no-JS sees everything). Bails out on prefers-reduced-motion.
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

  document.querySelectorAll('[data-reveal-group]').forEach((g) => io.observe(g));
  document.querySelectorAll('[data-reveal]').forEach((el) => {
    if (!el.closest('[data-reveal-group]')) io.observe(el);
  });
}
