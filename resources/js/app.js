import './bootstrap';

import Alpine from 'alpinejs';
import idleTimeout from './idle-timeout';
import setupUx from './ux';

window.Alpine = Alpine;

Alpine.data('idleTimeout', idleTimeout);

Alpine.start();

setupUx();
