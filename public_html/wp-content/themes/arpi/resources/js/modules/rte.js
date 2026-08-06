// Trix is ~150 KB, so it is code-split and loaded only when a <trix-editor> exists.
export default function initRte() {
  if (document.querySelector('trix-editor')) {
    import('./rte-trix');
  }
}
