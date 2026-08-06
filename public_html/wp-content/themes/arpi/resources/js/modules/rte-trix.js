import 'trix';
import 'trix/dist/trix.css';
import '../../css/components/rte.css';

// We ship our own attachment field, so disable Trix's inline file handling.
document.addEventListener('trix-file-accept', (event) => event.preventDefault());
