import { onBackendDomUpdate, SORTABLE_INTERACTIVE_FILTER } from './livewire-dom-hooks';

function readZoneIds(zone) {
    const el = document.querySelector(`[data-nav-zone="${zone}"]`);
    if (!el) {
        return [];
    }

    return [...el.querySelectorAll('[data-page-id]')]
        .map((n) => parseInt(n.dataset.pageId, 10))
        .filter((id) => !Number.isNaN(id));
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

    document.querySelectorAll('[data-nav-zone]').forEach((zoneEl) => {
        if (zoneEl._sortableInstance) {
            zoneEl._sortableInstance.destroy();
            zoneEl._sortableInstance = null;
        }

        zoneEl._sortableInstance = Sortable.create(zoneEl, {
            group: 'site-navigation',
            animation: 150,
            draggable: '[data-page-id]',
            delay: 120,
            delayOnTouchOnly: true,
            filter: SORTABLE_INTERACTIVE_FILTER,
            preventOnFilter: false,
            onEnd: () => {
                component.call(
                    'syncFromDrag',
                    readZoneIds('header'),
                    readZoneIds('footer'),
                );
            },
        });
    });
}

onBackendDomUpdate(bindNavigationSortables);
