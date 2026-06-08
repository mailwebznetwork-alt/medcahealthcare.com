function readOrderedKeys(listEl, itemSelector, keyAttr) {
    return [...listEl.querySelectorAll(itemSelector)]
        .map((node) => node.getAttribute(keyAttr))
        .filter((value) => value !== null && value !== '');
}

async function bindSortableList(listEl) {
    const method = listEl.dataset.sortableMethod;
    if (!method) {
        return;
    }

    const lwRoot = listEl.closest('[wire\\:id]');
    if (!lwRoot || !window.Livewire) {
        return;
    }

    const component = window.Livewire.find(lwRoot.getAttribute('wire:id'));
    if (!component) {
        return;
    }

    const { default: Sortable } = await import('sortablejs');

    if (listEl._sortableInstance) {
        listEl._sortableInstance.destroy();
        listEl._sortableInstance = null;
    }

    const itemSelector = listEl.dataset.sortableItem || '[data-sortable-item]';
    const keyAttr = listEl.dataset.sortableKey || 'data-sortable-item';
    const handle = listEl.dataset.sortableHandle || '[data-sortable-handle]';

    listEl._sortableInstance = Sortable.create(listEl, {
        animation: 150,
        handle,
        draggable: itemSelector,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        filter: 'input,textarea,button,select,option,a',
        preventOnFilter: false,
        onEnd: () => {
            const ordered = readOrderedKeys(listEl, itemSelector, keyAttr);
            component.call(method, ordered);
        },
    });
}

function bindAllSortables() {
    document.querySelectorAll('[data-sortable-list]').forEach((listEl) => {
        bindSortableList(listEl);
    });
}

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', () => {
        requestAnimationFrame(() => bindAllSortables());
    });
});

document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => bindAllSortables());
});
