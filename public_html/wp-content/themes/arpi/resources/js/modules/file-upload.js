/**
 * Custom file-input UI for [data-file-field] (whistleblower attachments).
 * Shows the chosen files with remove buttons and enforces the count/size limits
 * client-side (the server re-validates). Keeps input.files in sync via a
 * DataTransfer so removals actually update what gets submitted.
 */
export default function initFileUpload() {
  document.querySelectorAll('[data-file-field]').forEach(setup);
}

function setup(field) {
  const input = field.querySelector('[data-file-input]');
  const list = field.querySelector('[data-file-list]');
  const error = field.querySelector('[data-file-error]');
  if (!input || !list) return;

  const maxFiles = parseInt(field.dataset.maxFiles || '5', 10);
  const maxSize = parseInt(field.dataset.maxSize || '0', 10);

  // Keep the chip list in sync when the form is reset after a successful submit.
  const form = field.closest('form');
  if (form) {
    form.addEventListener('reset', () => {
      clear(input, list);
      hide(error);
    });
  }

  input.addEventListener('change', () => {
    const files = Array.from(input.files);
    hide(error);

    if (files.length > maxFiles) {
      show(error, field.dataset.errorMaxFiles || '');
      clear(input, list);
      return;
    }

    if (maxSize && files.some((f) => f.size > maxSize)) {
      show(error, field.dataset.errorMaxSize || '');
      clear(input, list);
      return;
    }

    render(files, input, list, error, field);
  });
}

function render(files, input, list, error, field) {
  list.textContent = '';

  files.forEach((file, index) => {
    const item = document.createElement('li');
    item.className = 'flex items-center justify-between gap-3 text-body-sm rounded-2xl bg-white fl-px-4/6 py-2';

    const name = document.createElement('span');
    name.className = 'truncate min-w-0 grow';
    name.textContent = file.name;

    const size = document.createElement('span');
    size.className = 'shrink-0 opacity-60 tabular-nums';
    size.textContent = formatSize(file.size);

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'text-red shrink-0 c-btn--underline';
    remove.textContent = '✕';
    remove.setAttribute('aria-label', `Usuń ${file.name}`);
    remove.addEventListener('click', () => {
      const kept = Array.from(input.files).filter((_, i) => i !== index);
      const dt = new DataTransfer();
      kept.forEach((f) => dt.items.add(f));
      input.files = dt.files;
      hide(error);
      render(Array.from(input.files), input, list, error, field);
    });

    item.append(name, size, remove);
    list.append(item);
  });
}

function formatSize(bytes) {
  if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  if (bytes >= 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${bytes} B`;
}

function clear(input, list) {
  input.value = '';
  list.textContent = '';
}

function show(node, message) {
  if (!node) return;
  node.textContent = message;
  node.classList.remove('hidden');
}

function hide(node) {
  if (!node) return;
  node.classList.add('hidden');
}
