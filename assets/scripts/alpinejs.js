/**
 * Alpine.js, used by the subscribe button.
 *
 * Only registered when the site doesn't already register an `alpinejs`
 * script, so the plugin doesn't start a second copy of Alpine.
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
