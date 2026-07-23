/**
 * Progressive-enhancement handler for the theme's AJAX forms
 * ([data-ajax-form]) — contact form + footer newsletter. Posts JSON to the
 * form's REST endpoint and reports success/error in its [data-form-status] node.
 */
export default function initForms() {
  document.querySelectorAll('[data-ajax-form]').forEach(setup);
}

function setup(form) {
  const status = form.querySelector('[data-form-status]');
  const button = form.querySelector('[type="submit"]');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
      return;
    }

    setBusy(button, true);
    hide(status);

    const payload = Object.fromEntries(new FormData(form).entries());

    try {
      const response = await fetch(form.dataset.endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': form.dataset.nonce || '',
        },
        body: JSON.stringify(payload),
      });

      const data = await response.json().catch(() => ({}));

      if (response.ok && data.ok) {
        show(status, form.dataset.success || '');
        form.reset();
      } else {
        show(status, data.message || form.dataset.error || '');
      }
    } catch (error) {
      show(status, form.dataset.error || '');
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

function show(status, message) {
  if (!status) return;
  status.textContent = message;
  status.classList.remove('hidden');
}

function hide(status) {
  if (!status) return;
  status.classList.add('hidden');
}
