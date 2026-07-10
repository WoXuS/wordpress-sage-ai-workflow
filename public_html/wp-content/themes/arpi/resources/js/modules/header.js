export default function initHeader() {
  const header = document.querySelector('[data-header]');
  if (!header) return;

  const SHRINK_AT = 100;
  const GROW_AT = 8;
  let ticking = false;

  const update = () => {
    ticking = false;
    const y = window.scrollY;
    if (y > SHRINK_AT) {
      header.dataset.scrolled = 'true';
    } else if (y <= GROW_AT) {
      delete header.dataset.scrolled;
    }
  };

  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(update);
  };

  window.addEventListener('scroll', onScroll, {passive: true});
  update();
}
