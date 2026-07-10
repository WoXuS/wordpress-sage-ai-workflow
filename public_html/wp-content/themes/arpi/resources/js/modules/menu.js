/**
 * Mobile menu: toggle a full-screen overlay via a `data-open` attribute.
 */
export default function initMenu() {
  const toggle = document.querySelector('[data-menu-toggle]');
  const overlay = document.querySelector('[data-menu-overlay]');
  if (!toggle || !overlay) return;

  const close = () => {
    if (!overlay.dataset.open) return;
    delete overlay.dataset.open;
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    toggle.focus();
  };
  const open = () => {
    overlay.dataset.open = 'true';
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    overlay.querySelector('[data-menu-close]')?.focus();
  };

  toggle.addEventListener('click', () => (overlay.dataset.open ? close() : open()));
  overlay.querySelector('[data-menu-close]')?.addEventListener('click', close);
  document.addEventListener('keydown', (e) => e.key === 'Escape' && close());
}
