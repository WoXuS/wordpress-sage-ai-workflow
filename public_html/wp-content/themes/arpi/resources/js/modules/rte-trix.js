/**
 * Lazy Trix bootstrap (loaded by rte.js only when a <trix-editor> is present).
 * Trix upgrades the element on import and syncs its HTML into the linked hidden
 * input (submitted as `message`, sanitized server-side with wp_kses_post).
 * We ship our own attachment field, so Trix's inline file handling is disabled.
 */
import 'trix';
import 'trix/dist/trix.css';
import '../../css/components/rte.css';

document.addEventListener('trix-file-accept', (event) => event.preventDefault());
