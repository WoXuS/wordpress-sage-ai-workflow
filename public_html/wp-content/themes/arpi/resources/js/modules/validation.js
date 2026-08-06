// Client-side validation for [data-validate] forms. Errors show on blur and
// clear live; messages are language-aware with an optional per-field [data-error].
const MESSAGES = {
  pl: {
    required: 'To pole jest wymagane.',
    email: 'Podaj poprawny adres e-mail.',
    short: 'Wpis jest za krótki.',
    long: 'Wpis jest za długi.',
    pattern: 'Nieprawidłowy format.',
    checkbox: 'Zaznacz to pole, aby kontynuować.',
    default: 'Sprawdź to pole.',
  },
  en: {
    required: 'This field is required.',
    email: 'Enter a valid email address.',
    short: 'This value is too short.',
    long: 'This value is too long.',
    pattern: 'Invalid format.',
    checkbox: 'Please check this box to continue.',
    default: 'Please check this field.',
  },
};

const errorNodes = new WeakMap();
let uid = 0;

export default function initValidation() {
  document.querySelectorAll('form[data-validate]').forEach(setup);
}

function setup(form) {
  const lang = formLang(form);

  controls(form).forEach((control) => {
    const revalidate = () => renderControl(control, lang);
    const events = control.type === 'checkbox' || control.tagName === 'SELECT' ? ['change'] : ['blur'];
    events.forEach((e) => control.addEventListener(e, revalidate));

    // Only revalidate live once a field is already showing an error.
    const liveEvent = control.tagName === 'SELECT' || control.type === 'checkbox' ? 'change' : 'input';
    control.addEventListener(liveEvent, () => {
      if (control.getAttribute('aria-invalid') === 'true') renderControl(control, lang);
    });
  });
}

export function validateForm(form) {
  const lang = formLang(form);
  let firstInvalid = null;

  controls(form).forEach((control) => {
    const ok = renderControl(control, lang);
    if (!ok && !firstInvalid) firstInvalid = control;
  });

  if (firstInvalid) {
    (firstInvalid.focus ? firstInvalid : firstInvalid).focus?.();
  }
  return !firstInvalid;
}

function renderControl(control, lang) {
  const { valid, message } = evaluate(control, lang);
  if (valid) clearError(control);
  else setError(control, message);
  return valid;
}

function evaluate(control, lang) {
  const m = MESSAGES[lang] || MESSAGES.pl;

  if (control.hasAttribute('data-rte-required')) {
    const ok = stripTags(control.value).trim() !== '';
    return { valid: ok, message: ok ? '' : control.dataset.error || m.required };
  }

  const ok = control.checkValidity();
  return { valid: ok, message: ok ? '' : messageFor(control, m) };
}

function messageFor(control, m) {
  if (control.dataset.error) return control.dataset.error;
  const v = control.validity;
  if (control.type === 'checkbox' && v.valueMissing) return m.checkbox;
  if (v.valueMissing) return m.required;
  if (v.typeMismatch) return m.email;
  if (v.tooShort) return m.short;
  if (v.tooLong) return m.long;
  if (v.patternMismatch) return m.pattern;
  return m.default;
}

function setError(control, message) {
  const target = styleTarget(control);
  target.classList.add('is-invalid');
  target.setAttribute('aria-invalid', 'true');

  let node = errorNodes.get(control);
  if (!node) {
    node = document.createElement('p');
    node.className = 'c-field-error';
    node.id = `field-error-${(uid += 1)}`;
    errorAnchor(control).insertAdjacentElement('afterend', node);
    errorNodes.set(control, node);
    control.setAttribute('aria-describedby',
      [control.getAttribute('aria-describedby'), node.id].filter(Boolean).join(' '));
  }
  node.textContent = message;
  node.hidden = false;
}

function clearError(control) {
  const target = styleTarget(control);
  target.classList.remove('is-invalid');
  target.setAttribute('aria-invalid', 'false');
  const node = errorNodes.get(control);
  if (node) node.hidden = true;
}

// [data-invalid-target] lets a hidden control put the red-border state on a sibling (e.g. Trix).
function styleTarget(control) {
  const sel = control.dataset.invalidTarget;
  if (sel) return control.parentElement?.querySelector(sel) || control;
  return control;
}

function errorAnchor(control) {
  if (control.type === 'checkbox' || control.type === 'radio') {
    return control.closest('label') || control;
  }
  return control;
}

function controls(form) {
  return Array.from(form.querySelectorAll('input, select, textarea')).filter((el) => {
    if (el.name === 'website' || el.disabled || el.hasAttribute('data-no-validate')) return false;
    if (el.type === 'hidden') return el.hasAttribute('data-rte-required');
    return (
      el.required ||
      el.type === 'email' ||
      el.type === 'url' ||
      el.pattern ||
      el.minLength > 0
    );
  });
}

function formLang(form) {
  return form.dataset.lang || form.querySelector('input[name="lang"]')?.value || 'pl';
}

function stripTags(value) {
  return String(value).replace(/<[^>]*>/g, '');
}
