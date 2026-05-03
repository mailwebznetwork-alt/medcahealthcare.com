import './bootstrap';

import Alpine from 'alpinejs';
import { bootIcons } from './shell';

window.Alpine = Alpine;

Alpine.start();

requestAnimationFrame(() => bootIcons());
