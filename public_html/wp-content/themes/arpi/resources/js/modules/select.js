// Accessible select-only combobox for [data-select]: a hidden input holds the
// value so the control posts like a native <select>. Open/close animation is CSS.
export default function initSelect() {
  document.querySelectorAll('[data-select]').forEach(setup);
}

function setup(root) {
  const trigger = root.querySelector('[data-select-trigger]');
  const list = root.querySelector('[data-select-list]');
  const input = root.querySelector('[data-select-input]');
  const valueEl = root.querySelector('[data-select-value]');
  const options = Array.from(root.querySelectorAll('[data-select-option]'));
  if (!trigger || !list || !input || !options.length) return;

  const baseId = `sel-${Math.round(performance.now())}-${options.length}`;
  options.forEach((opt, i) => { opt.id = `${baseId}-${i}`; });

  let open = false;
  let activeIndex = Math.max(0, options.findIndex((o) => o.getAttribute('aria-selected') === 'true'));
  let typeahead = '';
  let typeaheadTimer;

  const isOpen = () => open;

  function setActive(index, scroll = true) {
    activeIndex = (index + options.length) % options.length;
    options.forEach((opt, i) => {
      const active = i === activeIndex;
      opt.toggleAttribute('data-active', active);
      if (active && scroll) opt.scrollIntoView({ block: 'nearest' });
    });
    trigger.setAttribute('aria-activedescendant', options[activeIndex].id);
  }

  function openList() {
    if (open) return;
    open = true;
    root.setAttribute('data-open', '');
    trigger.setAttribute('aria-expanded', 'true');
    setActive(activeIndex);
  }

  function closeList(focusTrigger = false) {
    if (!open) return;
    open = false;
    root.removeAttribute('data-open');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.removeAttribute('aria-activedescendant');
    options.forEach((opt) => opt.removeAttribute('data-active'));
    if (focusTrigger) trigger.focus();
  }

  function select(index) {
    const opt = options[index];
    if (!opt) return;
    options.forEach((o) => o.setAttribute('aria-selected', o === opt ? 'true' : 'false'));
    input.value = opt.dataset.value;
    if (valueEl) valueEl.textContent = opt.querySelector('span')?.textContent ?? opt.textContent.trim();
    activeIndex = index;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  trigger.addEventListener('click', () => (open ? closeList() : openList()));

  trigger.addEventListener('keydown', (event) => {
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        open ? setActive(activeIndex + 1) : openList();
        break;
      case 'ArrowUp':
        event.preventDefault();
        open ? setActive(activeIndex - 1) : openList();
        break;
      case 'Home':
        if (open) { event.preventDefault(); setActive(0); }
        break;
      case 'End':
        if (open) { event.preventDefault(); setActive(options.length - 1); }
        break;
      case 'Enter':
      case ' ':
        event.preventDefault();
        if (open) { select(activeIndex); closeList(true); } else openList();
        break;
      case 'Escape':
        if (open) { event.preventDefault(); closeList(true); }
        break;
      case 'Tab':
        closeList();
        break;
      default:
        if (event.key.length === 1) handleTypeahead(event.key);
    }
  });

  function handleTypeahead(char) {
    if (!open) openList();
    clearTimeout(typeaheadTimer);
    typeahead += char.toLowerCase();
    typeaheadTimer = setTimeout(() => { typeahead = ''; }, 500);
    const match = options.findIndex((o) => o.textContent.trim().toLowerCase().startsWith(typeahead));
    if (match >= 0) setActive(match);
  }

  options.forEach((opt, i) => {
    opt.addEventListener('click', () => { select(i); closeList(true); });
    opt.addEventListener('mousemove', () => setActive(i, false));
  });

  document.addEventListener('click', (event) => {
    if (open && !root.contains(event.target)) closeList();
  });

  root.addEventListener('focusout', (event) => {
    if (open && !root.contains(event.relatedTarget)) closeList();
  });
}
