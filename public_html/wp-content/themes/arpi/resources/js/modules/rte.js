/**
 * Rich-text editor (Trix) for the whistleblower message field.
 *
 * Trix is ~150 KB, so it is code-split into its own chunk and loaded on demand —
 * only on pages that actually contain a <trix-editor> (the sygnalista form).
 */
export default function initRte() {
  if (document.querySelector('trix-editor')) {
    import('./rte-trix');
  }
}
