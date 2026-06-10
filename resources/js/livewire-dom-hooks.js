const updateCallbacks = new Set();
let hooksRegistered = false;

function scheduleDomUpdates(root = document) {
    requestAnimationFrame(() => {
        updateCallbacks.forEach((callback) => callback(root));
    });
}

function registerLivewireHooks() {
    if (hooksRegistered) {
        scheduleDomUpdates();

        return;
    }

    hooksRegistered = true;

    if (!window.Livewire) {
        return;
    }

    window.Livewire.hook('morph.updated', ({ el }) => {
        scheduleDomUpdates(el ?? document);
    });
    scheduleDomUpdates();
}

/**
 * Re-run callbacks after initial paint, Livewire morphs, and SPA navigations.
 * Safe when app.js loads after @livewireScripts (deferred module race).
 */
export function onBackendDomUpdate(callback) {
    updateCallbacks.add(callback);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleDomUpdates, { once: true });
    } else {
        scheduleDomUpdates();
    }

    if (window.Livewire) {
        registerLivewireHooks();
    } else {
        document.addEventListener('livewire:init', registerLivewireHooks, { once: true });
    }

    document.addEventListener('livewire:navigated', scheduleDomUpdates);
    document.addEventListener('livewire:initialized', scheduleDomUpdates);
}

/** SortableJS filter: block form controls but allow drag handles. */
export const SORTABLE_INTERACTIVE_FILTER = 'input, textarea, select, option, a, button:not([data-sortable-handle])';
