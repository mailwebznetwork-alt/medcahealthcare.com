import { onBackendDomUpdate, SORTABLE_INTERACTIVE_FILTER } from './livewire-dom-hooks';

function readParentPath(listEl) {
    try {
        const raw = listEl.dataset.navParentPath ?? '[]';
        const parsed = JSON.parse(raw);

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function readOrderedNodeKeys(listEl) {
    return [...listEl.querySelectorAll('[data-nav-node-key]')]
        .map((node) => node.getAttribute('data-nav-node-key'))
        .filter((value) => value !== null && value !== '');
}

async function bindNavigationSortables() {
    const root = document.getElementById('site-navigation-root');
    if (!root) {
        return;
    }

    const lwRoot = root.closest('[wire\\:id]');
    if (!lwRoot || !window.Livewire) {
        return;
    }

    const component = window.Livewire.find(lwRoot.getAttribute('wire:id'));
    if (!component) {
        return;
    }

    const { default: Sortable } = await import('sortablejs');

    document.querySelectorAll('[data-nav-sortable-list]').forEach((listEl) => {
        if (listEl._sortableInstance) {
            listEl._sortableInstance.destroy();
            listEl._sortableInstance = null;
        }

        listEl._sortableInstance = Sortable.create(listEl, {
            animation: 150,
            handle: '[data-sortable-handle]',
            draggable: '[data-nav-node-key]',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            delay: 120,
            delayOnTouchOnly: true,
            filter: SORTABLE_INTERACTIVE_FILTER,
            preventOnFilter: false,
            onEnd: () => {
                const zone = listEl.dataset.navZone ?? 'header';
                const parentPath = readParentPath(listEl);
                const orderedKeys = readOrderedNodeKeys(listEl);

                component.call('syncNavigationSiblingOrder', zone, parentPath, orderedKeys);
            },
        });
    });
}

onBackendDomUpdate(bindNavigationSortables);
