// Progressive-enhancement handler for [data-ajax-form]: POSTs to the form's REST
// endpoint. [data-multipart] forms send FormData; the rest send JSON.
import { validateForm } from './validation';

export default function initForms() {
  document.querySelectorAll('[data-ajax-form]').forEach(setup);
}

function setup(form) {
  const status = form.querySelector('[data-form-status]');
  const button = form.querySelector('[type="submit"]');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const valid = form.hasAttribute('data-validate')
      ? validateForm(form)
      : form.reportValidity?.() ?? true;
    if (!valid) return;

    setBusy(button, true);
    hide(status);

    const multipart = form.hasAttribute('data-multipart');
    const headers = { 'X-WP-Nonce': form.dataset.nonce || '' };
    let body;

    if (multipart) {
      // Let the browser set the multipart boundary; files ride along as-is.
      body = new FormData(form);
    } else {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(Object.fromEntries(new FormData(form).entries()));
    }

    try {
      const response = await fetch(form.dataset.endpoint, {
        method: 'POST',
        headers,
        body,
      });

      const data = await response.json().catch(() => ({}));

      if (response.ok && data.ok) {
        show(status, form.dataset.success || '', 'success');
        form.reset();
      } else {
        show(status, data.message || form.dataset.error || '', 'error');
      }
    } catch (error) {
      show(status, form.dataset.error || '', 'error');
    } finally {
      setBusy(button, false);
    }
  });
}

function setBusy(button, busy) {
  if (!button) return;
  button.disabled = busy;
  button.setAttribute('aria-busy', busy ? 'true' : 'false');
}

function show(status, message, type) {
  if (!status) return;
  status.textContent = message;
  status.classList.remove('hidden', 'is-success', 'is-error');
  if (type) status.classList.add(`is-${type}`);
}

function hide(status) {
  if (!status) return;
  status.classList.add('hidden');
  status.classList.remove('is-success', 'is-error');
}
