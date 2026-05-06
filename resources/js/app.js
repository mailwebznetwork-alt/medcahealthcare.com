import './bootstrap';
import './site-architect-navigation';

import Alpine from 'alpinejs';
import { bootIcons } from './shell';

// Livewire's script (layouts with @livewireScripts) runs before this deferred
// module and sets window.Alpine + window.Livewire. Starting npm Alpine here
// overwrites that instance and breaks wire:click / Livewire actions.
if (window.Livewire) {
    // Alpine is provided by Livewire; only boot Lucide.
} else {
    window.Alpine = Alpine;
    Alpine.start();
}

requestAnimationFrame(() => bootIcons());
