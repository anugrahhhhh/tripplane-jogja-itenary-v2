import './bootstrap';

import Alpine from 'alpinejs';
import idleTimeout from './idle-timeout';

window.Alpine = Alpine;

Alpine.data('idleTimeout', idleTimeout);

Alpine.start();
