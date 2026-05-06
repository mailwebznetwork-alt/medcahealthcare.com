import Sortable from 'sortablejs';

function readZoneIds(zone) {
    const el = document.querySelector(`[data-nav-zone="${zone}"]`);
    if (!el) {
        return [];
    }

    return [...el.querySelectorAll('[data-page-id]')]
        .map((n) => parseInt(n.dataset.pageId, 10))
        .filter((id) => ! Number.isNaN(id));
}

function bindNavigationSortables() {
    const root = document.getElementById('site-navigation-root');
    if (!root) {
        return;
    }

    const lwRoot = root.closest('[wire\\:id]');
    if (!lwRoot || !window.Livewire) {
        return;
    }

    const wid = lwRoot.getAttribute('wire:id');
    const component = window.Livewire.find(wid);
    if (!component) {
        return;
    }

    document.querySelectorAll('[data-nav-zone]').forEach((zoneEl) => {
        if (zoneEl._sortableInstance) {
            zoneEl._sortableInstance.destroy();
            zoneEl._sortableInstance = null;
        }

        zoneEl._sortableInstance = Sortable.create(zoneEl, {
            group: 'site-navigation',
            animation: 150,
            draggable: '[data-page-id]',
            onEnd: () => {
                component.call(
                    'syncFromDrag',
                    readZoneIds('header'),
                    readZoneIds('footer')
                );
            },
        });
    });
}

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', () => {
        requestAnimationFrame(bindNavigationSortables);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(bindNavigationSortables);
});
